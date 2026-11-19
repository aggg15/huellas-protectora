// Verificación manual asistida de HU-05 (T525) con Chrome real (sin interfaz) mediante DevTools Protocol.
// Uso: node walk.mjs <dir-salida> <seed.json> <base-url>
import { spawn } from 'node:child_process';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';

const [outDir, seedPath, base] = process.argv.slice(2);
const seed = JSON.parse(readFileSync(seedPath, 'utf8'));
mkdirSync(outDir, { recursive: true });
const chrome = spawn('/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', [
  '--headless=new', '--remote-debugging-port=9333', `--user-data-dir=${outDir}/profile`,
  '--no-first-run', '--no-default-browser-check', '--lang=es-ES', 'about:blank',
], { stdio: 'ignore' });
const sleep = ms => new Promise(r => setTimeout(r, ms));
let targets;
for (let i = 0; i < 50; i++) {
  try { targets = await (await fetch('http://127.0.0.1:9333/json/list')).json(); if (targets.some(t => t.type === 'page')) break; } catch {}
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
const waitFor = async (expr, label, ms = 10000) => {
  for (const end = Date.now() + ms; Date.now() < end; await sleep(150)) { if (await ev(expr)) return; }
  throw new Error('Tiempo agotado esperando: ' + label + ' en ' + await ev('location.href'));
};
const goto = async path => { await send('Page.navigate', { url: base + path }); await sleep(300); await waitFor(`document.readyState === 'complete'`, 'carga ' + path); };
const text = () => ev('document.querySelector("main").innerText');
const fill = (sel, value) => ev(`(() => { const el = document.querySelector(${JSON.stringify(sel)}); el.value = ${JSON.stringify(value)}; el.dispatchEvent(new Event('input', {bubbles: true})); el.dispatchEvent(new Event('change', {bubbles: true})); return true; })()`);
const click = sel => ev(`(() => { const el = document.querySelector(${JSON.stringify(sel)}); if (!el) return false; el.click(); return true; })()`);
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
const login = async (email) => {
  await send('Network.clearBrowserCookies');
  await goto('/acceso');
  await fill('input[name="_username"]', email);
  await fill('input[name="_password"]', seed.users[email]);
  await ev('document.querySelector(\'input[name="_password"]\').form.requestSubmit()');
  await waitFor(`!location.pathname.startsWith('/acceso')`, 'acceso de ' + email);
};
const madridMinusOneHour = () => new Date(Date.now() - 3600e3).toLocaleString('sv-SE', { timeZone: 'Europe/Madrid' }).slice(0, 16).replace(' ', 'T');

const log = []; let failures = 0;
const check = (route, step, expected, observed, ok) => { log.push({ route, step, expected, observed, result: ok ? 'OK' : 'FALLO' }); if (!ok) failures++; console.log((ok ? '✔ ' : '✘ ') + route + ' · ' + step + ' → ' + observed); };

await send('Page.enable'); await send('Runtime.enable'); await send('Network.enable');
const A = seed.animalA, B = seed.animalB;
const applicantEmail = 'familia.nueva@example.test';
const applicantPassword = 'Ficticia-' + Math.random().toString(36).slice(2, 12);

try {
  // ---------- Recorrido 1: satisfactorio, HU-02 → HU-03 → HU-04 → HU-05 (escritorio 1280 px) ----------
  const R1 = 'R1 satisfactorio';
  await viewport(1280, 900, false);
  await goto('/animales');
  check(R1, 'Catálogo anónimo muestra el animal disponible (HU-02)', `contiene ${A.code}`, (await text()).includes(A.code) ? `contiene ${A.code}` : 'no aparece', (await text()).includes(A.code));
  await goto('/animales/' + A.id);
  await click(`a[href="/animales/${A.id}/solicitud"]`);
  await waitFor(`location.pathname === '/acceso'`, 'redirección a acceso');
  await click('a[href="/registro"]');
  await waitFor(`location.pathname === '/registro'`, 'registro');
  await fill('input[name="name"]', 'Familia Nueva Ficticia');
  await fill('input[name="email"]', applicantEmail);
  await fill('input[name="password"]', applicantPassword);
  await fill('input[name="passwordConfirmation"]', applicantPassword);
  await ev('document.querySelector(\'input[name="email"]\').form.requestSubmit()');
  await waitFor(`location.pathname.endsWith('/solicitud')`, 'vuelta al formulario');
  check(R1, 'Alta pública y vuelta al formulario de solicitud (HU-03)', '/animales/{id}/solicitud', await ev('location.pathname'), true);
  await fill('select[name="housingType"]', 'house_outdoor');
  await fill('textarea[name="housing"]', 'Casa ficticia con jardín vallado; convivimos dos personas adultas.');
  await fill('select[name="experienceType"]', 'dogs');
  await fill('textarea[name="experience"]', 'Hemos convivido con un perro durante doce años (ficticio).');
  await fill('select[name="dailyTime"]', 'gt4');
  await fill('textarea[name="availability"]', 'Teletrabajo; los abuelos lo cuidan en las ausencias (ficticio).');
  await ev('document.querySelector(\'select[name="housingType"]\').form.requestSubmit()');
  await waitFor(`location.pathname === '/mis-solicitudes'`, 'mis solicitudes');
  check(R1, 'Solicitud presentada queda pendiente (HU-03)', 'Pendiente de valoración', (await text()).includes('Pendiente de valoración') ? 'Pendiente de valoración' : await text(), (await text()).includes('Pendiente de valoración'));

  await login('coordinadora@example.test');
  await goto('/admin/solicitudes');
  const appUrl = await ev(`[...document.querySelectorAll('tbody tr')].find(tr => tr.innerText.includes(${JSON.stringify(A.code)})).querySelector('a').getAttribute('href')`);
  await goto(appUrl);
  await ev('document.querySelector("details").open = true');
  await fill('#interviewAt', madridMinusOneHour());
  await fill('#interviewNotes', 'Entrevista ficticia en el refugio: familia adecuada.');
  await ev('document.querySelector("#interviewNotes").form.requestSubmit()');
  await waitFor(`document.body.innerText.includes('Entrevista registrada correctamente')`, 'entrevista');
  await ev('document.querySelector("#btn-approve").form.requestSubmit()');
  await waitFor(`!!document.querySelector('#form-formalize')`, 'aprobación');
  check(R1, 'Entrevista y aprobación: aparece «Formalizar la entrega» (HU-04 → HU-05)', 'formulario visible, animal reservado', (await text()).includes('Reservado') ? 'formulario visible, animal reservado' : 'sin reserva', (await text()).includes('Reservado'));
  await shot('01-escritorio-formalizar-solicitud-aprobada');

  await fill('#fee', '120,00');
  await ev('document.querySelector("#feePaid").checked = true');
  await ev('document.querySelector("#form-formalize").requestSubmit()');
  await waitFor(`location.pathname.startsWith('/admin/adopciones/')`, 'adopción');
  const adoptionPath = await ev('location.pathname');
  const adoptionText = await text();
  const rows = await ev(`[...document.querySelectorAll('#follow-ups tbody tr')].map(r => r.innerText.replace(/\\s+/g, ' ').trim())`);
  check(R1, 'Formalizar con 120,00 € y abono confirmado (CA-05-01/02)', 'mensaje de éxito, contrato CTR-…, 120,00 €', `${adoptionText.includes('Adopción formalizada') ? 'éxito' : 'sin mensaje'}, ${(adoptionText.match(/CTR-[0-9A-F]+/) || ['sin contrato'])[0]}, ${adoptionText.includes('120,00 €') ? '120,00 €' : 'sin cuota'}`, adoptionText.includes('Adopción formalizada') && /CTR-/.test(adoptionText) && adoptionText.includes('120,00 €'));
  check(R1, 'Planificación con dos seguimientos (CA-05-03)', '2 filas: al mes y a los seis meses, programados', JSON.stringify(rows), rows.length === 2 && rows[0].includes('al mes') && rows[1].includes('seis meses') && rows.every(r => r.includes('Programado')));
  await shot('02-escritorio-adopcion-y-seguimientos');

  await goto('/admin/seguimientos');
  const listRows = await ev(`document.querySelectorAll('#follow-ups tbody tr').length`);
  check(R1, 'Listado de seguimientos de la coordinadora (CA-05-03)', '2 filas ordenadas por fecha', `${listRows} filas`, listRows === 2);
  const contractPath = adoptionPath.replace('/admin/adopciones/', '/adopciones/') + '/contrato';
  await goto(contractPath);
  check(R1, 'Contrato como coordinadora con botón de impresión (CA-05-01)', 'título «Contrato de adopción», botón Imprimir', `${await ev('document.querySelector("h1").innerText')}, botón: ${await ev('!!document.querySelector("[data-controller=print]")')}`, (await ev('document.querySelector("h1").innerText')).startsWith('Contrato de adopción') && await ev('!!document.querySelector("[data-controller=print]")'));
  await send('Emulation.setEmulatedMedia', { media: 'print' });
  const printHidden = await ev(`getComputedStyle(document.querySelector('.site-header')).display === 'none' && getComputedStyle(document.querySelector('.no-print')).display === 'none'`);
  await shot('03-impresion-contrato');
  await send('Emulation.setEmulatedMedia', { media: '' });
  check(R1, 'Vista de impresión oculta cabecera y botones', 'cabecera y botones ocultos', printHidden ? 'ocultos' : 'visibles', printHidden);

  await send('Network.clearBrowserCookies');
  await goto('/acceso');
  await fill('input[name="_username"]', applicantEmail); await fill('input[name="_password"]', applicantPassword);
  await ev('document.querySelector(\'input[name="_password"]\').form.requestSubmit()');
  await waitFor(`location.pathname === '/mis-solicitudes'`, 'adoptante en mis solicitudes');
  await viewport(390, 844, true);
  await goto('/mis-solicitudes');
  check(R1, 'Adoptante ve «Adopción formalizada» y enlace al contrato (móvil 390 px)', 'estado y enlace', `${(await text()).includes('Adopción formalizada') ? 'estado' : 'sin estado'}, enlace: ${await ev('!!document.querySelector("a[href$=\\"/contrato\\"]")')}`, (await text()).includes('Adopción formalizada') && await ev('!!document.querySelector("a[href$=\\"/contrato\\"]")'));
  await shot('04-movil-mis-solicitudes');
  await click('a[href$="/contrato"]');
  await waitFor(`location.pathname.endsWith('/contrato')`, 'contrato del adoptante');
  const contractText = await text();
  check(R1, 'Adoptante abre su contrato en móvil, sin desplazamiento horizontal', `contiene ${A.code} y Familia Nueva Ficticia; sin scroll horizontal`, `${contractText.includes(A.code) && contractText.includes('Familia Nueva Ficticia') ? 'datos correctos' : 'faltan datos'}; scroll horizontal: ${!(await noHorizontalScroll())}`, contractText.includes(A.code) && contractText.includes('Familia Nueva Ficticia') && await noHorizontalScroll());
  await shot('05-movil-contrato');
  const forbidden = await ev(`fetch('/admin/seguimientos', {redirect: 'manual'}).then(r => r.status)`);
  check(R1, 'El adoptante no accede al listado de seguimientos (CA-05-11)', '403', String(forbidden), forbidden === 403);

  await send('Network.clearBrowserCookies');
  await viewport(1280, 900, false);
  await goto('/animales');
  const stillListed = (await text()).includes(A.code);
  const detailStatus = await ev(`fetch('/animales/${A.id}').then(r => r.status)`);
  check(R1, 'Visita anónima: el animal adoptado ya no está en el catálogo y su ficha da 404 (CA-05-04)', 'ausente; 404', `${stillListed ? 'presente' : 'ausente'}; ${detailStatus}`, !stillListed && detailStatus === 404);

  // ---------- Recorrido 2: error de estado, móvil y teclado (HU-04 → HU-05) ----------
  const R2 = 'R2 error';
  await login('coordinadora@example.test');
  await viewport(390, 844, true);
  await goto('/admin/solicitudes/' + B.application);
  check(R2, 'Ficha de solicitud aprobada en móvil sin desplazamiento horizontal', 'sin scroll horizontal', (await noHorizontalScroll()) ? 'sin scroll horizontal' : 'hay scroll horizontal', await noHorizontalScroll());
  await ev('document.activeElement && document.activeElement.blur(); window.scrollTo(0, 0)');
  let tabs = 0;
  while ((await active()) !== 'fee' && tabs < 80) { await tab(); tabs++; }
  check(R2, 'Teclado: el campo de cuota se alcanza con Tab', 'foco en #fee', `foco en #${await active()} tras ${tabs} pulsaciones`, (await active()) === 'fee');
  await send('Input.insertText', { text: '120,00' });
  await tab();
  const afterFee = await active();
  await tab();
  const afterCheckbox = await active();
  check(R2, 'Teclado: orden cuota → casilla de abono → botón', 'feePaid, btn-formalize', `${afterFee}, ${afterCheckbox}`, afterFee === 'feePaid' && afterCheckbox === 'btn-formalize');
  await key('Enter', 'Enter', 13, '\r');
  await waitFor(`!!document.querySelector('#error-feePaid')`, 'error de abono');
  const r422 = lastResponse('/formalizar');
  const errorText = await ev('document.querySelector("#error-feePaid").innerText');
  const keptFee = await ev('document.querySelector("#fee").value');
  check(R2, 'Enviar sin confirmar el abono (CA-05-07)', '422, error en la casilla, importe conservado', `${r422 && r422.status}, «${errorText}», importe «${keptFee}»`, r422 && r422.status === 422 && errorText.includes('Confirma') && keptFee === '120,00');
  const stillReserved = (await text()).includes('Reservado') && (await ev('!!document.querySelector("#form-formalize")'));
  check(R2, 'El animal sigue reservado y la solicitud aprobada', 'Reservado; formulario disponible', stillReserved ? 'Reservado; formulario disponible' : 'estado cambiado', stillReserved);
  await shot('06-movil-error-sin-abono');
  // Corrección con teclado: marcar la casilla con Espacio y enviar con Enter.
  await ev('document.querySelector("#feePaid").focus()');
  await key(' ', 'Space', 32, ' ');
  check(R2, 'Teclado: Espacio marca la casilla de abono', 'marcada', (await ev('document.querySelector("#feePaid").checked')) ? 'marcada' : 'sin marcar', await ev('document.querySelector("#feePaid").checked'));
  await ev('document.querySelector("#btn-formalize").focus()');
  await key('Enter', 'Enter', 13, '\r');
  await waitFor(`location.pathname.startsWith('/admin/adopciones/')`, 'adopción B');
  check(R2, 'Tras corregir, la formalización por teclado se completa', 'página de la adopción', await ev('location.pathname'), (await text()).includes('Adopción formalizada'));
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
