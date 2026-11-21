# Analyze · spec-004

Revisión de solo lectura de `spec.md`, `plan.md`, `data-model.md` y `tasks.md` antes de iniciar la implementación.

| Criterio | Plan / Tarea | Comprobación prevista |
|---|---|---|
| CA-04-01 | T403, T404, T405, T406, T407; `recordInterview()` | Registro de fecha y notas de entrevista por la coordinadora; validación de errores 422 ante campos vacíos. |
| CA-04-02 | T404, T405, T406, T407; `reject()` | Rechazo con motivo escrito obligatorio; intento de rechazo sin motivo responde con 422 y mantiene estado pendiente. |
| CA-04-03 | T404, T405, T406, T407; `approve()` | Aprobación tras entrevista válida no futura; solicitud pasa a `approved` y animal pasa a `reserved`. Intento sin entrevista o con entrevista futura rechazado. |
| CA-04-04 | T405, T406; `approve()` y actualización de pendientes | Al aprobar una solicitud, las demás solicitudes del animal en `pending` pasan a `waiting` atómicamente. |
| CA-04-05 | T403, T405; `SELECT ... FOR UPDATE` e índice parcial | Imposibilidad de generar dos reservas sobre el mismo animal; índice parcial en BD bloquea violaciones de unicidad. |

## Hallazgos de coherencia
- La entidad `AdoptionApplication` creada en HU-03 se extiende de forma natural con los campos de decisión y entrevista acordados en `data-model.md`.
- No se alteran ni eliminan migraciones previas; se crea una migración incremental `Version20261117120000.php`.
- La regla D09 queda blindada en `ApplicationService::approve()` al exigir que `interviewAt <= now()`.
- La regla D11 queda cubierta permitiendo revertir una reserva si la entrega no llega a formalizarse.
- El rol `ROLE_COORDINATOR` protege todas las rutas administrativas de decisión.

## Análisis previo a la entrega (2026-11-21)

Revisión de solo lectura de spec.md, clarify.md, plan.md, tasks.md, converge.md, `tests/Spec004Test.php`,
../../data-model.md y la Constitution, realizada por Claude Code el 2026-11-21 sobre el commit a74f85f. Se sigue el
método de `.agents/skills/speckit-analyze/SKILL.md` (inventario, pasadas de duplicación, ambigüedad,
infraespecificación, Constitution, cobertura e incoherencia; gravedades CRITICAL/HIGH/MEDIUM/LOW), pero la skill
`$speckit-analyze` no se ha invocado como comando. No existe `.specify/extensions.yml`, así que no hay hooks. El
análisis anterior (arriba) se conserva sin cambios. Los hallazgos de código C1-0X proceden de la revisión de código
del mismo día; se registran en `tasks.md` y `converge.md` por el equipo que los corrige, y aquí solo se enlazan.

### Informe inicial

