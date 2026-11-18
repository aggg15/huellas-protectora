# Modelo de datos compartido

Todas las fechas de negocio se guardan en UTC y se muestran en Europe/Madrid. Los identificadores
de entidades públicas son UUID. Las restricciones se mantienen también en PostgreSQL.

| Entidad | Campos y relaciones | Restricciones |
|---|---|---|
| User | id, email, nombre, password hash, roles | Email normalizado único; el registro público no acepta roles. |
| Animal | id, código, especie, edad aproximada en meses, tamaño, procedencia, recepción, estado, enAcogida | Código único; perro/gato; edad no negativa; estados cuarentena/disponible/reservado/adoptado. |
| AnimalPhoto | id, animal FK, nombre interno, tipo MIME | No contiene rutas aportadas por el usuario; eliminación ligada al animal. |
| VeterinaryReview | id, animal FK, veterinario FK, fecha, notas | Fecha entre recepción y momento actual; notas no vacías; conservación de autoría. |
| AdoptionApplication (HU-03/04) | id, animal FK, solicitante FK (User), housingType, housing, experienceType, experience, dailyTime, availability, status, submittedAt, interviewAt, interviewNotes, rejectionReason, cancellationReason, decisionAt. | Cuestionario completo (CHECK en PostgreSQL); estados pending/waiting/approved/rejected/cancelled. Máximo dos abiertas por cuenta bajo bloqueo de fila; sin duplicado abierto por animal. Índice parcial único sobre (animal_id) WHERE status = 'approved'. |

La reserva deriva de una única solicitud aprobada por animal. Un índice parcial impedirá dos
aprobadas incluso ante fallos de concurrencia. Las demás solicitudes activas pasan a espera.
El rechazo no elimina la solicitud y conserva un motivo. Las entidades de adopción formal,
acogidas, turnos y padrinos se definirán en HU-05 a HU-08, reutilizando estos identificadores.
El indicador enAcogida solo permite representar la visibilidad requerida en HU-02; su gestión
operativa pertenece a HU-06 y no se añade una pantalla de acogidas en esta iteración.
