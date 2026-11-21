# Converge · spec-007

Contraste del código de la rama `spec-007` (implementación de T705–T718) con spec.md, plan.md, tasks.md y la
Constitution, realizado por Claude Code el 2026-11-19, después de implement y de la verificación manual (T718).
Se sigue el método de `.agents/skills/speckit-converge/SKILL.md`: inventario de obligaciones, clasificación
missing / partial / contradicts / unrequested y tareas añadidas al final de tasks.md sin modificar las anteriores,
la spec ni el plan. La skill `$speckit-converge` no se ha invocado como comando.
`check-prerequisites.sh --require-spec --require-tasks` devuelve código 0. No hay hooks.

Las tareas marcadas no se aceptan como prueba: cada obligación se ha comprobado en el código y con las pruebas
([evidencia automática](../../docs/evidencias/spec-007-phpunit.txt)) o con el recorrido en Chrome
([evidencia manual](../../docs/evidencias/spec-007-manual/README.md)).

## Obligaciones contrastadas

| Origen | Estado | Comprobación |
|---|---|---|
| CA-07-01 consulta | Cumplido en el comportamiento; véase C1 | `testShiftShowsScheduleLimitsAndSignups`; R1 (captura 04) |
| CA-07-02 inscripción | Cumplido | `testVolunteerSignsUpForShiftWithPlaces`; R1 con teclado (captura 02) |
| CA-07-03 máximo | Cumplido | `testFullShiftRejectsAnotherSignup`; R2 (409, captura 05) |
| CA-07-04 destacado | Cumplido | Dos pruebas (ventana exacta y cambio de hora); R1 (captura 01) |
| CA-07-05 mínimo cubierto | Cumplido | `testShiftReachingMinimumIsNoLongerHighlighted`; R1 (captura 03) |
| CA-07-06 gestión | Cumplido | Dos pruebas; R1 (alta) y R2 (409 al bajar el máximo) |
| CA-07-07 validación | Cumplido | `testInvalidShiftDataIsRejected` (incluidas las dos horas del cambio de horario); R2 (422, captura 06) |
| CA-07-08 baja y empezados | Cumplido | Dos pruebas |
| CA-07-09 doble y coincidentes | Cumplido | `testDuplicateAndOverlappingSignupsAreRejected`; R1 (Bruno, captura 03) |
| CA-07-10 concurrencia | Cumplido | Tres pruebas de carrera; fallan con las mutaciones 1 y 2 (antes y después) |
| CA-07-11 permisos | Cumplido | Tres pruebas; R2 (veterinaria 403, anónimo redirigido) |
| R1–R10 | Cumplido | Cubiertas por los criterios anteriores |
| Plan: modelo, migración, único en el mapeo | Cumplido | `doctrine:schema:validate` OK; migrate up/down/up |
| Plan: orden de bloqueo y relectura | Cumplido | `ShiftService::signUp` (cuenta → turno), `update` y `leave` (turno) |
| Plan: consultas de `ShiftService` | **Parcial** | El plan nombra `upcoming`, `signedShiftIdsOf` y `signupCount`; el código usa `board` y `coverage`, y `signupCount` no lo usa nadie: C2 |
| Plan: rutas, vistas, mensajes de éxito, coincidencias sin botón (A1) | Cumplido | Rutas registradas; mensajes comprobados en pruebas y en R1 |
| Constitution V: móvil, escritorio y teclado | **Parcial** | Sin desbordamiento a 390 px (borde 374 px) y recorridos con teclado correctos; en móvil, parte de los datos cae en la columna de etiquetas: C1 |
| Constitution V: éxito, error y ausencia de resultados | Cumplido | Mensajes flash, 422/409 explicados y estados vacíos |
| Constitution II/III: trazabilidad y base común | Cumplido | `data-model.md`, plan común y tareas coherentes con el código, salvo C2 |

## Hallazgos

| ID | Tipo | Gravedad | Origen | Evidencia | Trabajo pendiente |
|---|---|---|---|---|---|
| C1 | partial | MEDIUM | Constitution V (uso en móvil); plan «Vistas» (`shift/index`) | A 390 px, en las filas apiladas, «(N plazas libres)» y la etiqueta «Te has apuntado» aparecen debajo de la etiqueta del campo, en la columna de etiquetas, y no junto al valor ([captura 02](../../docs/evidencias/spec-007-manual/02-movil-voluntaria-apuntada.png), [captura 05](../../docs/evidencias/spec-007-manual/05-movil-rechazo-turno-completo.png)). Causa: la celda apilada es una rejilla de dos columnas y cada nodo de la celda ocupa una casilla. Medido con [`comprobacion-t721.mjs`](../../docs/evidencias/spec-007-manual/comprobacion-t721.mjs): 5 elementos con desplazamiento 0 respecto a la celda ([resultado](../../docs/evidencias/spec-007-manual/07-movil-t721-antes.json)) | Que todos los datos de cada celda queden en la columna de valores a 390 px, comprobándolo con la posición real |
| C2 | partial | LOW | plan «Servicio», lista de consultas | `ShiftService::signupCount` no tiene llamadas; `upcoming` y `signedShiftIdsOf` no existen porque `board` reúne ambas | Eliminar `signupCount` y alinear la lista de consultas del plan con `board`, `coverage` y `signupsOf` |

