# Converge · spec-008

Contraste del código de la rama `spec-008` (implementación de T805–T819) con spec.md, plan.md, tasks.md y la
Constitution, realizado por Claude Code el 2026-11-19, después de implement y de la verificación manual (T818).
Se sigue el método de `.agents/skills/speckit-converge/SKILL.md`: inventario de obligaciones, clasificación
missing / partial / contradicts / unrequested y tareas añadidas al final de tasks.md sin modificar las anteriores,
la spec ni el plan. La skill `$speckit-converge` no se ha invocado como comando. No hay hooks.

Las tareas marcadas no se aceptan como prueba: cada obligación se ha comprobado en el código y con las pruebas
([evidencia automática](../../docs/evidencias/spec-008-phpunit.txt)) o con el recorrido en Chrome
([evidencia manual](../../docs/evidencias/spec-008-manual/README.md)).

## Obligaciones contrastadas

| Origen | Estado | Comprobación |
|---|---|---|
| CA-08-01 alta | Cumplido | `testSponsorRegistersMonthlyContributionForAnimal`; R1 con teclado (captura 02) |
| CA-08-02 mínimo | Cumplido | `testContributionBelowTenEurosIsRejected`; R1 (422, captura 01) |
| CA-08-03 novedades | Cumplido | Dos pruebas (incluida la nota de acogida oculta); R1 (captura 03) |
| CA-08-04 pendiente | Cumplido | `testAdoptedAnimalOffersSponsorToContinueOrFinish` con la formalización real de HU-05; R1 (captura 04) |
| CA-08-05 elección | Cumplido | `testSponsorChoiceIsRecordedWithoutUnilateralAssignment`; R1 (captura 05) |
| CA-08-06 cardinalidad | Cumplido | `testSponsorshipCardinalityAndVoluntaryEnd` |
| CA-08-07 disponibles | Cumplido | `testOnlyAvailableAnimalsCanBeSponsored`; R2 (404) |
| CA-08-08 elecciones no válidas | Cumplido | `testInvalidContinuityChoicesAreRejected` |
| CA-08-09 concurrencia | Cumplido | Dos pruebas de carrera; fallan con las mutaciones 1 y 2 |
| CA-08-10 permisos | **Parcial** | Código correcto (`own()` en terminar y cambiar), pero la prueba solo comprueba el 404 de terminar un apadrinamiento ajeno: C1 |
| R1–R10 | Cumplido | Cubiertas por los criterios anteriores |
| Plan: modelo, migración, único parcial en el mapeo | Cumplido | `doctrine:schema:validate` OK; migrate up/down/up (tras renombrar la migración, véase implement) |
| Plan: orden de bloqueo y relectura | Cumplido | `sponsor` (cuenta → animal), `finish` y `moveTo` (cuenta → apadrinamiento → animal), `addUpdate` (animal) |
| Plan: consultas y rutas | Cumplido | `mine`, `updatesFor`, `availableFor`, `overview` y las seis rutas registradas |
| Constitution V: móvil, escritorio y teclado | Cumplido | 390 px sin desbordar en tres páginas; recorridos con teclado |
| Constitution V: éxito, error y ausencia de resultados | **Parcial** | Éxito y error comprobados; los estados vacíos de «Mis apadrinamientos» y de la página de la coordinadora existen en las vistas, pero ninguna prueba ni el recorrido los comprueba: C2 |
| Constitution II/III: trazabilidad y base común | Cumplido | `data-model.md`, plan común, decisiones y tareas coherentes con el código; HU-05 y HU-06 sin cambios |
| Constitution IV: archivos comunes | Cumplido | Solo `base.html.twig`, `public/show.html.twig` y `app.css`; Spec002 (ficha pública) sigue 8/8 |

## Hallazgos

