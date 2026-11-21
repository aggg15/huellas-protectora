# Tareas spec-007

Historia: HU-07 · Inscribirse en turnos y detectar falta de voluntarios.
Responsable: `aggg15`. Revisora: `abcb5-ua`.
Fuentes: [spec](spec.md), [clarify](clarify.md), [plan](plan.md). Fecha: 2026-11-19.
Desarrollo asistido por Claude Code; no se ha ejecutado la skill `$speckit-tasks`.

Cada tarea indica su origen (criterio o decisión) y cómo se da por terminada. Una tarea marcada no
sustituye la evidencia: el cierre se acredita con `docs/evidencias/spec-007-phpunit.txt`.

## Especificación y planificación

- [x] **T701** · Revisar HU-01 a HU-06 integradas y actualizar el estado desfasado (AGENTS, README, CONTRIBUTING,
  agrupamiento, plan común y decisiones) antes de especificar, sin atribuir revisiones que no constan.
  Origen: Constitution II/IV. Línea base: suite completa 81/81 en una base `_test` nueva (véase la evidencia).
- [x] **T702** · Especificar y aclarar HU-07: D26–D33, CA-07-06 a CA-07-11, reglas R1–R10 y casos límite.
  Origen: ficha inicial, preguntas 1–3.
- [x] **T703** · Redactar `plan.md` y actualizar `data-model.md` y el plan común. Origen: enunciado §3 (plan).
- [x] **T704** · Ejecutar analyze sobre spec, plan y tasks; conservar el informe en `analyze.md` antes de corregir
  hallazgos. Terminada cuando: no quedan criterios sin tarea ni tareas sin origen, o los hallazgos están
  registrados y resueltos.

## Modelo y persistencia

- [x] **T705** · Crear las entidades `Shift` (con `KIND_LABELS`) y `ShiftSignup`, con el único
  `uniq_shift_signup_volunteer` declarado en el mapeo. Origen: D27, D29; CA-07-01, CA-07-09.
- [x] **T706** · Migración `Version20261119180000`: tablas, claves ajenas, único, índice por inicio y CHECK de tipo,
  horas y límites. `down()` reversible. Terminada cuando: `doctrine:migrations:migrate` (up/down/up) y
  `doctrine:schema:validate` pasan en una base `_test` nueva. Origen: D27, D29; obligación técnica del plan.

## Reglas de negocio

- [x] **T707** · `ShiftCoverage`: `hasStarted`, `isUndercovered` (ventana de 48 horas sobre instantes) y `overlaps`.
  Origen: D29, D31; CA-07-04, CA-07-05, CA-07-09.
- [x] **T708** · `ShiftService::create` y `update`: validación 422 (tipo, fecha y horas reales en Madrid, incluida la
  hora inexistente del cambio de horario; fin > inicio; inicio en el futuro y hasta un año; límites 1–20) y reglas
  409 bajo bloqueo del turno (empezado, máximo < inscripciones, cambio de tipo, fecha u horas con inscripciones).
  Origen: D27, D28; CA-07-06, CA-07-07.
- [x] **T709** · `ShiftService::signUp` y `leave`: bloqueo cuenta → turno y relectura; rechazos 409 por turno
  empezado, ya inscrito, completo, coincidente o no inscrito; violación del único → `ShiftRefused`. Consultas
  `upcoming`, `signupsOf`, `signedShiftIdsOf` y `signupCount`. Origen: D29, D30, D32, D33; CA-07-02, CA-07-03,
  CA-07-08, CA-07-09, CA-07-10.

## Controlador y permisos

- [x] **T710** · `ShiftController`: `GET /admin/turnos` y `GET /admin/turnos/{id}` (`ROLE_VOLUNTEER`),
  `GET|POST /admin/turnos/nuevo` y `POST /admin/turnos/{id}/editar` (`ROLE_COORDINATOR`),
  `POST /admin/turnos/{id}/inscripcion` y `/baja` (`ROLE_VOLUNTEER`), con CSRF, 303 con mensaje de éxito / 422 / 409.
  Origen: D26, D27, D30; CA-07-02, CA-07-06, CA-07-08, CA-07-11.

## Interfaz

