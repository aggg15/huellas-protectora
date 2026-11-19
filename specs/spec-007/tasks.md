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

- [ ] **T705** · Crear las entidades `Shift` (con `KIND_LABELS`) y `ShiftSignup`, con el único
  `uniq_shift_signup_volunteer` declarado en el mapeo. Origen: D27, D29; CA-07-01, CA-07-09.
- [ ] **T706** · Migración `Version20261119180000`: tablas, claves ajenas, único, índice por inicio y CHECK de tipo,
  horas y límites. `down()` reversible. Terminada cuando: `doctrine:migrations:migrate` (up/down/up) y
  `doctrine:schema:validate` pasan en una base `_test` nueva. Origen: D27, D29; obligación técnica del plan.

## Reglas de negocio

- [ ] **T707** · `ShiftCoverage`: `hasStarted`, `isUndercovered` (ventana de 48 horas sobre instantes) y `overlaps`.
  Origen: D29, D31; CA-07-04, CA-07-05, CA-07-09.
- [ ] **T708** · `ShiftService::create` y `update`: validación 422 (tipo, fecha y horas reales en Madrid, incluida la
  hora inexistente del cambio de horario; fin > inicio; inicio en el futuro y hasta un año; límites 1–20) y reglas
  409 bajo bloqueo del turno (empezado, máximo < inscripciones, cambio de tipo, fecha u horas con inscripciones).
  Origen: D27, D28; CA-07-06, CA-07-07.
- [ ] **T709** · `ShiftService::signUp` y `leave`: bloqueo cuenta → turno y relectura; rechazos 409 por turno
  empezado, ya inscrito, completo, coincidente o no inscrito; violación del único → `ShiftRefused`. Consultas
  `upcoming`, `signupsOf`, `signedShiftIdsOf` y `signupCount`. Origen: D29, D30, D32, D33; CA-07-02, CA-07-03,
  CA-07-08, CA-07-09, CA-07-10.

## Controlador y permisos

- [ ] **T710** · `ShiftController`: `GET /admin/turnos` y `GET /admin/turnos/{id}` (`ROLE_VOLUNTEER`),
  `GET|POST /admin/turnos/nuevo` y `POST /admin/turnos/{id}/editar` (`ROLE_COORDINATOR`),
  `POST /admin/turnos/{id}/inscripcion` y `/baja` (`ROLE_VOLUNTEER`), con CSRF, 303 con mensaje de éxito / 422 / 409.
  Origen: D26, D27, D30; CA-07-02, CA-07-06, CA-07-08, CA-07-11.

## Interfaz

- [ ] **T711** · `shift/index.html.twig`: aviso de turnos faltos de voluntarios, tabla apilada en móvil con
  cobertura y estado («Faltan voluntarios: N para el mínimo», «Completo», «En curso», «Mínimo cubierto»), marca
  «Te has apuntado», botones de inscripción y baja, «Coincide con otro de tus turnos» sin botón en los turnos
  coincidentes, estado vacío y «Nuevo turno» para la coordinadora.
  Origen: CA-07-01 a CA-07-05, CA-07-08, CA-07-09; analyze A1, A3.
- [ ] **T712** · `shift/new.html.twig` y `shift/show.html.twig` (datos, personas inscritas, formulario de
  modificación), con errores por campo (`aria-invalid`/`aria-describedby`) y valores conservados; enlace «Turnos» en
  `base.html.twig`. Origen: CA-07-01, CA-07-06, CA-07-07.

## Verificación

- [ ] **T713** · `tests/Spec007Test.php`: recorrido — CA-07-01, CA-07-02, CA-07-03, CA-07-04 (incluida la ventana
  exacta y el cambio de hora con `MockClock`), CA-07-05 y CA-07-06 (incluido bajar el máximo hasta las
  inscripciones, que deja el turno «Completo»). Origen: analyze A2.
- [ ] **T714** · `tests/Spec007Test.php`: entradas inválidas y estados — CA-07-07 (incluida la hora repetida de
  octubre, que se interpreta como la primera), CA-07-08 (baja que devuelve el destacado; baja sin figurar; turno
  empezado) y CA-07-09 (doble inscripción, coincidente y contiguo). Origen: analyze A2.
- [ ] **T715** · `tests/Support/shift-race.php` y pruebas CA-07-10: dos inscripciones simultáneas en la última plaza,
  dos inscripciones simultáneas de una cuenta en turnos coincidentes y una bajada del máximo simultánea a una
  inscripción. La prueba de la plaza debe fallar si se quita el bloqueo del turno.
- [ ] **T716** · Pruebas CA-07-11: anónimo → `/acceso`; solicitante, familia de acogida y veterinario → 403;
  voluntario → 403 al crear o modificar; CSRF inválido sin cambios; nombre con etiquetas HTML escapado en la ficha.
  En todos los casos no cambia ningún dato.
- [ ] **T717** · Regresión completa (`vendor/bin/phpunit --testdox`), `doctrine:schema:validate` y linters del CI en
  una base `_test` nueva; registrar la salida en `docs/evidencias/spec-007-phpunit.txt`.
- [ ] **T718** · Verificación manual en escritorio y móvil (≤ 400 px) y por teclado: recorrido coordinadora →
  voluntario (destacado y desaparición) y recorrido con error (turno completo o coincidente). Registrar
  precondiciones, datos, pasos y resultado esperado y observado.

## Cierre

- [ ] **T719** · Redactar `implement.md` y `converge.md`; añadir aquí las tareas correctivas que salgan de converge
  sin borrar las anteriores.
- [ ] **T720** · Abrir la PR hacia `main` con la plantilla, señalando el cambio en la cabecera común y
  `data-model.md`, y pedir la revisión de `abcb5-ua`.
