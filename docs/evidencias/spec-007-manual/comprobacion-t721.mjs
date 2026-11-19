// Comprobación de T721 (converge C1 de HU-07): en móvil (390 px), cada dato de las filas apiladas del listado de
// turnos debe quedar en la columna de valores, a la derecha de su etiqueta, y no debajo de ella.
// Uso: node comprobacion-t721.mjs <dir-salida> <seed.json> <base-url> <chrome> <nombre-captura>
import { spawn } from 'node:child_process';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';

const [outDir, seedPath, base, chromePath, shotName] = process.argv.slice(2);
const seed = JSON.parse(readFileSync(seedPath, 'utf8'));
mkdirSync(outDir, { recursive: true });
const chrome = spawn(chromePath, ['--headless=new', '--remote-debugging-port=9336', `--user-data-dir=${outDir}/profile-t721`, '--no-first-run', '--lang=es-ES', 'about:blank'], { stdio: 'ignore' });
const sleep = ms => new Promise(r => setTimeout(r, ms));
let targets;
for (let i = 0; i < 50; i++) { try { targets = await (await fetch('http://127.0.0.1:9336/json/list')).json(); if (targets.some(t => t.type === 'page')) break; } catch {} await sleep(200); }
const ws = new WebSocket(targets.find(t => t.type === 'page').webSocketDebuggerUrl);
await new Promise(r => ws.addEventListener('open', r));
let seq = 0; const pending = new Map();
ws.addEventListener('message', ({ data }) => { const m = JSON.parse(data); if (m.id && pending.has(m.id)) { pending.get(m.id)(m); pending.delete(m.id); } });
const send = (method, params = {}) => new Promise(res => { const id = ++seq; pending.set(id, m => res(m.result)); ws.send(JSON.stringify({ id, method, params })); });
const ev = async e => (await send('Runtime.evaluate', { expression: e, returnByValue: true, awaitPromise: true })).result.value;
const waitFor = async e => { for (let i = 0; i < 200 && !(await ev(e)); i++) await sleep(150); };
const goto = async p => { await send('Page.navigate', { url: base + p }); await sleep(300); await waitFor(`document.readyState === 'complete'`); };

await send('Page.enable'); await send('Runtime.enable');
await send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
await goto('/acceso');
await ev(`document.querySelector('input[name="_username"]').value = 'ana@example.test'`);
await ev(`document.querySelector('input[name="_password"]').value = ${JSON.stringify(seed.users['ana@example.test'])}`);
await ev(`document.querySelector('input[name="_password"]').form.requestSubmit()`);
await waitFor(`!location.pathname.startsWith('/acceso')`);
await goto('/admin/turnos');
// Para cada celda: posición izquierda de sus elementos respecto a la celda. La columna de etiquetas mide 7rem (112 px).
const report = await ev(`(() => {
  const cells = [...document.querySelectorAll('#shifts tbody td')];
  const misplaced = [];
  for (const td of cells) {
    const left = td.getBoundingClientRect().left;
    for (const child of td.querySelectorAll(':scope > *')) {
      const offset = Math.round(child.getBoundingClientRect().left - left);
      if (offset < 112) misplaced.push({ label: td.dataset.label, text: child.innerText.trim(), offset });
    }
  }
  return { innerWidth: window.innerWidth, scrollWidth: document.documentElement.scrollWidth, cells: cells.length, misplaced };
})()`);
const { cssContentSize } = await send('Page.getLayoutMetrics');
const { data } = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width: cssContentSize.width, height: Math.min(cssContentSize.height, 4000), scale: 1 } });
writeFileSync(`${outDir}/${shotName}.png`, Buffer.from(data, 'base64'));
writeFileSync(`${outDir}/${shotName}.json`, JSON.stringify(report, null, 2));
console.log(JSON.stringify(report));
ws.close(); chrome.kill();
process.exit(report.misplaced.length ? 1 : 0);
