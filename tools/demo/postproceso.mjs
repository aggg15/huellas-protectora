// Convierte los .webm grabados a mp4 H.264, genera los pósters y demo-completa.mp4 con ffmpeg en Docker, y escribe datos.js e index.html.
//   node tools/demo/postproceso.mjs <dir-grabacion (con raw/ y resultados/)> <dir-salida (demo/)>
// Variables: CRF (30 por defecto), FFMPEG_IMAGE (jrottenberg/ffmpeg:6.1-ubuntu).
import { spawnSync } from 'node:child_process';
import { copyFileSync, mkdirSync, readFileSync, readdirSync, writeFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const [rec, salida] = process.argv.slice(2).map((p) => resolve(p));
const IMAGE = process.env.FFMPEG_IMAGE ?? 'jrottenberg/ffmpeg:6.1-ubuntu';
const CRF = process.env.CRF ?? '30';
const aqui = dirname(fileURLToPath(import.meta.url));
const video = join(salida, 'video');
mkdirSync(video, { recursive: true });

const docker = (tool, args) => {
  const r = spawnSync('docker', ['run', '--rm', '--entrypoint', tool, '-v', `${join(rec, 'raw')}:/raw:ro`, '-v', `${video}:/video`, IMAGE, ...args], { encoding: 'utf8', maxBuffer: 64 * 1024 * 1024, env: { ...process.env, MSYS_NO_PATHCONV: '1' } });
  if (r.status !== 0) throw new Error(`${tool} ${args.join(' ')}\n${r.stderr}`);
  return r.stdout;
};
const duracion = (f) => Number(docker('ffprobe', ['-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', `/video/${f}`]).trim());

/** Instante (s) del primer fotograma oscuro del .webm: la tarjeta de presentación sustituye a la página en blanco inicial. */
const primerOscuro = (id) => {
  const out = docker('ffmpeg', ['-v', 'error', '-t', '25', '-i', `/raw/${id}.webm`, '-vf', 'fps=10,signalstats,metadata=print:key=lavfi.signalstats.YAVG:file=-', '-f', 'null', '-']);
  let t = null;
  for (const linea of out.split('\n')) {
    const m = /pts_time:([0-9.]+)/.exec(linea);
    if (m) t = Number(m[1]);
    const y = /YAVG=([0-9.]+)/.exec(linea);
    if (y && t !== null && Number(y[1]) < 120) return t;
  }
  return 0;
};

const resultados = readdirSync(join(rec, 'resultados')).filter((f) => f.endsWith('.json')).sort().map((f) => JSON.parse(readFileSync(join(rec, 'resultados', f), 'utf8')));
const historias = [];
let inicio = 0;
for (const r of resultados) {
  const id = r.id;
  console.log('Convirtiendo', id);
  // El vídeo empieza antes que el guion (página en blanco mientras se abre el navegador): se recorta para que t = 0 sea la tarjeta.
  const recorte = Math.max(0, primerOscuro(id) - (r.tTarjeta ?? 0.3));
  console.log(`  recorte inicial ${recorte.toFixed(2)} s`);
  docker('ffmpeg', ['-y', '-ss', recorte.toFixed(2), '-i', `/raw/${id}.webm`, '-vf', 'fps=25,scale=1280:720,format=yuv420p', '-c:v', 'libx264', '-preset', 'medium', '-crf', CRF, '-an', '-movflags', '+faststart', `/video/${id}.mp4`]);
  const dur = duracion(`${id}.mp4`);
  // El vídeo de Playwright avanza algo más despacio que el reloj del guion: se escala el instante de cada escenario.
  const k = dur / r.duracionBruta;
  console.log(`  factor de escala ${k.toFixed(3)}`);
  const t0 = Math.max(0.5, (r.escenarios[0]?.t ?? 5) * k - 0.5);
  docker('ffmpeg', ['-y', '-ss', String(t0), '-i', `/video/${id}.mp4`, '-frames:v', '1', '-q:v', '4', `/video/${id}.jpg`]);
  historias.push({
    id, spec: r.def.spec, titulo: r.def.titulo, rol: r.def.rol, historia: r.def.historia,
    escenarios: r.escenarios.map((e) => ({ titulo: e.titulo, t: Number((e.t * k).toFixed(2)), ca: e.ca })),
    previstos: r.def.escenarios.map((e) => e.titulo),
    duracion: Number(dur.toFixed(2)), inicio: Number(inicio.toFixed(2)), video: `video/${id}.mp4`, poster: `video/${id}.jpg`,
  });
  inicio += dur;
}
writeFileSync(join(video, 'lista.txt'), historias.map((h) => `file '/video/${h.id}.mp4'`).join('\n') + '\n');
console.log('Concatenando demo-completa.mp4');
docker('ffmpeg', ['-y', '-f', 'concat', '-safe', '0', '-i', '/video/lista.txt', '-c', 'copy', '-movflags', '+faststart', '/video/demo-completa.mp4']);
const total = duracion('demo-completa.mp4');
writeFileSync(join(salida, 'datos.js'), `window.DEMO = ${JSON.stringify({ completa: 'video/demo-completa.mp4', duracion: total, historias, generado: new Date().toISOString() }, null, 2)};\n`);
copyFileSync(join(aqui, 'site', 'index.html'), join(salida, 'index.html'));
console.log(`Hecho: ${historias.length} vídeos, ${total.toFixed(1)} s en la demo completa.`);