Métricas: 11 criterios y 10 reglas contrastados; 5 decisiones del plan; 3 principios de la Constitution con
obligaciones comprobables (II, III, V). Hallazgos: 0 missing, 2 partial, 0 contradicts, 0 unrequested.
Por gravedad: 0 críticos, 0 altos, 1 medio y 1 bajo.

Resultado: se añaden T721–T722 en «Phase 8: Convergence» de [tasks.md](tasks.md). Se ejecutarán con implement y
se repetirá converge sobre el resultado.

## Segunda pasada · 2026-11-19, tras implementar T721–T722

Repetida sobre el código con T721–T722, con el mismo método y el mismo inventario. Las dos tareas se han
comprobado en el código, con medición y con pruebas, no por estar marcadas.

| Hallazgo | Estado | Comprobación |
|---|---|---|
| C1 · Constitution V, plan «Vistas» | Resuelto | `comprobacion-t721.mjs`: a 390 px los elementos en la columna de etiquetas pasan de 5 a 0 y el contenido mide 390 px ([captura 08](../../docs/evidencias/spec-007-manual/08-movil-t721-despues.png)); recorrido completo repetido, 19/19, escritorio sin cambios |
| C2 · consultas del plan | Resuelto | `signupCount` eliminado, sin referencias en `src` ni `tests`; la lista de consultas y la trazabilidad del plan nombran `board`, `coverage` y `signupsOf` |

Efectos colaterales revisados: T721 envuelve el contenido de dos celdas de una vista de HU-07 y añade la clase
`.shift-tags`; T722 elimina un método sin llamadas. Suite completa repetida en una base `_test` nueva: 99/99, esquema
y linters correctos ([evidencia](../../docs/evidencias/spec-007-phpunit.txt)).

Hallazgos nuevos: ninguno (0 missing, 0 partial, 0 contradicts, 0 unrequested). Según el método, esta pasada no
añade tareas ni fase a tasks.md. Resultado: **convergido**. Queda pendiente la revisión de `abcb5-ua` (T720).

## Pasada de revisión previa a la entrega (2026-11-21)

Origen: revisión de código previa a la entrega. Ejecutada por `aggg15` asistido por Claude Code; revisión pendiente.

| Hallazgo | Gravedad | Corrección | Prueba | Resultado antes → después |
|---|---|---|---|---|
| C2-01 · POST sin un campo del formulario de turnos daba 500 con `twig.strict_variables` | MEDIUM | `ShiftController` rellena con '' las claves esperadas (alta y modificación) antes de renderizar | `Spec007Test::testShiftFormsWithMissingFieldAreRejectedWith422` (cada campo ausente, CSRF válido, alta y edición) | Antes: falla (500). Después: pasa (422 con el mensaje) |
| C2-08 · un turno terminado se mostraba «En curso» | LOW | `ShiftService::coverage` devuelve `ended` (`now >= endsAt`); `_status.html.twig` muestra «Terminado» | `Spec007Test::testEndedShiftIsShownAsEnded` | Antes: falla. Después: pasa |
| C2-11 · `nextLastSunday` fallaba del 26 al 30 de octubre (siguiente cambio a más de un año) | LOW | Devuelve null si el cambio queda fuera de 360 días; la prueba usa los cambios de marzo y octubre que caen dentro (siempre al menos uno) y comprueba la hora inexistente y la repetida cuando corresponde | `Spec007Test::testInvalidShiftDataIsRejected` | Antes: fallaba en la ventana indicada (comprobado por análisis, no ejecutado con fecha simulada). Después: pasa hoy (21/11) con ambos cambios dentro de la ventana |
| C2-03 (parte de HU-07) · bloqueo `FOR UPDATE` de la cuenta en la inscripción | LOW | `FOR NO KEY UPDATE` en `ShiftService::signUp` | Pruebas de concurrencia existentes de `Spec007Test` | Pasan antes y después; no hay prueba nueva del interbloqueo |
| Suite final · `testStartedShiftRejectsSignupLeaveAndChanges` daba 422 en lugar de 409 cuando se ejecutaba después de las 21:00 (la validación de datos precedía a la comprobación del estado) | MEDIUM | `ShiftService::update` comprueba el inicio del turno antes de validar y lo vuelve a comprobar bajo bloqueo (T727) | `Spec007Test::testStartedShiftRejectsSignupLeaveAndChanges` | Antes: falla de forma reproducible a las 21:17 (Europe/Madrid); después: Spec007 OK (21 pruebas, 310 aserciones) |
| CI de GitHub · dos pruebas de Spec007 creaban de noche turnos que cruzaban la medianoche (datos de prueba, no defecto de la aplicación) | LOW | `Spec007Test::at()` ancla «+N days» a las 10:00 de Madrid (T728) | `testCoordinatorChangesLimitsWithinRules`, `testConcurrentMaximumChangeAndSignupStayWithinLimit` | Antes: 2 fallos en GitHub Actions a las 22:32; después: Spec007 OK a las 22:37 |
