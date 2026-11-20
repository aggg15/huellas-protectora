# Modelo de datos compartido

Todas las fechas de negocio se guardan en UTC y se muestran en Europe/Madrid. Los identificadores
de entidades públicas son UUID. Las restricciones se mantienen también en PostgreSQL.

| Entidad | Campos y relaciones | Restricciones |
|---|---|---|
| User | id, email, nombre, password hash, roles | Email normalizado único; el registro público no acepta roles. |
| Animal | id, código, especie, edad aproximada en meses, tamaño, procedencia, recepción, estado, enAcogida | Código único; perro/gato; edad no negativa; estados cuarentena/disponible/reservado/adoptado. |
| AnimalPhoto | id, animal FK, nombre interno, tipo MIME | No contiene rutas aportadas por el usuario; eliminación ligada al animal. |
| VeterinaryReview | id, animal FK, veterinario FK, fecha, notas | Fecha entre recepción y momento actual; notas no vacías; conservación de autoría. |
| AdoptionApplication (HU-03/04) | id, animal FK, solicitante FK (User), housingType, housing, experienceType, experience, dailyTime, availability, status, submittedAt, interviewAt, interviewNotes, rejectionReason, cancellationReason, decisionAt. | Cuestionario completo (CHECK en PostgreSQL); estados pending/waiting/approved/rejected/cancelled y formalized (HU-05, no abierta). Máximo dos abiertas por cuenta bajo bloqueo de fila; sin duplicado abierto por animal. Índice parcial único sobre (animal_id) WHERE status = 'approved'. |
| Adoption (HU-05) | id, animal FK, solicitud FK, adoptante FK (User), formalizadaPor FK (User), contractNumber, deliveredAt, feeCents, copia del contrato (nombre y email del adoptante; código, especie, edad y tamaño del animal) | Únicos sobre animal, solicitud y número de contrato; cuota entre 1 y 999999 céntimos. La copia no cambia tras formalizar. |
| FollowUp (HU-05) | id, adoption FK, kind (one_month/six_months), dueOn (fecha Europe/Madrid), status (scheduled) | Único (adopción, kind); dos por adopción; último día del mes si el día no existe. |
| FosterHome (HU-06) | id, familia FK (User con ROLE_FOSTER), capacity, createdAt | Una casa por cuenta (único); capacidad entre 1 y 10. |
| FosterAvailability (HU-06) | id, casa FK, startsOn, endsOn (fechas Europe/Madrid, incluidas) | Fin ≥ inicio; sin solapes en la misma casa (validado bajo bloqueo de la casa). |
| FosterPlacement (HU-06) | id, animal FK, casa FK, registradaPor FK (User), startedOn, plannedEndOn, endedOn, endReason (returned/adopted) | Una acogida activa (endedOn nulo) por animal: índice parcial único. Activas de una casa ≤ capacidad (bajo bloqueo). Fechas coherentes; endedOn y endReason nulos a la vez. |
| FosterNote (HU-06) | id, acogida FK, autora FK (User), writtenAt, body | Texto de 1 a 2000 caracteres; inmutable; interna. |
| Shift (HU-07) | id, kind (walk/cleaning), startsAt, endsAt (instantes UTC; mismo día en Europe/Madrid), minVolunteers, maxVolunteers, creadoPor FK (User), createdAt | endsAt > startsAt; 1 ≤ mínimo ≤ máximo ≤ 20 (CHECK). Inscripciones ≤ máximo y sin turnos coincidentes por persona, validados bajo bloqueo. |
| ShiftSignup (HU-07) | id, turno FK, voluntario FK (User con ROLE_VOLUNTEER), signedUpAt | Único (turno, voluntario). La baja elimina la fila. |
| Sponsorship (HU-08) | id, animal FK, padrino FK (User público), monthlyCents, startedAt, endedAt, endReason (finished/moved), anterior FK (Sponsorship) | Importe entre 1000 y 999999 céntimos; fin y motivo nulos a la vez; endedAt ≥ startedAt (CHECK). Único parcial (animal, padrino) WHERE endedAt IS NULL. Anterior único. Animal disponible al crear, validado bajo bloqueo. |
| SponsorUpdate (HU-08) | id, animal FK, autora FK (User coordinadora), writtenAt, body | Texto de 1 a 2000 caracteres (CHECK); inmutable; visible para los padrinos activos en su fecha. |

La reserva deriva de una única solicitud aprobada por animal. Un índice parcial impedirá dos
aprobadas incluso ante fallos de concurrencia. Las demás solicitudes activas pasan a espera.
El rechazo no elimina la solicitud y conserva un motivo. Al formalizar (HU-05) la solicitud pasa a
formalized, el animal a adoptado y fuera de acogida, y las demás abiertas del animal a canceladas con
motivo, en una sola transacción. Los turnos (HU-07) solo se relacionan con las cuentas de voluntario. Los apadrinamientos
(HU-08) se relacionan con el animal y con una cuenta pública; un apadrinamiento activo de un animal adoptado está
pendiente de la decisión del padrino (estado derivado, sin columna), y la fecha de la pausa se obtiene de la adopción
del animal (D18). Formalizar la adopción no modifica los apadrinamientos.
El indicador enAcogida (`Animal.fostered`) es verdadero mientras el animal tiene una acogida activa (HU-06):
lo cambian, en la misma transacción que la acogida, su registro, su finalización y la formalización de la
adopción, que además finaliza la acogida con motivo `adopted`. La acogida no cambia el estado del animal.
