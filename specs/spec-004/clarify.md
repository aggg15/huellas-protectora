# Clarify · spec-004

Fecha: 2026-11-17.
Historia: HU-04 · Evaluar solicitudes, entrevistar y reservar el animal.
Estado: Precisiones consensuadas e incorporadas al plan y modelo común (`docs/decisiones.md`).

## 1. Gestión de entrevistas (D09, CA-04-01)

* **Campos requeridos:**
  * `interviewAt`: Fecha y hora de la entrevista (zona horaria de entrada `Europe/Madrid`, persistida en `UTC`).
  * `interviewNotes`: Notas de la conversación y valoración cualitativa (texto no vacío, máximo 5000 caracteres).
* **Momento y obligatoriedad de la entrevista:**
  * Para **aprobar** una solicitud, es condición sine qua non que la entrevista esté registrada y que su fecha `interviewAt` sea menor o igual al momento actual (`interviewAt <= now()`), asegurando que la entrevista haya tenido lugar efectivamente antes de reservar.
  * Para **rechazar**, no es obligatorio que haya habido entrevista; la coordinadora puede descartar una solicitud directamente por incompatibilidad evidente del cuestionario.

## 2. Decisión de rechazo (CA-04-02)

* **Motivo obligatorio:**
  * El formulario de rechazo exige obligatoriamente un texto justificativo (`rejectionReason`, no vacío, máximo 2000 caracteres).
  * Si el motivo está en blanco o solo contiene espacios, la solicitud no se rechaza y se responde con HTTP 422.
* **Efectos:**
  * La solicitud pasa a `rejected`.
  * No se altera el estado del animal ni el resto de solicitudes.
  * Una solicitud rechazada no cuenta como solicitud abierta para el límite de 2 por adoptante (D07).

## 3. Aprobación, reserva y lista de espera (D10, CA-04-03, CA-04-04)

* **Aprobación de la candidatura:**
  * Se verifica que el animal esté en estado `available` (bajo bloqueo de fila `FOR UPDATE`).
  * La solicitud pasa a `approved`.
  * El animal pasa a `status = 'reserved'`.
  * La fecha de decisión (`decisionAt`) queda registrada.
* **Transición de las demás solicitudes a espera:**
  * Todas las solicitudes del mismo animal que estuvieran en estado `pending` pasan atómicamente a `waiting` (`En espera`).
  * Las solicitudes en estado `rejected` o `cancelled` no sufren cambios.
  * Los adoptantes con solicitud en `waiting` ven su nuevo estado reflejado en «Mis solicitudes».

## 4. Prevención de reservas incompatibles y concurrencia (D10, CA-04-05)

* **Control de concurrencia y atomicidad:**
  * La transacción bloquea la fila del animal: `SELECT status FROM animal WHERE id = ? FOR UPDATE`.
  * Si el animal no está en estado `available`, la operación lanza una excepción de negocio y no se aplica.
* **Restricción a nivel de base de datos:**
  * Se añade un índice único parcial en PostgreSQL:
    `CREATE UNIQUE INDEX uniq_application_approved_animal ON AdoptionApplication (animal_id) WHERE status = 'approved';`
  * Esto asegura matemáticamente que ningún error de aplicación o carrera concurrente pueda dejar dos solicitudes aprobadas para el mismo animal.

## 5. Liberación de reserva en caso de fallo (D11)

* Si una familia aprobada renuncia o no formaliza la entrega:
  * La coordinadora puede liberar la reserva con un motivo obligatorio (`cancellationReason`).
  * La solicitud aprobada pasa a `cancelled`.
  * El animal vuelve de `reserved` a `available`.
  * Las solicitudes de ese animal que estaban en `waiting` vuelven automáticamente a `pending` para que la coordinadora pueda evaluarlas.
