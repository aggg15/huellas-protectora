// Genera docs/evidencias/verificacion-integrada.md a partir de los resultados de las grabaciones 09 y 10.
//   node tools/demo/informe.mjs <dir-grabacion con resultados/> [salida.md]
// El «resultado observado» sale tal cual de las aserciones de Playwright ejecutadas en la grabación; no se redacta a mano.
import { execSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';
import { join, resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const aqui = dirname(fileURLToPath(import.meta.url));
const raiz = resolve(aqui, '..', '..');
const rec = resolve(process.argv[2]);
const salida = resolve(process.argv[3] ?? join(raiz, 'docs', 'evidencias', 'verificacion-integrada.md'));
const git = (c) => execSync(`git ${c}`, { cwd: raiz, encoding: 'utf8' }).trim();
const sha = git('rev-parse HEAD');
const rama = git('rev-parse --abbrev-ref HEAD');
const sucio = git('status --short -- src templates config migrations').split('\n').filter(Boolean);
const cel = (s) => String(s).replace(/\|/g, '\\|').replace(/\n/g, ' ');
const leer = (id) => JSON.parse(readFileSync(join(rec, 'resultados', `${id}.json`), 'utf8'));

const recorridos = [
  {
    id: '09-flujo-satisfactorio', nombre: 'R1 · Recorrido satisfactorio', video: 'video/09-flujo-satisfactorio.mp4',
    historias: 'HU-01 (aggg15) → HU-02 (abcb5-ua) → HU-08 (abcb5-ua) → HU-03 (aggg15) → HU-04 (abcb5-ua) → HU-05 (aggg15) → HU-08 (abcb5-ua)',
    pre: [
      'Base `huellas_demo` recién cargada con `tools/demo/seed.php --reset` (PostgreSQL 16, `APP_ENV=dev`, servidor integrado de PHP 8.3).',
      'Cuentas ficticias `@example.org` con la contraseña de demostración: voluntaria, veterinario, coordinadora, madrina Paula y animales de demostración (entre ellos `HUE-DISPON000001`, perro cachorro disponible).',
      'La cuenta de la solicitante (Rosa Registrada) no existe: se crea durante el recorrido desde la ficha pública (HU-03).',
    ],
    datos: [
      'Animal nuevo: gata, pequeña, 14 meses, recepción registrada 11 días antes (registro tardío, de modo que la cuarentena de diez días ya está cumplida), una fotografía generada.',
      'Revisión veterinaria: «Revisión: vacunas puestas, sin hallazgos».',
      'Apadrinamiento: 12,00 € al mes. Solicitud: piso de 70 m², experiencia con perros, 2 a 4 horas diarias. Entrevista el mismo día. Cuota de adopción: 90,00 €.',
    ],
    esperado: 'El animal se da de alta y queda disponible, se ve y se filtra en el catálogo público, se apadrina, recibe una solicitud, se aprueba (reservado, fuera del catálogo), se formaliza (contrato, dos seguimientos, adoptado, ficha pública 404), la adoptante ve su contrato y la madrina recibe la decisión pendiente y elige otro animal con la misma aportación.',
  },
  {
    id: '10-flujo-con-error', nombre: 'R2 · Recorrido con errores, estados y permisos', video: 'video/10-flujo-con-error.mp4',
    historias: 'HU-03 (aggg15) → HU-04 (abcb5-ua) → HU-02 (abcb5-ua) → HU-05 (aggg15) → HU-08 (abcb5-ua)',
    pre: [
      'Base `huellas_demo` recién cargada con `tools/demo/seed.php --reset`.',
      'Samuel Solicitante ya tiene dos solicitudes abiertas (gato y perro grande). Tres candidaturas pendientes (Silvia, Sergio y Sonia) para la gata `HUE-DISPON000004`.',
      'Cuentas de voluntaria, solicitante, coordinadora, madrina y solicitantes con la contraseña de demostración.',
    ],
    datos: [
      'Solicitud sobre el cachorro (tercera de Samuel) y sobre el gato (repetida).',
      'Aprobación de Silvia sin entrevista y con ella; aprobación de Sergio (en espera) sobre el animal ya reservado.',
      'Formalización de Silvia con cuota 75,50 €, primero sin confirmar el abono y después confirmándolo.',
    ],
    esperado: 'La tercera solicitud y la repetida se rechazan y se conservan las dos existentes; aprobar sin entrevista y aprobar sobre un animal ya reservado se rechazan (422 y 409); el animal reservado no está en el catálogo y su ficha y su solicitud dan 404; el voluntariado, las solicitantes y las personas sin sesión no acceden a la gestión (403 o acceso); formalizar sin confirmar el abono se rechaza; otra cuenta no abre el contrato (404); un animal adoptado no se apadrina (404) y el personal tampoco (403).',
  },
];

let md = `# Verificación integrada (enunciado, apartado 5.4)\n\n`;
md += `Recorridos completos que conectan historias de los dos integrantes (\`aggg15\`: HU-01, HU-03, HU-05, HU-07; \`abcb5-ua\`: HU-02, HU-04, HU-06, HU-08). `;
md += `Cada recorrido se ha ejecutado de verdad con Playwright 1.48 (Chromium) contra la aplicación en marcha y está grabado en vídeo; las aserciones de la columna «Resultado observado» son las del propio script \`tools/demo/guiones-d.mjs\`.\n\n`;
md += `- **Versión del código** (\`git rev-parse HEAD\`): \`${sha}\` (rama \`${rama}\`).\n`;
md += sucio.length
  ? `- **Cambios sin confirmar en el código de la aplicación al grabar** (\`git status --short -- src templates config migrations\`):\n${sucio.map((l) => `  - \`${l}\``).join('\n')}\n`
  : `- Sin cambios sin confirmar en \`src/\`, \`templates/\`, \`config/\` ni \`migrations/\` al grabar.\n`;
