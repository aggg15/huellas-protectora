// Verificación manual asistida de HU-08 (T818) con Chrome real (sin interfaz) mediante DevTools Protocol.
// Adaptado de ../spec-007-manual/recorrido.mjs.
// Uso: node recorrido.mjs <dir-salida> <seed.json> <base-url> <chrome> <comando-adoptar>
// <comando-adoptar> es la orden (sin el identificador del animal) que formaliza la adopción con HU-05.
import { spawn, execSync } from 'node:child_process';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';

const [outDir, seedPath, base, chromePath, adoptCommand] = process.argv.slice(2);
const seed = JSON.parse(readFileSync(seedPath, 'utf8'));
mkdirSync(outDir, { recursive: true });
const chrome = spawn(chromePath, [
  '--headless=new', '--remote-debugging-port=9336', `--user-data-dir=${outDir}/profile`,
  '--no-first-run', '--no-default-browser-check', '--lang=es-ES', 'about:blank',
], { stdio: 'ignore' });
const sleep = ms => new Promise(r => setTimeout(r, ms));
let targets;
for (let i = 0; i < 50; i++) {
  try { targets = await (await fetch('http://127.0.0.1:9336/json/list')).json(); if (targets.some(t => t.type === 'page')) break; } catch {}
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
const fill = (sel, value) => ev(`(() => { const el = document.querySelector(${JSON.stringify(sel)}); el.value = ${JSON.stringify(value)}; el.dispatchEvent(new Event('input', {bubbles: true})); el.dispatchEvent(new Event('change', {bubbles: true})); return true; })()`);
const viewport = async (width, height, mobile) => send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile });
const shot = async name => {
  const { cssContentSize } = await send('Page.getLayoutMetrics');
  const { data } = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width: cssContentSize.width, height: Math.min(cssContentSize.height, 4000), scale: 1 } });
  writeFileSync(`${outDir}/${name}.png`, Buffer.from(data, 'base64'));
};
// Desplazamiento horizontal y borde derecho real de cajas, filas y avisos (lección de T626 y T721).
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
const typeText = async value => { for (const ch of value) { await send('Input.dispatchKeyEvent', { type: 'char', text: ch }); } };
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
const card = code => ev(`(() => { const c = [...document.querySelectorAll('.sponsorship-card')].find(c => c.querySelector('h2').innerText.trim() === ${JSON.stringify(code)}); return c ? { id: c.id.replace('sponsorship-', ''), text: c.innerText.replace(/\\s+/g, ' '), tag: c.querySelector('.tag').innerText.trim() } : null; })()`);

const log = []; let failures = 0;
const check = (route, step, expected, observed, ok) => { log.push({ route, step, expected, observed, result: ok ? 'OK' : 'FALLO' }); if (!ok) failures++; console.log((ok ? '✔ ' : '✘ ') + route + ' · ' + step + ' → ' + (typeof observed === 'string' ? observed : JSON.stringify(observed))); };

await send('Page.enable'); await send('Runtime.enable'); await send('Network.enable');
const [A1, A2, A3] = seed.animals;

