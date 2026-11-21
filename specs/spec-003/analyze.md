# Analyze · spec-003

Revisión de solo lectura de spec.md, clarify.md, plan.md, ../../data-model.md y tasks.md antes de
implementar, realizada por Claude Code el 2026-11-17. No se ha ejecutado la skill `$speckit-analyze`.

| Criterio | Plan/tarea | Comprobación prevista |
|---|---|---|
| CA-03-01 | T302/T303; `ApplicationService::submit` | Envío desde la ficha pública; solicitud pendiente ligada a cuenta y animal. |
| CA-03-02 | T302; `validate` y errores por campo | 422, campos pendientes enumerados, respuestas conservadas, sin filas. |
| CA-03-03 | T302; recuento de abiertas bajo bloqueo | Tercera solicitud 409; las dos existentes intactas. Aprobada cuenta; rechazada/cancelada no. |
| CA-03-04 | T302/T303; `ApplicationReviewController` | Coordinadora ve listado y cuestionario completo; voluntario y veterinario 403. |
| CA-03-05 | T302; 404 y relectura bajo bloqueo | Cuarentena/reservado/adoptado 404; reserva tras cargar la ficha rechazada; duplicado 409. |
| CA-03-06 | T302; `FOR UPDATE` de la cuenta | Cuatro procesos simultáneos: exactamente dos admitidas. |

## Hallazgos

1. **Modelo común.** `data-model.md` describía «vivienda, experiencia, disponibilidad, entrevista,
   motivo, versión» de forma genérica. Se concretan las columnas del cuestionario y se deja la
   entrevista, el motivo y la versión para HU-04, que es quien los usa. Se actualiza el modelo común.
2. **Destino tras acceder.** `security.yaml` envía a `admin_animals` tras el acceso: una cuenta
   solicitante recibiría 403. El plan prevé un destino por rol (`/panel`).
3. **Alta de cuenta.** D06 exige cuenta pero no existía forma pública de crearla; el límite de la
   ficha excluye «un proceso de registro no definido». Se interpreta como alta mínima de D06
   (nombre, email, contraseña) sin verificación ni recuperación, y se registra en decisiones.md.
4. **Ficha pública de HU-02.** El botón «Iniciar solicitud» apuntaba a la portada. HU-02 indica que
   HU-03 proporciona el enlace: cambio mínimo en `public/show.html.twig`, a comunicar en la PR.
5. **CI.** Solo ejecutaba Spec001. Se añade un paso condicional para Spec003 sin renombrar el job,
   para no alterar la comprobación requerida en `main`.

No se detectan criterios sin tarea ni tareas sin criterio.

## Análisis previo a la entrega (2026-11-21)

Revisión de solo lectura de spec.md, clarify.md, plan.md, tasks.md, converge.md, `tests/Spec003Test.php`,
../../data-model.md y la Constitution, realizada por Claude Code el 2026-11-21 sobre el commit a74f85f. Se sigue el
método de `.agents/skills/speckit-analyze/SKILL.md` (inventario, pasadas de duplicación, ambigüedad,
infraespecificación, Constitution, cobertura e incoherencia; gravedades CRITICAL/HIGH/MEDIUM/LOW), pero la skill
`$speckit-analyze` no se ha invocado como comando. No existe `.specify/extensions.yml`, así que no hay hooks. El
análisis anterior (arriba) se conserva sin cambios. Los hallazgos de código C1-0X proceden de la revisión de código
del mismo día; se registran en `tasks.md` y `converge.md` por el equipo que los corrige, y aquí solo se enlazan.

### Informe inicial

