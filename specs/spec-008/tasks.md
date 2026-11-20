# Tareas spec-008

Historia: HU-08 · Apadrinar un animal y recibir sus novedades.
Responsable: `abcb5-ua`. Revisor: `aggg15`.
Fuentes: [spec](spec.md), [clarify](clarify.md), [plan](plan.md). Fecha: 2026-11-19.
Desarrollo asistido por Claude Code; no se ha ejecutado la skill `$speckit-tasks`.

Cada tarea indica su origen (criterio o decisión) y cómo se da por terminada. Una tarea marcada no
sustituye la evidencia: el cierre se acredita con `docs/evidencias/spec-008-phpunit.txt` y
`docs/evidencias/spec-008-manual/README.md`.

## Especificación y planificación

- [x] **T801** · Revisar HU-01 a HU-07 integradas, comprobar spec-007 y actualizar el estado desfasado (AGENTS, README,
  CONTRIBUTING, agrupamiento y estado de spec-007) antes de especificar, sin atribuir revisiones que no constan.
  Origen: Constitution II/IV. Línea base: suite completa en una base `_test` nueva (véase la evidencia).
- [x] **T802** · Especificar y aclarar HU-08: D34–D41, CA-08-06 a CA-08-10, reglas R1–R10 y casos límite.
  Origen: ficha inicial, preguntas 1–3.
- [x] **T803** · Redactar `plan.md` y actualizar `data-model.md`, el plan común y las decisiones. Origen: enunciado §3 (plan).
- [x] **T804** · Ejecutar analyze sobre spec, plan y tasks; conservar el informe en `analyze.md` antes de corregir
  hallazgos. Terminada cuando: no quedan criterios sin tarea ni tareas sin origen, o los hallazgos están
  registrados y resueltos.

## Modelo y persistencia

- [x] **T805** · Crear las entidades `Sponsorship` (importe en céntimos, inicio, fin, motivo y anterior) y
  `SponsorUpdate`, con el único parcial `uniq_sponsorship_active` y el índice (animal, fecha) declarados en el mapeo.
  Origen: D35, D36, D37, D39; CA-08-01, CA-08-06.
- [x] **T806** · Migración `Version20261119190000`: tablas, claves ajenas, único parcial, índices y CHECK de importe, fin
  y motivo, fechas y texto. `down()` reversible. Terminada cuando: `doctrine:migrations:migrate` (up/down/up) y
  `doctrine:schema:validate` pasan en una base `_test` nueva. Origen: D36, D37, D39; obligación técnica del plan.

## Reglas de negocio

- [x] **T807** · `SponsorshipService::amountCents` y `sponsor`: importe de 10,00 € a 9 999,99 € (422); cuentas de trabajo,
  animal no disponible o ya apadrinado (409) bajo bloqueo cuenta → animal; violación del único → `SponsorshipRefused`.
  Origen: D34, D35, D36, D41; CA-08-01, CA-08-02, CA-08-06, CA-08-07.
- [x] **T808** · `finish` y `moveTo`: bloqueo cuenta → apadrinamiento → animal elegido y relectura; rechazos 409 (ajeno,
  terminado, animal no adoptado, mismo animal, no disponible, ya apadrinado); el cambio termina el anterior (`moved`) y
  crea el nuevo con el mismo importe y `previous` en una transacción. Origen: D38, D39, D41; CA-08-05, CA-08-08, CA-08-09.
- [x] **T809** · `addUpdate` (422 de 1 a 2000 caracteres; 409 sin padrinos activos, bajo bloqueo del animal) y consultas
  `mine`, `state`, `updatesFor`, `availableFor` y `overview`. Origen: D37, D38, D40; CA-08-03, CA-08-04.

## Controladores y permisos

- [x] **T810** · `SponsorshipController`: `GET|POST /animales/{id}/apadrinar`, `GET /mis-apadrinamientos`,
  `POST /mis-apadrinamientos/{id}/terminar` y `/cambiar`, con `ROLE_USER`, 403 para personal y familias, 404 para
  apadrinamientos ajenos y animales no públicos, CSRF y 303 / 422 / 409. Origen: D34, D35, D39; CA-08-01, CA-08-05,
  CA-08-07, CA-08-08, CA-08-10.
- [x] **T811** · `SponsorshipAdminController`: `GET /admin/apadrinamientos` y `POST /admin/apadrinamientos/{id}/novedades`
  (`ROLE_COORDINATOR`, CSRF, 303 / 422 / 409). Origen: D37, D40; CA-08-03, CA-08-10.

## Interfaz

- [x] **T812** · `sponsorship/new.html.twig` y recuadro «¿Prefieres apadrinarlo?» en `public/show.html.twig` (oculto para
  personal y familias); campo etiquetado con errores accesibles y valor conservado. Origen: CA-08-01, CA-08-02, CA-08-10.
