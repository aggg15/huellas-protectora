# Implement · spec-007

Fecha: 2026-11-19. Aplicado por Claude Code a petición de `aggg15`. No se ha ejecutado la skill
`$speckit-implement`. Tareas: T705–T718 de [tasks.md](tasks.md), tras [analyze](analyze.md).

## Componentes

- **Entidades.** `Shift` (tipo, inicio y fin en UTC, mínimo, máximo, coordinadora y alta; `KIND_LABELS`, índice
  por inicio) y `ShiftSignup` (turno, voluntario y hora; único `uniq_shift_signup_volunteer` declarado en el mapeo).
  `User` no cambia; no hay roles nuevos.
- **Migración** `Version20261119180000`: dos tablas, claves ajenas, único, índice por inicio y CHECK de tipo, horas
  (`endsAt > startsAt`) y personas (1 ≤ mínimo ≤ máximo ≤ 20). No modifica migraciones aplicadas. `down()` borra
  las dos tablas. Se escribió a partir del diff de Doctrine quedándose solo con las sentencias de HU-07: la base de
  desarrollo local es antigua y el diff incluía cambios ajenos.
- **Servicios.**
  - `ShiftCoverage`: `hasStarted`, `isUndercovered` (48 horas sobre instantes) y `overlaps` (intervalos semiabiertos).
  - `ShiftService`: `create`, `update`, `signUp`, `leave` y las consultas `board` (listado con la cobertura para quien
    consulta: inscritas, plazas, estado, «Te has apuntado», coincidencias y permisos de cada botón), `coverage` y
    `signupsOf`. Validación con `ValidationError` (422) y reglas con `ShiftRefused` (409), decididas con lecturas
    DBAL después de `FOR UPDATE` (cuenta → turno al inscribirse; turno al modificar o dar de baja).
- **Controlador.** `ShiftController` (`ROLE_VOLUNTEER`; alta y modificación con `ROLE_COORDINATOR`). Los rechazos
  de inscripción o baja vuelven a mostrar el listado con el motivo (409); los de modificación, la ficha con los
  errores y lo escrito (422/409).
- **Vistas.** `shift/index`, `shift/new`, `shift/show`, los parciales `_form` y `_status`, el enlace «Turnos» de la
  cabecera común y los estilos `.shift-undercovered` y `.shift-alert-tag`. Tabla apilada en móvil con `stacked-table`.
- **Pruebas.** `tests/Spec007Test.php` (18 pruebas) y `tests/Support/shift-race.php`. El CI las detecta por su nombre
  sin cambios en `ci.yml`, que ya incluía la rama `spec-007`.

## Decisiones tomadas durante la implementación

- **Hora repetida de octubre.** `DateTimeImmutable::createFromFormat` interpreta las 02:30 del último domingo de
  octubre en Madrid como la segunda (horario de invierno, UTC+1), y la spec fija la primera (horario de verano). La
  prueba lo detectó (se guardaba 01:30 UTC en lugar de 00:30 UTC). El servicio comprueba si una hora antes el reloj
  de Madrid marcaba lo mismo y, en ese caso, usa ese instante. Mutación 3 de la evidencia.
- **Comparación por minutos.** El formulario no tiene segundos. Comparar instantes exactos hacía que un formulario
  sin cambios pareciera un cambio de horario (un 409 indebido con inscripciones, o un 422 «debe empezar después de
  este momento» en lugar del 409 de turno empezado). Se comparan minutos y, si las horas no cambian, se conservan
  las guardadas.
- **Modificación por SQL.** `update` escribe todos los campos con una sentencia y refresca la entidad: una entidad
  cargada antes de otro cambio no escribiría los valores que coinciden con su copia (lección de HU-06).
- **Coincidencias en el listado.** `board` calcula los turnos de quien consulta entre los que no han terminado: un
  turno terminado no puede coincidir con uno que todavía no ha empezado, así que basta con ese conjunto.
- **Pruebas sobre un único EntityManager**, como en HU-06: las pruebas de servicio construyen `ShiftService` sobre el
  EntityManager de la prueba, con `NativeClock` o `MockClock`.

## Resultado

`vendor/bin/phpunit --testdox`: 99 pruebas y 1039 aserciones correctas (Spec007 18/18; Spec001–Spec006 sin cambios en
sus pruebas). Esquema sincronizado, migraciones up/down/up y linters correctos. Las pruebas de concurrencia y de la
hora repetida fallan con las tres mutaciones registradas. Detalle: [evidencia](../../docs/evidencias/spec-007-phpunit.txt).
Verificación manual (T718): 19/19, con una observación visual en móvil que pasa a converge
([evidencia](../../docs/evidencias/spec-007-manual/README.md)).

## Tareas de converge (2026-11-19)

- **T721.** `templates/shift/index.html.twig`: las celdas «Inscritas» y «Estado» tienen un solo elemento hijo (la
  celda apilada en móvil es una rejilla etiqueta | valor y cada nodo ocupaba una casilla); las etiquetas del estado
  se agrupan con `.shift-tags`. Medido en Chrome: a 390 px los elementos en la columna de etiquetas pasan de 5 a 0; a
  1280 px la vista no cambia ([evidencia](../../docs/evidencias/spec-007-manual/README.md#comprobación-de-t721--filas-apiladas-en-móvil-2026-11-19)).
- **T722.** Se elimina `ShiftService::signupCount`, que no tenía llamadas, y se alinea la lista de consultas del plan
  con las implementadas (`board`, `coverage`, `signupsOf`).
