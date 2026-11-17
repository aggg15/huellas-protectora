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
