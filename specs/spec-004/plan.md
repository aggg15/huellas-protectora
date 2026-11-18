# Plan técnico · spec-004

Fecha: 2026-11-17.
Historia: HU-04 · Evaluar solicitudes, entrevistar y reservar el animal.
Arquitectura: Symfony 7.4, Twig, Bulma CSS y Doctrine ORM sobre PostgreSQL 16.

## 1. Modelo de datos y migración

* **Nueva migración Doctrine (`Version20261117120000.php`):**
  * `ALTER TABLE AdoptionApplication ADD interviewAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;`
  * `ALTER TABLE AdoptionApplication ADD interviewNotes TEXT DEFAULT NULL;`
  * `ALTER TABLE AdoptionApplication ADD rejectionReason TEXT DEFAULT NULL;`
  * `ALTER TABLE AdoptionApplication ADD cancellationReason TEXT DEFAULT NULL;`
  * `ALTER TABLE AdoptionApplication ADD decisionAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;`
  * `CREATE UNIQUE INDEX uniq_application_approved_animal ON AdoptionApplication (animal_id) WHERE status = 'approved';`
* **Entidad `AdoptionApplication`:**
  * Incorporación de las propiedades correspondientes con tipos nulos por defecto.
  * Métodos auxiliares de negocio: `hasValidInterview(\DateTimeImmutable $now): bool`, `isApproved(): bool`, `isDecided(): bool`.

## 2. Servicios y transacciones (`ApplicationService`)

* `recordInterview(AdoptionApplication $app, array $data): void`:
  * Valida formato `!Y-m-d\TH:i` de `interviewAt` y longitud de `interviewNotes` (1-5000 caracteres).
  * Guarda la fecha en UTC y las notas.
* `reject(AdoptionApplication $app, string $reason): void`:
  * Valida que `$reason` no esté vacío (1-2000 caracteres).
  * Pasa el estado a `rejected`, registra `$reason` y `decisionAt = now()`.
* `approve(AdoptionApplication $app): void`:
  * Ejecutado en transacción (`$em->wrapInTransaction`).
  * Comprueba que `$app->hasValidInterview($now)` sea verdadero (fecha de entrevista menor o igual a `now()`). En caso contrario lanza `ValidationError`.
  * Bloquea la fila del animal: `SELECT status FROM animal WHERE id = ? FOR UPDATE`.
  * Si `animal.status !== 'available'`, lanza `ApplicationRefused` (animal ya no disponible o ya reservado).
  * `$app->status = 'approved'`.
  * `$app->decisionAt = $now`.
  * `$animal->status = 'reserved'`.
  * Transición atómica de solicitudes concurrentes del mismo animal:
    `UPDATE adoptionapplication SET status = 'waiting' WHERE animal_id = ? AND status = 'pending' AND id <> ?`.
* `cancelReservation(AdoptionApplication $app, string $reason): void` (D11):
  * Ejecutado en transacción.
  * Requiere `$app->status === 'approved'`.
  * Valida `$reason` obligatorio.
  * Bloquea la fila del animal (`FOR UPDATE`).
  * `$app->status = 'cancelled'`.
  * `$app->cancellationReason = $reason`.
  * `$animal->status = 'available'`.
  * Reactiva solicitudes en espera del mismo animal:
    `UPDATE adoptionapplication SET status = 'pending' WHERE animal_id = ? AND status = 'waiting'`.

## 3. Controladores y seguridad (`ApplicationReviewController`)

* Restringido a `ROLE_COORDINATOR` a nivel de clase.
* Nuevas rutas POST con token CSRF individual:
  * `POST /admin/solicitudes/{id}/entrevista` (`admin_application_interview`)
  * `POST /admin/solicitudes/{id}/aprobar` (`admin_application_approve`)
  * `POST /admin/solicitudes/{id}/rechazar` (`admin_application_reject`)
  * `POST /admin/solicitudes/{id}/cancelar-reserva` (`admin_application_cancel_reservation`)

## 4. Vistas Twig e interfaz Bulma

* `templates/application/admin_show.html.twig`:
  * Módulo de entrevista: muestra la entrevista concertada/realizada y formulario para registrarla/editarla.
  * Módulo de decisiones:
    * Botón "Aprobar y reservar animal" con validación visual previa de entrevista.
    * Formulario de "Rechazar solicitud" con campo de texto obligatorio para el motivo.
    * Si la solicitud está aprobada, panel informativo de reserva y opción "Liberar reserva" con motivo.
  * Panel lateral con otras solicitudes para el mismo animal.
* `templates/application/mine.html.twig`:
  * Visualización actualizada para el solicitante: badges de `Aprobada`, `En espera`, `Rechazada`, fecha de entrevista si existe y motivo de rechazo.

## 5. Pruebas y verificación (`Spec004Test.php`)

* Verificación de CA-04-01: Registro de entrevista y validaciones de fecha/notas.
* Verificación de CA-04-02: Rechazo con motivo obligatorio y rechazo de petición con motivo vacío (422).
* Verificación de CA-04-03: Aprobación tras entrevista y cambio del animal a `reserved`.
* Verificación de CA-04-04: Transición automática de las demás solicitudes a `waiting`.
* Verificación de CA-04-05: Imposibilidad de aprobar sobre animal reservado (bloqueo transaccional e índice parcial).
* Verificación de D11: Liberación de reserva con motivo, vuelta del animal a `available` y reactivación de esperas a pendientes.
* Control de acceso: Denegación de acceso para solicitantes o voluntarios sin `ROLE_COORDINATOR`.
