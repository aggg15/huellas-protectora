// Graba los vídeos de la demo con Playwright contra la aplicación real.
//
//   PLAYWRIGHT_MODULE=/pw/node_modules/playwright/index.mjs BASE_URL=http://huellas-app-demo:8000 CTL_URL=http://huellas-app-demo:8001 \
//   OUT_DIR=/out node tools/demo/record.mjs [ids separados por comas]
//
// Salida: OUT_DIR/raw/<id>.webm y OUT_DIR/resultados/<id>.json (instantes de cada escenario y comprobaciones).
// Variables: SLOW_MO (ms, 450 por defecto), FAST=1 (depuración sin esperas de lectura).
import { mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { Grabador, OUT } from './grabador.mjs';
import { VIDEOS } from './guiones.mjs';

const solo = (process.argv[2] ?? process.env.ONLY ?? '').split(',').filter(Boolean);
mkdirSync(join(OUT, 'resultados'), { recursive: true });
let total = 0;
for (const def of VIDEOS) {
  if (solo.length && !solo.some((s) => def.id.startsWith(s))) continue;
  console.log(`\n=== ${def.id} · ${def.titulo}`);
  const g = await new Grabador(def).iniciar();
  try {
    await def.fn(g);
  } catch (error) {
    g.fallos++;
    console.log('  Error no controlado:', error.message);
  }
  const resultado = await g.finalizar();
  resultado.def = { id: def.id, spec: def.spec, titulo: def.titulo, rol: def.rol, historia: def.historia, escenarios: def.escenarios };
  writeFileSync(join(OUT, 'resultados', `${def.id}.json`), JSON.stringify(resultado, null, 2));
  const ok = resultado.checks.filter((c) => c.ok).length;
  console.log(`=== ${def.id}: ${ok}/${resultado.checks.length} comprobaciones correctas, ${resultado.duracionBruta}s`);
  total += resultado.fallos;
}
console.log(`\nFallos totales: ${total}`);
process.exit(0);
