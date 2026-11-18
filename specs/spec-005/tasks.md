# Tareas spec-005

Historia: HU-05 · Formalizar la adopción y programar los seguimientos.
Responsable: `aggg15`. Revisor: `abcb5-ua` (y responsable de las tareas marcadas «coordinar HU-04»).
Fuentes: [spec](spec.md), [clarify](clarify.md), [plan](plan.md). Fecha: 2026-11-18.
Desarrollo asistido por Claude Code; no se ha ejecutado la skill `$speckit-tasks`.

Cada tarea indica su origen (criterio o decisión) y cómo se da por terminada. Una tarea marcada no
sustituye la evidencia: el cierre se acredita con `docs/evidencias/spec-005-phpunit.txt`.

## Especificación y planificación

- [x] **T501** · Revisar HU-01 a HU-04 y corregir la documentación desfasada antes de especificar.
  Origen: Constitution II/IV. Hecho en b001d3f; evidencia `docs/evidencias/revision-previa-spec-005-2026-11-18.txt`.
- [x] **T502** · Especificar y aclarar HU-05: D12–D18, CA-05-06 a CA-05-11, reglas y casos límite.
  Origen: ficha inicial, preguntas 1–3. Hecho en 366ba6e.
- [x] **T503** · Redactar `plan.md` y actualizar `data-model.md`. Origen: enunciado §3 (plan). Hecho en 358b48f.
- [x] **T504** · Ejecutar analyze sobre spec, plan y tasks; conservar el informe en `analyze.md` antes de corregir hallazgos.
  Terminada cuando: no quedan criterios sin tarea ni tareas sin origen, o los hallazgos están registrados y resueltos.

## Modelo y persistencia

- [x] **T505** · Crear las entidades `Adoption` y `FollowUp` con sus relaciones, la copia del contrato y los
  índices únicos declarados en el mapeo (animal, solicitud, número de contrato; adopción + kind).
  Origen: D13, D16, D17; CA-05-01, CA-05-05, CA-05-10.
- [x] **T506** · Añadir el estado `formalized` y su etiqueta «Adopción formalizada» a `AdoptionApplication`,
  sin incluirlo en `OPEN_STATUSES` ni en `UNDECIDED_STATUSES`. Origen: D07, D15; CA-05-08.
- [x] **T507** · Migración `Version20261118230000`: tablas nuevas, claves ajenas, únicos, CHECK de cuota,
  `kind` y `status`, y sustitución del CHECK `adoption_application_status`. `down()` reversible.
  Terminada cuando: `doctrine:migrations:migrate` y `doctrine:schema:validate` pasan en una base `_test` nueva.
  Origen: D14, D15, D16; obligación técnica del plan.

## Reglas de negocio

- [x] **T508** · `FollowUpCalendar::dueDate`: meses naturales sobre el día de Europe/Madrid, último día
  del mes si el día no existe, sin `modify('+N month')`. Origen: D16; CA-05-03, CA-05-09.
- [x] **T509** · Validar la cuota en `AdoptionService`: formato `^\d{1,4}([.,]\d{1,2})?$`, conversión a
  céntimos sin coma flotante, mayor que 0, y confirmación `feePaid`. Errores por campo con `ValidationError` (422).
  Origen: D14; CA-05-07.
- [x] **T510** · `AdoptionService::formalize` en una transacción: bloquear animal y solicitud, releer sus
  estados, crear adopción y dos seguimientos, solicitud a `formalized`, animal a `adopted` con
  `fostered = false`, cancelar las demás abiertas con el motivo «El animal ha sido adoptado», y convertir
  una violación de único en `ApplicationRefused` (409).
  Origen: D12, D15, D17; CA-05-01, CA-05-02, CA-05-06, CA-05-08, CA-05-10.
- [x] **T511** · `AdoptionService::findByAnimal(Animal)`: devuelve la adopción única o `null`. Origen: D18; CA-05-05.
- [x] **T512** · *Coordinar HU-04.* `ApplicationService::cancelReservation`: bloquear y releer la solicitud
  tras el bloqueo del animal; si ya no está aprobada o el animal no está reservado, `ApplicationRefused`
  sin cambios. Se conserva la validación previa (422).
  Terminada cuando: Spec004 pasa sin modificar sus pruebas. Origen: D17; CA-05-10.

## Controladores y permisos

- [x] **T513** · `ApplicationReviewController::formalize`: `POST /admin/solicitudes/{id}/formalizar` (CSRF
  `formalize-{id}`, 303 a la adopción / 422 / 409 sobre la ficha de la solicitud). `AdoptionController`:
  `GET /admin/adopciones/{id}` y `GET /admin/seguimientos`. Todas con `ROLE_COORDINATOR`.
  Origen: D12, D16; CA-05-03, CA-05-11; analyze A1.
- [x] **T514** · `GET /adopciones/{id}/contrato` con `ROLE_USER`: solo la coordinadora o el adoptante titular;
  cualquier otra cuenta recibe 404. Origen: D13; CA-05-01, CA-05-11.
- [x] **T515** · *Coordinar HU-04.* `ApplicationReviewController`: `show()` acepta un código de estado opcional
  (por defecto 422) y `cancelReservation` captura `ApplicationRefused`. Origen: plan, ajuste de HU-04; CA-05-10.

## Interfaz

- [x] **T516** · *Coordinar HU-04.* `application/admin_show.html.twig`: formulario «Formalizar entrega» en
  solicitudes aprobadas (importe, casilla de abono, errores por campo, `aria-invalid`), bloque `formalized`
  sin acciones y con enlaces a la adopción y al contrato, y `formalized` en el mapa de clases de estado.
  Origen: CA-05-01, CA-05-07, CA-05-08; analyze A5.