| ID | Categoría | Gravedad | Ubicación | Hallazgo | Recomendación |
|---|---|---|---|---|---|
| A1 | Incoherencia | MEDIUM | clarify.md | La «Concreción del 2026-11-17» fija campos y códigos HTTP, pero no hay registro de preguntas, respuestas y criterios ni diferencias con la ficha inicial (enunciado §5.1). | Añadir la tabla y las diferencias en `clarify.md` (hecho en la ronda del 2026-11-21). |
| A2 | Cobertura | MEDIUM | tasks.md T301–T305 | Las tareas son de fase (T302 implementa los seis criterios) y ninguna nombra un criterio; la relación criterio → tarea solo se deduce de la tabla del análisis anterior. | Mantener `docs/trazabilidad.md` como fuente criterio → decisión → tarea → prueba. |
| A3 | Incoherencia | LOW | spec.md («Revisión del compañero: pendiente», «implementada en la rama `spec-003`»); tasks.md («pendientes de identificación… Codex»); converge.md | Los textos no reflejan lo que consta en `docs/agrupamiento.md`: HU-03 es de `aggg15` (PR #6) y `abcb5-ua` aprobó la PR; la historia está integrada en `main`. | Actualizar las menciones en la pasada final, sin atribuir nada que no conste. |
| A4 | Incoherencia | MEDIUM | CA-03-05; clarify.md sección 4; `ApplicationController` | La spec dice que la solicitud de un animal reservado entre la carga y el envío «se rechaza al enviar» y clarify fija 409, pero el controlador resuelve el animal con `getPublicAnimal` y responde 404. La prueba solo comprueba el rechazo llamando al servicio, no por HTTP. Origen: C1-04. | Alinear el código con clarify (409) y añadir la prueba HTTP. |
| A5 | Cobertura | MEDIUM | CA-03-03, CA-03-05, CA-03-06; plan.md «Diseño» | El límite de dos y el duplicado de la misma cuenta y animal se garantizan solo con bloqueos y comprobaciones de aplicación; el plan menciona CHECK como defensa adicional, pero no hay un índice único que impida dos solicitudes abiertas de la misma cuenta para el mismo animal. Origen: C1-10. | Añadir el índice único parcial y declararlo en el mapeo de la entidad. |
| A6 | Infraespecificación | LOW | spec.md R1 y R7; CA-03-01 | La spec no dice si una cuenta de personal puede enviar solicitudes (todas las cuentas reciben `ROLE_USER`) ni si la persona puede retirar una solicitud abierta. | Decidir en clarify (Q8 y Q9). |
| A7 | Cobertura | LOW | Constitution V; converge.md | La comprobación visual en móvil, escritorio y teclado consta como pendiente. La cubre en parte `docs/evidencias/spec-005-manual` (recorridos 1 y 2: alta, solicitud y «Mis solicitudes» a 1280 y 390 px, con una observación sobre la tabla en móvil), sin los errores de validación de HU-03. | Citar ese recorrido con su alcance o registrar uno propio. |

Sin hallazgos CRITICAL ni HIGH propios. No hay conflictos con la Constitution salvo lo señalado en A7: verificación
de recorrido normal, entradas inválidas y permisos o estado en T304.

### Cobertura

| Requisito | ¿Tarea? | Tareas | Pruebas (`tests/Spec003Test.php`) | Nota |
|---|---|---|---|---|
| CA-03-01 envío | Sí | T302, T303, T304 | `testApplicantSubmitsCompleteQuestionnaireForAvailableAnimal`, `testPublicRegistrationCreatesApplicantAccountAndReturnsToApplication` | |
| CA-03-02 cuestionario incompleto | Sí | T302, T303, T304 | `testIncompleteQuestionnaireIsNotAdmittedAndListsPendingData`, `testNoPreviousExperienceIsAValidAnswer` | Véase C1-09. |
| CA-03-03 máximo de dos | Sí | T302, T304 | `testThirdOpenApplicationIsRejectedAndExistingOnesAreKept`, `testApprovedCountsAsOpenWhileRejectedAndCancelledDoNot` | |
| CA-03-04 candidaturas | Sí | T302, T303, T304 | `testCoordinatorAccessesReceivedApplicationWithCompleteQuestionnaire` | |
| CA-03-05 animal no disponible o duplicado | Sí | T301, T302, T304 | `testAnimalsThatAreNotAvailableDoNotReceiveApplications`, `testDuplicateOpenApplicationForSameAnimalIsRejected` | Véanse A4 y A5. |
| CA-03-06 concurrencia | Sí | T301, T302, T304 | `testConcurrentSubmissionsNeverExceedTwoOpenApplications` | |
| Permisos y CSRF (R7) | Sí | T302, T304 | `testPermissionsAndCsrfProtectApplications` | Sin criterio propio. |

Tareas sin requisito: ninguna. T305 corresponde a obligaciones del proceso (Constitution II y V).

Métricas (commit a74f85f): 6 criterios (2 derivados); 5 tareas (T301–T305); 11 pruebas; criterios sin tarea 0;
criterios sin prueba 0; ambigüedades 1 en los criterios (A6) y 2 decisiones pendientes de confirmar en clarify
(Q8 y Q9); duplicaciones 0; críticos 0.

### Registro de hallazgos

La gravedad de los C1-0X de la tabla es la provisional de la revisión; la fijada al corregir consta en la tabla de la «Pasada de revisión previa a la entrega» de `converge.md`. Estados: Corregido (con su tarea), Decidido (con su Dxx o Qn), Aceptado (con su justificación) y Abierto (nada hecho). Actualizado el 2026-11-21 contra `tasks.md`, `converge.md`, `clarify.md` y `docs/decisiones.md` de la rama `revision-entrega`.

| ID | Historia / criterio | Gravedad | Evidencia | Decisión | Responsable | Estado |
|---|---|---|---|---|---|---|
| A1 | HU-03 / CA-03-01 a CA-03-06 | MEDIUM | `clarify.md` sin tabla de preguntas ni diferencias | Añadir ambas | `aggg15` | Corregido en clarify.md el 2026-11-21; Q8 y Q9 decididas el mismo día |
| A2 | HU-03 / CA-03-01 a CA-03-06 | MEDIUM | `tasks.md` T301–T305 sin criterio por tarea | Matriz en `docs/trazabilidad.md`; sin tareas nuevas | `aggg15` (revisa `abcb5-ua`) | Aceptado: las tareas siguen siendo de fase; la relación criterio → tarea está en `docs/trazabilidad.md` (62/62 criterios con tarea y prueba). La revisión cruzada de las filas consta en la PR de la historia |
| A3 | HU-03 / documentación | LOW | spec.md, tasks.md y converge.md frente a `docs/agrupamiento.md` | Actualizar responsables y estado | `aggg15` | Corregido en parte: `spec.md`, `plan.md` y `tasks.md` recogen la «Actualización 2026-11-21» (PR #6 integrada, aprobada por `abcb5-ua` según `docs/agrupamiento.md`); `converge.md` conserva su línea «Pendiente: … revisión del compañero» sin actualizar |
| A4 | HU-03 / CA-03-05 | MEDIUM | clarify.md sección 4 (409) frente a `ApplicationController` (404) | Ver C1-04 | `aggg15` | Corregido: T306 (`testAnimalReservedOrAdoptedWhileTheFormIsOpenAnswersConflict`; D43) |
| A5 | HU-03 / CA-03-03, CA-03-05, CA-03-06 | MEDIUM | Migraciones sin índice único de solicitud abierta por cuenta y animal | Ver C1-10 | `aggg15` | Corregido: T308 (migración `Version20261121100000`, índice único parcial; `testDatabaseRejectsTwoOpenApplicationsOfTheSameApplicantForTheSameAnimal`) |
| A6 | HU-03 / CA-03-01, CA-03-03 | LOW | spec.md R1 y R7 sin regla para el personal ni para la retirada | Decidir en clarify (Q8 y Q9) | `aggg15` | Decidido: Q8 → D45 (el personal no solicita adopciones; las familias de acogida sí), aplicada en T312 (`testStaffCannotApplyButFosterFamiliesCan`); Q9 (sin retirada de solicitudes; solo la coordinadora las cierra), decidida el 2026-11-21 sin cambio de código |
| A7 | HU-03 / Constitution V | LOW | converge.md: comprobación visual pendiente | Citar o registrar un recorrido | `aggg15` | Aceptado como limitación: no se registró un recorrido manual propio de HU-03; se cita `docs/evidencias/spec-005-manual` (recorridos 1 y 2: alta, solicitud y «Mis solicitudes» a 1280 y 390 px), sin los errores de validación. Esos errores sí los cubren pruebas automáticas (422, campos pendientes, textos) |
| C1-04 | HU-03 / CA-03-05 | MEDIUM (provisional) | 404 en lugar de 409 al enviar una solicitud de un animal ya reservado (véase A4) | Revisión de código del 2026-11-21 | `aggg15` | Corregido: T306 (D43) |
| C1-06 | HU-03 / CA-03-05 | LOW (provisional) | Las páginas de error 403 y 404 que ve la persona están en inglés | Revisión de código del 2026-11-21 | `aggg15` | Corregido: T210 (páginas de error en español, transversal; registrada en spec-002) |
| C1-09 | HU-03 / CA-03-02 | MEDIUM (provisional) | `ApplicationService` usa `trim()` de PHP, que no recorta espacios Unicode ni valida NUL | Revisión de código del 2026-11-21 | `aggg15` | Corregido: T307 y T311 (`TextNormalizer`; D44) |
| C1-10 | HU-03 / CA-03-03, CA-03-05 | MEDIUM (provisional) | Sin índice único de solicitud abierta (véase A5) | Revisión de código del 2026-11-21 | `aggg15` | Corregido: T308 |

### Informe final (2026-11-21)

Informe de cierre de esta pasada, redactado por Claude Code el 2026-11-21 después de integrar las correcciones de la
rama `revision-entrega` (HEAD 114b74d) y de contrastar este registro con `tasks.md` (fase 3: T306–T312), `converge.md`
(«Pasada de revisión previa a la entrega»), `clarify.md` (Q8 y Q9, decididas el 2026-11-21) y `docs/decisiones.md`
(D43 a D45). El informe inicial de arriba se conserva sin cambios.

| Métrica (rama `revision-entrega`) | Valor |
|---|---|
| Criterios de aceptación | 6 (2 derivados) |
| Tareas | 12 (T301–T312) |
| Pruebas de `tests/Spec003Test.php` | 19 (11 del informe inicial + 8 nuevas: T306 a T310, T311 ×2 y T312) |
| Criterios sin tarea | 0 |
| Criterios sin prueba | 0 |
| Criterios con prueba parcial | 0 (CA-03-05 pasa a completa con T306) |
| Hallazgos CRITICAL / HIGH | 0 / 0 |

Hallazgos por estado (11): **Corregido 8** (A1, A3, A4, A5, C1-04, C1-06, C1-09, C1-10; en A3 queda sin actualizar
`converge.md`); **Decidido 1** (A6: Q8 → D45 y Q9); **Aceptado 2** (A2 y A7); **Abierto 0**.

Verificación: el CI de la rama integrada (76223b2) dio `OK (18 tests, 215 assertions)` en esta clase y
`OK (144 tests, 1659 assertions)` en la suite completa, con todos los pasos correctos
([`summary.txt`](../../docs/evidencias/revision-entrega-2026-11-21/ci-rama-integrada/summary.txt), [Spec003](../../docs/evidencias/revision-entrega-2026-11-21/ci-rama-integrada/13-phpunit-Spec003Test.log)). La prueba 19 (D45, T312) se añadió después de esa ejecución y se
verificó con la suite de Spec003 (19 pruebas OK, según el README de la carpeta de evidencias). Las pruebas de C1-04,
C1-09, C1-10, C2-03 y las de HU-01 de T311 fallan sobre el código anterior y pasan tras la corrección, según `converge.md`;
C1-20c es cobertura nueva. La prueba de concurrencia `testConcurrentSubmissionsNeverExceedTwoOpenApplications` sigue pasando
(procesos reales).

Lo que no se declara: la revisión cruzada de los cambios de la rama `revision-entrega` se hará en la PR de esa rama. La revisión de la historia original consta en su PR (#6, revisada por `abcb5-ua`). El resto del criterio de cierre (0 contradicciones críticas o altas y ningún
criterio sin tarea y prueba) se cumple.

Nota de método: ronda realizada con Claude Code siguiendo el procedimiento de
`.agents/skills/speckit-analyze/SKILL.md`; no se ha ejecutado la skill de Codex.
