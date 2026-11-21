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

La gravedad de los C1-0X de la tabla es la provisional de la revisión; la fijada al corregir consta en la tabla de la «Pasada de revisión previa a la entrega» de `converge.md`. Estados: Corregido (con su tarea), Decidido (con su Dxx o Qn), Aceptado (con su justificación) y Abierto (nada hecho). Actualizado el 2026-11-21 contra `tasks.md`, `converge.md`, `clarify.md` y `docs/decisiones.md` de la rama `revision-entrega`.

| ID | Historia / criterio | Gravedad | Evidencia | Decisión | Responsable | Estado |
|---|---|---|---|---|---|---|
| A1 | HU-04 / CA-04-01 a CA-04-05 | MEDIUM | `clarify.md` sin tabla de preguntas ni diferencias | Añadir ambas | `abcb5-ua` | Corregido en clarify.md el 2026-11-21; Q6–Q9 decididas el mismo día |
| A2 | HU-04 / D11 y permisos | MEDIUM | Pruebas y tareas T412–T413 sin criterio en spec.md | Proponer criterios derivados | `abcb5-ua` (revisa `aggg15`) | Aceptado con mitigación: `spec.md` no se editó y no hay criterios derivados; los tres comportamientos (liberación D11, solicitud decidida, permisos) constan con tarea y prueba en la sección «Comportamientos con tarea y prueba sin criterio propio» de `docs/trazabilidad.md`. Limitación: la Constitution II pide identificadores de criterio |
| A3 | HU-04 / spec.md | LOW | `historia.md` con «Límites»; spec.md sin ellos | Recuperar los límites | `abcb5-ua` | Abierto: no se recuperaron los «Límites» de la ficha inicial en `spec.md`. Gravedad baja, sin efecto en criterios, tareas ni pruebas |
| A4 | HU-04 / permisos | MEDIUM | `Spec004Test.php::testRoleAccessControl` solo con GET | Ver C1-08 | `abcb5-ua` | Corregido: T420 (`testReviewActionsRequireValidCsrfTokenAndCoordinatorRole`) |
| A5 | HU-04 / CA-04-05 | MEDIUM | `ApplicationService::approve`: «estado actual: reserved» | Ver C1-02 | `abcb5-ua` | Corregido: T415 (`testApprovalRefusalNamesTheAnimalStateInSpanish`) |
| A6 | HU-04 / CA-04-01 | MEDIUM | `admin_show.html.twig`: formularios dentro de `<details>` | Ver C1-03 | `abcb5-ua` | Corregido: T416 (`testInterviewFormStaysOpenWithTypedValuesAfterValidationError`, `testRejectionAndReleaseFormsKeepTypedValuesAfterValidationError`) |
| A7 | HU-04 / CA-04-02, CA-04-03, CA-04-05 | HIGH | `assertUndecided` fuera de la transacción; `reject` sin bloqueo | Ver C1-01 | `abcb5-ua` | Corregido: T414 (`testSimultaneousApproveAndRejectOfTheSameApplicationNeverMix`, con procesos reales; `testDecisionsReReadTheStateUnderLock`, `testStateConflictsAnswerConflict`; D43) |
| A8 | HU-04 / CA-04-01, CA-04-03 | LOW | `recordInterview` solo valida el formato de la fecha | Decidir (Q7) y ver C1-12 | `abcb5-ua` | Decidido: Q7 → D42 (la entrevista no es anterior al envío), aplicada en T417 (`testInterviewCannotPrecedeTheSubmission`) |
| A9 | HU-04 / Constitution V | LOW | tasks.md sin verificación manual propia | Citar o registrar un recorrido | `abcb5-ua` | Aceptado como limitación: no se registró un recorrido manual propio de HU-04; se cita `docs/evidencias/spec-005-manual` (recorridos 1 y 2: entrevista y aprobación a 1280 y 390 px), sin los errores de validación, que sí cubren pruebas automáticas (T416) |
| C1-01 | HU-04 / CA-04-02, CA-04-03, CA-04-05 | HIGH (provisional) | Carrera entre aprobar y rechazar (véase A7) | Revisión de código del 2026-11-21 | `abcb5-ua` | Corregido: T414 (D43) |
| C1-02 | HU-04 / CA-04-05 | LOW (provisional) | Mensaje con «reserved» en inglés (véase A5) | Revisión de código del 2026-11-21 | `abcb5-ua` | Corregido: T415 |
| C1-03 | HU-04 / CA-04-01 | MEDIUM (provisional) | Errores ocultos en `<details>` (véase A6) | Revisión de código del 2026-11-21 | `abcb5-ua` | Corregido: T416 |
| C1-06 | HU-04 / permisos | LOW (provisional) | Las páginas de error 403 y 404 que ven las personas están en inglés | Revisión de código del 2026-11-21 | `abcb5-ua` | Corregido: T210 (páginas de error en español, transversal; registrada en spec-002) |
| C1-08 | HU-04 / permisos | MEDIUM (provisional) | Rutas POST sin pruebas de CSRF y rol (véase A4) | Revisión de código del 2026-11-21 | `abcb5-ua` | Corregido: T420 (solo pruebas: pasan antes y después) |
| C1-09 | HU-04 / CA-04-01, CA-04-02 | MEDIUM (provisional) | `recordInterview`, `reject` y `cancelReservation` validan con `trim()` de PHP, que no recorta espacios Unicode ni valida NUL | Revisión de código del 2026-11-21 | `abcb5-ua` | Corregido: T421 (`TextNormalizer`; D44) |
| C1-12 | HU-04 / CA-04-01, CA-04-03 | LOW (provisional) | Entrevista anterior a la solicitud (véase A8) | Revisión de código del 2026-11-21 | `abcb5-ua` | Decidido: D42 (Q7) y corregido en T417 |