| ID | Tipo | Gravedad | Origen | Evidencia | Trabajo pendiente |
|---|---|---|---|---|---|
| C1 | partial | LOW | spec.md CA-08-10 («no puede terminar ni cambiar el apadrinamiento de otra (404)») | `testOnlyPublicAccountsSponsorAndOnlyCoordinatorPublishes` solo envía `/terminar` con una cuenta ajena | Comprobar también el 404 de `/cambiar` con una cuenta ajena |
| C2 | partial | LOW | Constitution V («estados de … ausencia de resultados»); plan «Vistas» | Los bloques `.empty-state` de `sponsorship/mine` y `sponsorship/admin_index` no se ejecutan en ninguna prueba ni en el recorrido | Probar ambos estados vacíos, incluido que un apadrinamiento terminado deja de figurar para la coordinadora |

Revisado y descartado: los textos «fue adoptado» y «ha sido adoptado» concuerdan con «el animal» (masculino
gramatical), como «¿Quieres adoptarlo?» de la ficha pública de HU-02; no presuponen el sexo del animal y no se
registran como hallazgo.

Métricas: 10 criterios y 10 reglas contrastados; 4 decisiones del plan; 4 principios de la Constitution con
obligaciones comprobables (II, III, IV, V). Hallazgos: 0 missing, 2 partial, 0 contradicts, 0 unrequested.
Por gravedad: 0 críticos, 0 altos, 0 medios y 2 bajos.

Resultado: se añaden T822–T823 en «Phase 9: Convergence» de [tasks.md](tasks.md). Se ejecutarán con implement y
se repetirá converge sobre el resultado.

## Segunda pasada · 2026-11-19, tras implementar T822–T823

Repetida sobre el código con T822–T823, con el mismo método y el mismo inventario. Las dos tareas se han comprobado
ejecutando las pruebas, no por estar marcadas.

| Hallazgo | Estado | Comprobación |
|---|---|---|
| C1 · CA-08-10 | Resuelto | La prueba de permisos envía `/cambiar` con una cuenta ajena: 404 y sin cambios |
| C2 · Constitution V | Resuelto | `testEmptyStatesAreExplained`: «Todavía no apadrinas a ningún animal» con enlace al catálogo y «No hay apadrinamientos activos», sin el animal cuyo apadrinamiento terminó |

Efectos colaterales revisados: T822–T823 solo añaden pruebas; el código no cambia. Suite completa repetida en una base
`_test` nueva: 112/112 y 1301 aserciones, esquema, migraciones down/up y linters correctos
([evidencia](../../docs/evidencias/spec-008-phpunit.txt)).

Hallazgos nuevos: ninguno (0 missing, 0 partial, 0 contradicts, 0 unrequested). Según el método, esta pasada no
añade tareas ni fase a tasks.md. Resultado: **convergido**. Queda pendiente la revisión de `aggg15` (T821).

## Pasada de revisión previa a la entrega (2026-11-21)

Origen: revisión de código previa a la entrega. Ejecutada por `aggg15` asistido por Claude Code; revisión pendiente.

| Hallazgo | Gravedad | Corrección | Prueba | Resultado antes → después |
|---|---|---|---|---|
| C2-01 · POST de apadrinar sin `amount` daba 500 con `twig.strict_variables` | MEDIUM | `SponsorshipController::create` completa `amount` con '' | `Spec008Test::testSponsorFormWithoutAmountFieldIsRejectedWith422` | Antes: error 500. Después: pasa (422) |
| C2-03 · el bloqueo `FOR UPDATE` de la cuenta podía interbloquearse con la clave foránea de `Adoption` | LOW | `FOR NO KEY UPDATE` en `SponsorshipService` (alta y `lockActive`) y comentario de la clase corregido | Pruebas de concurrencia existentes de `Spec008Test` | Pasan antes y después; no hay prueba nueva del interbloqueo |
| C2-04 · terminar un apadrinamiento ya terminado daba 409 sin mensaje | LOW | El include de errores pasa al nivel de la tarjeta en `mine.html.twig` | `Spec008Test::testFinishingAnEndedSponsorshipShowsTheRefusal` | Antes: falla. Después: pasa |
| C2-05 · un animal con la cuarentena recién cumplida no aparecía como destino | LOW | `mine()` y `move()` llaman a `AnimalService::releaseDueAnimals()` | `Spec008Test::testAnimalWhoseQuarantineJustEndedIsOfferedAsTarget` | Antes: falla (lista vacía). Después: pasa |
