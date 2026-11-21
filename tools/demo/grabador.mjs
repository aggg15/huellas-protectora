// Núcleo de la grabación de la demo: abre el navegador con vídeo, pinta el rótulo del escenario,
// registra el instante de cada escenario y guarda las comprobaciones (esperado / observado).
// No sabe nada de las historias: los guiones están en guiones.mjs.
import { mkdirSync, renameSync } from 'node:fs';
import { join } from 'node:path';

const pw = await import(process.env.PLAYWRIGHT_MODULE ?? 'playwright');
const { chromium } = pw.default ?? pw;

export const BASE = process.env.BASE_URL ?? 'http://localhost:8000';
export const CTL = process.env.CTL_URL ?? 'http://localhost:8001';
export const OUT = process.env.OUT_DIR ?? './out';
export const PASSWORD = 'Huellas-Demo-2026';
const SLOW_MO = Number(process.env.SLOW_MO ?? 450);
/** FAST=1 acorta las esperas de lectura para depurar los guiones sin grabar a ritmo real. */
const READ_FACTOR = process.env.FAST ? 0.15 : Number(process.env.READ_FACTOR ?? 1);
const TYPE_DELAY = process.env.FAST ? 0 : Number(process.env.TYPE_DELAY ?? 28);
const VIEWPORT = { width: 1280, height: 720 };

// --- Fechas en la zona horaria de Madrid, como las ve la aplicación ---------------------------------------------
const madridFmt = new Intl.DateTimeFormat('en-CA', {
  timeZone: 'Europe/Madrid', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
});
export function madrid(offsetMinutes = 0) {
  const p = Object.fromEntries(madridFmt.formatToParts(new Date(Date.now() + offsetMinutes * 60000)).map((x) => [x.type, x.value]));
  return { date: `${p.year}-${p.month}-${p.day}`, time: `${p.hour}:${p.minute}`, local: `${p.year}-${p.month}-${p.day}T${p.hour}:${p.minute}`, d: Number(p.day), m: Number(p.month), y: Number(p.year) };
}
export const diaMadrid = (offsetDays) => madrid(offsetDays * 1440).date;
export const ddmmyyyy = (iso) => iso.split('-').reverse().join('/');
/** Fecha (YYYY-MM-DD) de hoy + n meses naturales, con tope en el último día del mes (D16 de HU-05). */
export function mesesDespues(n) {
  const h = madrid();
  const idx = h.y * 12 + (h.m - 1) + n;
  const y = Math.floor(idx / 12), m = (idx % 12) + 1;
  const ultimo = new Date(Date.UTC(y, m, 0)).getUTCDate();
  return `${y}-${String(m).padStart(2, '0')}-${String(Math.min(h.d, ultimo)).padStart(2, '0')}`;
}

