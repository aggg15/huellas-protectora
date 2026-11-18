# Implement · spec-005

Fecha: 2026-11-18. Aplicado por Claude Code a petición del usuario (`aggg15`). No se ha ejecutado
la skill `$speckit-implement`. Tareas: T505–T524 de [tasks.md](tasks.md), tras [analyze](analyze.md).

## Componentes

- **Entidades.** `Adoption` (animal y solicitud uno a uno, adoptante, coordinadora, número de contrato,
  entrega UTC, cuota en céntimos y copia de los datos del contrato) y `FollowUp` (tipo, fecha y estado,
  único por adopción y tipo). `AdoptionApplication` gana el estado `formalized` («Adopción formalizada»),
  fuera de `OPEN_STATUSES` y `UNDECIDED_STATUSES`.
- **Migración** `Version20261118230000`: tablas, claves ajenas, únicos (declarados también en el mapeo),
  CHECK de cuota, copia del contrato, tipo y estado, y CHECK de estados de solicitud con `formalized`.
  No modifica migraciones aplicadas. `down()` falla si hay solicitudes formalizadas, a propósito.
- **Servicios.**
  - `FollowUpCalendar::dueDate`: meses naturales sobre el día de Madrid, ajustados al último día del mes.
  - `AdoptionService`: `validate()` (cuota y confirmación, 422); `formalize()` (transacción con bloqueo
    de animal y solicitud y relectura de sus estados, 409; crea adopción y seguimientos, solicitud
    formalizada, animal adoptado y fuera de acogida, cancela las demás abiertas con motivo; convierte
    una violación de único en rechazo); `findByAnimal()` para HU-08; `scheduledFollowUps()`.
  - `ApplicationService::cancelReservation` (HU-04, T512): relee solicitud y animal bajo bloqueo.
- **Controladores.** `ApplicationReviewController::formalize` (`POST /admin/solicitudes/{id}/formalizar`,
  CSRF) y `show()` con código de error y datos del formulario opcionales; `cancelReservation` captura el
  conflicto (409). `AdoptionController`: `/admin/adopciones/{id}`, `/admin/seguimientos` (coordinadora) y
  `/adopciones/{id}/contrato` (coordinadora o adoptante titular; resto 404). `ApplicationController::mine`
  pasa las adopciones del adoptante.
- **Vistas.** Formulario «Formalizar entrega» y bloque `formalized` en la ficha de la solicitud;
  `adoption/show`, `adoption/follow_ups` y `adoption/contract`; motivo de toda cancelada y enlace al
  contrato en «Mis solicitudes»; enlace «Seguimientos» en la cabecera. Stimulus `print` y `@media print`.
- **Pruebas.** `tests/Spec005Test.php` (18 pruebas) y `tests/Support/adoption-race.php` (procesos
  concurrentes). El CI las detecta por su nombre sin cambios.

## Decisiones tomadas durante la implementación

- Las fechas de seguimiento se guardan como fecha sin hora y se muestran con `|date('d/m/Y', false)`,
  para que la zona de Twig no desplace el día.
- Doctrine cierra el EntityManager cuando falla una transacción. Los rechazos se lanzan antes de modificar
  entidades en memoria, así que la ficha que se muestra después del rechazo refleja el estado real. En
  las pruebas, cada intento rechazado usa un EntityManager nuevo (`resetManager`).
- La prueba de liberación concurrente arranca la liberación 0,5 s después de formalizar, con la solicitud
  ya cargada. Así el caso del estado obsoleto es determinista, y la prueba falla sin T512
  (evidencia del antes y el después).

## Resultado

`vendor/bin/phpunit --testdox`: 57 pruebas y 484 aserciones correctas (Spec005 18/18; Spec001–Spec004
sin cambios en sus pruebas). Esquema sincronizado y linters correctos. Detalle:
[evidencia](../../docs/evidencias/spec-005-phpunit.txt).

Pendiente: verificación manual (T525), converge (T526) y PR con revisión de `abcb5-ua` (T527).
