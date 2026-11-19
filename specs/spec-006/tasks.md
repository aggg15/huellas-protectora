# Tareas spec-006

Historia: HU-06 · Gestionar casas de acogida y la evolución del animal.
Responsable: `abcb5-ua`. Revisor: `aggg15` (y responsable de las tareas marcadas «coordinar HU-01/HU-05»).
Fuentes: [spec](spec.md), [clarify](clarify.md), [plan](plan.md). Fecha: 2026-11-19.
Desarrollo asistido por Claude Code; no se ha ejecutado la skill `$speckit-tasks`.

Cada tarea indica su origen (criterio o decisión) y cómo se da por terminada. Una tarea marcada no
sustituye la evidencia: el cierre se acredita con `docs/evidencias/spec-006-phpunit.txt`.

## Especificación y planificación

- [x] **T601** · Revisar HU-01 a HU-05 integradas y actualizar el estado desfasado (AGENTS, agrupamiento,
  plan común) antes de especificar. Origen: Constitution II/IV. Línea base: suite completa 58/58 en una base
  `_test` nueva (véase la evidencia).
- [x] **T602** · Especificar y aclarar HU-06: D19–D25, CA-06-06 a CA-06-10, reglas R1–R11 y casos límite.
  Origen: ficha inicial, preguntas 1–3.
- [x] **T603** · Redactar `plan.md` y actualizar `data-model.md` y el plan común. Origen: enunciado §3 (plan).
- [x] **T604** · Ejecutar analyze sobre spec, plan y tasks; conservar el informe en `analyze.md` antes de corregir hallazgos.
  Terminada cuando: no quedan criterios sin tarea ni tareas sin origen, o los hallazgos están registrados y resueltos.

## Modelo y persistencia

- [x] **T605** · Crear las entidades `FosterHome`, `FosterAvailability`, `FosterPlacement` y `FosterNote`
  con sus relaciones, el único de la familia y el índice parcial `uniq_foster_active_animal` declarado en el mapeo.
  Origen: D19–D21, D24; CA-06-01, CA-06-02, CA-06-05, CA-06-09.
- [x] **T606** · Migración `Version20261119120000`: tablas, claves ajenas, únicos, índice parcial y CHECK de
  capacidad, fechas, motivo de fin y texto de la novedad. `down()` reversible.
  Terminada cuando: `doctrine:migrations:migrate` (up/down/up) y `doctrine:schema:validate` pasan en una base `_test` nueva.
  Origen: D20–D24; obligación técnica del plan.

## Reglas de negocio

- [x] **T607** · `AvailabilityCalendar`: `madridDay` y `covers` (une periodos solapados o consecutivos;
  compara días sin zonas horarias). Origen: D20, D21; CA-06-02, CA-06-03.
- [x] **T608** · `FosterService::saveCapacity`, `addPeriod` y `removePeriod`: validación 422 (capacidad, fechas,
  solapes) y reglas 409 bajo bloqueo de la casa (capacidad ≥ acogidas activas; disponibilidad necesaria).
  Origen: D20, D23; CA-06-01, CA-06-06, CA-06-07.
- [x] **T609** · `FosterService::place`: validación 422; transacción con bloqueo de animal y casa y relectura;
  rechazo 409 de animal adoptado, animal en acogida, casa no disponible o completa; acogida activa y
  `fostered = true`; violación del índice parcial → `FosterRefused`.
  Origen: D21, D25; CA-06-02, CA-06-03, CA-06-04, CA-06-09.
- [x] **T610** · `FosterService::end` (fin hoy, motivo `returned`, `fostered = false`, 409 si ya finalizada) y
  `addNote` (1–2000 caracteres, 409 si la acogida ha finalizado); consultas `homeOf`, `activePlacementOf`,
  `historyOf`, `activePlacementsOf`, `homesOverview` y «fin previsto superado».
  Origen: D22, D24; CA-06-05, CA-06-08.
- [x] **T611** · *Coordinar HU-05.* `AdoptionService::formalize`: finalizar la acogida activa con el día de
  Madrid de la entrega y motivo `adopted`, en la misma transacción.
  Terminada cuando: Spec005 pasa sin modificar sus pruebas. Origen: D25; CA-06-04, CA-06-09.

## Controladores y permisos

- [x] **T612** · `FosterController` (`ROLE_FOSTER`): `GET /acogida`, `POST /acogida/capacidad`,
  `POST /acogida/periodos`, `POST /acogida/periodos/{id}/eliminar` y `POST /acogida/acogidas/{id}/novedades`,
  con CSRF, 303 con mensaje de éxito / 422 / 409 y 404 para periodos o acogidas de otra casa.
  Origen: D19, D23, D24; CA-06-01, CA-06-05, CA-06-10; analyze A4.
- [x] **T613** · `FosterAdminController` (`ROLE_COORDINATOR`): `GET /admin/acogidas`,
  `POST /admin/animales/{id}/acogida` y `POST /admin/acogidas/{id}/finalizar`, con CSRF y mensaje de éxito;
  errores reenviados a la ficha del animal con 422 o 409. Origen: D21, D22; CA-06-02, CA-06-03, CA-06-08, CA-06-10; analyze A4.
- [x] **T614** · *Coordinar HU-01.* `AnimalController::show` (argumentos opcionales `fosterErrors` y
  `fosterData`, datos de acogida), `SecurityController::dashboard` (familia → `/acogida`) y `CreateUserCommand`
  (`ROLE_FOSTER`). Terminada cuando: Spec001 pasa sin modificar sus pruebas. Origen: D19; CA-06-02, CA-06-05.

## Interfaz