// --- Rótulo y cursor inyectados en cada página --------------------------------------------------------------------
function initOverlay() {
  const css = `
  #demo-root{all:initial}
  #demo-root *{box-sizing:border-box}
  body{padding-bottom:130px !important}
  #demo-bar{position:fixed;left:16px;right:16px;bottom:14px;z-index:2147483646;display:none;gap:16px;align-items:center;
    background:rgba(18,22,40,.94);color:#fff;border-left:7px solid #f2b84b;border-radius:12px;padding:10px 18px;
    font:600 21px/1.25 Inter,system-ui,-apple-system,"Segoe UI",sans-serif;box-shadow:0 6px 24px rgba(0,0,0,.35);pointer-events:none}
  #demo-badge{flex:none;background:#f2b84b;color:#151a2e;border-radius:8px;padding:4px 10px;font:800 15px/1.2 system-ui,sans-serif;letter-spacing:.04em}
  #demo-text{flex:1;min-width:0}
  #demo-title{display:block}
  #demo-note{display:block;font:400 15.5px/1.35 Inter,system-ui,sans-serif;color:#cfd4f2;margin-top:2px}
  #demo-url{flex:none;max-width:34%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font:500 14px/1.2 ui-monospace,Consolas,monospace;
    background:rgba(255,255,255,.12);border-radius:999px;padding:5px 12px;color:#dfe3ff}
  #demo-cursor{position:fixed;left:0;top:0;width:22px;height:22px;margin:-11px 0 0 -11px;border-radius:50%;z-index:2147483647;pointer-events:none;
    background:rgba(242,184,75,.55);border:2px solid #f2b84b;transition:transform .05s linear;display:none}
  #demo-cursor.click{animation:demo-click .45s ease-out}
  @keyframes demo-click{0%{box-shadow:0 0 0 0 rgba(242,184,75,.8)}100%{box-shadow:0 0 0 26px rgba(242,184,75,0)}}`;
  const build = () => {
    if (!document.documentElement) return;
    let root = document.getElementById('demo-root');
    if (!root) {
      root = document.createElement('div');
      root.id = 'demo-root';
      root.innerHTML = `<style>${css}</style><div id="demo-bar"><span id="demo-badge"></span><div id="demo-text"><span id="demo-title"></span><span id="demo-note"></span></div><span id="demo-url"></span></div><div id="demo-cursor"></div>`;
    }
    if (!root.isConnected) document.documentElement.appendChild(root);
    let c = null;
    try { c = JSON.parse(sessionStorage.getItem('demoCaption') || 'null'); } catch (e) { /* about:blank */ }
    const bar = root.querySelector('#demo-bar');
    if (c) {
      bar.style.display = 'flex';
      root.querySelector('#demo-badge').textContent = c.badge;
      root.querySelector('#demo-title').textContent = c.title;
      const note = root.querySelector('#demo-note');
      note.textContent = c.note || '';
      note.style.display = c.note ? 'block' : 'none';
      root.querySelector('#demo-url').textContent = location.pathname + location.search;
    } else bar.style.display = 'none';
  };
  window.__demoBuild = build;
  document.addEventListener('DOMContentLoaded', build);
  setInterval(build, 250);
  window.addEventListener('mousemove', (e) => {
    const cur = document.getElementById('demo-cursor');
    if (!cur) return;
    cur.style.display = 'block';
    cur.style.left = e.clientX + 'px';
    cur.style.top = e.clientY + 'px';
  }, true);
  window.addEventListener('mousedown', () => {
    const cur = document.getElementById('demo-cursor');
    if (!cur) return;
    cur.classList.remove('click');
    void cur.offsetWidth;
    cur.classList.add('click');
  }, true);
}

const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

/** Tarjeta de presentación de la historia: título, relato y lista de escenarios que van apareciendo. */
function tarjeta(def) {
  const items = def.escenarios.map((e, i) => `<li style="animation-delay:${(1.6 + i * 0.32).toFixed(2)}s"><b>${i + 1}</b><span>${esc(e.titulo)}</span></li>`).join('');
  return `<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
    html,body{margin:0;height:100%}
    body{background:linear-gradient(135deg,#171b30,#2a2144);color:#fff;font:400 20px/1.5 Inter,system-ui,-apple-system,"Segoe UI",sans-serif;display:flex;align-items:center}
    main{padding:0 110px;width:100%}
    .k{color:#f2b84b;font:800 15px/1 system-ui,sans-serif;letter-spacing:.12em;text-transform:uppercase}
    h1{font:700 46px/1.15 Georgia,"Times New Roman",serif;margin:12px 0 14px;max-width:1000px}
    p{margin:0 0 20px;max-width:930px;color:#e9e9f7;font-size:23px;line-height:1.45}
    p b{color:#fff}
    ol{list-style:none;margin:0;padding:0;columns:2;column-gap:46px;max-width:1060px}
    li{display:grid;grid-template-columns:30px 1fr;gap:6px;margin:0 0 6px;font-size:17.5px;color:#d4d7ee;opacity:0;animation:in .5s ease forwards;break-inside:avoid}
    li b{color:#f2b84b}
    @keyframes in{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
  </style></head><body><main>
    <div class="k">${esc(def.spec)} · ${esc(def.rol)}</div>
    <h1>${esc(def.titulo)}</h1>
    <p>${def.historia}</p>
    <ol>${items}</ol>
  </main></body></html>`;
}

