# Converge · spec-006

Contraste del código de la rama `spec-006` (implementación de T605–T623) con spec.md, plan.md, tasks.md y la
Constitution, realizado por Claude Code el 2026-11-19, después de implement y de la verificación manual (T623).
Se sigue el método de `.agents/skills/speckit-converge/SKILL.md`: inventario de obligaciones, clasificación
missing / partial / contradicts / unrequested y tareas añadidas al final de tasks.md sin modificar las anteriores,
la spec ni el plan. La skill `$speckit-converge` no se ha invocado como comando.
`check-prerequisites.sh --require-spec --require-tasks` devuelve código 0. No hay hooks.

Las tareas marcadas no se aceptan como prueba: cada obligación se ha comprobado en el código y con las pruebas
([evidencia automática](../../docs/evidencias/spec-006-phpunit.txt)) o con el recorrido en Chrome
([evidencia manual](../../docs/evidencias/spec-006-manual/README.md)).

## Obligaciones contrastadas

| Origen | Estado | Comprobación |
|---|---|---|
| CA-06-01 condiciones | Cumplido en el comportamiento; véase C1 | `testFamilyRecordsCapacityAndAvailabilityPeriods`; R1 (captura 01) |
| CA-06-02 acogida | Cumplido | `testCoordinatorPlacesAnimalInAvailableHomeWithCapacity`, `testPlacementStartsOnMadridCalendarDay`; R1 (captura 02) |
| CA-06-03 disponibilidad y capacidad | Cumplido | Tres pruebas; R2 (409 con la casa completa, captura 05) |
| CA-06-04 web pública y coherencia | Cumplido | `testFosteredAvailableAnimalStaysPublicWithFosterBadge`, `testPlacementDoesNotChangeQuarantineOrReservation`, `testAdoptionEndsActivePlacement`; R1 (captura 03) |
| CA-06-05 novedades | Cumplido | `testFamilyNotesAreAssociatedWithTheAnimal`; R1 (teclado, captura 04) |
| CA-06-06 validación | Cumplido | Dos pruebas; R2 (422, captura 06) |
| CA-06-07 cambios con acogidas | Cumplido | `testConditionChangesRespectActivePlacements` |
| CA-06-08 finalización | Cumplido | `testCoordinatorEndsPlacement`, `testOverduePlacementStaysActiveAndIsFlagged` |
| CA-06-09 concurrencia | Cumplido | Tres pruebas de carrera; fallan con las mutaciones registradas (antes y después) |
| CA-06-10 permisos | Cumplido | Cinco pruebas; R2 (anónimo redirigido) |
| R1–R11 | Cumplido | Cubiertas por los criterios anteriores |
| Plan: modelo, migración, índice parcial en el mapeo | Cumplido | `doctrine:schema:validate` OK; migrate up/down/up |
| Plan: orden de bloqueo y relectura | Cumplido | `FosterService::place`, `end`, `addNote`, `removePeriod`, `saveCapacity` |
| Plan: firmas de `FosterService` | **Parcial** | `end(FosterPlacement, User $coordinator)` recibe la coordinadora y no la usa: C2 |
| Plan: rutas, vistas, mensajes de éxito, ajuste de HU-05 | Cumplido | Rutas registradas (`debug:router`); mensajes comprobados en pruebas y en R1 |
| Constitution V: móvil, escritorio y teclado | **Parcial** | Teclado y formularios correctos a 390 px; las tarjetas de «Mi acogida» sobresalen del ancho: C1 |
| Constitution V: éxito, error y ausencia de resultados | Cumplido | Mensajes flash, 422/409 explicados y estados vacíos |
| Constitution II/III: trazabilidad y base común | Cumplido | `data-model.md`, plan común y tareas coherentes con el código |

## Hallazgos

| ID | Tipo | Gravedad | Origen | Evidencia | Trabajo pendiente |
|---|---|---|---|---|---|
| C1 | partial | MEDIUM | Constitution V (uso en móvil); plan «Vistas» (`foster/home`) | A 390 px, las tarjetas de `foster/home.html.twig` sobresalen unos 6 px por la derecha y su borde queda cortado ([captura 04](../../docs/evidencias/spec-006-manual/04-movil-familia-novedad.png), [captura 06](../../docs/evidencias/spec-006-manual/06-movil-familia-error-capacidad.png)). Causa: los dos campos `date` del alta de periodos en `columns is-mobile` fijan un ancho mínimo que ensancha la columna de `.detail-grid`. La comprobación `scrollWidth <= innerWidth` no lo detecta con emulación móvil | Que ninguna tarjeta supere el ancho de la ventana a 390 px, comprobándolo con la posición real de las tarjetas y no solo con el desplazamiento |
| C2 | partial | LOW | plan: firma de `FosterService::end` | El parámetro `$coordinator` no se usa: no se registra quién finaliza la acogida y la spec no lo exige | Eliminar el parámetro (y su paso desde el controlador y las pruebas), ya que no hay requisito que lo use |

Fuera del alcance de HU-06, sin tarea: en la captura 03 del catálogo (HU-02), una tarjeta aparece sin foto
porque las imágenes usan `loading="lazy"` y la captura se toma antes de que carguen. No se ha comprobado como
defecto.

Métricas: 10 criterios y 11 reglas contrastados; 5 decisiones del plan; 3 principios de la Constitution con
obligaciones comprobables (II, III, V). Hallazgos: 0 missing, 2 partial, 0 contradicts, 0 unrequested.
Por gravedad: 0 críticos, 0 altos, 1 medio y 1 bajo.

Resultado: se añaden T626–T627 en «Phase 8: Convergence» de [tasks.md](tasks.md). Se ejecutarán con implement y
se repetirá converge sobre el resultado.

## Segunda pasada · 2026-11-19, tras implementar T626–T627

Repetida sobre el código con T626–T627, con el mismo método y el mismo inventario. Las dos tareas se han
comprobado en el código, con medición y con pruebas, no por estar marcadas.

| Hallazgo | Estado | Comprobación |
|---|---|---|
| C1 · Constitution V, plan «Vistas» | Resuelto | `comprobacion-t626.mjs`: a 390 px el borde derecho de las tarjetas pasa de 396 a 374 px y el contenido mide 390 px; a 1280 px no cambia ([captura 08](../../docs/evidencias/spec-006-manual/08-movil-t626-despues.png)) |
| C2 · firma de `end` | Resuelto | `FosterService::end(FosterPlacement)`; sin referencias al parámetro en `src` ni `tests`; firma del plan actualizada |

Efectos colaterales revisados: T626 solo cambia la clase de las columnas del alta de periodos en una vista de
HU-06. T627 cambia una firma interna usada por un controlador y una prueba de HU-06. Suite completa repetida en una
base `_test` nueva: 81/81, esquema y linters correctos ([evidencia](../../docs/evidencias/spec-006-phpunit.txt)).

Hallazgos nuevos: ninguno (0 missing, 0 partial, 0 contradicts, 0 unrequested). Según el método, esta pasada no
añade tareas ni fase a tasks.md. Resultado: **convergido**. Queda pendiente la revisión de `aggg15` (T625).