md += `- **Entorno**: contenedores Docker (\`huellas-ci-php\` con PHP 8.3, \`postgres:16-alpine\`, \`mcr.microsoft.com/playwright:v1.48.0-jammy\`), \`APP_ENV=dev\`, base propia \`huellas_demo\`. Cómo repetirlo: \`tools/demo/README.md\`.\n\n`;

for (const rc of recorridos) {
  const r = leer(rc.id);
  const ok = r.checks.filter((c) => c.ok).length;
  md += `## ${rc.nombre}\n\n`;
  md += `Vídeo: \`${rc.video}\` (duración ${Math.round(r.duracionBruta)} s). Historias cruzadas: ${rc.historias}.\n\n`;
  md += `### Precondiciones\n\n${rc.pre.map((x) => `- ${x}`).join('\n')}\n\n`;
  md += `### Datos de prueba\n\n${rc.datos.map((x) => `- ${x}`).join('\n')}\n\n`;
  md += `### Pasos\n\n${r.def.escenarios.map((e, i) => `${i + 1}. ${e.titulo}`).join('\n')}\n\n`;
  md += `### Resultado esperado\n\n${rc.esperado}\n\n`;
  md += `### Resultado observado\n\n`;
  md += `**${ok} de ${r.checks.length} comprobaciones correctas.**${ok === r.checks.length ? '' : ' Las marcadas «FALLO» son fallos reales de la ejecución, no se han retocado.'}\n\n`;
  md += `| Paso | Id | Comprobación | Esperado | Observado | Resultado |\n|---|---|---|---|---|---|\n`;
  for (const c of r.checks) {
    md += `| ${c.escenario} | ${c.id} | ${cel(c.descripcion)} | ${cel(c.esperado)} | ${cel(c.observado)} | ${c.ok ? 'OK' : '**FALLO**'} |\n`;
  }
  md += `\nEjecutado el ${r.generado}.\n\n`;
}
writeFileSync(salida, md);
console.log('Escrito', salida);