const snip = (text, needle) => {
  const i = text.toLowerCase().indexOf(String(needle).toLowerCase());
  if (i < 0) return text.slice(0, 170) + (text.length > 170 ? '…' : '');
  return (i > 70 ? '…' : '') + text.slice(Math.max(0, i - 70), i + String(needle).length + 90) + '…';
};

export class Grabador {
  constructor(def) {
    this.def = def;
    this.checks = [];
    this.escenarios = [];
    this.responses = [];
    this.currentN = 0;
    this.fallos = 0;
  }

  async iniciar() {
    const resp = await fetch(`${CTL}/reset${this.def.releaseIn ? `?release_in=${this.def.releaseIn}` : ''}`);
    if (!resp.ok) throw new Error('No se pudo cargar la base de demo: ' + (await resp.text()));
    this.d = await resp.json();
    this.browser = await chromium.launch({ slowMo: SLOW_MO, args: ['--force-device-scale-factor=1', '--hide-scrollbars'] });
    mkdirSync(join(OUT, 'raw'), { recursive: true });
    this.rawDir = join(OUT, 'raw', `tmp-${this.def.id}`);
    this.context = await this.browser.newContext({
      viewport: VIEWPORT, locale: 'es-ES', timezoneId: 'Europe/Madrid', deviceScaleFactor: 1,
      recordVideo: { dir: this.rawDir, size: VIEWPORT },
    });
    await this.context.addInitScript(initOverlay);
    this.t0 = Date.now();
    this.page = await this.context.newPage();
    this.page.setDefaultTimeout(9000);
    this.page.on('response', (r) => {
      const t = r.request().resourceType();
      if ((t === 'document' || t === 'fetch') && r.url().startsWith(BASE)) {
        this.responses.push({ method: r.request().method(), url: r.url(), status: r.status() });
      }
    });
    this.page.on('dialog', (dlg) => dlg.dismiss());
    // Seguimiento de las peticiones en curso, para saber cuándo la página ha terminado de reaccionar.
    this.inflight = 0;
    this.lastNet = Date.now();
    const mueve = (delta) => () => { this.inflight = Math.max(0, this.inflight + delta); this.lastNet = Date.now(); };
    this.page.on('request', mueve(1));
    this.page.on('requestfinished', mueve(-1));
    this.page.on('requestfailed', mueve(-1));
    // Tarjeta de presentación (no es un escenario; el índice la trata como «Presentación de la historia»).
    await this.page.setContent(tarjeta(this.def));
    this.tTarjeta = this.now();
    await this.leer(Math.max(6200, 2200 + this.def.escenarios.length * 330 + 2200));
    // A partir de aquí la página está en el origen de la aplicación y el rótulo puede guardarse en sessionStorage.
    await this.page.goto(BASE + '/', { waitUntil: 'load' });
    return this;
  }

  /** Instante actual dentro del vídeo, en segundos. */
  now() { return (Date.now() - this.t0) / 1000; }

  async leer(ms = 2600) { await this.page.waitForTimeout(ms * READ_FACTOR); }

  async rotulo(badge, title, note) {
    this.caption = { badge, title, note: note ?? '' };
    await this.page.evaluate((c) => {
      try { sessionStorage.setItem('demoCaption', JSON.stringify(c)); } catch (e) { /* sin sessionStorage */ }
      if (window.__demoBuild) window.__demoBuild();
    }, this.caption).catch(() => {});
  }

