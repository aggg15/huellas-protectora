# Converge · spec-004

Contraste del código desarrollado con los criterios de aceptación de `spec.md`, el plan y las tareas:

| Criterio | Estado | Comprobación en `Spec004Test.php` | Resultado |
|---|---|---|---|
| **CA-04-01** | Cumplido | `testCoordinatorCanViewApplicationAndRecordInterview()`, `testInterviewRequiresValidDateAndNotes()` | Consulta del cuestionario completo y registro de entrevista con fecha/hora y notas; validación estricta de entradas (422). |
| **CA-04-02** | Cumplido | `testRejectionWithoutReasonIsNotAdmitted()`, `testRejectionWithReasonIsRecordedAndDoesNotAlterAnimalAvailability()` | Rechazo con motivo escrito obligatorio registrado junto a la decisión; rechazo sin motivo bloqueado con 422; no altera disponibilidad del animal. |
| **CA-04-03** | Cumplido | `testApprovalWithoutInterviewIsNotAdmitted()`, `testApprovalWithFutureInterviewIsNotAdmitted()`, `testApprovalAfterValidInterviewReservesAnimalAndMovesOtherApplicationsToWaiting()` | Aprobación condicionada a entrevista previa no futura (D09); cambio de la solicitud a `approved` y del animal a `reserved`. |
| **CA-04-04** | Cumplido | `testApprovalAfterValidInterviewReservesAnimalAndMovesOtherApplicationsToWaiting()` | Al aprobar una candidatura, todas las demás solicitudes activas del animal en `pending` pasan a `waiting` atómicamente en la misma transacción (D10). |
| **CA-04-05** | Cumplido | `testCannotApproveApplicationForAlreadyReservedAnimal()` | Imposibilidad de generar dos reservas sobre el mismo animal; garantizado por bloqueo `FOR UPDATE` e índice parcial único en PostgreSQL. |
| **D11** | Cumplido | `testReleaseReservationRestoresAnimalToAvailableAndWaitingToPending()` | Liberación controlada de reserva por la coordinadora con motivo obligatorio; el animal vuelve a `available` y las solicitudes en `waiting` pasan a `pending`. |
| **Seguridad** | Cumplido | `testRoleAccessControl()` | Rutas y formularios administrativos inaccesibles para solicitantes o voluntarios sin `ROLE_COORDINATOR` (403 Forbidden). |

## Hallazgos de convergencia
* Se mantiene plena compatibilidad con las historias previas HU-01, HU-02 y HU-03.
* Todas las tareas de `tasks.md` (T401 a T410) han sido verificadas y completadas.
* La base técnica queda preparada de forma ideal para HU-05 (*Formalizar la adopción y programar los seguimientos*), la cual tomará directamente como punto de partida las solicitudes en estado `approved` y los animales en estado `reserved`.

## Verificación ejecutada (2026-11-17)
La primera ejecución real de la suite detectó tres desviaciones, corregidas mediante T411–T413:
* `doctrine:schema:validate` fallaba porque el índice parcial solo existía en la migración (CA-04-05).
* La ficha de una solicitud aprobada seguía ofreciendo aprobar/rechazar y ocultaba «Liberar reserva» (D11), porque la vista usaba `isOpen()`, que incluye `approved` por D07.
* El servicio permitía decidir sobre solicitudes ya resueltas (p. ej. rechazar una aprobada dejando el animal reservado).

Resultado tras las correcciones: `vendor/bin/phpunit --testdox` → OK (39 tests, 329 assertions); Spec004 13/13. Detalle en `docs/evidencias/spec-004-phpunit.txt`.

## Pasada de revisión previa a la entrega (2026-11-21)

Origen: revisión de código previa a la entrega. Tareas T414 a T421 de [tasks.md](tasks.md). Responsable de la
ejecución: `aggg15` (asistido por Claude Code); revisión: pendiente en la PR de `revision-entrega`. Método de la
columna «Antes / después»: la prueba nueva se ejecutó el 2026-11-21 sobre el código sin corregir (los archivos de
`src/` y `templates/` de `HEAD`) y después sobre el código corregido.

| Hallazgo | Gravedad | Corrección | Prueba | Antes / después |
|---|---|---|---|---|
| C1-01 / C2-02 | Media | `approve`, `reject` y `recordInterview` en `wrapInTransaction`, con bloqueo animal → solicitud, recarga bajo bloqueo y revalidación; el conflicto de estado responde 409 | `testSimultaneousApproveAndRejectOfTheSameApplicationNeverMix` (proceso auxiliar `tests/Support/application-race.php`), `testDecisionsReReadTheStateUnderLock`, `testStateConflictsAnswerConflict` | Fallan: la carrera dejaba `approve:ok` y `reject:ok` a la vez, y con el estado en memoria desfasado se aprobaba o rechazaba una solicitud ya resuelta / pasan (aprobada con animal reservado o rechazada con animal disponible, nunca mezcla) |
| C1-02 / C2-06 | Media | «El animal ya está reservado para otra candidatura»; en otros estados, etiqueta en español | `testApprovalRefusalNamesTheAnimalStateInSpanish`; `testCannotApproveApplicationForAlreadyReservedAnimal` (aserción ajustada; ahora 409) | Fallan (mostraba «estado actual: reserved») / pasan |
| C1-03 | Media | Los formularios de entrevista y de liberar reserva salen abiertos tras un 422 y conservan lo escrito; también el motivo de rechazo | `testInterviewFormStaysOpenWithTypedValuesAfterValidationError`, `testRejectionAndReleaseFormsKeepTypedValuesAfterValidationError` | Fallan (el `<details>` salía cerrado y se perdía el texto) / pasan |
| C1-12 | Baja | La entrevista exige `interviewAt >= submittedAt` (al minuto) | `testInterviewCannotPrecedeTheSubmission` | Falla (se admitía una entrevista anterior al envío) / pasa |
| C1-17 / C2-07 | Baja | Se quita «(D11)» del botón «Liberar reserva» | `testRejectionAndReleaseFormsKeepTypedValuesAfterValidationError` | Falla / pasa |
| C1-18 | Baja | «Mis solicitudes» muestra «realizada» si la fecha ya pasó | `testMyApplicationsShowsPastInterviewAsDone` | Falla (siempre «programada») / pasa |
| C1-08 | Baja | Solo pruebas: CSRF y rol de las cuatro acciones | `testReviewActionsRequireValidCsrfTokenAndCoordinatorRole` | Pasa antes y después (cobertura que faltaba) |
| C1-09 | Baja | `TextNormalizer` en notas de entrevista, motivo de rechazo y motivo de liberación | `testReviewTextsAreNormalizedAndRejectNulAndInvalidUtf8` | Falla / pasa |

Cambio de comportamiento: el conflicto de estado de entrevista, aprobación y rechazo pasa de 422 a 409, igual que
la liberación de reserva y la formalización; los dos tests existentes afectados (`testCannotApproveApplicationForAlreadyReservedAnimal`
y `testDecidedApplicationAdmitsNoFurtherDecisions`) se ajustaron a 409. Los errores de validación del formulario siguen en 422.
Resultado de la pasada: sin hallazgos abiertos de HU-04.
