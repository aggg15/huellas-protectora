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
- [ ] **T504** · Ejecutar analyze sobre spec, plan y tasks; conservar el informe en `analyze.md` antes de corregir hallazgos.
  Terminada cuando: no quedan criterios sin tarea ni tareas sin origen, o los hallazgos están registrados y resueltos.

## Modelo y persistencia

- [ ] **T505** · Crear las entidades `Adoption` y `FollowUp` con sus relaciones, la copia del contrato y los
  índices únicos declarados en el mapeo (animal, solicitud, número de contrato; adopción + kind).
  Origen: D13, D16, D17; CA-05-01, CA-05-05, CA-05-10.
- [ ] **T506** · Añadir el estado `formalized` y su etiqueta «Adopción formalizada» a `AdoptionApplication`,
  sin incluirlo en `OPEN_STATUSES` ni en `UNDECIDED_STATUSES`. Origen: D07, D15; CA-05-08.
- [ ] **T507** · Migración `Version20261118230000`: tablas nuevas, claves ajenas, únicos, CHECK de cuota,
  `kind` y `status`, y sustitución del CHECK `adoption_application_status`. `down()` reversible.
  Terminada cuando: `doctrine:migrations:migrate` y `doctrine:schema:validate` pasan en una base `_test` nueva.
  Origen: D14, D15, D16; obligación técnica del plan.

## Reglas de negocio

- [ ] **T508** · `FollowUpCalendar::dueDate`: meses naturales sobre el día de Europe/Madrid, último día
  del mes si el día no existe, sin `modify('+N month')`. Origen: D16; CA-05-03, CA-05-09.
- [ ] **T509** · Validar la cuota en `AdoptionService`: formato `^\d{1,4}([.,]\d{1,2})?$`, conversión a
  céntimos sin coma flotante, mayor que 0, y confirmación `feePaid`. Errores por campo con `ValidationError` (422).
  Origen: D14; CA-05-07.
- [ ] **T510** · `AdoptionService::formalize` en una transacción: bloquear animal y solicitud, releer sus
  estados, crear adopción y dos seguimientos, solicitud a `formalized`, animal a `adopted` con
  `fostered = false`, cancelar las demás abiertas con el motivo «El animal ha sido adoptado», y convertir
  una violación de único en `ApplicationRefused` (409).
  Origen: D12, D15, D17; CA-05-01, CA-05-02, CA-05-06, CA-05-08, CA-05-10.
- [ ] **T511** · `AdoptionService::findByAnimal(Animal)`: devuelve la adopción única o `null`. Origen: D18; CA-05-05.
- [ ] **T512** · *Coordinar HU-04.* `ApplicationService::cancelReservation`: bloquear y releer la solicitud
  tras el bloqueo del animal; si ya no está aprobada o el animal no está reservado, `ApplicationRefused`
  sin cambios. Se conserva la validación previa (422).
  Terminada cuando: Spec004 pasa sin modificar sus pruebas. Origen: D17; CA-05-10.

## Controladores y permisos

- [ ] **T513** · `AdoptionController`: `POST /admin/solicitudes/{id}/formalizar` (CSRF `formalize-{id}`, 303 / 422 / 409),
  `GET /admin/adopciones/{id}` y `GET /admin/seguimientos`, todas con `ROLE_COORDINATOR`.
  Origen: D12, D16; CA-05-03, CA-05-11.
- [ ] **T514** · `GET /adopciones/{id}/contrato` con `ROLE_USER`: solo la coordinadora o el adoptante titular;
  cualquier otra cuenta recibe 404. Origen: D13; CA-05-01, CA-05-11.
- [ ] **T515** · *Coordinar HU-04.* `ApplicationReviewController`: `show()` acepta un código de estado opcional
  (por defecto 422) y `cancelReservation` captura `ApplicationRefused`. Origen: plan, ajuste de HU-04; CA-05-10.

## Interfaz

- [ ] **T516** · *Coordinar HU-04.* `application/admin_show.html.twig`: formulario «Formalizar entrega» en
  solicitudes aprobadas (importe, casilla de abono, errores por campo, `aria-invalid`) y bloque `formalized`
  sin acciones y con enlaces a la adopción y al contrato. Origen: CA-05-01, CA-05-07, CA-05-08.
- [ ] **T517** · Vistas `adoption/show.html.twig` (entrega, cuota `120,00 €`, seguimientos) y
  `adoption/follow_ups.html.twig` (ordenado por fecha, estado vacío). Origen: CA-05-02, CA-05-03.
- [ ] **T518** · `adoption/contract.html.twig` con la copia del contrato y las fechas de seguimiento,
  controlador Stimulus `print` y estilos `@media print`. Sin afirmar validez jurídica. Origen: D13; CA-05-01.
- [ ] **T519** · `application/mine.html.twig`: enlace al contrato en «Adopción formalizada» y motivo en las
  canceladas por adopción; enlace «Seguimientos» en `base.html.twig` para la coordinadora. Origen: CA-05-08, D13.

## Verificación

- [ ] **T520** · `tests/Spec005Test.php`: recorrido y fechas — CA-05-01, CA-05-02 (incluida acogida),
  CA-05-03 con `MockClock` (10-mar-2027), CA-05-04 (catálogo, ficha y fotos) y CA-05-05.
- [ ] **T521** · `tests/Spec005Test.php`: entradas inválidas y estados — CA-05-06 (pending, waiting, rejected,
  cancelled, formalized), CA-05-07 (vacío, 0, −5, abc, 12,345, 10000; válidos 120,5 y 120.50), CA-05-08
  (huecos libres en el máximo de dos, sin «Liberar reserva») y CA-05-09 (31-ago-2027, 31-ene-2027, 23:30 UTC del 31-mar).
- [ ] **T522** · `tests/Support/adoption-race.php` y pruebas CA-05-10: dos formalizaciones simultáneas y
  formalización + liberación simultáneas; estado final coherente. La prueba debe fallar si se quita el bloqueo.
- [ ] **T523** · Pruebas CA-05-11: anónimo, solicitante, voluntario y veterinario no formalizan ni listan
  seguimientos; contrato ajeno 404; CSRF inválido sin cambios.
- [ ] **T524** · Regresión completa (`vendor/bin/phpunit --testdox`), `doctrine:schema:validate` y linters del CI
  en una base `_test` nueva; registrar la salida en `docs/evidencias/spec-005-phpunit.txt`.
- [ ] **T525** · Verificación manual HU-03 → HU-04 → HU-05 en escritorio y móvil (≤ 400 px) y por teclado,
  más un recorrido con error (formalizar sin confirmar el abono). Registrar precondiciones, datos, pasos y
  resultado esperado y observado.

## Cierre

- [ ] **T526** · Redactar `implement.md` y `converge.md`; añadir aquí las tareas correctivas que salgan de converge
  sin borrar las anteriores.
- [ ] **T527** · Abrir la PR hacia `main` con la plantilla, señalando los cambios en código de HU-04
  (T512, T515, T516), el CHECK común y `data-model.md`, y pedir la revisión de `abcb5-ua`.
