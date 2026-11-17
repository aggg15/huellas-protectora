# Implement · spec-004

Fecha: 2026-11-17.
Historia: HU-04 · Evaluar solicitudes, entrevistar y reservar el animal.

## Componentes implementados
- **Persistencia y Base de Datos:**
  - Migración Doctrine `Version20261117120000.php`: añade columnas `interviewAt`, `interviewNotes`, `rejectionReason`, `cancellationReason` y `decisionAt` en la tabla `AdoptionApplication`.
  - Índice parcial único `uniq_application_approved_animal` en PostgreSQL sobre `(animal_id) WHERE status = 'approved'`, garantizando matemáticamente la imposibilidad de doble reserva (CA-04-05).
- **Entidades (`App\Entity\AdoptionApplication`):**
  - Nuevas propiedades y métodos auxiliares `hasInterview()`, `hasValidInterview(\DateTimeImmutable $now)` y `isApproved()`.
- **Servicios de dominio (`App\Service\ApplicationService`):**
  - `recordInterview()`: validación de fecha y notas (1-5000 caracteres), almacenamiento en UTC (CA-04-01).
  - `reject()`: validación de motivo obligatorio por escrito (1-2000 caracteres), registro de fecha de decisión (CA-04-02).
  - `approve()`: verificación obligatoria de entrevista realizada previa no futura (D09), transacción con bloqueo de fila `FOR UPDATE`, actualización a `status = 'approved'`, paso del animal a `status = 'reserved'` y transición atómica de todas las demás solicitudes `pending` del animal a `waiting` (CA-04-03, CA-04-04, CA-04-05).
  - `cancelReservation()`: liberación controlada de reserva según D11 con motivo obligatorio, vuelta del animal a `available` y reactivación de solicitudes en espera a `pending`.
- **Controlador (`App\Controller\ApplicationReviewController`):**
  - Rutas POST bajo `ROLE_COORDINATOR` con validación CSRF individual: `/entrevista`, `/aprobar`, `/rechazar` y `/cancelar-reserva`.
- **Vistas Twig y diseño Bulma:**
  - `templates/application/admin_show.html.twig`: formulario de entrevista, formulario de aprobación condicional, formulario de rechazo con motivo, panel de liberación de reserva y listado de solicitudes concurrentes.
  - `templates/application/mine.html.twig`: tabla enriquecida para el adoptante con etiquetas de estado, información de entrevista programada y motivos de rechazo si aplica.
- **Pruebas automatizadas (`tests/Spec004Test.php`):**
  - Suite de 10 pruebas cubriendo CA-04-01 a CA-04-05, D09, D10, D11 y control estricto de roles.