  /** Marca el inicio del escenario n (1..N) y muestra su rótulo. `note` explica lo que se va a ver. */
  async esc(n, note) {
    const e = this.def.escenarios[n - 1];
    this.currentN = n;
    this.escenarios.push({ n, titulo: e.titulo, ca: e.ca ?? [], t: Number(Math.max(0, this.now() - 0.1).toFixed(2)) });
    console.log(`  [${n}/${this.def.escenarios.length}] ${e.titulo}`);
    await this.rotulo(`${n}/${this.def.escenarios.length}`, e.titulo, note);
    await this.leer(1500);
  }

  async nota(texto, pausa = 0) {
    await this.rotulo(`${this.currentN}/${this.def.escenarios.length}`, this.def.escenarios[this.currentN - 1]?.titulo ?? '', texto);
    if (pausa) await this.leer(pausa);
  }

  /** Ejecuta un escenario; si falla por una excepción inesperada lo anota como fallo y sigue con el siguiente. */
  async escenario(n, nota, fn) {
    try {
      await this.esc(n, nota);
      await fn();
    } catch (error) {
      this.fallos++;
      const msg = String(error.message ?? error).split('\n')[0];
      this.checks.push({ escenario: n, id: `E${n}`, descripcion: `Escenario ${n} sin errores de ejecución`, esperado: 'El guion llega al final del escenario', observado: `Excepción: ${msg}`, ok: false, t: this.now() });
      console.log(`    FALLO de ejecución en el escenario ${n}: ${msg}`);
      if (process.env.DEBUG_SHOTS) await this.page.screenshot({ path: join(OUT, `debug-${this.def.id}-${n}.png`) }).catch(() => {});
    }
  }

  // --- Navegación y acciones --------------------------------------------------------------------------------------
  /** Espera a que no haya peticiones en curso ni actividad de red durante un instante, y deja un respiro para que se vea el resultado. */
  async asentar(ms = 500) {
    await this.page.waitForLoadState('load').catch(() => {});
    await this.page.waitForTimeout(350);
    const limite = Date.now() + 9000;
    while ((this.inflight > 0 || Date.now() - this.lastNet < 450) && Date.now() < limite) await this.page.waitForTimeout(80);
    await this.page.waitForTimeout(ms * READ_FACTOR + 60);
  }

  /** Abre una ruta y devuelve su código HTTP. */
  async go(path) {
    const r = await this.page.goto(BASE + path, { waitUntil: 'load' });
    await this.asentar();
    return r ? r.status() : 0;
  }

  /** Código de la última respuesta HTML recibida (tras un envío de formulario, la del resultado). */
  ultimoEstado() { return this.responses.at(-1)?.status ?? 0; }

  async reveal(loc) {
    await loc.first().evaluate((el) => el.scrollIntoView({ block: 'center', behavior: 'smooth' })).catch(() => {});
    await this.page.waitForTimeout(650 * READ_FACTOR + 60);
  }

  async moverA(loc) {
    const box = await loc.first().boundingBox();
    if (box) await this.page.mouse.move(box.x + box.width / 2, box.y + box.height / 2, { steps: process.env.FAST ? 2 : 14 });
  }

  /** Desplaza hasta el elemento, mueve el cursor y pulsa. */
  async click(loc, { asentar = true } = {}) {
    await this.reveal(loc);
    await this.moverA(loc);
    await loc.first().click();
    if (asentar) await this.asentar();
  }

  /** Escribe como una persona: foco, borra y teclea. */
  async type(loc, text, delay = TYPE_DELAY) {
    await this.reveal(loc);
    await this.moverA(loc);
    await loc.first().click();
    await loc.first().fill('');
    if (delay) await loc.first().pressSequentially(text, { delay });
    else await loc.first().fill(text);
  }

  async fill(loc, value) {
    await this.reveal(loc);
    await this.moverA(loc);
    await loc.first().fill(value);
  }

  async select(loc, option) {
    await this.reveal(loc);
    await this.moverA(loc);
    await loc.first().selectOption(option);
  }

  /** Desactiva la validación del navegador en el formulario para que el servidor sea quien valide. */
  async sinValidacionNavegador(selector) {
    await this.page.evaluate((s) => { const f = document.querySelector(s); if (f) f.noValidate = true; }, selector);
  }