- [x] **T711** · `shift/index.html.twig`: aviso de turnos faltos de voluntarios, tabla apilada en móvil con
  cobertura y estado («Faltan voluntarios: N para el mínimo», «Completo», «En curso», «Mínimo cubierto»), marca
  «Te has apuntado», botones de inscripción y baja, «Coincide con otro de tus turnos» sin botón en los turnos
  coincidentes, estado vacío y «Nuevo turno» para la coordinadora.
  Origen: CA-07-01 a CA-07-05, CA-07-08, CA-07-09; analyze A1, A3.
- [x] **T712** · `shift/new.html.twig` y `shift/show.html.twig` (datos, personas inscritas, formulario de
  modificación), con errores por campo (`aria-invalid`/`aria-describedby`) y valores conservados; enlace «Turnos» en
  `base.html.twig`. Origen: CA-07-01, CA-07-06, CA-07-07.

## Verificación

- [x] **T713** · `tests/Spec007Test.php`: recorrido — CA-07-01, CA-07-02, CA-07-03, CA-07-04 (incluida la ventana
  exacta y el cambio de hora con `MockClock`), CA-07-05 y CA-07-06 (incluido bajar el máximo hasta las
  inscripciones, que deja el turno «Completo»). Origen: analyze A2.
- [x] **T714** · `tests/Spec007Test.php`: entradas inválidas y estados — CA-07-07 (incluida la hora repetida de
  octubre, que se interpreta como la primera), CA-07-08 (baja que devuelve el destacado; baja sin figurar; turno
  empezado) y CA-07-09 (doble inscripción, coincidente y contiguo). Origen: analyze A2.
- [x] **T715** · `tests/Support/shift-race.php` y pruebas CA-07-10: dos inscripciones simultáneas en la última plaza,
  dos inscripciones simultáneas de una cuenta en turnos coincidentes y una bajada del máximo simultánea a una
  inscripción. La prueba de la plaza debe fallar si se quita el bloqueo del turno.
- [x] **T716** · Pruebas CA-07-11: anónimo → `/acceso`; solicitante, familia de acogida y veterinario → 403;
  voluntario → 403 al crear o modificar; CSRF inválido sin cambios; nombre con etiquetas HTML escapado en la ficha.
  En todos los casos no cambia ningún dato.
- [x] **T717** · Regresión completa (`vendor/bin/phpunit --testdox`), `doctrine:schema:validate` y linters del CI en
  una base `_test` nueva; registrar la salida en `docs/evidencias/spec-007-phpunit.txt`.
- [x] **T718** · Verificación manual en escritorio y móvil (≤ 400 px) y por teclado: recorrido coordinadora →
  voluntario (destacado y desaparición) y recorrido con error (turno completo o coincidente). Registrar
  precondiciones, datos, pasos y resultado esperado y observado.

## Cierre

- [x] **T719** · Redactar `implement.md` y `converge.md`; añadir aquí las tareas correctivas que salgan de converge
  sin borrar las anteriores.
- [x] **T720** · Abrir la PR hacia `main` con la plantilla, señalando el cambio en la cabecera común y
  `data-model.md`, y pedir la revisión de `abcb5-ua`.

## Ejecución del 2026-11-19

Implementación de T705–T718 asistida por Claude Code a petición de `aggg15`; revisión de `abcb5-ua` pendiente. Actualización 2026-11-21: integrada en `main` con la PR #11; aprobada e integrada por `abcb5-ua` (según `docs/agrupamiento.md`).
Detalle de componentes en [implement](implement.md).

- T705–T706 → entidades `Shift` y `ShiftSignup` y migración `Version20261119180000` (up/down/up comprobados;
  esquema sincronizado con el único declarado en el mapeo).
- T707–T709 → `ShiftCoverage`, `ShiftService` y `ShiftRefused`. Las pruebas detectaron dos defectos antes de cerrar
  la tarea (hora repetida de octubre y comparación con segundos), corregidos en el servicio (véase implement).
- T710 → `ShiftController`.
- T711–T712 → vistas `shift/*`, enlace «Turnos» en la cabecera común y estilos del destacado.
- T713–T716 → `tests/Spec007Test.php` (18 pruebas) y `tests/Support/shift-race.php`. Las pruebas de concurrencia y de
  la hora repetida fallan con las mutaciones descritas en la evidencia y pasan con el código.
