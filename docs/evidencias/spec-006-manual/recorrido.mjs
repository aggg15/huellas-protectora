// Verificación manual asistida de HU-06 (T623) con Chrome real (sin interfaz) mediante DevTools Protocol.
// Adaptado de ../spec-005-manual/recorrido.mjs. Uso: node recorrido.mjs <dir-salida> <seed.json> <base-url> <chrome>
import { spawn } from 'node:child_process';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';

const [outDir, seedPath, base, chromePath] = process.argv.slice(2);
const seed = JSON.parse(readFileSync(seedPath, 'utf8'));
mkdirSync(outDir, { recursive: true });
const chrome = spawn(chromePath, [
  '--headless=new', '--remote-debugging-port=9334', `--user-data-dir=${outDir}/profile`,
  '--no-first-run', '--no-default-browser-check', '--lang=es-ES', 'about:blank',
], { stdio: 'ignore' });
const sleep = ms => new Promise(r => setTimeout(r, ms));
let targets;
for (let i = 0; i < 50; i++) {
  try { targets = await (await fetch('http://127.0.0.1:9334/json/list')).json(); if (targets.some(t => t.type === 'page')) break; } catch {}
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
const waitFor = async (expr, label, ms = 60000) => {
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
const noHorizontalScroll = () => ev('document.documentElement.scrollWidth <= window.innerWidth');
const key = async (key, code, keyCode, text) => {
  await send('Input.dispatchKeyEvent', { type: 'keyDown', key, code, windowsVirtualKeyCode: keyCode, text });
  await send('Input.dispatchKeyEvent', { type: 'keyUp', key, code, windowsVirtualKeyCode: keyCode });
};
const tab = () => key('Tab', 'Tab', 9);
const active = () => ev('document.activeElement && (document.activeElement.id || document.activeElement.tagName)');
const lastResponse = fragment => [...responses].reverse().find(r => r.url.includes(fragment));
const login = async email => {
  await send('Network.clearBrowserCookies');
  await goto('/acceso');
  await fill('input[name="_username"]', email);
  await fill('input[name="_password"]', seed.users[email]);
  await ev('document.querySelector(\'input[name="_password"]\').form.requestSubmit()');
  await waitFor(`!location.pathname.startsWith('/acceso')`, 'acceso de ' + email);
};
const addDays = n => { const d = new Date(seed.today + 'T00:00:00Z'); d.setUTCDate(d.getUTCDate() + n); return d.toISOString().slice(0, 10); };
const es = iso => iso.split('-').reverse().join('/');

const log = []; let failures = 0;
const check = (route, step, expected, observed, ok) => { log.push({ route, step, expected, observed, result: ok ? 'OK' : 'FALLO' }); if (!ok) failures++; console.log((ok ? '✔ ' : '✘ ') + route + ' · ' + step + ' → ' + observed); };

await send('Page.enable'); await send('Runtime.enable'); await send('Network.enable');
const A = seed.animalA, B = seed.animalB, C = seed.animalC;
const note = 'Duerme toda la noche y ya pasea sin miedo (novedad ficticia).';

try {
  // ---------- Recorrido 1: satisfactorio, familia → coordinadora → web pública → novedad ----------
  const R1 = 'R1 satisfactorio';
  await viewport(1280, 900, false);
  await login('familia.encina@example.test');
  check(R1, 'La familia accede y llega a «Mi acogida» (D19)', '/acogida', await ev('location.pathname'), (await ev('location.pathname')) === '/acogida');
  check(R1, 'Estado vacío inicial', 'Todavía no has indicado tus condiciones', (await text()).includes('Todavía no has indicado') ? 'mensaje visible' : 'sin mensaje', (await text()).includes('Todavía no has indicado'));
  await fill('#capacity', '2'); await submit('#form-capacity');
  await waitFor(`!!document.querySelector('#form-period')`, 'capacidad guardada');
  await fill('#startsOn', seed.today); await fill('#endsOn', addDays(30)); await submit('#form-period');
  await waitFor(`!!document.querySelector('#periods')`, 'periodo añadido');
  const occupancy = await ev('document.querySelector("#occupancy").innerText');
  const periodRow = await ev('document.querySelector("#periods tbody tr").innerText.replace(/\\s+/g, " ")');
  check(R1, 'Capacidad 2 y periodo de 30 días guardados (CA-06-01)', `0 de 2 plazas ocupadas; ${es(seed.today)} – ${es(addDays(30))}`, `${occupancy}; ${periodRow}`, occupancy.includes('0 de 2') && periodRow.includes(es(seed.today)) && periodRow.includes(es(addDays(30))));
  await shot('01-escritorio-familia-condiciones');

  await login('coordinadora@example.test');
  await goto('/admin/acogidas');
  const homeRow = await ev(`[...document.querySelectorAll('#homes tbody tr')].map(r => r.innerText.replace(/\\s+/g, ' ')).find(t => t.includes('Encina')) || ''`);
  check(R1, 'La coordinadora ve la casa con su ocupación (CA-06-01)', 'Familia Encina Ficticia · 0 de 2', homeRow, homeRow.includes('0 de 2'));
  await goto('/admin/animales/' + A.id);
  const homeOption = await ev(`[...document.querySelectorAll('#foster-home option')].find(o => o.innerText.includes('Encina')).value`);
  await fill('#foster-home', homeOption); await fill('#foster-planned-end', addDays(20)); await submit('#form-foster-place');
  await waitFor(`!!document.querySelector('#foster-active')`, 'acogida registrada');
  const activeText = await ev('document.querySelector("#foster-active").innerText.replace(/\\s+/g, " ")');
  check(R1, 'Registrar la acogida de A hasta dentro de 20 días (CA-06-02)', `éxito; Familia Encina, desde ${es(seed.today)}, fin ${es(addDays(20))}`, `${(await text()).includes('Acogida registrada') ? 'éxito' : 'sin mensaje'}; ${activeText}`, (await text()).includes('Acogida registrada') && activeText.includes('Encina') && activeText.includes(es(addDays(20))));
  await shot('02-escritorio-ficha-animal-en-acogida');

  await send('Network.clearBrowserCookies');
  await goto('/animales');
  const card = await ev(`[...document.querySelectorAll('.animal-card')].map(c => c.innerText).find(t => t.includes(${JSON.stringify(A.code)})) || ''`);
  check(R1, 'Catálogo anónimo: A sigue visible con «En casa de acogida» (CA-06-04)', 'tarjeta con la indicación', card ? (card.includes('En casa de acogida') ? 'tarjeta con la indicación' : 'sin indicación') : 'no aparece', card.includes('En casa de acogida'));
  const cCard = await ev(`(document.querySelector('main').innerText).includes(${JSON.stringify(C.code)})`);
  await shot('03-escritorio-catalogo-publico');

  // Familia en móvil: anotar una novedad solo con el teclado.
  await login('familia.encina@example.test');
  await viewport(390, 844, true);
  await goto('/acogida');
  check(R1, 'Página de la familia en móvil sin desplazamiento horizontal', 'sin scroll horizontal', (await noHorizontalScroll()) ? 'sin scroll horizontal' : 'hay scroll horizontal', await noHorizontalScroll());
  await ev('document.activeElement && document.activeElement.blur(); window.scrollTo(0, 0)');
  let tabs = 0;
  while (!String(await active()).startsWith('body-') && tabs < 80) { await tab(); tabs++; }
  check(R1, 'Teclado: el campo de la novedad se alcanza con Tab', 'foco en la novedad', `foco en #${await active()} tras ${tabs} pulsaciones`, String(await active()).startsWith('body-'));
  await send('Input.insertText', { text: note });
  await tab();
  const onButton = await ev('document.activeElement.tagName + ":" + document.activeElement.innerText');
  await key('Enter', 'Enter', 13, '\r');
  await waitFor(`document.body.innerText.includes('Novedad anotada')`, 'novedad anotada');
  check(R1, 'Teclado: Tab lleva al botón y Enter guarda la novedad (CA-06-05)', 'BUTTON:Guardar novedad; mensaje de éxito; novedad visible', `${onButton}; ${(await text()).includes(note) ? 'novedad visible' : 'no visible'}`, onButton === 'BUTTON:Guardar novedad' && (await text()).includes(note));
  await shot('04-movil-familia-novedad');

  await login('coordinadora@example.test');
  await viewport(1280, 900, false);
  await goto('/admin/animales/' + A.id);
  const notes = await ev('document.querySelector("#foster-notes").innerText');
  check(R1, 'El personal ve la novedad en la ficha interna (CA-06-05)', 'novedad y autora', notes.includes(note) && notes.includes('Familia Encina') ? 'novedad y autora' : notes, notes.includes(note) && notes.includes('Familia Encina'));
  await send('Network.clearBrowserCookies');
  const publicHtml = await ev(`fetch('/animales/${A.id}').then(r => r.text())`);
  check(R1, 'La novedad no aparece en la ficha pública (D24)', 'ausente', publicHtml.includes('Duerme toda la noche') ? 'presente' : 'ausente', !publicHtml.includes('Duerme toda la noche'));

  // ---------- Recorrido 2: error de capacidad y de datos, móvil 390 px ----------
  const R2 = 'R2 error';
  await login('coordinadora@example.test');
  await viewport(390, 844, true);
  await goto('/admin/animales/' + B.id);
  const fullOption = await ev(`[...document.querySelectorAll('#foster-home option')].find(o => o.innerText.includes('Álamo')).value`);
  await fill('#foster-home', fullOption); await fill('#foster-planned-end', addDays(10));
  await ev('document.querySelector("#form-foster-place button").focus()');
  await key('Enter', 'Enter', 13, '\r');
  await waitFor(`!!document.querySelector('#foster-error-placement')`, 'error de capacidad');
  const r409 = lastResponse('/acogida');
  const capError = await ev('document.querySelector("#foster-error-placement").innerText');
  check(R2, 'Registrar B en la casa completa de Álamo (CA-06-03)', '409; «ya atiende su máximo de 1 animal»', `${r409 && r409.status}; «${capError}»`, r409 && r409.status === 409 && capError.includes('ya atiende su máximo de 1 animal'));
  check(R2, 'B sigue sin acogida y el formulario conserva la fecha', `formulario visible; fin ${addDays(10)}`, `${(await ev('!!document.querySelector("#foster-active")')) ? 'con acogida' : 'formulario visible'}; fin ${await ev('document.querySelector("#foster-planned-end").value')}`, !(await ev('!!document.querySelector("#foster-active")')) && (await ev('document.querySelector("#foster-planned-end").value')) === addDays(10));
  check(R2, 'Ficha con error en móvil sin desplazamiento horizontal', 'sin scroll horizontal', (await noHorizontalScroll()) ? 'sin scroll horizontal' : 'hay scroll horizontal', await noHorizontalScroll());
  await shot('05-movil-error-casa-completa');

  await login('familia.alamo@example.test');
  await goto('/acogida');
  await fill('#capacity', '0'); await submit('#form-capacity');
  await waitFor(`!!document.querySelector('#error-capacity')`, 'error de capacidad 0');
  const r422 = lastResponse('/acogida/capacidad');
  check(R2, 'La familia indica capacidad 0 (CA-06-06)', '422; campo marcado como no válido; capacidad guardada sin cambios', `${r422 && r422.status}; aria-invalid=${await ev('document.querySelector("#capacity").getAttribute("aria-invalid")')}`, r422 && r422.status === 422 && (await ev('document.querySelector("#capacity").getAttribute("aria-invalid")')) === 'true');
  await shot('06-movil-familia-error-capacidad');
  await goto('/acogida');
  check(R2, 'Tras el error sigue la capacidad anterior (1 de 1)', '1 de 1 plaza ocupada', await ev('document.querySelector("#occupancy").innerText'), (await ev('document.querySelector("#occupancy").innerText')).includes('1 de 1'));
  const anonStatus = await (async () => { await send('Network.clearBrowserCookies'); return ev(`fetch('/acogida', {redirect: 'manual'}).then(r => r.type === 'opaqueredirect' ? 'redirección' : String(r.status))`); })();
  check(R2, 'Persona anónima en /acogida (CA-06-10)', 'redirección a /acceso', anonStatus, anonStatus === 'redirección');
  void cCard;
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
