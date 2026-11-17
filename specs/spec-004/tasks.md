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
