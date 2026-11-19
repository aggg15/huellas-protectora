# Plan de implementación · HU-06

Fecha: 2026-11-19. Rama `spec-006`, creada desde `main` (87a78a0), que integra HU-01 a HU-05.
Responsable: `abcb5-ua`. Revisión prevista: `aggg15`. Spec: [spec.md](spec.md); decisiones: [clarify](clarify.md).

## Base común y alcance

El [plan común](../../plan.md), [modelo de datos](../../data-model.md),
[decisiones](../../docs/decisiones.md) y [Constitution](../../.specify/memory/constitution.md)
son las fuentes compartidas. Este documento añade el contexto de HU-06 sin duplicar esos acuerdos.
Se implementan las condiciones de cada casa (capacidad y periodos), el registro y la finalización de
acogidas, las novedades de la familia y el cierre de la acogida al adoptar. No se implementan acogidas
futuras, avisos, alta pública de familias ni el uso de las novedades con padrinos (HU-08).

## Technical Context

- Lenguaje: PHP 8.3; Symfony 7.4, Twig y Doctrine ORM 3. Sin dependencias nuevas. `symfony/clock`
  (ya instalado) aporta `MockClock` para las pruebas del día de Madrid.
- Interfaz: Turbo y Bulma servidos localmente; sin controladores Stimulus nuevos (`input type="date"`).
- Persistencia: PostgreSQL 16; migración nueva `Version20261119120000` (no modifica las aplicadas).
- Entidades nuevas: `FosterHome`, `FosterAvailability`, `FosterPlacement`, `FosterNote`. `Animal`
  conserva `fostered` como indicador derivado de la acogida activa (HU-02 lo usa); `User` no cambia.
- Rol nuevo: `ROLE_FOSTER`, fuera de `ROLE_STAFF` en la jerarquía.
- Pruebas: PHPUnit con HTTP simulado y PostgreSQL `_test`; procesos PHP independientes para la concurrencia.

## Constitution Check

| Principio | Cumplimiento |
|---|---|
| I. Fidelidad y alcance | Solo disponibilidad, capacidad, acogida temporal, visibilidad y novedades (SI-30, p. 1). Sin compensaciones, avisos ni padrinos. |
| II. Trazabilidad | CA-06-01 a CA-06-10 → componentes → pruebas `Spec006Test` (tabla de trazabilidad). |
| III. Base compartida | Una aplicación; reutiliza `Animal.fostered`, roles, CSRF, `ValidationError` y el orden de bloqueo del plan común; amplía `data-model.md`. |
| IV. Coordinación | Cambios en código de HU-01 (`AnimalController::show`, `animal/show.html.twig`, `CreateUserCommand`, `SecurityController::dashboard`) y de HU-05 (`AdoptionService::formalize`) se señalan en la PR para `aggg15`. |
| V. Calidad | Recorrido normal, entradas inválidas, estados incompatibles, permisos, CSRF, concurrencia, día de Madrid y uso en móvil y por teclado. |

## Modelo de datos

Migración nueva `Version20261119120000`, generada con `doctrine:migrations:diff` y completada a mano con
los CHECK, como en HU-03 y HU-05:

| Tabla | Columnas | Restricciones |
|---|---|---|
| `FosterHome` | `id` UUID; `family_id` FK `app_user`; `capacity` int; `createdAt` (UTC) | Único sobre `family_id` (D19); `CHECK (capacity BETWEEN 1 AND 10)` (D20). |
| `FosterAvailability` | `id` UUID; `home_id` FK; `startsOn`, `endsOn` (fechas de Madrid) | `CHECK (endsOn >= startsOn)`. El no solapamiento se valida en el servicio bajo bloqueo de la casa. |
| `FosterPlacement` | `id` UUID; `animal_id`, `home_id`, `assignedBy_id` FK; `startedOn`, `plannedEndOn`, `endedOn` (nulo si activa); `endReason` (`returned`/`adopted`, nulo si activa) | Índice único parcial `uniq_foster_active_animal` sobre `animal_id` WHERE `endedOn IS NULL` (D21), declarado también en el mapeo; CHECK de fechas (`plannedEndOn >= startedOn`, `endedOn >= startedOn`), de `endReason` y de que `endedOn` y `endReason` sean ambos nulos o ambos no nulos. |
| `FosterNote` | `id` UUID; `placement_id`, `author_id` FK; `writtenAt` (UTC); `body` texto | `CHECK (btrim(body) <> '' AND char_length(body) <= 2000)` (D24). |