| ID | Categoría | Gravedad | Ubicación | Hallazgo | Recomendación |
|---|---|---|---|---|---|
| A1 | Incoherencia | MEDIUM | clarify.md | Las cinco secciones de `clarify.md` describen el resultado, pero no hay registro de preguntas, respuestas y criterios ni diferencias con la ficha inicial (enunciado §5.1); tampoco constan las tres preguntas de la ficha inicial. | Añadir la tabla y las diferencias en `clarify.md` (hecho en la ronda del 2026-11-21). |
| A2 | Cobertura | MEDIUM | spec.md (alcance, D11, T412); `tests/Spec004Test.php` | Hay comportamiento implementado, con tarea y prueba, sin criterio de aceptación: la liberación de reserva (D11; `testReleaseReservationRestoresAnimalToAvailableAndWaitingToPending`, `testReleaseReservationRequiresReason`), la solicitud ya decidida (T412; `testDecidedApplicationAdmitsNoFurtherDecisions`) y los permisos (`testRoleAccessControl`). La Constitution II pide identificadores nuevos para los criterios derivados. | Proponer a `abcb5-ua` criterios derivados (liberación de la reserva; solicitud ya decidida; permisos) en `spec.md`; esta pasada no edita la spec. |
| A3 | Incoherencia | LOW | spec.md frente a `spec-004/historia.md` | La ficha inicial tenía «Límites» (sin puntuación automática ni agenda externa de entrevistas) y la spec vigente no tiene esa sección ni «Casos límite». | Recuperar los límites en `spec.md`. |
| A4 | Cobertura | MEDIUM | plan.md §5 («Control de acceso»); `Spec004Test::testRoleAccessControl` | La prueba solo hace peticiones GET. Las cuatro rutas POST (`entrevista`, `aprobar`, `rechazar`, `cancelar-reserva`) no tienen prueba de rol ni de CSRF. Origen: C1-08. | Añadir pruebas de 403 sin rol y sin token para cada ruta POST. |
| A5 | Incoherencia | MEDIUM | CA-04-05, escenario 5.1; `ApplicationService::approve` | El escenario exige «un mensaje claro de que el animal ya está reservado». El mensaje compone «(estado actual: …)» con el valor interno del estado, que sale en inglés (`reserved`), y la prueba solo comprueba «no está disponible para reserva». Origen: C1-02. | Traducir el estado en el mensaje y aseverarlo en la prueba. |
| A6 | Cobertura | MEDIUM | CA-04-01, escenario 1.2; `admin_show.html.twig` (líneas 70 y 182) | Los formularios de entrevista y de liberar reserva están dentro de `<details>`; un error 422 se devuelve con el panel plegado, de modo que la persona puede no ver «se indica el error correspondiente». Las pruebas comprueban el HTML, no lo que se ve. Origen: C1-03. | Mantener el panel abierto cuando hay errores. |
| A7 | Incoherencia | HIGH | CA-04-02, CA-04-03, CA-04-05; plan.md §2; `ApplicationService::approve` y `reject` | El plan dice que `approve` se ejecuta en transacción con bloqueo del animal, pero la comprobación de que la solicitud sigue sin decidir (`assertUndecided`) se hace antes de la transacción y `reject` no usa transacción ni bloqueo. Una aprobación y un rechazo simultáneos de la misma solicitud podrían dejar el animal reservado con una solicitud rechazada, el estado que `testDecidedApplicationAdmitsNoFurtherDecisions` impide en secuencia. El análisis de spec-005 (A4) lo aceptó como riesgo de HU-04. Origen: C1-01. | Releer y comprobar la solicitud bajo bloqueo en `approve`, `reject` y `recordInterview`, con prueba de concurrencia. |
| A8 | Infraespecificación | LOW | CA-04-01, CA-04-03; `recordInterview` | La spec exige una entrevista «no futura», pero no dice que no pueda ser anterior a la solicitud; `recordInterview` solo valida el formato. Origen: C1-12. | Decidir en clarify (Q7) y cubrirlo con una prueba. |
| A9 | Cobertura | LOW | Constitution V; tasks.md | Ninguna tarea de HU-04 registra la verificación en móvil, escritorio y teclado. La cubre en parte `docs/evidencias/spec-005-manual` (recorridos 1 y 2: entrevista y aprobación a 1280 y 390 px), sin los errores de validación de HU-04. | Citar ese recorrido con su alcance o registrar uno propio. |

Sin hallazgos CRITICAL. El hallazgo HIGH (A7) es de código y está en corrección; no puede quedar abierto al cierre
(enunciado §5.2). No hay otros conflictos con la Constitution salvo lo señalado en A9: verificación de recorrido
normal, entradas inválidas y permisos o estado en T409.

### Cobertura

| Requisito | ¿Tarea? | Tareas | Pruebas (`tests/Spec004Test.php`) | Nota |
|---|---|---|---|---|
| CA-04-01 entrevista | Sí | T403, T404, T405, T406, T407, T408, T409 | `testCoordinatorCanViewApplicationAndRecordInterview`, `testInterviewRequiresValidDateAndNotes` | Véanse A6, A8 y C1-09. |
| CA-04-02 rechazo con motivo | Sí | T404, T405, T406, T407, T409 | `testRejectionWithoutReasonIsNotAdmitted`, `testRejectionWithReasonIsRecordedAndDoesNotAlterAnimalAvailability` | Véanse A7 y C1-09. |
| CA-04-03 aprobación y reserva | Sí | T404, T405, T406, T407, T409, T412 | `testApprovalWithoutInterviewIsNotAdmitted`, `testApprovalWithFutureInterviewIsNotAdmitted`, `testApprovalAfterValidInterviewReservesAnimalAndMovesOtherApplicationsToWaiting` | Véase A8. |
| CA-04-04 espera | Sí | T405, T406, T409 | `testApprovalAfterValidInterviewReservesAnimalAndMovesOtherApplicationsToWaiting` | |
| CA-04-05 no doble reserva | Sí | T403, T405, T409, T411 | `testCannotApproveApplicationForAlreadyReservedAnimal`, `testDatabaseRejectsTwoApprovedApplicationsForSameAnimal` | Véanse A5 y A7. |
| D11 liberación (sin CA) | Sí | T405, T406, T407, T413 | `testReleaseReservationRestoresAnimalToAvailableAndWaitingToPending`, `testReleaseReservationRequiresReason` | Véase A2. |
| Solicitud decidida (sin CA) | Sí | T412 | `testDecidedApplicationAdmitsNoFurtherDecisions` | Véase A2. |
| Permisos (sin CA) | Sí | T406, T409 | `testRoleAccessControl` | Véanse A2 y A4. |

Tareas sin requisito: ninguna. T401, T402 y T410 corresponden a obligaciones del proceso (Constitution II y V).

Métricas (commit a74f85f): 5 criterios (0 derivados); 13 tareas (T401–T413); 13 pruebas; criterios sin tarea 0;
criterios sin prueba 0; 3 comportamientos con tarea y prueba sin criterio (A2); ambigüedades 1 en los criterios
(A8) y 4 decisiones pendientes de confirmar en clarify (Q6–Q9); duplicaciones 0; críticos 0; altos 1 (A7, en
corrección).