### Informe final (2026-11-21)

Informe de cierre de esta pasada, redactado por Claude Code el 2026-11-21 después de integrar las correcciones de la
rama `revision-entrega` (HEAD 114b74d) y de contrastar este registro con `tasks.md` (fase 5: T414–T421), `converge.md`
(«Pasada de revisión previa a la entrega»), `clarify.md` (Q6–Q9, decididas el 2026-11-21) y `docs/decisiones.md`
(D42 a D44). El informe inicial de arriba se conserva sin cambios.

| Métrica (rama `revision-entrega`) | Valor |
|---|---|
| Criterios de aceptación | 5 (0 derivados; 3 comportamientos con tarea y prueba sin criterio, A2) |
| Tareas | 21 (T401–T421) |
| Pruebas de `tests/Spec004Test.php` | 23 (13 del informe inicial + 10 nuevas: T414 ×3, T415, T416 ×2, T417, T419, T420 y T421) |
| Criterios sin tarea | 0 |
| Criterios sin prueba | 0 |
| Criterios con prueba parcial | 0 (CA-04-01 pasa a completa con T416 y CA-04-05 con T414 y T415) |
| Hallazgos CRITICAL / HIGH abiertos | 0 / 0 (el HIGH A7 está corregido en T414) |

Hallazgos por estado (16): **Corregido 11** (A1, A4, A5, A6, A7, C1-01, C1-02, C1-03, C1-06, C1-08, C1-09); **Decidido 2**
(A8 y C1-12, ambos por Q7 / D42 y T417); **Aceptado 2** (A2 y A9); **Abierto 1** (A3, de gravedad baja).

Verificación: el CI de la rama integrada (76223b2) dio `OK (23 tests, 193 assertions)` en esta clase y
`OK (144 tests, 1659 assertions)` en la suite completa, con todos los pasos correctos
([`summary.txt`](../../docs/evidencias/revision-entrega-2026-11-21/ci-rama-integrada/summary.txt), [Spec004](../../docs/evidencias/revision-entrega-2026-11-21/ci-rama-integrada/13-phpunit-Spec004Test.log)). El «antes» de HU-03 y HU-04 está en
[`hu03-hu04-pruebas-nuevas-sobre-codigo-anterior.txt`](../../docs/evidencias/revision-entrega-2026-11-21/hu03-hu04-pruebas-nuevas-sobre-codigo-anterior.txt)
(21 pruebas nuevas sobre el código anterior: 1 error y 16 fallos) y su «después» en
[`hu03-hu04-suite-tras-correccion.txt`](../../docs/evidencias/revision-entrega-2026-11-21/hu03-hu04-suite-tras-correccion.txt)
(129 pruebas, 1487 aserciones, OK). La carrera aprobar/rechazar dejaba `approve:ok` y `reject:ok` a la vez antes de la
corrección; después el resultado es siempre una solicitud aprobada con animal reservado o rechazada con animal disponible.

Lo que no se declara: la revisión cruzada de los cambios de la rama `revision-entrega` se hará en la PR de esa rama. La revisión de la historia original consta en su PR (#8, revisada por `aggg15`). El criterio de cierre (0 contradicciones críticas o altas y ningún criterio
sin tarea y prueba) se cumple; A3 sigue abierto por ser de gravedad baja y no afectar a ningún criterio.

Nota de método: ronda realizada con Claude Code siguiendo el procedimiento de
`.agents/skills/speckit-analyze/SKILL.md`; no se ha ejecutado la skill de Codex.
