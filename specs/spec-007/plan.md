# Plan de implementación · HU-07

Fecha: 2026-11-19. Rama `spec-007`, creada desde `main` (edc7941), que integra HU-01 a HU-06.
Responsable: `aggg15`. Revisión prevista: `abcb5-ua`. Spec: [spec.md](spec.md); decisiones: [clarify](clarify.md).

## Base común y alcance

El [plan común](../../plan.md), [modelo de datos](../../data-model.md),
[decisiones](../../docs/decisiones.md) y [Constitution](../../.specify/memory/constitution.md)
son las fuentes compartidas. Este documento añade el contexto de HU-07 sin duplicar esos acuerdos.
Se implementan la creación y modificación de turnos por la coordinadora, su consulta, la inscripción y la baja
de los voluntarios y el destacado de los turnos faltos de voluntarios. No se implementan avisos, asistencia,
cancelación o borrado de turnos, turnos periódicos ni historial.

## Technical Context

- Lenguaje: PHP 8.3; Symfony 7.4, Twig y Doctrine ORM 3. Sin dependencias nuevas. `symfony/clock` aporta
  `MockClock` para las pruebas de la ventana de 48 horas.
- Interfaz: Turbo y Bulma servidos localmente; sin controladores Stimulus nuevos (`input type="date"` y `"time"`).
- Persistencia: PostgreSQL 16; migración nueva `Version20261119180000` (no modifica las aplicadas).
- Entidades nuevas: `Shift` y `ShiftSignup`. `User` no cambia; no hay roles nuevos.
- Pruebas: PHPUnit con HTTP simulado y PostgreSQL `_test`; procesos PHP independientes para la concurrencia.

## Constitution Check

| Principio | Cumplimiento |
|---|---|
| I. Fidelidad y alcance | Solo turnos de paseo y limpieza, mínimo, máximo, inscripción libre y destacado a 48 horas (SI-30, pp. 1-2). Sin correos, asistencia ni sanciones. |
| II. Trazabilidad | CA-07-01 a CA-07-11 → componentes → pruebas `Spec007Test` (tabla de trazabilidad). |
| III. Base compartida | Una aplicación; reutiliza `User`, `ROLE_VOLUNTEER`/`ROLE_COORDINATOR`, CSRF, `ValidationError`, instantes UTC mostrados en Madrid y el criterio de bloqueo y relectura; amplía `data-model.md`. |
| IV. Coordinación | Solo se toca un archivo común fuera de HU-07: el enlace «Turnos» en `base.html.twig`. Se señala en la PR para `abcb5-ua`. |
| V. Calidad | Recorrido normal, entradas inválidas, estados (completo, empezado, coincidente), permisos, CSRF, concurrencia, ventana de 48 h con `MockClock` y uso en móvil y por teclado. |

## Modelo de datos

Migración nueva `Version20261119180000`, generada con `doctrine:migrations:diff` y completada a mano con los
CHECK, como en HU-03, HU-05 y HU-06:

| Tabla | Columnas | Restricciones |
|---|---|---|
| `Shift` | `id` UUID; `kind` (`walk`/`cleaning`); `startsAt`, `endsAt` (instantes UTC); `minVolunteers`, `maxVolunteers` int; `createdBy_id` FK `app_user`; `createdAt` (UTC) | `CHECK (kind IN ('walk','cleaning'))`; `CHECK (endsAt > startsAt)`; `CHECK (minVolunteers BETWEEN 1 AND 20 AND maxVolunteers BETWEEN minVolunteers AND 20)` (D27). Índice sobre `startsAt` para el listado. |
| `ShiftSignup` | `id` UUID; `shift_id` FK; `volunteer_id` FK `app_user`; `signedUpAt` (UTC) | Único `uniq_shift_signup_volunteer` (`shift_id`, `volunteer_id`) (D29, D33), declarado en el mapeo. |

- El máximo de inscripciones no se puede expresar como CHECK de fila: se garantiza bajo el bloqueo del turno (D33).
- Las coincidencias tampoco: se comprueban bajo el bloqueo de la cuenta. No se usa una restricción de exclusión
  (`btree_gist`), que exigiría una extensión de PostgreSQL y un índice que Doctrine no sabe mapear.
- `down()` borra las dos tablas; ninguna otra historia depende de ellas.

## Diseño

### Cobertura (`App\Service\ShiftCoverage`)

Funciones puras sobre instantes, sin zonas horarias en el cálculo:

- `WINDOW_HOURS = 48`.
- `hasStarted(\DateTimeImmutable $startsAt, \DateTimeImmutable $now): bool` → `$now >= $startsAt`.
- `isUndercovered(\DateTimeImmutable $startsAt, int $signups, int $min, \DateTimeImmutable $now): bool` → no ha
  empezado, `$signups < $min` y `$startsAt->getTimestamp() - $now->getTimestamp() <= 48 * 3600` (D31, R8).