- El índice parcial se declara en el mapeo con el predicado que devuelve PostgreSQL, como
  `uniq_application_approved_animal` (lección de T411 en HU-04), para que `doctrine:schema:validate` pase.
- Las fechas de periodos y acogidas son días del calendario de Madrid, guardadas sin hora (`date_immutable`)
  y mostradas con `|date('d/m/Y', false)` para que la zona de Twig no desplace el día (lección de HU-05).
- Las novedades se asocian al animal a través de su acogida: el historial del animal reúne las novedades
  de todas sus acogidas, activas o finalizadas.
- `down()` borra las cuatro tablas. No hay datos de otras historias que dependan de ellas.

## Diseño

### Calendario de disponibilidad (`App\Service\AvailabilityCalendar`)

Funciones puras:

- `madridDay(\DateTimeImmutable $instant): \DateTimeImmutable`: día de Europe/Madrid del instante, como
  fecha a medianoche UTC (mismo convenio que `FollowUpCalendar`).
- `covers(array $periods, \DateTimeImmutable $from, \DateTimeImmutable $to): bool`: ordena los periodos,
  une los que se solapan o son consecutivos (inicio ≤ fin anterior + 1 día) y comprueba si un tramo unido
  contiene `[from, to]`. Compara cadenas `Y-m-d`, sin zonas horarias (D20, R4, CA-06-03).

### Servicio (`App\Service\FosterService`)

Recibe `EntityManagerInterface` y `ClockInterface`. Lanza `ValidationError` (422) para datos no válidos y
la excepción nueva `FosterRefused` (409) para reglas de estado, capacidad o disponibilidad. Las lecturas que
deciden se hacen con DBAL después del bloqueo, no con entidades en memoria (plan común).

| Método | Validación (422) | Transacción y reglas (409) |
|---|---|---|
| `saveCapacity(User $family, array $data)` | `capacity` entero de 1 a 10 | Crea la casa o bloquea la existente (`FOR UPDATE`); rechaza una capacidad menor que sus acogidas activas (D23). |
| `addPeriod(FosterHome, array $data)` | `startsOn`/`endsOn` con formato `Y-m-d` real; inicio ≥ hoy; fin ≥ inicio; fin ≤ hoy + 2 años | Bloquea la casa y comprueba bajo bloqueo que no se solapa con otro periodo; el solapamiento es un dato no válido: `ValidationError` en `startsOn` (422, CA-06-06; analyze A3). |
| `removePeriod(FosterHome, FosterAvailability)` | — | Bloquea la casa; con los periodos restantes, toda acogida activa con fin previsto ≥ hoy debe seguir cubierta desde hoy (D23). |
| `place(Animal, User $coordinator, array $data)` | `home` existente; `plannedEndOn` con formato real y ≥ hoy | Bloquea el animal y después la casa; rechaza animal adoptado, animal con acogida activa, casa no disponible de hoy al fin previsto o casa completa; crea la acogida y pone `fostered = true` (D21, D25). |
| `end(FosterPlacement)` (sin la coordinadora desde T627) | — | Bloquea el animal y la acogida; rechaza una acogida ya finalizada; fin = hoy, motivo `returned`, `fostered = false` (D22). |
| `addNote(FosterPlacement, User $family, array $data)` | `body` de 1 a 2000 caracteres tras recortar espacios | Bloquea la acogida; rechaza una acogida finalizada; guarda la novedad con la hora actual (D24). |

- Orden de bloqueo: animal → casa → acogida, el mismo que la formalización (animal primero). `saveCapacity`
  y los periodos solo bloquean la casa. Así no hay ciclos de espera.
- Una violación del índice parcial al registrar una acogida se convierte en `FosterRefused` (última defensa).
- Consultas: `homeOf(User)`, `activePlacementOf(Animal)`, `historyOf(Animal)` (acogidas con sus novedades),
  `activePlacementsOf(FosterHome)` y `homesOverview()` (casas con su ocupación y periodos).
- «Hoy» es `AvailabilityCalendar::madridDay($clock->now())`. Una acogida tiene «fin previsto superado» si
  está activa y `plannedEndOn` < hoy.

### Ajuste coordinado de HU-05 (`AdoptionService::formalize`)

