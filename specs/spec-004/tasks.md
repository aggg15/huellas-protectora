# Tareas spec-004

Historia: HU-04 · Evaluar solicitudes, entrevistar y reservar el animal.
Metodología: Spec-Driven Development.

## Tareas de especificación y planificación
- [x] **T401**: Concretar decisiones D09, D10 y D11 en `clarify.md` y redactar escenarios Gherkin para CA-04-01 a CA-04-05 en `spec.md`.
- [x] **T402**: Diseñar arquitectura técnica en `plan.md` (migración de esquema, transacciones con bloqueo, índice parcial, controlador y vistas Twig).

## Tareas de implementación
- [x] **T403**: Crear migración Doctrine `Version20261117120000.php` agregando columnas `interviewAt`, `interviewNotes`, `rejectionReason`, `cancellationReason`, `decisionAt` e índice parcial único `uniq_application_approved_animal`.
- [x] **T404**: Actualizar la entidad `AdoptionApplication` con las nuevas propiedades y métodos de consulta de estado.
- [x] **T405**: Extender `ApplicationService` con `recordInterview()`, `reject()`, `approve()` y `cancelReservation()`, implementando bloqueos `FOR UPDATE` y transiciones atómicas.
- [x] **T406**: Incorporar acciones de entrevista, aprobación, rechazo y liberación de reserva en `ApplicationReviewController` bajo `ROLE_COORDINATOR` con validación CSRF.
- [x] **T407**: Diseñar e implementar formularios y paneles de entrevista/decisión en `templates/application/admin_show.html.twig`.
- [x] **T408**: Actualizar `templates/application/mine.html.twig` para mostrar el estado actualizado al solicitante (entrevista, espera, motivo de rechazo).

## Tareas de verificación y convergencia
- [x] **T409**: Implementar suite de pruebas automatizadas `tests/Spec004Test.php` verificando todos los criterios de aceptación (CA-04-01 a CA-04-05), casos límite y control de roles.
- [x] **T410**: Redactar informes `analyze.md`, `implement.md` y `converge.md`, verificando trazabilidad completa.

## Tareas de convergencia tras la primera ejecución real (2026-11-17)
- [x] **T411**: Declarar el índice parcial `uniq_application_approved_animal` en el mapeo de `AdoptionApplication` para que `doctrine:schema:validate` pase.
- [x] **T412**: Limitar entrevista, aprobación y rechazo a solicitudes sin decisión (`pending`/`waiting`) en vista y servicio; mostrar el panel de reserva y la liberación (D11) en solicitudes aprobadas.
- [x] **T413**: Añadir pruebas de regresión (solicitud resuelta, índice parcial en BD, liberación sin motivo) y registrar la evidencia real en `docs/evidencias/spec-004-phpunit.txt`.

## Phase 5: Revisión previa a la entrega (2026-11-21)

Origen: revisión de código previa a la entrega, hallazgos C1-01, C1-02, C1-03, C1-08, C1-09, C1-12, C1-17 y C1-18
(con C2-02, C2-06 y C2-07; informe en [converge](converge.md), «Pasada de revisión previa a la entrega»). Las
secciones anteriores se cuentan como fases 1 a 4. Responsable de la ejecución: `aggg15` (asistido por Claude Code);
revisión: pendiente en la PR de `revision-entrega`.

- [x] T414 C1-01 / C2-02: `ApplicationService::approve`, `reject` y `recordInterview` se ejecutan en `wrapInTransaction`, bloquean primero el animal y después la solicitud (`FOR UPDATE`, como `AdoptionService::formalize`), recargan ambos bajo bloqueo y revalidan antes de mutar. El conflicto de estado responde 409, como la liberación y la formalización. `Spec004Test::testSimultaneousApproveAndRejectOfTheSameApplicationNeverMix` (con `tests/Support/application-race.php`), `testDecisionsReReadTheStateUnderLock` y `testStateConflictsAnswerConflict`
- [x] T415 C1-02 / C2-06: el rechazo de la aprobación por el estado del animal dice «ya está reservado para otra candidatura» o nombra el estado en español, no el código interno; `Spec004Test::testApprovalRefusalNamesTheAnimalStateInSpanish` y `testCannotApproveApplicationForAlreadyReservedAnimal` (ajustada)
- [x] T416 C1-03: los formularios de entrevista y de liberar reserva salen abiertos tras un 422 y conservan lo escrito (también el motivo de rechazo); `admin_show.html.twig` y `ApplicationReviewController` pasan los datos enviados; `Spec004Test::testInterviewFormStaysOpenWithTypedValuesAfterValidationError` y `testRejectionAndReleaseFormsKeepTypedValuesAfterValidationError`
- [x] T417 C1-12: `recordInterview` exige `interviewAt >= submittedAt` (al minuto, como el formulario); `Spec004Test::testInterviewCannotPrecedeTheSubmission`
- [x] T418 C1-17 / C2-07: se quita «(D11)» del texto del botón «Liberar reserva»; comprobado en `testRejectionAndReleaseFormsKeepTypedValuesAfterValidationError`
- [x] T419 C1-18: «Mis solicitudes» muestra la entrevista como «realizada» si la fecha ya pasó y «programada» si es futura; `Spec004Test::testMyApplicationsShowsPastInterviewAsDone`. La repoblación del formulario de revisión veterinaria (HU-01) consta en T311 de spec-003
- [x] T420 C1-08: pruebas de POST de entrevista, aprobar, rechazar y liberar con token CSRF inválido o ausente y con usuarios que no son coordinadora (403); `Spec004Test::testReviewActionsRequireValidCsrfTokenAndCoordinatorRole`
- [x] T421 C1-09: motivo de rechazo, motivo de liberación y notas de la entrevista normalizados con `TextNormalizer` (el NUL y el UTF-8 inválido dan 422); `Spec004Test::testReviewTextsAreNormalizedAndRejectNulAndInvalidUtf8`