- `overlaps(a, b)`: `a.startsAt < b.endsAt && b.startsAt < a.endsAt` (D29, R6).

### Servicio (`App\Service\ShiftService`)

Recibe `EntityManagerInterface` y `ClockInterface`. Lanza `ValidationError` (422) para datos no válidos y la
excepción nueva `ShiftRefused` (409) para reglas de estado, plazas o coincidencias. Las decisiones se toman con
lecturas DBAL después del bloqueo, no con entidades en memoria (plan común).

| Método | Validación (422) | Transacción y reglas (409) |
|---|---|---|
| `create(User $coordinator, array $data)` | `kind`; `date` (`Y-m-d` real); `startTime`/`endTime` (`H:i` real, existente en Madrid ese día); fin > inicio; inicio > ahora y ≤ ahora + 1 año; `min`/`max` enteros, 1 ≤ min ≤ max ≤ 20 | Crea el turno. |
| `update(Shift, array $data)` | Igual que `create`, pero las dos condiciones temporales (inicio después de ahora y hasta un año) solo se validan si cambian la fecha o las horas (analyze A4) | Bloquea el turno y relee su inicio; rechaza con 409 un turno empezado (también si empezó entre la carga del formulario y el envío), un máximo menor que las inscripciones y un cambio de tipo, fecha u horas con inscripciones (D28). |
| `signUp(Shift, User $volunteer)` | — | Bloquea la cuenta y después el turno; relee el turno y rechaza si ha empezado, si la cuenta ya figura, si está completo o si coincide con otro turno de la cuenta (D29, D33). Violación del único → `ShiftRefused`. |
| `leave(Shift, User $volunteer)` | — | Bloquea el turno; rechaza si ha empezado o si la cuenta no figura; elimina la inscripción (D30). |

- Orden de bloqueo: cuenta → turno en `signUp`; solo turno en `update` y `leave`. Sin ciclos de espera.
- Una edición que cambia las horas de un turno sin inscripciones mientras alguien se apunta: la inscripción espera
  al bloqueo del turno y relee las horas nuevas antes de comprobar coincidencias.
- Consultas: `upcoming()` (turnos no terminados por inicio, con número de inscripciones; una consulta agrupada),
  `signupsOf(Shift)` (inscripciones con nombre, por hora de inscripción), `signedShiftIdsOf(User)` y
  `signupCount(Shift)`. `now()` expone el reloj a las vistas.
- Las horas del formulario se interpretan con `DateTimeImmutable::createFromFormat('!Y-m-d H:i', …, Europe/Madrid)`
  y se aceptan solo si al volver a formatearlas en Madrid se obtiene lo escrito: así se rechaza la hora inexistente
  del cambio de horario (CA-07-07). Se guardan en UTC, como `Adoption.deliveredAt`.

### Rutas y controlador (`ShiftController`)

| Ruta | Nombre | Acceso | Uso |
|---|---|---|---|
| `GET /admin/turnos` | `shifts` | `ROLE_VOLUNTEER` | Listado con cobertura, destacados, aviso y botones «Apuntarme»/«Darme de baja» |
| `GET, POST /admin/turnos/nuevo` | `shift_new` | `ROLE_COORDINATOR`, CSRF `shift-new` | Formulario y alta; 303 al listado / 422 |
| `GET /admin/turnos/{id}` | `shift_show` | `ROLE_VOLUNTEER` | Ficha: datos, cobertura, personas inscritas; formulario de modificación para la coordinadora |
| `POST /admin/turnos/{id}/editar` | `shift_edit` | `ROLE_COORDINATOR`, CSRF `shift-edit-{id}` | Modificación; 303 a la ficha / 422 / 409 |
| `POST /admin/turnos/{id}/inscripcion` | `shift_signup` | `ROLE_VOLUNTEER`, CSRF `shift-signup-{id}` | Apuntarse; 303 al listado / 409 |
| `POST /admin/turnos/{id}/baja` | `shift_leave` | `ROLE_VOLUNTEER`, CSRF `shift-leave-{id}` | Darse de baja; 303 al listado / 409 |

- Las rutas están bajo `/admin`, donde `access_control` ya exige `ROLE_STAFF`; `IsGranted` añade el rol concreto.
  El veterinario es personal pero no voluntario: recibe 403.
- Un rechazo de inscripción o baja vuelve a mostrar el listado con el motivo (409); un error de modificación vuelve
  a mostrar la ficha con los errores y lo escrito (422/409). Cada operación correcta redirige con un mensaje de éxito.