Dentro de la transacción existente, después del cambio del animal a adoptado (que ya pone `fostered = false`):
`UPDATE fosterplacement SET endedOn = ?, endReason = 'adopted' WHERE animal_id = ? AND endedOn IS NULL`, con
el día de Madrid de la entrega (D25, CA-06-04). El animal ya está bloqueado por la formalización, así que
una acogida simultánea espera y después ve el animal adoptado (CA-06-09). Spec005 debe pasar sin cambios.

### Rutas y controladores

| Ruta | Nombre | Acceso | Uso |
|---|---|---|---|
| `GET /acogida` | `foster_home` | `ROLE_FOSTER` | Condiciones, periodos, animales acogidos y sus novedades |
| `POST /acogida/capacidad` | `foster_capacity` | `ROLE_FOSTER`, CSRF `foster-capacity` | Capacidad; 303 / 422 / 409 |
| `POST /acogida/periodos` | `foster_period_add` | `ROLE_FOSTER`, CSRF `foster-period` | Añadir periodo; 303 / 422 |
| `POST /acogida/periodos/{id}/eliminar` | `foster_period_remove` | `ROLE_FOSTER`, CSRF `foster-period-remove-{id}`; periodo de otra casa → 404 | Retirar periodo; 303 / 409 |
| `POST /acogida/acogidas/{id}/novedades` | `foster_note_add` | `ROLE_FOSTER`, CSRF `foster-note-{id}`; acogida de otra casa → 404 | Anotar novedad; 303 / 422 / 409 |
| `GET /admin/acogidas` | `admin_fosters` | `ROLE_COORDINATOR` | Casas (capacidad, ocupación, periodos) y acogidas activas |
| `POST /admin/animales/{id}/acogida` | `admin_foster_place` | `ROLE_COORDINATOR`, CSRF `foster-place-{id}` | Registrar acogida; 303 a la ficha / 422 / 409 |
| `POST /admin/acogidas/{id}/finalizar` | `admin_foster_end` | `ROLE_COORDINATOR`, CSRF `foster-end-{id}` | Finalizar; 303 a la ficha / 409 |

- `FosterController` (familia) y `FosterAdminController` (coordinadora). Las páginas de la familia quedan
  fuera de `/admin`, porque `access_control` exige `ROLE_STAFF` allí y la familia no es personal.
- Los formularios de la coordinadora están en la ficha interna del animal (HU-01). Ante un error,
  `FosterAdminController` reenvía (`forward`) a `AnimalController::show` con `fosterErrors` y los valores
  escritos, y ajusta el código (422 o 409). `AnimalController::show` recibe esos dos argumentos opcionales
  y los datos de acogida de `FosterService`; su comportamiento actual no cambia.
- Un periodo o acogida de otra familia responde 404, no 403, para no revelar su existencia (como el contrato de HU-05).
- Cada operación correcta redirige con un mensaje de éxito (`addFlash('success', …)`): condiciones guardadas,
  periodo añadido o retirado, acogida registrada o finalizada y novedad anotada (Constitution V; analyze A4).
- `SecurityController::dashboard`: una cuenta `ROLE_FOSTER` sin rol de personal va a `/acogida`.
- `CreateUserCommand` admite `ROLE_FOSTER`. `security.yaml` no cambia: `ROLE_FOSTER` no hereda `ROLE_STAFF`.

### Vistas

- `foster/home.html.twig` (familia): capacidad con ocupación («1 de 2 plazas ocupadas»); tabla de periodos
  (clase `stacked-table` de T528 para móvil) con botón «Retirar» por periodo y formulario de alta (dos campos
  `date` etiquetados); tarjetas de los animales acogidos (código, especie, edad, tamaño, inicio, fin previsto
  y etiqueta «Fin previsto superado»), con sus novedades y un formulario «Anotar novedad». Errores por campo
  con `aria-invalid` y `aria-describedby`. Estados vacíos: «Todavía no has indicado tus condiciones»,
  «No tienes periodos de disponibilidad», «Ahora mismo no acoges ningún animal». Los periodos ya terminados
  se muestran después de los vigentes con la etiqueta «Terminado» y también se pueden retirar (analyze A2).
- `foster/admin_index.html.twig` (coordinadora): tabla de casas (familia, email, capacidad, ocupación,
  periodos vigentes; los terminados no se listan) y tabla de acogidas activas (animal con enlace, familia,
  inicio, fin previsto, etiqueta de fin superado). Ambas apiladas en móvil. Estados vacíos.
