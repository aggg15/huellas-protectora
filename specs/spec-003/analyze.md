# Analyze · spec-003

Revisión de solo lectura de spec.md, clarify.md, plan.md, ../../data-model.md y tasks.md antes de
implementar, realizada por Claude Code el 2026-11-17. No se ha ejecutado la skill `$speckit-analyze`.

| Criterio | Plan/tarea | Comprobación prevista |
|---|---|---|
| CA-03-01 | T302/T303; `ApplicationService::submit` | Envío desde la ficha pública; solicitud pendiente ligada a cuenta y animal. |
| CA-03-02 | T302; `validate` y errores por campo | 422, campos pendientes enumerados, respuestas conservadas, sin filas. |
| CA-03-03 | T302; recuento de abiertas bajo bloqueo | Tercera solicitud 409; las dos existentes intactas. Aprobada cuenta; rechazada/cancelada no. |
| CA-03-04 | T302/T303; `ApplicationReviewController` | Coordinadora ve listado y cuestionario completo; voluntario y veterinario 403. |
| CA-03-05 | T302; 404 y relectura bajo bloqueo | Cuarentena/reservado/adoptado 404; reserva tras cargar la ficha rechazada; duplicado 409. |
| CA-03-06 | T302; `FOR UPDATE` de la cuenta | Cuatro procesos simultáneos: exactamente dos admitidas. |

## Hallazgos

1. **Modelo común.** `data-model.md` describía «vivienda, experiencia, disponibilidad, entrevista,
   motivo, versión» de forma genérica. Se concretan las columnas del cuestionario y se deja la
   entrevista, el motivo y la versión para HU-04, que es quien los usa. Se actualiza el modelo común.
2. **Destino tras acceder.** `security.yaml` envía a `admin_animals` tras el acceso: una cuenta
   solicitante recibiría 403. El plan prevé un destino por rol (`/panel`).
3. **Alta de cuenta.** D06 exige cuenta pero no existía forma pública de crearla; el límite de la
   ficha excluye «un proceso de registro no definido». Se interpreta como alta mínima de D06
   (nombre, email, contraseña) sin verificación ni recuperación, y se registra en decisiones.md.
4. **Ficha pública de HU-02.** El botón «Iniciar solicitud» apuntaba a la portada. HU-02 indica que
   HU-03 proporciona el enlace: cambio mínimo en `public/show.html.twig`, a comunicar en la PR.
5. **CI.** Solo ejecutaba Spec001. Se añade un paso condicional para Spec003 sin renombrar el job,
   para no alterar la comprobación requerida en `main`.

No se detectan criterios sin tarea ni tareas sin criterio.
