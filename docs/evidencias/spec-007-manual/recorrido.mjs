// Verificación manual asistida de HU-07 (T718) con Chrome real (sin interfaz) mediante DevTools Protocol.
// Adaptado de ../spec-006-manual/recorrido.mjs.
// Uso: node recorrido.mjs <dir-salida> <seed.json> <base-url> <chrome> <comando-ocupar>
// <comando-ocupar> es la orden (sin el identificador del turno) que hace que otra persona ocupe una plaza.
import { spawn, execSync } from 'node:child_process';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';

const [outDir, seedPath, base, chromePath, occupyCommand] = process.argv.slice(2);
const seed = JSON.parse(readFileSync(seedPath, 'utf8'));
mkdirSync(outDir, { recursive: true });
const chrome = spawn(chromePath, [
  '--headless=new', '--remote-debugging-port=9335', `--user-data-dir=${outDir}/profile`,
  '--no-first-run', '--no-default-browser-check', '--lang=es-ES', 'about:blank',
], { stdio: 'ignore' });
const sleep = ms => new Promise(r => setTimeout(r, ms));
let targets;
for (let i = 0; i < 50; i++) {
  try { targets = await (await fetch('http://127.0.0.1:9335/json/list')).json(); if (targets.some(t => t.type === 'page')) break; } catch {}
  await sleep(200);
}
const ws = new WebSocket(targets.find(t => t.type === 'page').webSocketDebuggerUrl);
await new Promise(r => ws.addEventListener('open', r));
let seq = 0; const pending = new Map(); const responses = [];
ws.addEventListener('message', ({ data }) => {
  const msg = JSON.parse(data);
  if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg); pending.delete(msg.id); }
  if (msg.method === 'Network.responseReceived' && ['Document', 'Fetch'].includes(msg.params.type)) {
    responses.push({ url: msg.params.response.url, status: msg.params.response.status, type: msg.params.type });
  }
});
const send = (method, params = {}) => new Promise((resolve, reject) => {
  const id = ++seq; pending.set(id, m => m.error ? reject(new Error(method + ': ' + m.error.message)) : resolve(m.result));
  ws.send(JSON.stringify({ id, method, params }));
});
const ev = async expr => (await send('Runtime.evaluate', { expression: expr, returnByValue: true, awaitPromise: true })).result.value;
const waitFor = async (expr, label, ms = 30000) => {
  for (const end = Date.now() + ms; Date.now() < end; await sleep(150)) { if (await ev(expr)) return; }
  throw new Error('Tiempo agotado esperando: ' + label + ' en ' + await ev('location.href'));
};
const goto = async path => { await send('Page.navigate', { url: base + path }); await sleep(300); await waitFor(`document.readyState === 'complete'`, 'carga ' + path); };
const text = () => ev('document.querySelector("main").innerText');
const fill = (sel, value) => ev(`(() => { const el = document.querySelector(${JSON.stringify(sel)}); el.value = ${JSON.stringify(value)}; el.dispatchEvent(new Event('input', {bubbles: true})); el.dispatchEvent(new Event('change', {bubbles: true})); return true; })()`);
const submit = sel => ev(`document.querySelector(${JSON.stringify(sel)}).requestSubmit()`);
const viewport = async (width, height, mobile) => send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile });
const shot = async name => {
  const { cssContentSize } = await send('Page.getLayoutMetrics');
  const { data } = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width: cssContentSize.width, height: Math.min(cssContentSize.height, 4000), scale: 1 } });
  writeFileSync(`${outDir}/${name}.png`, Buffer.from(data, 'base64'));
};
// Lección de T626 (HU-06): además del desplazamiento, se mide el borde derecho real de cajas, filas y avisos.
const widthReport = () => ev(`(() => {
  const els = [...document.querySelectorAll('main .box, main tr, main .notification, main .page-heading, header .nav-content')];
  const right = Math.max(...els.map(e => e.getBoundingClientRect().right));
  return { scrollWidth: document.documentElement.scrollWidth, innerWidth: window.innerWidth, maxRight: Math.round(right) };
})()`);
const fits = r => r.scrollWidth <= r.innerWidth && r.maxRight <= r.innerWidth;
const key = async (key, code, keyCode, text) => {
  await send('Input.dispatchKeyEvent', { type: 'keyDown', key, code, windowsVirtualKeyCode: keyCode, text });
  await send('Input.dispatchKeyEvent', { type: 'keyUp', key, code, windowsVirtualKeyCode: keyCode });
};
const tab = () => key('Tab', 'Tab', 9);
const enter = () => key('Enter', 'Enter', 13, '\r');
const activeText = () => ev('document.activeElement ? (document.activeElement.innerText || document.activeElement.id || document.activeElement.tagName).replace(/\\s+/g, " ").trim() : ""');
const tabUntil = async (predicateExpr, max = 80) => {
  await ev('document.activeElement && document.activeElement.blur(); window.scrollTo(0, 0)');
  let n = 0;
  while (!(await ev(predicateExpr)) && n < max) { await tab(); n++; }
  return n;
};
const lastResponse = fragment => [...responses].reverse().find(r => r.url.includes(fragment));
const login = async email => {
  await send('Network.clearBrowserCookies');
  await goto('/acceso');
  await fill('input[name="_username"]', email);
  await fill('input[name="_password"]', seed.users[email]);
  await ev('document.querySelector(\'input[name="_password"]\').form.requestSubmit()');
  await waitFor(`!location.pathname.startsWith('/acceso')`, 'acceso de ' + email);
};
const row = id => ev(`(() => { const r = document.querySelector('#shift-' + ${JSON.stringify(id)}); return r ? { text: r.innerText.replace(/\\s+/g, ' '), cls: r.className, buttons: [...r.querySelectorAll('button')].map(b => b.innerText.replace(/\\s+/g, ' ').trim()) } : null; })()`);
const newRowId = () => ev(`(() => { const r = [...document.querySelectorAll('#shifts tbody tr')].find(r => r.innerText.includes(${JSON.stringify(seed.newShift.start + '–' + seed.newShift.end)})); return r ? r.id.replace('shift-', '') : null; })()`);
const es = iso => iso.split('-').reverse().join('/');