### Registro de hallazgos

La gravedad de los C1-0X es provisional: la fija `converge.md` de la rama `revision-entrega`.

| ID | Historia / criterio | Gravedad | Evidencia | Decisión | Responsable | Estado |
|---|---|---|---|---|---|---|
| A1 | HU-04 / CA-04-01 a CA-04-05 | MEDIUM | `clarify.md` sin tabla de preguntas ni diferencias | Añadir ambas | `abcb5-ua` | Corregido en clarify.md el 2026-11-21 |
| A2 | HU-04 / D11 y permisos | MEDIUM | Pruebas y tareas T412–T413 sin criterio en spec.md | Proponer criterios derivados | `abcb5-ua` (revisa `aggg15`) | Abierto: `spec.md` no se edita en esta pasada |
| A3 | HU-04 / spec.md | LOW | `historia.md` con «Límites»; spec.md sin ellos | Recuperar los límites | `abcb5-ua` | Abierto |
| A4 | HU-04 / permisos | MEDIUM | `Spec004Test.php::testRoleAccessControl` solo con GET | Ver C1-08 | `abcb5-ua` | En corrección en la rama `revision-entrega` (C1-08) |
| A5 | HU-04 / CA-04-05 | MEDIUM | `ApplicationService::approve`: «estado actual: reserved» | Ver C1-02 | `abcb5-ua` | En corrección en la rama `revision-entrega` (C1-02) |
| A6 | HU-04 / CA-04-01 | MEDIUM | `admin_show.html.twig`: formularios dentro de `<details>` | Ver C1-03 | `abcb5-ua` | En corrección en la rama `revision-entrega` (C1-03) |
| A7 | HU-04 / CA-04-02, CA-04-03, CA-04-05 | HIGH | `assertUndecided` fuera de la transacción; `reject` sin bloqueo | Ver C1-01 | `abcb5-ua` | En corrección en la rama `revision-entrega` (C1-01) |
| A8 | HU-04 / CA-04-01, CA-04-03 | LOW | `recordInterview` solo valida el formato de la fecha | Decidir (Q7) y ver C1-12 | `abcb5-ua` | Abierto: decisión pendiente de confirmar |
| A9 | HU-04 / Constitution V | LOW | tasks.md sin verificación manual propia | Citar o registrar un recorrido | `abcb5-ua` | Abierto |
| C1-01 | HU-04 / CA-04-02, CA-04-03, CA-04-05 | HIGH (provisional) | Carrera entre aprobar y rechazar (véase A7) | Revisión de código del 2026-11-21 | `abcb5-ua` | En corrección en la rama `revision-entrega` |
| C1-02 | HU-04 / CA-04-05 | LOW (provisional) | Mensaje con «reserved» en inglés (véase A5) | Revisión de código del 2026-11-21 | `abcb5-ua` | En corrección en la rama `revision-entrega` |
| C1-03 | HU-04 / CA-04-01 | MEDIUM (provisional) | Errores ocultos en `<details>` (véase A6) | Revisión de código del 2026-11-21 | `abcb5-ua` | En corrección en la rama `revision-entrega` |
| C1-06 | HU-04 / permisos | LOW (provisional) | Las páginas de error 403 y 404 que ven las personas están en inglés | Revisión de código del 2026-11-21 | `abcb5-ua` | En corrección en la rama `revision-entrega` |
| C1-08 | HU-04 / permisos | MEDIUM (provisional) | Rutas POST sin pruebas de CSRF y rol (véase A4) | Revisión de código del 2026-11-21 | `abcb5-ua` | En corrección en la rama `revision-entrega` |
| C1-09 | HU-04 / CA-04-01, CA-04-02 | MEDIUM (provisional) | `recordInterview`, `reject` y `cancelReservation` validan con `trim()` de PHP, que no recorta espacios Unicode ni valida NUL | Revisión de código del 2026-11-21 | `abcb5-ua` | En corrección en la rama `revision-entrega` |
| C1-12 | HU-04 / CA-04-01, CA-04-03 | LOW (provisional) | Entrevista anterior a la solicitud (véase A8) | Revisión de código del 2026-11-21 | `abcb5-ua` | En corrección en la rama `revision-entrega` |

### Informe final

Este informe se cerrará después de integrar las correcciones de la rama `revision-entrega` y de repetir las
pasadas sobre los documentos y pruebas corregidos. Hoy, 2026-11-21, el estado es: A1 corregido; A2, A3 y A9
abiertos; A4 a A8 y C1-01, C1-02, C1-03, C1-06, C1-08, C1-09 y C1-12 en corrección. No se declara cerrado: el cierre
exige 0 contradicciones críticas o altas (hoy A7 sigue abierto como HIGH) y ningún criterio sin tarea y prueba, con
la revisión cruzada de `aggg15` (pendiente).

Nota de método: ronda realizada con Claude Code siguiendo el procedimiento de
`.agents/skills/speckit-analyze/SKILL.md`; no se ha ejecutado la skill de Codex.