try {
  // ---------- Recorrido 1: satisfactorio, madrina (móvil, teclado) → coordinadora → adopción → elección ----------
  const R1 = 'R1 satisfactorio';
  await viewport(390, 844, true);
  await login('lucia@example.test');
  await goto('/animales/' + A1.id);
  let tabs = await tabUntil(`document.activeElement && document.activeElement.closest && !!document.activeElement.closest('#sponsor-box')`);
  check(R1, 'Teclado (390 px): el enlace «Apadrinar» de la ficha pública se alcanza con Tab', `foco en «Apadrinar a ${A1.code}»`, `«${await activeText()}» tras ${tabs} pulsaciones`, (await activeText()) === 'Apadrinar a ' + A1.code);
  await enter();
  await waitFor(`location.pathname === '/animales/${A1.id}/apadrinar'`, 'formulario de apadrinamiento');
  let wr = await widthReport();
  check(R1, 'Formulario de apadrinamiento en móvil sin desbordar', 'scrollWidth y borde ≤ 390', wr, fits(wr));

  // Error dentro del recorrido: 9,99 € (CA-08-02), escrito con teclado.
  await ev('document.querySelector("#amount").focus()');
  await typeText('9,99');
  await enter();
  await waitFor(`!!document.querySelector('#error-amount')`, 'error de importe');
  const err = await ev('document.querySelector("#error-amount").innerText');
  check(R1, 'Importe de 9,99 € rechazado y conservado (CA-08-02)', '422; error del importe; valor 9,99; aria-invalid', `${lastResponse('/apadrinar').status}; «${err}»; valor ${await ev('document.querySelector("#amount").value')}; aria-invalid=${await ev('document.querySelector("#amount").getAttribute("aria-invalid")')}`, lastResponse('/apadrinar').status === 422 && err.includes('al menos 10,00 €') && (await ev('document.querySelector("#amount").value')) === '9,99');
  await shot('01-movil-importe-no-valido');
  await fill('#amount', '');
  await ev('document.querySelector("#amount").focus()');
  await typeText('15,50');
  await enter();
  await waitFor(`location.pathname === '/mis-apadrinamientos'`, 'mis apadrinamientos');
  let c = await card(A1.code);
  check(R1, 'Apadrinamiento con 15,50 € al mes (CA-08-01)', '«Ya apadrinas…»; tarjeta Activo con 15,50 €', c && `${c.tag}; ${c.text}`, (await ev('document.body.innerText')).includes(`Ya apadrinas a ${A1.code} con 15,50 € al mes`) && !!c && c.tag === 'Activo' && c.text.includes('15,50 €'));
  wr = await widthReport();
  check(R1, '«Mis apadrinamientos» en móvil sin desbordar', 'scrollWidth y borde ≤ 390', wr, fits(wr));
  await shot('02-movil-apadrinamiento-activo');

  await viewport(1280, 900, false);
  await login('coordinadora@example.test');
  await goto('/admin/apadrinamientos');
  const section = `#sponsored-${A1.id}`;
  check(R1, 'La coordinadora ve el animal con su madrina e importe (D40)', 'Lucía Madrina Ficticia · 15,50 €', await ev(`document.querySelector('${section} .sponsors').innerText.replace(/\\s+/g, ' ')`), (await ev(`document.querySelector('${section} .sponsors').innerText`)).includes('Lucía Madrina Ficticia'));
  await ev(`document.querySelector('#body-${A1.id}').focus()`);
  await typeText('Esta semana ha aprendido a pasear con correa y ya juega con otros perros. <b>Muy contento</b>.');
  tabs = 0; await tab(); tabs++;
  const btn = await activeText();
  await enter();
  await waitFor(`document.body.innerText.includes('Novedad de ${A1.code} publicada')`, 'novedad publicada');
  check(R1, 'Teclado: textarea → Tab → «Publicar novedad» y Enter (CA-08-03)', 'éxito; la novedad aparece en la lista', `foco «${btn}»; ${await ev(`document.querySelector('${section} .sponsor-updates').innerText.replace(/\\s+/g, ' ')`)}`, btn === 'Publicar novedad' && (await ev(`document.querySelector('${section} .sponsor-updates').innerText`)).includes('pasear con correa'));
  await shot('03-escritorio-coordinadora-novedad');

  await viewport(390, 844, true);
  await login('lucia@example.test');
  await goto('/mis-apadrinamientos');
  c = await card(A1.code);
  const escaped = await ev(`document.querySelectorAll('.sponsor-updates b').length === 0`);
  check(R1, 'La madrina recibe la novedad, escapada (CA-08-03)', 'novedad visible con su fecha; sin HTML interpretado', c && c.text, !!c && c.text.includes('pasear con correa') && c.text.includes('<b>Muy contento</b>') && escaped);

  execSync(adoptCommand + ' ' + A1.id, { stdio: 'pipe' });
  await goto('/mis-apadrinamientos');
  c = await card(A1.code);
  check(R1, 'Tras la adopción (HU-05) queda pendiente de decisión con la aportación en pausa (CA-08-04)', '«Pendiente de tu decisión»; fecha de adopción; aportación en pausa', c && `${c.tag}; ${c.text}`, !!c && c.tag === 'Pendiente de tu decisión' && c.text.includes('ha encontrado familia') && c.text.includes('en pausa'));
  const options = await ev(`[...document.querySelectorAll('#animal-${c.id} option')].map(o => o.innerText.split(' · ')[0])`);
  check(R1, 'Se ofrecen los animales disponibles que no apadrina y «Terminar mi colaboración»', `${A2.code} y ${A3.code}`, options, options.includes(A2.code) && options.includes(A3.code) && !options.includes(A1.code));
  wr = await widthReport();
  check(R1, 'Decisión pendiente en móvil sin desbordar', 'scrollWidth y borde ≤ 390', wr, fits(wr));
  await shot('04-movil-decision-pendiente');
  await ev(`document.querySelector('#animal-${c.id}').focus()`);
  await fill(`#animal-${c.id}`, A2.id);
  await tab();
  const moveBtn = await activeText();
  await enter();
  await waitFor(`document.body.innerText.includes('Ahora apadrinas a ${A2.code}')`, 'cambio de animal');
  const c1 = await card(A1.code); const c2 = await card(A2.code);
  check(R1, 'Teclado: selector → Tab → botón y Enter; la elección queda reflejada (CA-08-05)', `${A2.code} activo con 15,50 €; ${A1.code} terminado «Cambio a otro animal (${A2.code})»`, `foco «${moveBtn}»; ${c2 && c2.tag + ' ' + c2.text} | ${c1 && c1.tag + ' ' + c1.text}`, moveBtn === 'Apadrinar al animal elegido' && !!c2 && c2.tag === 'Activo' && c2.text.includes('15,50 €') && !!c1 && c1.tag === 'Terminado' && c1.text.includes(`Cambio a otro animal (${A2.code})`) && c1.text.includes('pasear con correa'));
  await shot('05-movil-eleccion-registrada');

  // ---------- Recorrido 2: errores de permisos y de estado ----------
  const R2 = 'R2 permisos y estado';
  await viewport(1280, 900, false);
  await login('voluntario@example.test');
  await goto('/animales/' + A3.id);
  check(R2, 'La ficha pública no ofrece apadrinar a una cuenta de personal (D34)', 'sin recuadro «Apadrinar»', await ev('!!document.querySelector("#sponsor-box")') ? 'recuadro visible' : 'sin recuadro', !(await ev('!!document.querySelector("#sponsor-box")')));
  await goto('/animales/' + A3.id + '/apadrinar');
  check(R2, 'Una cuenta de personal no apadrina (CA-08-10)', '403', String(lastResponse('/apadrinar').status), lastResponse('/apadrinar').status === 403);
  await goto('/admin/apadrinamientos');
  check(R2, 'Un voluntario no ve los apadrinamientos (CA-08-10)', '403', String(lastResponse('/admin/apadrinamientos').status), lastResponse('/admin/apadrinamientos').status === 403);
  await send('Network.clearBrowserCookies');
  await goto('/mis-apadrinamientos');
  check(R2, 'Anónimo dirigido a la página de acceso (CA-08-10)', '/acceso', await ev('location.pathname'), (await ev('location.pathname')) === '/acceso');
  await goto('/animales/' + A1.id + '/apadrinar');
  await login('lucia@example.test');
  await goto('/animales/' + A1.id + '/apadrinar');
  check(R2, 'Un animal adoptado no se puede apadrinar (CA-08-07)', '404', String(lastResponse('/apadrinar').status), lastResponse('/apadrinar').status === 404);
  await shot('06-escritorio-animal-adoptado-404');
} catch (error) {
  check('error', 'Ejecución del recorrido', 'sin excepciones', String(error), false);
  await shot('error');
} finally {
  writeFileSync(`${outDir}/resultado.json`, JSON.stringify({ when: new Date().toISOString(), base, checks: log, failures, responses }, null, 2));
  console.log(`${log.length - failures}/${log.length} comprobaciones correctas`);
  ws.close(); chrome.kill();
  process.exit(failures ? 1 : 0);
}
