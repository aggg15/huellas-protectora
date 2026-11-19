// Comprobación de T626 (converge C1): a 390 px ninguna tarjeta de «Mi acogida» supera el ancho de la ventana.
// Mide la posición real de las tarjetas, no solo el desplazamiento (la emulación móvil ensancha el viewport).
// Uso: node comprobacion-t626.mjs <dir-salida> <seed.json> <base-url> <chrome> <etiqueta>
import { spawn } from 'node:child_process';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';

const [outDir, seedPath, base, chromePath, label] = process.argv.slice(2);
const seed = JSON.parse(readFileSync(seedPath, 'utf8'));
mkdirSync(outDir, { recursive: true });
const chrome = spawn(chromePath, ['--headless=new', '--remote-debugging-port=9335', `--user-data-dir=${outDir}/profile-t626`, '--no-first-run', '--lang=es-ES', 'about:blank'], { stdio: 'ignore' });
const sleep = ms => new Promise(r => setTimeout(r, ms));
let targets;
for (let i = 0; i < 50; i++) { try { targets = await (await fetch('http://127.0.0.1:9335/json/list')).json(); if (targets.some(t => t.type === 'page')) break; } catch {} await sleep(200); }
const ws = new WebSocket(targets.find(t => t.type === 'page').webSocketDebuggerUrl);
await new Promise(r => ws.addEventListener('open', r));
let seq = 0; const pending = new Map();
ws.addEventListener('message', ({ data }) => { const m = JSON.parse(data); if (m.id && pending.has(m.id)) { pending.get(m.id)(m); pending.delete(m.id); } });
const send = (method, params = {}) => new Promise((res, rej) => { const id = ++seq; pending.set(id, m => m.error ? rej(new Error(m.error.message)) : res(m.result)); ws.send(JSON.stringify({ id, method, params })); });
const ev = async e => (await send('Runtime.evaluate', { expression: e, returnByValue: true, awaitPromise: true })).result.value;
const waitFor = async (e, l) => { for (const end = Date.now() + 60000; Date.now() < end; await sleep(150)) { if (await ev(e)) return; } throw new Error('Tiempo agotado: ' + l); };
const goto = async p => { await send('Page.navigate', { url: base + p }); await sleep(300); await waitFor(`document.readyState === 'complete'`, p); };
const shot = async name => { const { cssContentSize } = await send('Page.getLayoutMetrics'); const { data } = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width: cssContentSize.width, height: Math.min(cssContentSize.height, 4000), scale: 1 } }); writeFileSync(`${outDir}/${name}.png`, Buffer.from(data, 'base64')); };

const results = [];
try {
  await send('Page.enable'); await send('Runtime.enable');
  await goto('/acceso');
  await ev(`document.querySelector('input[name="_username"]').value = 'familia.encina@example.test'`);
  await ev(`document.querySelector('input[name="_password"]').value = ${JSON.stringify(seed.users['familia.encina@example.test'])}`);
  await ev(`document.querySelector('input[name="_password"]').form.requestSubmit()`);
  await waitFor(`location.pathname === '/acogida'`, 'acceso');
  for (const [width, mobile] of [[390, true], [1280, false]]) {
    await send('Emulation.setDeviceMetricsOverride', { width, height: 900, deviceScaleFactor: 1, mobile });
    await goto('/acogida');
    const m = await ev(`(() => { const boxes = [...document.querySelectorAll('main .box')]; return { boxes: boxes.length, maxRight: Math.max(...boxes.map(b => Math.round(b.getBoundingClientRect().right))), contentWidth: document.documentElement.scrollWidth, innerWidth: window.innerWidth }; })()`);
    const ok = m.boxes > 0 && m.maxRight <= width && m.contentWidth <= width;
    results.push({ width, ...m, expected: `borde derecho de las tarjetas ≤ ${width} px y ancho del contenido ≤ ${width} px`, result: ok ? 'OK' : 'FALLO' });
    console.log((ok ? '✔ ' : '✘ ') + `${width} px: ${m.boxes} tarjetas, borde derecho máximo ${m.maxRight} px, contenido ${m.contentWidth} px, innerWidth ${m.innerWidth}`);
    await shot(`t626-${label}-${width}`);
  }
} finally {
  writeFileSync(`${outDir}/resultado-t626-${label}.json`, JSON.stringify({ base, label, date: new Date().toISOString(), results }, null, 2));
  ws.close(); chrome.kill();
  process.exit(results.length === 2 && results.every(r => r.result === 'OK') ? 0 : 1);
}
