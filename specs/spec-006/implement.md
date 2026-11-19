# Implement · spec-006

Fecha: 2026-11-19. Aplicado por Claude Code a petición de `abcb5-ua`. No se ha ejecutado la skill
`$speckit-implement`. Tareas: T605–T623 de [tasks.md](tasks.md), tras [analyze](analyze.md).

## Componentes

- **Entidades.** `FosterHome` (familia uno a uno, capacidad, periodos ordenados), `FosterAvailability` (días de
  inicio y fin; `isOver()` para los terminados), `FosterPlacement` (animal, casa, coordinadora, inicio, fin
  previsto, fin real y motivo; `isActive()`, `isOverdue()`; índice parcial `uniq_foster_active_animal` declarado
  en el mapeo) y `FosterNote` (acogida, autora, hora y texto). `Animal` y `User` no cambian.
- **Migración** `Version20261119120000`: cuatro tablas, claves ajenas, único de la familia, índice parcial y CHECK
  de capacidad, fechas de periodo y de acogida, coherencia de fin y motivo, y texto de la novedad. No modifica
  migraciones aplicadas. `down()` borra las cuatro tablas.
- **Servicios.**
  - `AvailabilityCalendar`: `madridDay()` y `covers()` (une periodos solapados o consecutivos; compara `Y-m-d`).
  - `FosterService`: `saveCapacity`, `addPeriod`, `removePeriod`, `place`, `end`, `addNote` y las consultas
    del plan (`homeOf`, `activePlacementOf`, `historyOf`, `activePlacementsOf`, `homesOverview`, `activePlacements`,
    `activeCount`). Validación con `ValidationError` (422) y reglas con `FosterRefused` (409), decididas con lecturas
    DBAL después de `FOR UPDATE` (animal → casa → acogida).
  - `AdoptionService::formalize` (HU-05, T611): cierra la acogida activa con el día de Madrid de la entrega y
    motivo `adopted`, y escribe `fostered = FALSE` por SQL, en la misma transacción.
- **Controladores.** `FosterController` (`ROLE_FOSTER`: página, capacidad, periodos, novedades; comprueba la
  propiedad antes del CSRF para responder 404 a otra familia) y `FosterAdminController` (`ROLE_COORDINATOR`:
  listado, registro y finalización; reenvía los errores a la ficha del animal con 422 o 409). En HU-01:
  `AnimalController::show` recibe `fosterErrors` y `fosterData` y pasa los datos de acogida;
  `SecurityController::dashboard` lleva a la familia a `/acogida`; `CreateUserCommand` admite `ROLE_FOSTER`.
- **Vistas.** `foster/home`, `foster/admin_index`, `foster/_animal_panel` (incluido en `animal/show`), enlaces
  «Mi acogida» y «Acogidas» en la cabecera y estilo `.foster-animal`. Tablas con `stacked-table` (T528 de HU-05).
- **Pruebas.** `tests/Spec006Test.php` (23 pruebas) y `tests/Support/foster-race.php`. El CI las detecta por su
  nombre sin cambios en `ci.yml`, que ya incluía la rama `spec-006`.

## Decisiones tomadas durante la implementación

- **Indicador `fostered` por SQL.** `place`, `end` y `formalize` escriben el indicador con una sentencia SQL, no
  solo en la entidad. Si la entidad en memoria ya tiene el valor que se asigna (página cargada antes de un cambio
  de otra sesión), Doctrine no detecta cambio y no escribe nada. La prueba de carrera adopción/acogida lo
  demuestra: sin la sentencia queda un animal adoptado con `fostered = true` (véase la evidencia, mutación 3).
  El código anterior de HU-05 (`$animal->fostered = false`) tenía el mismo límite, sin efecto mientras nadie
  ponía el indicador a verdadero; se señala en la PR para `aggg15`.
- **Orden de comprobaciones en las rutas de la familia.** Primero la propiedad del periodo o la acogida (404) y
  después el CSRF, para que otra familia no distinga recursos ajenos aunque no tenga token (CA-06-10).
- **Errores tras un rechazo.** `wrapInTransaction` cierra el EntityManager al fallar, pero las páginas de error
  solo leen y los rechazos se lanzan antes de modificar entidades, así que se muestran sin reiniciarlo.
- **Pruebas sobre un único EntityManager.** Tras una petición HTTP el kernel de pruebas se reinicia. Las pruebas
  construyen `FosterService`, `ApplicationService` y `AdoptionService` sobre el EntityManager de la prueba, para
  no mezclar entidades de un contenedor con servicios de otro.
- **Margen de las carreras.** La carrera adopción/acogida es determinista (acogida primero, formalización 1,5 s
  después) para cubrir el camino con estado obsoleto; con inicio simultáneo solía ganar la formalización.

## Resultado

`vendor/bin/phpunit --testdox`: 81 pruebas y 778 aserciones correctas (Spec006 23/23; Spec001–Spec005 sin
cambios en sus pruebas). Esquema sincronizado, migraciones up/down/up y linters correctos. Detalle:
[evidencia](../../docs/evidencias/spec-006-phpunit.txt). Verificación manual (T623): 17/17, con una
observación visual en móvil que pasa a converge ([evidencia](../../docs/evidencias/spec-006-manual/README.md)).

## Tareas de converge (2026-11-19)

- **T626.** `templates/foster/home.html.twig`: el alta de periodos usa `columns` sin `is-mobile`; en móvil los dos
  campos de fecha se apilan y la columna de la rejilla ya no se ensancha. Medido en Chrome: a 390 px el borde
  derecho de las tarjetas pasa de 396 a 374 px; a 1280 px no cambia
  ([evidencia](../../docs/evidencias/spec-006-manual/README.md#comprobación-de-t626--mi-acogida-en-móvil-2026-11-19)).
- **T627.** `FosterService::end(FosterPlacement)` ya no recibe la coordinadora, que no se usaba; se ajustan el
  controlador, la prueba y la firma del plan.
