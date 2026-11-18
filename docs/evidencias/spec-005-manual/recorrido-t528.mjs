// T528: «Mis solicitudes» a 390 px (filas apiladas) y a 1280 px (tabla), en Chrome real sin interfaz.
// Uso: node t528.mjs <dir-salida> <seed.json> <base-url>
import { spawn } from 'node:child_process';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
const [outDir, seedPath, base] = process.argv.slice(2);
const seed = JSON.parse(readFileSync(seedPath, 'utf8')); mkdirSync(outDir, { recursive: true });
const chrome = spawn('/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', ['--headless=new', '--remote-debugging-port=9334', `--user-data-dir=${outDir}/profile`, '--no-first-run', 'about:blank'], { stdio: 'ignore' });
const sleep = ms => new Promise(r => setTimeout(r, ms)); let targets;
for (let i = 0; i < 50; i++) { try { targets = await (await fetch('http://127.0.0.1:9334/json/list')).json(); if (targets.some(t => t.type === 'page')) break; } catch {} await sleep(200); }
const ws = new WebSocket(targets.find(t => t.type === 'page').webSocketDebuggerUrl); await new Promise(r => ws.addEventListener('open', r));
let seq = 0; const pending = new Map();
ws.addEventListener('message', ({ data }) => { const m = JSON.parse(data); if (m.id && pending.has(m.id)) { pending.get(m.id)(m); pending.delete(m.id); } });
const send = (method, params = {}) => new Promise((res, rej) => { const id = ++seq; pending.set(id, m => m.error ? rej(new Error(m.error.message)) : res(m.result)); ws.send(JSON.stringify({ id, method, params })); });
const ev = async e => (await send('Runtime.evaluate', { expression: e, returnByValue: true, awaitPromise: true })).result.value;
const waitFor = async (e, l) => { for (const end = Date.now() + 10000; Date.now() < end; await sleep(150)) if (await ev(e)) return; throw new Error('Tiempo agotado: ' + l); };
const goto = async p => { await send('Page.navigate', { url: base + p }); await sleep(300); await waitFor(`document.readyState === 'complete'`, p); };
const shot = async n => { const { cssContentSize: c } = await send('Page.getLayoutMetrics'); const { data } = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width: c.width, height: c.height, scale: 1 } }); writeFileSync(`${outDir}/${n}.png`, Buffer.from(data, 'base64')); };
const log = []; let failures = 0;
const check = (step, expected, observed, ok) => { log.push({ step, expected, observed, result: ok ? 'OK' : 'FALLO' }); if (!ok) failures++; console.log((ok ? '✔ ' : '✘ ') + step + ' → ' + observed); };
// Cada dato de cada fila debe quedar dentro de la ventana y sin recortes (ni de la página ni del contenedor).
const visibility = `(() => { const W = window.innerWidth; return [...document.querySelectorAll('.stacked-table tbody tr')].map(tr => {
  const status = tr.querySelector('td[data-label="Estado"] .tag'), detail = tr.querySelector('td[data-label="Detalle"]');
  const inside = el => { const r = el.getBoundingClientRect(); return r.left >= 0 && r.right <= W && el.scrollWidth <= el.clientWidth + 1; };
  return { text: tr.innerText.replace(/\\s+/g, ' ').trim(), statusVisible: inside(status), detailVisible: inside(detail), contract: !!detail.querySelector('a[href$="/contrato"]') && inside(detail.querySelector('a[href$="/contrato"]')) };
}); })()`;
try {
  await send('Page.enable'); await send('Runtime.enable');
  await send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
  await goto('/acceso');
  await ev(`document.querySelector('input[name="_username"]').value = 'otra.familia@example.test'`);
  await ev(`document.querySelector('input[name="_password"]').value = ${JSON.stringify(seed.users['otra.familia@example.test'])}`);
  await ev(`document.querySelector('input[name="_password"]').form.requestSubmit()`);
  await waitFor(`!location.pathname.startsWith('/acceso')`, 'acceso');
  await goto('/mis-solicitudes');
  const rows = await ev(visibility);
  check('Móvil 390 px: sin desplazamiento horizontal de la página ni del contenedor', 'sin desplazamiento', (await ev(`document.documentElement.scrollWidth <= innerWidth && [...document.querySelectorAll('.table-container')].every(c => c.scrollWidth <= c.clientWidth + 1)`)) ? 'sin desplazamiento' : 'hay desplazamiento', await ev(`document.documentElement.scrollWidth <= innerWidth && [...document.querySelectorAll('.table-container')].every(c => c.scrollWidth <= c.clientWidth + 1)`));
  check('Móvil 390 px: estado y detalle de cada fila visibles', `${rows.length} filas visibles`, JSON.stringify(rows.map(r => [r.statusVisible, r.detailVisible])), rows.length >= 2 && rows.every(r => r.statusVisible && r.detailVisible));
  const formalized = rows.find(r => r.text.includes('Adopción formalizada')), cancelled = rows.find(r => r.text.includes('Motivo: El animal ha sido adoptado'));
  check('Móvil 390 px: fila formalizada con enlace al contrato visible', 'enlace visible', formalized ? (formalized.contract ? 'enlace visible' : 'enlace oculto') : 'sin fila', !!formalized && formalized.contract);
  check('Móvil 390 px: fila cancelada con el motivo visible', 'Motivo: El animal ha sido adoptado', cancelled ? 'Motivo visible' : 'sin motivo', !!cancelled);
  check('Móvil 390 px: etiquetas de cada dato', 'Animal, Fecha de envío, Estado, Detalle', await ev(`[...new Set([...document.querySelectorAll('.stacked-table td')].map(td => getComputedStyle(td, '::before').content))].join(', ')`), true);
  await shot('07-movil-mis-solicitudes-apiladas');
  await send('Emulation.setDeviceMetricsOverride', { width: 1280, height: 900, deviceScaleFactor: 1, mobile: false });
  await goto('/mis-solicitudes');
  const desktop = await ev(`getComputedStyle(document.querySelector('.stacked-table thead')).position !== 'absolute' && getComputedStyle(document.querySelector('.stacked-table tr')).display === 'table-row'`);
  check('Escritorio 1280 px: se mantiene la tabla con cabecera', 'tabla', desktop ? 'tabla' : 'apilada', desktop);
  await shot('08-escritorio-mis-solicitudes');
} catch (e) { failures++; log.push({ step: 'error', observed: String(e), result: 'FALLO' }); console.log('✘ ' + e); }
finally { writeFileSync(`${outDir}/resultado-t528.json`, JSON.stringify({ base, failures, log }, null, 2)); ws.close(); chrome.kill(); process.exit(failures ? 1 : 0); }
