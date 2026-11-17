# Analyze · spec-004

Revisión de solo lectura de `spec.md`, `plan.md`, `data-model.md` y `tasks.md` antes de iniciar la implementación.

| Criterio | Plan / Tarea | Comprobación prevista |
|---|---|---|
| CA-04-01 | T403, T404, T405, T406, T407; `recordInterview()` | Registro de fecha y notas de entrevista por la coordinadora; validación de errores 422 ante campos vacíos. |
| CA-04-02 | T404, T405, T406, T407; `reject()` | Rechazo con motivo escrito obligatorio; intento de rechazo sin motivo responde con 422 y mantiene estado pendiente. |
| CA-04-03 | T404, T405, T406, T407; `approve()` | Aprobación tras entrevista válida no futura; solicitud pasa a `approved` y animal pasa a `reserved`. Intento sin entrevista o con entrevista futura rechazado. |
| CA-04-04 | T405, T406; `approve()` y actualización de pendientes | Al aprobar una solicitud, las demás solicitudes del animal en `pending` pasan a `waiting` atómicamente. |
| CA-04-05 | T403, T405; `SELECT ... FOR UPDATE` e índice parcial | Imposibilidad de generar dos reservas sobre el mismo animal; índice parcial en BD bloquea violaciones de unicidad. |

## Hallazgos de coherencia
- La entidad `AdoptionApplication` creada en HU-03 se extiende de forma natural con los campos de decisión y entrevista acordados en `data-model.md`.
- No se alteran ni eliminan migraciones previas; se crea una migración incremental `Version20261117120000.php`.
- La regla D09 queda blindada en `ApplicationService::approve()` al exigir que `interviewAt <= now()`.
- La regla D11 queda cubierta permitiendo revertir una reserva si la entrega no llega a formalizarse.
- El rol `ROLE_COORDINATOR` protege todas las rutas administrativas de decisión.