- [x] **T615** · `foster/home.html.twig`: capacidad y ocupación, periodos (tabla apilada en móvil, «Retirar»,
  alta; terminados al final con «Terminado»), animales acogidos con «Fin previsto superado», novedades y
  «Anotar novedad»; errores por campo con `aria-invalid`/`aria-describedby` y estados vacíos.
  Origen: CA-06-01, CA-06-05, CA-06-06, CA-06-08; analyze A2.
- [x] **T616** · `foster/admin_index.html.twig` (casas con periodos vigentes y acogidas activas, apiladas en móvil,
  estados vacíos) y enlaces «Mi acogida» y «Acogidas» en `base.html.twig`. Origen: CA-06-01, CA-06-08; analyze A2.
- [x] **T617** · *Coordinar HU-01.* `foster/_animal_panel.html.twig` incluido en `animal/show.html.twig`:
  acogida activa y «Finalizar acogida», formulario «Registrar acogida», historial de acogidas y novedades.
  Origen: CA-06-02, CA-06-03, CA-06-05, CA-06-08.

## Verificación

- [x] **T618** · `tests/Spec006Test.php`: recorrido — CA-06-01, CA-06-02 (incluido el día de Madrid con
  `MockClock`), CA-06-03 (fuera de periodo, inicio futuro, hueco, completa, consecutivos), CA-06-04 (catálogo
  y ficha pública, cuarentena, reserva y su liberación, adopción) y CA-06-05.
- [x] **T619** · `tests/Spec006Test.php`: entradas inválidas y estados — CA-06-06, CA-06-07 y CA-06-08
  (incluido «fin previsto superado» y doble finalización).
- [x] **T620** · `tests/Support/foster-race.php` y pruebas CA-06-09: dos acogidas simultáneas en una casa de
  una plaza, dos acogidas simultáneas del mismo animal, y adopción y acogida simultáneas. La prueba de la plaza
  debe fallar si se quita el bloqueo de la casa.
- [x] **T621** · Pruebas CA-06-10: anónimo → `/acceso`; solicitante, voluntario y veterinario → 403 en las rutas
  de la coordinadora; cuentas sin `ROLE_FOSTER` → 403 en las de la familia; otra familia → 404; novedades
  ausentes de la web pública; una novedad con `<script>` se muestra escapada en la ficha interna; CSRF inválido
  sin cambios. En todos los casos no cambia ningún dato. Origen: CA-06-10; analyze A5.
- [x] **T622** · Regresión completa (`vendor/bin/phpunit --testdox`), `doctrine:schema:validate` y linters del CI
  en una base `_test` nueva; registrar la salida en `docs/evidencias/spec-006-phpunit.txt`.
- [x] **T623** · Verificación manual en escritorio y móvil (≤ 400 px) y por teclado: recorrido familia →
  coordinadora → web pública → novedad, y recorrido con error (casa completa). Registrar precondiciones,
  datos, pasos y resultado esperado y observado.

## Cierre

- [x] **T624** · Redactar `implement.md` y `converge.md`; añadir aquí las tareas correctivas que salgan de converge
  sin borrar las anteriores.
- [ ] **T625** · Abrir la PR hacia `main` con la plantilla, señalando los cambios en código de HU-01 (T614, T617)
  y HU-05 (T611) y `data-model.md`, y pedir la revisión de `aggg15`.

## Ejecución del 2026-11-19

Implementación de T605–T623 asistida por Claude Code a petición de `abcb5-ua`; revisión de `aggg15` pendiente.
Detalle de componentes en [implement](implement.md).

- T605–T606 → entidades `FosterHome`, `FosterAvailability`, `FosterPlacement`, `FosterNote` y migración
  `Version20261119120000` (up/down/up comprobados; esquema sincronizado con el índice parcial).
- T607–T610 → `AvailabilityCalendar`, `FosterService` y `FosterRefused`.
- T611 → cambio en `AdoptionService::formalize` (HU-05, a revisar por `aggg15`). Spec005 pasa sin modificar sus pruebas.
- T612–T613 → `FosterController` y `FosterAdminController`.
- T614, T617 → cambios en código de HU-01 (a revisar por `aggg15`). Spec001 pasa sin modificar sus pruebas.
- T615–T616 → vistas `foster/*` y enlaces de la cabecera.
- T618–T621 → `tests/Spec006Test.php` (23 pruebas) y `tests/Support/foster-race.php`. Las pruebas de capacidad
  concurrente y de adopción fallan con las mutaciones descritas en la evidencia y pasan con el código.
- T622 → suite completa: 81 pruebas / 778 aserciones; [evidencia](../../docs/evidencias/spec-006-phpunit.txt).
- T623 → recorridos R1 y R2 en Chrome real, escritorio y 390 px, con teclado: 17/17 comprobaciones correctas y una
  observación visual. Véase ../../docs/evidencias/spec-006-manual/README.md.

- T624 → [implement](implement.md) y [converge](converge.md): 2 hallazgos (C1 MEDIUM, C2 LOW), añadidos debajo como T626–T627.

Pendiente: T625 (PR).

## Phase 8: Convergence

Añadida por converge el 2026-11-19 ([informe](converge.md)). Las secciones anteriores se cuentan como
fases 1 a 7. Responsable: `abcb5-ua`; revisor: `aggg15`.

- [x] T626 Ajustar `foster/home.html.twig` para que a 390 px ninguna tarjeta supere el ancho de la ventana (campos de fecha del alta de periodos apilados en móvil) y comprobarlo midiendo la posición de las tarjetas en Chrome per Constitution V, plan «Vistas» (partial)
- [x] T627 Eliminar el parámetro sin uso `$coordinator` de `FosterService::end` y de sus llamadas per plan: firmas de `FosterService` (partial)