- `foster/_animal_panel.html.twig`, incluido en `animal/show.html.twig` (HU-01): acogida activa (familia,
  inicio, fin previsto) con «Finalizar acogida» para la coordinadora, o el formulario «Registrar acogida»
  (casa con su ocupación y periodos, fin previsto) si el animal no está adoptado; historial de acogidas y de
  novedades para todo el personal. Las novedades se escapan y conservan los saltos de línea.
- `base.html.twig`: enlace «Mi acogida» para `ROLE_FOSTER` y «Acogidas» para `ROLE_COORDINATOR`.
- La web pública (HU-02) no cambia: ya muestra «En casa de acogida» con `fostered`.

## Trazabilidad

| Criterio | Implementación | Verificación (`tests/Spec006Test.php`) |
|---|---|---|
| CA-06-01 | `saveCapacity`, `addPeriod`, `foster/home`, `admin_fosters` | `testFamilyRecordsCapacityAndAvailabilityPeriods` |
| CA-06-02 | `place`, `AvailabilityCalendar::madridDay`, panel del animal | `testCoordinatorPlacesAnimalInAvailableHomeWithCapacity`, `testPlacementStartsOnMadridCalendarDay` |
| CA-06-03 | `covers`, ocupación bajo bloqueo, 409 | `testPlacementRefusedWhenHomeIsNotAvailableForThePeriod`, `testPlacementRefusedWhenCapacityWouldBeExceeded`, `testConsecutivePeriodsCoverThePlacement` |
| CA-06-04 | `fostered`, HU-02 sin cambios, ajuste de `formalize` | `testFosteredAvailableAnimalStaysPublicWithFosterBadge`, `testPlacementDoesNotChangeQuarantineOrReservation`, `testAdoptionEndsActivePlacement` |
| CA-06-05 | `addNote`, `historyOf`, panel del animal | `testFamilyNotesAreAssociatedWithTheAnimal` |
| CA-06-06 | Validaciones 422 | `testInvalidConditionsAreRejected`, `testInvalidPlacementAndNoteAreRejected` |
| CA-06-07 | `saveCapacity`, `removePeriod` (D23) | `testConditionChangesRespectActivePlacements` |
| CA-06-08 | `end`, «fin previsto superado» | `testCoordinatorEndsPlacement`, `testOverduePlacementStaysActiveAndIsFlagged` |
| CA-06-09 | Bloqueos, índice parcial, ajuste de `formalize` | `testConcurrentPlacementsDoNotExceedCapacity`, `testConcurrentPlacementsOfOneAnimalCreateOne`, `testConcurrentAdoptionAndPlacementLeaveNoActivePlacement` (`tests/Support/foster-race.php`) |
| CA-06-10 | `IsGranted`, CSRF, comprobación de propiedad | `testOnlyFosterFamiliesManageConditionsAndNotes`, `testOnlyCoordinatorPlacesEndsAndListsPlacements`, `testFamilyCannotTouchAnotherHome`, `testNotesAreNotPublic`, `testFosterFormsRequireCsrf` |

La regresión ejecuta la suite completa: Spec001 (ficha del animal), Spec002 (indicador público) y Spec005
(formalización) dependen de los cambios. La prueba de concurrencia sigue el patrón de
`tests/Support/adoption-race.php`: procesos con inicio sincronizado y comprobación del estado final.

## Verificación manual prevista

Con datos ficticios en escritorio y móvil (≤ 400 px): la familia indica capacidad y periodos; la coordinadora
registra la acogida de un animal disponible; la persona anónima lo ve en el catálogo con «En casa de acogida»;
la familia anota una novedad y el personal la ve en la ficha interna. Recorrido con error: registrar una acogida
en una casa completa (409, el animal no cambia). Navegación por teclado de los formularios de la familia.

## Riesgos y coordinación

- **Cambio en `AdoptionService::formalize` (código de `aggg15`):** una sola sentencia en la transacción
  existente. Spec005 debe pasar sin modificar sus pruebas.
- **Cambio en la ficha interna del animal (HU-01, `aggg15`):** dos argumentos opcionales en `show()` y un
  `include`; la revisión veterinaria sigue usando `errors`, separado de `fosterErrors`.
- **Indicador `fostered` desnormalizado:** solo lo modifican `place`, `end` y `formalize`, siempre en la
  misma transacción que la acogida. Los datos de prueba de Spec002 y Spec005 lo ponen a mano sin acogida;
  sigue siendo válido para esas pruebas.
- **Base `_test` local antigua:** usar una base `_test` recién migrada (véase README).

## Estado

Plan redactado el 2026-11-19. Tareas en [tasks.md](tasks.md).