  async login(key) {
    await this.context.clearCookies();
    const s = await this.go('/acceso');
    await this.type(this.page.locator('#email'), `${key}@example.org`);
    await this.type(this.page.locator('#password'), PASSWORD);
    await this.click(this.page.getByRole('button', { name: 'Acceder', exact: true }));
    return s;
  }

  async logout() {
    await this.click(this.page.getByRole('button', { name: 'Salir' }));
  }

  nav(nombre) { return this.page.locator('header nav').getByRole('link', { name: nombre, exact: true }); }

  texto(loc) { return loc.first().innerText().then((s) => s.replace(/\s+/g, ' ').trim()); }
  async cuerpo() { return (await this.page.locator('main').innerText()).replace(/\s+/g, ' ').trim(); }
  async ruta() { const u = new URL(this.page.url()); return u.pathname + u.search; }

  // --- Comprobaciones ---------------------------------------------------------------------------------------------
  /** Anota una comprobación. `fn` devuelve { ok, observado } con lo que de verdad ocurrió. */
  async check(id, descripcion, esperado, fn) {
    let ok = false, observado = '';
    try {
      const r = await fn();
      ok = !!r.ok;
      observado = r.observado ?? '';
    } catch (error) {
      observado = 'Excepción: ' + String(error.message ?? error).split('\n')[0];
    }
    if (!ok) this.fallos++;
    this.checks.push({ escenario: this.currentN, id, descripcion, esperado, observado, ok, t: Number(this.now().toFixed(1)) });
    console.log(`    ${ok ? 'OK   ' : 'FALLO'} ${id} ${descripcion} → ${observado.slice(0, 150)}`);
    return ok;
  }

  /** La página (o `loc`) contiene el texto. */
  async veTexto(id, descripcion, needle, loc = null) {
    return this.check(id, descripcion, `Aparece «${needle}»`, async () => {
      const t = loc ? await this.texto(loc) : await this.cuerpo();
      return { ok: t.toLowerCase().includes(String(needle).toLowerCase()), observado: snip(t, needle) };
    });
  }

  async noVeTexto(id, descripcion, needle, loc = null) {
    return this.check(id, descripcion, `No aparece «${needle}»`, async () => {
      const t = loc ? await this.texto(loc) : await this.cuerpo();
      const hay = t.toLowerCase().includes(String(needle).toLowerCase());
      return { ok: !hay, observado: hay ? `Aparece: ${snip(t, needle)}` : `No aparece «${needle}» en la página` };
    });
  }

  async veEstado(id, descripcion, esperado, real) {
    return this.check(id, descripcion, `HTTP ${esperado}`, async () => ({ ok: real === esperado, observado: `HTTP ${real}` }));
  }

  async veRuta(id, descripcion, fragmento) {
    return this.check(id, descripcion, `La ruta contiene «${fragmento}»`, async () => {
      const r = await this.ruta();
      return { ok: r.includes(fragmento), observado: `Ruta ${r}` };
    });
  }

  async veCantidad(id, descripcion, loc, esperado) {
    return this.check(id, descripcion, `${esperado} elementos`, async () => {
      const n = await loc.count();
      return { ok: n === esperado, observado: `${n} elementos` };
    });
  }

  async finalizar() {
    await this.leer(2200);
    const video = this.page.video();
    const tCierre = this.now();
    await this.context.close();
    const tmp = await video.path();
    const destino = join(OUT, 'raw', `${this.def.id}.webm`);
    renameSync(tmp, destino);
    await this.browser.close();
    return {
      id: this.def.id, duracionBruta: Number(tCierre.toFixed(2)), tTarjeta: Number(this.tTarjeta.toFixed(2)), fallos: this.fallos,
      escenarios: this.escenarios, checks: this.checks, generado: new Date().toISOString(), datos: { password: PASSWORD },
    };
  }
}