- `/admin/turnos/nuevo` se declara con `priority` para que no lo capture `/admin/turnos/{id}` (como `animal_new`).
- `base.html.twig`: enlace «Turnos» para `ROLE_VOLUNTEER` (la coordinadora lo hereda).

### Vistas

- `shift/index.html.twig`: aviso (`role="status"`) «N turnos de las próximas 48 horas no cubren su mínimo» o
  ningún aviso; tabla apilada en móvil (`stacked-table`) con tipo, fecha, horario, mínimo, máximo, inscritos,
  plazas libres y estado: «Faltan voluntarios: N para el mínimo» (etiqueta y fila destacadas), «Completo»,
  «En curso» o «Mínimo cubierto»; marca «Te has apuntado» (analyze A3); botón «Apuntarme» si procede o «Darme de
  baja» si figura; en un turno que coincide con otro de quien consulta, la indicación «Coincide con otro de tus
  turnos» sin botón (el servidor lo sigue comprobando; analyze A1); enlace a la ficha. Botón «Nuevo turno» para la coordinadora. Estado vacío: «No hay turnos programados».
- `shift/new.html.twig` y el formulario de `shift/show.html.twig`: campos etiquetados (tipo, fecha, inicio, fin,
  mínimo, máximo), errores por campo con `aria-invalid`/`aria-describedby` y valores conservados.
- `shift/show.html.twig`: datos y cobertura del turno, lista de personas inscritas (nombre escapado y hora de
  inscripción) o «Nadie se ha apuntado todavía», y formulario de modificación para la coordinadora (avisando de que
  con inscripciones solo cambian mínimo y máximo).
- Las horas se muestran con el filtro `date` de Twig, cuya zona es Europe/Madrid.

## Trazabilidad

| Criterio | Implementación | Verificación (`tests/Spec007Test.php`) |
|---|---|---|
| CA-07-01 | `upcoming`, `signupsOf`, `shift/index`, `shift/show` | `testShiftShowsScheduleLimitsAndSignups` |
| CA-07-02 | `signUp`, listado | `testVolunteerSignsUpForShiftWithPlaces` |
| CA-07-03 | `signUp` (plazas bajo bloqueo) | `testFullShiftRejectsAnotherSignup` |
| CA-07-04 | `ShiftCoverage::isUndercovered`, aviso | `testUndercoveredShiftIsHighlightedWithin48Hours`, `testHighlightWindowIsExactAndUsesRealHours` |
| CA-07-05 | `isUndercovered` | `testShiftReachingMinimumIsNoLongerHighlighted` |
| CA-07-06 | `create`, `update` (D28) | `testCoordinatorCreatesShift`, `testCoordinatorChangesLimitsWithinRules` |
| CA-07-07 | Validaciones 422 | `testInvalidShiftDataIsRejected` |
| CA-07-08 | `leave`, estado «En curso» | `testVolunteerLeavesShiftAndHighlightReturns`, `testStartedShiftRejectsSignupLeaveAndChanges` |
| CA-07-09 | `signUp` (único y coincidencias) | `testDuplicateAndOverlappingSignupsAreRejected` |
| CA-07-10 | Bloqueos, índice único | `testConcurrentSignupsDoNotExceedMaximum`, `testConcurrentOverlappingSignupsCreateOne`, `testConcurrentMaximumChangeAndSignupStayWithinLimit` (`tests/Support/shift-race.php`) |
| CA-07-11 | `IsGranted`, CSRF | `testOnlyVolunteersSeeAndJoinShifts`, `testOnlyCoordinatorCreatesAndEditsShifts`, `testShiftFormsRequireCsrf` |

La regresión ejecuta la suite completa; ninguna historia anterior cambia de comportamiento. La prueba de
concurrencia sigue el patrón de `tests/Support/foster-race.php`.

## Verificación manual prevista

Con datos ficticios en escritorio y móvil (≤ 400 px): la coordinadora crea un turno de paseo dentro de 30 horas
con mínimo 2; un voluntario ve el turno destacado y se apunta; un segundo voluntario se apunta y el destacado
desaparece. Recorrido con error: apuntarse a un turno completo o coincidente (409, sin cambios). Navegación por
teclado del listado y del formulario de alta.

## Riesgos y coordinación

- **Cabecera común (`base.html.twig`):** un enlace más para `ROLE_VOLUNTEER`; comprobar que la navegación en móvil
  no desborda (la cabecera ya agrupa enlaces en móvil).
- **Zona horaria:** PHP trabaja en UTC y Twig muestra en Europe/Madrid; las pruebas fijan el reloj con `MockClock`
  y comprueban la ventana con el cambio de hora de marzo.
- **Base `_test` local antigua:** usar una base `_test` recién migrada (véase README).

## Estado

Plan redactado el 2026-11-19. Tareas en [tasks.md](tasks.md).