- [x] **T517** · Vistas `adoption/show.html.twig` (entrega, cuota `120,00 €`, seguimientos) y
  `adoption/follow_ups.html.twig` (ordenado por fecha, estado vacío). Origen: CA-05-02, CA-05-03.
- [x] **T518** · `adoption/contract.html.twig` con la copia del contrato y las fechas de seguimiento,
  controlador Stimulus `print` y estilos `@media print`. Sin afirmar validez jurídica. Origen: D13; CA-05-01.
- [x] **T519** · *Coordinar HU-04.* `application/mine.html.twig`: enlace al contrato en «Adopción formalizada»
  y motivo en toda solicitud cancelada (por adopción o por D11), que hoy aparece como pendiente; enlace
  «Seguimientos» en `base.html.twig` para la coordinadora. Origen: CA-05-08, D11, D13; analyze A2.

## Verificación

- [x] **T520** · `tests/Spec005Test.php`: recorrido y fechas — CA-05-01, CA-05-02 (incluida acogida),
  CA-05-03 con `MockClock` (10-mar-2027), CA-05-04 (catálogo, ficha y fotos) y CA-05-05.
- [x] **T521** · `tests/Spec005Test.php`: entradas inválidas y estados — CA-05-06 (pending, waiting, rejected,
  cancelled, formalized), CA-05-07 (vacío, 0, −5, abc, 12,345, 10000; válidos 120,5 y 120.50), CA-05-08
  (huecos libres en el máximo de dos, sin «Liberar reserva») y CA-05-09 (31-ago-2027, 31-ene-2027, 23:30 UTC del 31-mar).
- [x] **T522** · `tests/Support/adoption-race.php` y pruebas CA-05-10: dos formalizaciones simultáneas y
  formalización + liberación simultáneas; estado final coherente. La prueba debe fallar si se quita el bloqueo.
- [x] **T523** · Pruebas CA-05-11: la persona anónima es redirigida a `/acceso`; solicitante, voluntario y
  veterinario reciben 403 al formalizar o listar seguimientos; contrato ajeno 404; CSRF inválido sin cambios.
  En todos los casos no cambia ningún dato. Origen: CA-05-11; analyze A3.
- [x] **T524** · Regresión completa (`vendor/bin/phpunit --testdox`), `doctrine:schema:validate` y linters del CI
  en una base `_test` nueva; registrar la salida en `docs/evidencias/spec-005-phpunit.txt`.
- [x] **T525** · Verificación manual HU-03 → HU-04 → HU-05 en escritorio y móvil (≤ 400 px) y por teclado,
  más un recorrido con error (formalizar sin confirmar el abono). Registrar precondiciones, datos, pasos y
  resultado esperado y observado.

## Cierre

- [x] **T526** · Redactar `implement.md` y `converge.md`; añadir aquí las tareas correctivas que salgan de converge
  sin borrar las anteriores.
- [ ] **T527** · Abrir la PR hacia `main` con la plantilla, señalando los cambios en código de HU-04
  (T512, T515, T516), el CHECK común y `data-model.md`, y pedir la revisión de `abcb5-ua`.

## Ejecución del 2026-11-18

Implementación de T505–T524 asistida por Claude Code a petición de `aggg15`; revisión de `abcb5-ua` pendiente.
Detalle de componentes en [implement](implement.md).

- T505–T507 → `Adoption`, `FollowUp`, estado `formalized`, migración `Version20261118230000` (up/down/up
  comprobados; esquema sincronizado).
- T508–T511 → `FollowUpCalendar`, `AdoptionService` (`validate`, `formalize`, `findByAnimal`).
- T512, T515, T516, T519 → cambios en código de HU-04, a revisar por `abcb5-ua`. Spec004 pasa sin modificar sus pruebas.
- T513–T514, T517–T518 → `ApplicationReviewController::formalize`, `AdoptionController` y vistas `adoption/*`.
- T520–T523 → `tests/Spec005Test.php` (18 pruebas) y `tests/Support/adoption-race.php`. La prueba de
  liberación concurrente falla con el código anterior a T512 y pasa con él.
- T524 → suite completa: 57 pruebas / 484 aserciones; [evidencia](../../docs/evidencias/spec-005-phpunit.txt).

Pendientes: T525 (verificación manual), T526 (converge.md; implement.md ya redactado) y T527 (PR).

## Ejecución del 2026-11-19

- T525 → recorridos R1 (satisfactorio) y R2 (error y teclado) en Chrome real, en escritorio y a 390 px:
  20/20 comprobaciones correctas, con dos observaciones visuales.
  Véase ../../docs/evidencias/spec-005-manual/README.md.
- T526 → [converge](converge.md): 3 hallazgos (C1 MEDIUM, C2 y C3 LOW), añadidos debajo como T528–T530.

## Phase 8: Convergence

Añadida por converge el 2026-11-19 ([informe](converge.md)). Las secciones anteriores se cuentan como
fases 1 a 7. Responsable: `aggg15`; revisor: `abcb5-ua`.

- [x] T528 Presentar «Mis solicitudes» en móvil (≤ 760 px) sin desplazamiento interno de la tabla, con estado, motivo y enlace al contrato visibles; comprobarlo con una captura a 390 px. *Coordinar HU-03/04* per CA-05-08, Constitution V (partial)
- [x] T529 Añadir una prueba que cambie el nombre y el email de la cuenta y los datos del animal después de formalizar y compruebe que el contrato muestra los datos de la entrega per R3 / D13 (partial)
- [x] T530 Eliminar `AdoptionApplication::isFormalized()`, que no se usa, o justificar su uso per plan: estado formalized (unrequested)