- T717 → suite completa: 99 pruebas / 1039 aserciones; [evidencia](../../docs/evidencias/spec-007-phpunit.txt).
- T718 → recorridos R1 y R2 en Chrome real, escritorio y 390 px, con teclado: 19/19 comprobaciones correctas y una
  observación visual. Véase ../../docs/evidencias/spec-007-manual/README.md.
- T719 → [implement](implement.md) y [converge](converge.md): 2 hallazgos (C1 MEDIUM, C2 LOW), añadidos debajo como T721–T722.

- T720 → [PR #11](https://github.com/aggg15/huellas-protectora/pull/11), con revisión pedida a `abcb5-ua` (pendiente).
  Actualización del 2026-11-19 (T801 de spec-008): `abcb5-ua` aprobó la PR #11 y la integró en `main`; CI correcto. Actualización 2026-11-21: integrada en `main` con la PR #11 (merge 5e379e6, 2026-11-19).

## Phase 8: Convergence

Añadida por converge el 2026-11-19 ([informe](converge.md)). Las secciones anteriores se cuentan como
fases 1 a 7. Responsable: `aggg15`; revisora: `abcb5-ua`.

- [x] T721 Ajustar las celdas de `shift/index.html.twig` para que a 390 px todos sus datos («(N plazas libres)», «Te has apuntado») queden en la columna de valores de la fila apilada, y comprobarlo midiendo su posición en Chrome per Constitution V, plan «Vistas» (partial)
- [x] T722 Eliminar `ShiftService::signupCount`, sin llamadas, y alinear la lista de consultas del plan con las implementadas (`board`, `coverage`, `signupsOf`) per plan «Servicio» (partial)

## Phase 9: Revisión previa a la entrega (2026-11-21)

Origen: revisión de código previa a la entrega, hallazgos C2-01, C2-08 y C2-11 (LOW/MEDIUM). Responsable de la
ejecución: `aggg15` (asistido por Claude Code); revisión: pendiente en la PR de `revision-entrega`. Detalle en [converge](converge.md).

- [x] T723 Rellenar con '' las claves del formulario de turnos que falten en el POST (alta y modificación) para que responda 422 y no 500 con `twig.strict_variables`, y probar cada campo ausente con CSRF válido per Constitution V, CA-07-07 (partial)
- [x] T724 Añadir el estado `ended` (`now >= endsAt`) a `ShiftService::coverage` y la etiqueta «Terminado» en `shift/_status.html.twig`, con prueba, para que un turno terminado no se muestre «En curso» per D31 (partial)
- [x] T725 Hacer que `Spec007Test::nextLastSunday` elija siempre un cambio de hora dentro de la ventana de 360 días y que se pruebe la hora inexistente o la repetida en cada ejecución según cuál caiga dentro per CA-07-07 (partial)
- [x] T726 Bloquear la cuenta con `FOR NO KEY UPDATE` en `ShiftService::signUp` para no interbloquear con la clave foránea de `Adoption`, manteniendo la serialización por cuenta (misma causa que C2-03 de spec-008) per plan «Concurrencia» (partial)
- [x] T727 Comprobar en `ShiftService::update` que el turno no ha empezado antes de validar los datos, de modo que un turno empezado responda siempre 409 (analyze A4). Detectado en la suite final del 2026-11-21: `testStartedShiftRejectsSignupLeaveAndChanges` fallaba con 422 si se ejecutaba después de las 21:00 (Europe/Madrid), porque el turno de la prueba (+1 h a +3 h) terminaba al día siguiente y la validación de «mismo día» se evaluaba primero. Antes: falla de forma reproducible a las 21:17; después: Spec007 OK (21 pruebas)
- [x] T728 Anclar a las 10:00 (Europe/Madrid) los turnos de prueba creados «dentro de N días» en `Spec007Test::at()`: el CI de GitHub de la PR de `revision-entrega` (22:32 Madrid) falló en `testCoordinatorChangesLimitsWithinRules` y `testConcurrentMaximumChangeAndSignupStayWithinLimit` porque el turno de prueba de 2 h cruzaba la medianoche y D27 lo rechazaba al editarlo. Antes: 2 fallos ([evidencia](../../docs/evidencias/revision-entrega-2026-11-21/t728-ci-github-antes.txt)); después, a las 22:37: Spec007 OK (20 pruebas, 302 aserciones)