- [x] **T813** · `sponsorship/mine.html.twig` (estados, importe, fechas, motivo, decisión pendiente con selector y fin,
  novedades del periodo, estado vacío) y `sponsorship/admin_index.html.twig` (padrinos por animal, «En pausa», formulario y
  novedades, estado vacío); enlaces en `base.html.twig`. Origen: CA-08-03, CA-08-04, CA-08-05.

## Verificación

- [x] **T814** · `tests/Spec008Test.php`: recorrido — CA-08-01 a CA-08-05 (incluida la formalización real de HU-05, la
  pausa y que la adopción no termina ni crea apadrinamientos). Origen: plan «Trazabilidad».
- [x] **T815** · `tests/Spec008Test.php`: entradas inválidas y estados — CA-08-02 (formatos), CA-08-03 (validación, periodo y
  sin destinatarios), CA-08-06, CA-08-07 y CA-08-08. Origen: plan «Trazabilidad».
- [x] **T816** · `tests/Support/sponsorship-race.php` y pruebas CA-08-09: dos altas simultáneas de la misma cuenta y animal;
  dos cambios y un fin simultáneos del mismo apadrinamiento. La segunda debe fallar si se quitan los bloqueos.
- [x] **T817** · Pruebas CA-08-10: anónimo → `/acceso`; voluntario, veterinario, coordinadora y familia → 403 (y el servicio
  rechaza sus cuentas); apadrinamiento ajeno → 404; solo la coordinadora publica; CSRF inválido sin cambios.
- [x] **T818** · Verificación manual en escritorio y móvil (≤ 400 px) y por teclado: recorrido madrina → coordinadora →
  adopción → elección, y recorrido con errores (importe, permisos y animal adoptado). Registrar precondiciones, datos,
  pasos y resultado esperado y observado.
- [x] **T819** · Regresión completa (`vendor/bin/phpunit --testdox`), migraciones up/down/up, `doctrine:schema:validate` y
  linters del CI en una base `_test` nueva; registrar la salida en `docs/evidencias/spec-008-phpunit.txt`.

## Cierre

- [x] **T820** · Redactar `implement.md` y `converge.md`; añadir aquí las tareas correctivas que salgan de converge
  sin borrar las anteriores.
- [x] **T821** · Abrir la PR hacia `main` con la plantilla, señalando los archivos comunes y `data-model.md`, y pedir la
  revisión de `aggg15`.

## Ejecución del 2026-11-19

Implementación de T805–T819 asistida por Claude Code a petición de `abcb5-ua`; revisión de `aggg15` pendiente.
Detalle de componentes en [implement](implement.md).

- T801 → línea base de `main` (5e379e6, con HU-07): 99 pruebas / 1039 aserciones; estado de AGENTS, README, CONTRIBUTING,
  agrupamiento y spec-007 actualizado tras la PR #11.
- T802–T804 → spec, clarify (D34–D41 elegidas por `abcb5-ua`), plan, tareas y [analyze](analyze.md) con tres hallazgos
  (A1–A3) corregidos.
- T805–T806 → entidades y migración `Version20261119190000` (generada con el diff de Doctrine y completada con los CHECK).
- T807–T809 → `SponsorshipService` y `SponsorshipRefused`.
- T810–T811 → `SponsorshipController` y `SponsorshipAdminController`.
- T812–T813 → vistas `sponsorship/*`, recuadro en la ficha pública, enlaces de la cabecera y estilos.
- T814–T817 → `tests/Spec008Test.php` (12 pruebas; 13 con T823) y `tests/Support/sponsorship-race.php`; las pruebas de concurrencia
  fallan con las dos mutaciones registradas en la evidencia.
- T818 → recorridos R1 y R2 en Chrome real, 390 px y 1280 px, con teclado: 17/17; véase
  ../../docs/evidencias/spec-008-manual/README.md.
- T819 → suite completa en una base `_test` nueva; [evidencia](../../docs/evidencias/spec-008-phpunit.txt).
- T820 → [implement](implement.md) y [converge](converge.md): dos hallazgos (C1 y C2, LOW), añadidos debajo como T822–T823.
- T821 → [PR #12](https://github.com/aggg15/huellas-protectora/pull/12), con revisión pedida a `aggg15` (pendiente).

## Phase 9: Convergence

Añadida por converge el 2026-11-19 ([informe](converge.md)). Las secciones anteriores se cuentan como
fases 1 a 8. Responsable: `abcb5-ua`; revisor: `aggg15`.

- [x] T822 Comprobar en `tests/Spec008Test.php` el 404 al cambiar un apadrinamiento ajeno (`/mis-apadrinamientos/{id}/cambiar`) per spec CA-08-10 (partial)
- [x] T823 Probar los estados vacíos de «Mis apadrinamientos» y de `/admin/apadrinamientos`, incluido que un apadrinamiento terminado deja de figurar, per Constitution V, plan «Vistas» (partial)