const log = []; let failures = 0;
const check = (route, step, expected, observed, ok) => { log.push({ route, step, expected, observed, result: ok ? 'OK' : 'FALLO' }); if (!ok) failures++; console.log((ok ? '✔ ' : '✘ ') + route + ' · ' + step + ' → ' + (typeof observed === 'string' ? observed : JSON.stringify(observed))); };

await send('Page.enable'); await send('Runtime.enable'); await send('Network.enable');
const N = seed.newShift;

try {
  // ---------- Recorrido 1: satisfactorio, coordinadora → voluntaria (móvil, teclado) → voluntario → ficha ----------
  const R1 = 'R1 satisfactorio';
  await viewport(1280, 900, false);
  await login('coordinadora@example.test');
  await goto('/admin/turnos');
  let tabs = await tabUntil(`document.activeElement && document.activeElement.innerText && document.activeElement.innerText.trim() === 'Nuevo turno'`);
  check(R1, 'Teclado: «Nuevo turno» se alcanza con Tab', 'foco en «Nuevo turno»', `«${await activeText()}» tras ${tabs} pulsaciones`, (await activeText()) === 'Nuevo turno');
  await enter();
  await waitFor(`location.pathname === '/admin/turnos/nuevo'`, 'formulario de alta');
  await fill('#kind', 'walk'); await fill('#date', N.date); await fill('#startTime', N.start); await fill('#endTime', N.end);
  await fill('#minVolunteers', '2'); await fill('#maxVolunteers', '3');
  await ev('document.querySelector("#form-shift button").focus()');
  await enter();
  await waitFor(`location.pathname === '/admin/turnos' && document.body.innerText.includes('Turno creado')`, 'turno creado');
  const newId = await newRowId();
  let r = await row(newId);
  check(R1, 'La coordinadora crea un paseo dentro de unas 30 horas, mínimo 2 y máximo 3 (CA-07-06)', `éxito; ${es(N.date)}, ${N.start}–${N.end}; destacado «Faltan voluntarios: 2»`, r && r.text, !!r && r.text.includes(es(N.date)) && r.cls === 'shift-undercovered' && r.text.includes('Faltan voluntarios: 2 para el mínimo'));
  const notice = await ev('document.querySelector("#undercovered-notice") && document.querySelector("#undercovered-notice").innerText');
  check(R1, 'Aviso de turnos faltos de voluntarios en las próximas 48 horas (CA-07-04)', '1 turno de las próximas 48 horas no cubre su mínimo', notice || 'sin aviso', !!notice && notice.includes('1 turno de las próximas 48 horas no cubre'));
  await shot('01-escritorio-coordinadora-turno-destacado');

  await login('ana@example.test');
  await viewport(390, 844, true);
  await goto('/admin/turnos');
  let wr = await widthReport();
  check(R1, 'Listado en móvil (390 px) sin desbordar: desplazamiento y borde derecho de cajas y filas', 'scrollWidth y borde ≤ 390', wr, fits(wr));
  r = await row(newId);
  check(R1, 'La voluntaria ve el turno destacado con «Apuntarme» (CA-07-01, CA-07-04)', 'destacado; 0 inscritas; botón Apuntarme', r && `${r.cls}; ${r.text}`, !!r && r.cls === 'shift-undercovered' && r.text.includes('0 (3 plazas libres)') && r.buttons.some(b => b.startsWith('Apuntarme')));
  tabs = await tabUntil(`document.activeElement && document.activeElement.closest && !!document.activeElement.closest('#shift-${newId}') && document.activeElement.tagName === 'BUTTON'`);
  const focused = await activeText();
  await enter();
  await waitFor(`document.body.innerText.includes('Te has apuntado al turno')`, 'inscripción');
  r = await row(newId);
  check(R1, 'Teclado: Tab hasta «Apuntarme» y Enter; la inscripción cuenta (CA-07-02)', 'éxito; 1 inscrita; «Te has apuntado»; sigue destacado con 1 para el mínimo', `${tabs} pulsaciones, foco «${focused}»; ${r && r.text}`, focused.startsWith('Apuntarme') && !!r && r.text.includes('1 (2 plazas libres)') && r.text.includes('Te has apuntado') && r.text.includes('Faltan voluntarios: 1 para el mínimo'));
  wr = await widthReport();
  check(R1, 'Tras el mensaje de éxito, el listado sigue sin desbordar en móvil', 'scrollWidth y borde ≤ 390', wr, fits(wr));
  await shot('02-movil-voluntaria-apuntada');

  await login('bruno@example.test');
  await viewport(1280, 900, false);
  await goto('/admin/turnos');
  r = await row(newId);
  await ev(`document.querySelector('#shift-${newId} form').requestSubmit()`);
  await waitFor(`document.body.innerText.includes('Te has apuntado al turno')`, 'segunda inscripción');
  r = await row(newId);
  const noticeAfter = await ev('!!document.querySelector("#undercovered-notice")');
  check(R1, 'Al apuntarse la segunda persona, el turno deja de destacarse (CA-07-05)', 'sin destacar; «Mínimo cubierto»; sin aviso', `${r && r.cls || 'sin clase'}; ${r && r.text}; aviso ${noticeAfter ? 'visible' : 'ausente'}`, !!r && r.cls === '' && r.text.includes('Mínimo cubierto') && !noticeAfter);
  const overlapRow = await row(seed.overlap);
  check(R1, 'Bruno ve el paseo coincidente sin botón (CA-07-09)', '«Coincide con otro de tus turnos», sin botón', overlapRow && `${overlapRow.text}; botones: ${overlapRow.buttons.length}`, !!overlapRow && overlapRow.text.includes('Coincide con otro de tus turnos') && overlapRow.buttons.length === 0);
  await shot('03-escritorio-voluntario-minimo-cubierto');

  await login('coordinadora@example.test');
  await goto('/admin/turnos/' + newId);
  const signups = await ev('document.querySelector("#signups") ? document.querySelector("#signups").innerText : ""');
  const facts = await ev('[...document.querySelectorAll("#shift-facts dd")].map(d => d.innerText).join(" · ")');
  check(R1, 'Ficha del turno: límites, inscritas y nombres (CA-07-01)', '2 personas · 3 personas · 2 · 1; Ana y Bruno', `${facts}; ${signups.replace(/\s+/g, ' ')}`, facts === '2 personas · 3 personas · 2 · 1' && signups.includes('Ana Voluntaria') && signups.includes('Bruno Voluntario'));
  await shot('04-escritorio-ficha-turno');

  // ---------- Recorrido 2: errores y permisos, móvil 390 px ----------
  const R2 = 'R2 error';
  await login('ana@example.test');
  await viewport(390, 844, true);
  await goto('/admin/turnos');
  r = await row(seed.lastPlace);
  check(R2, 'Limpieza con una plaza libre: botón «Apuntarme» visible', '1 (1 plaza libre); Apuntarme', r && r.text, !!r && r.text.includes('1 (1 plaza libre)') && r.buttons.some(b => b.startsWith('Apuntarme')));
  execSync(`${occupyCommand} ${seed.lastPlace}`, { stdio: 'pipe' });
  await ev(`document.querySelector('#shift-${seed.lastPlace} button').focus()`);
  await enter();
  await waitFor(`!!document.querySelector('#shift-refusal')`, 'rechazo por turno completo');
  const r409 = lastResponse('/inscripcion');
  const refusal = await ev('document.querySelector("#shift-refusal").innerText');
  r = await row(seed.lastPlace);
  check(R2, 'Otra persona ocupa la última plaza con la página abierta; Enter en «Apuntarme» (CA-07-03)', '409; «ya tiene cubierto su máximo de 2 personas»; «Completo» sin botón', `${r409 && r409.status}; «${refusal}»; ${r && r.text}`, !!r409 && r409.status === 409 && refusal.includes('ya tiene cubierto su máximo de 2 personas') && !!r && r.text.includes('Completo') && r.buttons.length === 0);
  wr = await widthReport();
  check(R2, 'El rechazo en móvil no desborda', 'scrollWidth y borde ≤ 390', wr, fits(wr));
  await shot('05-movil-rechazo-turno-completo');

  await login('coordinadora@example.test');
  await goto('/admin/turnos/' + newId);
  await fill('#maxVolunteers', '1'); await fill('#minVolunteers', '1');
  await submit('#form-shift-edit');
  await waitFor(`!!document.querySelector('#error-shift')`, 'rechazo del máximo');
  const rMax = lastResponse('/editar');
  check(R2, 'Bajar el máximo a 1 con dos inscritas (CA-07-06)', '409; «el máximo no puede ser menor»', `${rMax && rMax.status}; «${await ev('document.querySelector("#error-shift").innerText')}»`, !!rMax && rMax.status === 409 && (await ev('document.querySelector("#error-shift").innerText')).includes('el máximo no puede ser menor'));
  await goto('/admin/turnos/' + newId);
  const factsAfter = await ev('[...document.querySelectorAll("#shift-facts dd")].map(d => d.innerText).join(" · ")');
  check(R2, 'El turno no cambia tras el rechazo', '2 personas · 3 personas · 2 · 1', factsAfter, factsAfter === '2 personas · 3 personas · 2 · 1');

  await goto('/admin/turnos/nuevo');
  await fill('#kind', 'cleaning'); await fill('#date', N.date); await fill('#startTime', '12:00'); await fill('#endTime', '11:00');
  await fill('#minVolunteers', '3'); await fill('#maxVolunteers', '2');
  await submit('#form-shift');
  await waitFor(`!!document.querySelector('#error-endTime')`, 'errores de validación');
  const r422 = lastResponse('/admin/turnos/nuevo');
  const invalid = await ev('[...document.querySelectorAll("[aria-invalid=true]")].map(e => e.id).join(",")');
  check(R2, 'Alta con fin anterior al inicio y máximo menor que el mínimo (CA-07-07)', '422; endTime y maxVolunteers marcados; valores conservados', `${r422 && r422.status}; aria-invalid: ${invalid}; fin ${await ev('document.querySelector("#endTime").value')}`, !!r422 && r422.status === 422 && invalid === 'endTime,maxVolunteers' && (await ev('document.querySelector("#endTime").value')) === '11:00');
  wr = await widthReport();
  check(R2, 'Formulario con errores en móvil sin desbordar', 'scrollWidth y borde ≤ 390', wr, fits(wr));
  await shot('06-movil-alta-con-errores');

  await login('veterinaria@example.test');
  const vetStatus = await ev(`fetch('/admin/turnos').then(r => r.status)`);
  check(R2, 'La veterinaria no accede a los turnos (CA-07-11, D26)', '403', String(vetStatus), vetStatus === 403);
  await send('Network.clearBrowserCookies');
  const anonStatus = await ev(`fetch('/admin/turnos', {redirect: 'manual'}).then(r => r.type === 'opaqueredirect' ? 'redirección' : String(r.status))`);
  check(R2, 'Persona anónima en /admin/turnos (CA-07-11)', 'redirección a /acceso', anonStatus, anonStatus === 'redirección');
} catch (error) {
  failures++;
  log.push({ route: 'ejecución', step: 'error', expected: '', observed: String(error), result: 'FALLO' });
  console.log('✘ ' + error);
  await shot('error').catch(() => {});
} finally {
  writeFileSync(`${outDir}/resultado.json`, JSON.stringify({ base, failures, log, responses: responses.filter(r => !r.url.includes('/vendor/')) }, null, 2));
  ws.close(); chrome.kill();
  process.exit(failures ? 1 : 0);
}
