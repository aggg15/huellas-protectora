# Plan de implementación · HU-05

Fecha: 2026-11-18. Rama `spec-005`, creada desde `main` (cdb0416), que integra HU-01 a HU-04.
Responsable: `aggg15`. Revisión prevista: `abcb5-ua`. Spec: [spec.md](spec.md); decisiones: [clarify](clarify.md).

## Base común y alcance

El [plan común](../../plan.md), [modelo de datos](../../data-model.md),
[decisiones](../../docs/decisiones.md) y [Constitution](../../.specify/memory/constitution.md)
son las fuentes compartidas. Este documento añade el contexto de HU-05 sin duplicar esos acuerdos.
Se implementan la formalización (contrato y cuota), el paso a adoptado, el cierre de solicitudes y
la programación de seguimientos. No se implementan visitas, padrinos (HU-08) ni casas de acogida (HU-06).

## Technical Context

- Lenguaje: PHP 8.3; Symfony 7.4, Twig y Doctrine ORM 3. Sin dependencias nuevas: el contrato es
  HTML con hoja de estilos de impresión; `symfony/clock` (ya instalado) aporta `MockClock` para pruebas.
- Interfaz: Turbo, Stimulus y Bulma servidos localmente. Stimulus `print` abre el diálogo de impresión.
- Persistencia: PostgreSQL 16; migración nueva (no modifica las aplicadas).
- Entidades: nuevas `Adoption` y `FollowUp`; `AdoptionApplication` gana el estado `formalized`;
  `Animal` y `User` no cambian de columnas.
- Pruebas: PHPUnit con HTTP simulado y PostgreSQL `_test`; procesos PHP independientes para la concurrencia.

## Constitution Check

| Principio | Cumplimiento |
|---|---|
| I. Fidelidad y alcance | Solo entrega, contrato, cuota, adoptado y dos seguimientos (SI-30, p. 1). Sin pasarela, firma, PDF, visitas ni contabilidad. |
| II. Trazabilidad | CA-05-01 a CA-05-11 → componentes → pruebas `Spec005Test` (tabla de trazabilidad). |
| III. Base compartida | Una aplicación; amplía `AdoptionApplication` y `data-model.md`; reutiliza roles, CSRF y bloqueos del plan común. |
| IV. Coordinación | Cambios en código de HU-04 (`cancelReservation`, `admin_show`, `ApplicationReviewController::show`) se señalan en la PR para `abcb5-ua`. |
| V. Calidad | Recorrido normal, entradas inválidas, estados incompatibles, permisos, CSRF, concurrencia y fechas límite. |

## Modelo de datos

Migración nueva `Version20261118230000` (generada con `doctrine:migrations:diff` y completada a mano
con los CHECK, como en HU-03):

| Tabla | Columnas | Restricciones |
|---|---|---|
| `Adoption` | `id` UUID; `animal_id`, `application_id`, `adopter_id`, `formalizedBy_id` FK; `contractNumber`; `deliveredAt` (UTC); `feeCents` int; copia del contrato: `adopterName`, `adopterEmail`, `animalCode`, `animalSpecies`, `animalAgeMonths`, `animalSize` | Únicos sobre `animal_id`, `application_id` y `contractNumber` (D17); `CHECK (feeCents BETWEEN 1 AND 999999)` (D14). |
| `FollowUp` | `id` UUID; `adoption_id` FK; `kind` (`one_month`/`six_months`); `dueOn` (fecha); `status` (`scheduled`) | Único sobre (`adoption_id`, `kind`); CHECK de `kind` y `status` (D16). |
| `AdoptionApplication` | Sin columnas nuevas | El CHECK `adoption_application_status` se sustituye para admitir `formalized`. |

- Los índices únicos se declaran también en el mapeo (`OneToOne` y `UniqueConstraint`), para que
  `doctrine:schema:validate` pase (lección de T411 en HU-04). Los CHECK siguen el patrón existente: solo en la migración.
- Copia del contrato (D13: «no cambia tras generarse»): los datos del adoptante y del animal se copian
  al formalizar; el contrato muestra la copia, no los valores actuales de `User` o `Animal`.
- `contractNumber` = `CTR-` + código del animal sin `HUE-`. Es único porque cada animal tiene una sola adopción.
- `down()`: borra `FollowUp` y `Adoption` y restaura el CHECK anterior. Falla si existen solicitudes
  `formalized`; es intencionado, para no perder adopciones al revertir.

## Diseño

### Calendario de seguimientos (`App\Service\FollowUpCalendar`)

Función pura `dueDate(\DateTimeImmutable $deliveredAt, int $months): \DateTimeImmutable`: convierte a
Europe/Madrid, toma año, mes y día; suma los meses a año/mes; usa `min(día, días del mes destino)`.
No usa `modify('+N month')`, que desborda (31-ene → 3-mar). Devuelve una fecha sin hora (D16, CA-05-09).

### Formalización (`App\Service\AdoptionService::formalize`)

`formalize(AdoptionApplication $application, User $coordinator, array $data): Adoption`

1. Valida fuera de la transacción (lanza `ValidationError`, HTTP 422):
   - `fee`: tras recortar espacios, `^\d{1,4}([.,]\d{1,2})?$`; se convierte a céntimos sin coma
     flotante; debe ser mayor que 0. Máximo 9 999,99 € (CA-05-07).
   - `feePaid`: debe valer `1` (casilla de confirmación del abono).
2. En `wrapInTransaction`, por este orden (el mismo que `approve()` y `cancelReservation()`, para evitar interbloqueos):
   - `SELECT status FROM animal WHERE id = ? FOR UPDATE`
   - `SELECT status, animal_id FROM adoptionapplication WHERE id = ? FOR UPDATE`
   - Si la solicitud no está `approved` o el animal no está `reserved`, lanza `ApplicationRefused` (409, CA-05-06).
     Se usan los valores leídos bajo bloqueo, no los de la entidad en memoria.
3. Crea la `Adoption` con `deliveredAt = clock->now()` en UTC, la copia del contrato y `formalizedBy`.
4. Crea los dos `FollowUp` con `FollowUpCalendar` (1 y 6 meses).
5. Solicitud → `formalized`; animal → `adopted` y `fostered = false` (D15).
6. `UPDATE adoptionapplication SET status = 'cancelled', cancellationReason = 'El animal ha sido adoptado'
   WHERE animal_id = ? AND status IN ('pending','waiting') AND id <> ?` (CA-05-08).
7. `flush()`. Si salta un índice único (`UniqueConstraintViolationException`), se convierte en
   `ApplicationRefused`: es la última defensa de D17.

El servicio recibe `ClockInterface`, como los anteriores. Las pruebas de fechas construyen el servicio
con `MockClock`, sin cambiar el reloj global de pruebas: Spec004 compara entrevistas con la hora real.

### Ajuste coordinado de HU-04 (`ApplicationService::cancelReservation`)

Problema de la revisión previa (D17, CA-05-10): el estado se comprueba antes del bloqueo. El cambio:

- Se mantiene la validación previa actual (no aprobada o sin motivo → `ValidationError` 422), para
  conservar el comportamiento que comprueba Spec004.
- Dentro de la transacción, tras `FOR UPDATE` del animal, se bloquea y relee la solicitud. Si ya no es
  `approved` o el animal ya no es `reserved`, lanza `ApplicationRefused` y no cambia nada.
- `ApplicationReviewController::cancelReservation` captura `ApplicationRefused`. `show()` recibe un
  código de estado opcional (por defecto 422) para devolver 409 en los conflictos de estado de HU-05.

### Rutas y controladores

| Ruta | Nombre | Acceso | Uso |
|---|---|---|---|
| `POST /admin/solicitudes/{id}/formalizar` | `admin_application_formalize` | `ROLE_COORDINATOR`, CSRF `formalize-{id}` | Formalizar; éxito 303 a la adopción; 422 o 409 con el formulario |
| `GET /admin/adopciones/{id}` | `admin_adoption_show` | `ROLE_COORDINATOR` | Adopción, cuota y planificación (CA-05-03) |
| `GET /admin/seguimientos` | `admin_follow_ups` | `ROLE_COORDINATOR` | Seguimientos programados por fecha, con animal, adoptante y enlace |
| `GET /adopciones/{id}/contrato` | `adoption_contract` | `ROLE_USER`; coordinadora o adoptante titular; resto 404 | Contrato imprimible (CA-05-01, CA-05-11) |

- `AdoptionController` reúne las tres rutas nuevas de lectura y la de formalizar. El contrato queda
  fuera de `/admin` porque `access_control` exige `ROLE_STAFF` allí y el adoptante no es personal.
- Ante una cuenta no autorizada el contrato responde 404, no 403, para no revelar que la adopción existe.
- `AdoptionService::findByAnimal(Animal)` devuelve la adopción única del animal o `null`: es el
  punto de consulta para HU-08 (D18, CA-05-05).

### Vistas

- `application/admin_show.html.twig` (HU-04): en el bloque `approved`, formulario «Formalizar entrega»
  (importe con `inputmode="decimal"`, casilla obligatoria «Confirmo que se ha abonado la cuota», errores
  por campo y `aria-invalid`), encima de «Liberar reserva». Nuevo bloque `formalized` con enlace a la
  adopción y al contrato y sin acciones.
- `adoption/show.html.twig`: datos de entrega, cuota con formato `120,00 €`, contrato y tabla de los dos seguimientos.
- `adoption/follow_ups.html.twig`: tabla ordenada por fecha; estado vacío «No hay seguimientos programados».
- `adoption/contract.html.twig`: número, fecha, partes, animal, cuota, fechas de los seguimientos y
  compromisos de cuidado y seguimiento. Botón «Imprimir» (Stimulus `print`). No afirma validez
  jurídica (Constitution V).
- `application/mine.html.twig`: el estado «Adopción formalizada» enlaza al contrato. Las canceladas
  por adopción muestran su motivo.
- `base.html.twig`: enlace «Seguimientos» para `ROLE_COORDINATOR`.
- `public/assets/app.css`: `@media print` oculta cabecera, pie y botones.
- `AdoptionApplication::STATUS_LABELS`: `formalized` → «Adopción formalizada».

## Trazabilidad

| Criterio | Implementación | Verificación (`tests/Spec005Test.php`) |
|---|---|---|
| CA-05-01 | `AdoptionService::formalize`, contrato y `adoption_contract` | `testFormalizationGeneratesContractLinkedToAnimalAndAdopter` |
| CA-05-02 | Cuota en céntimos, `deliveredAt`, animal `adopted`, `fostered = false` | `testFormalizationRecordsFeeAndMarksAnimalAdopted`, `testFosteredAnimalLeavesFosterWhenAdopted` |
| CA-05-03 | `FollowUpCalendar`, `FollowUp`, `admin_adoption_show`, `admin_follow_ups` | `testTwoFollowUpsAreScheduledAtOneAndSixMonths` (`MockClock` 10-mar-2027), `testFollowUpListIsOrderedByDate` |
| CA-05-04 | Estado `adopted` + exclusión de HU-02 | `testAdoptedAnimalDisappearsFromPublicCatalogDetailAndPhotos` |
| CA-05-05 | Único sobre `animal_id`, `findByAnimal` | `testAdoptionIsIdentifiableFromTheAnimal` |
| CA-05-06 | Relectura bajo bloqueo, 409 | `testFormalizationRequiresApprovedApplicationAndReservedAnimal` (pending, waiting, rejected, cancelled, formalized) |
| CA-05-07 | Validación de `fee` y `feePaid`, 422 | `testFormalizationRequiresValidFeeAndPaymentConfirmation` (vacío, 0, −5, abc, 12,345, 10000; formatos 120,5 / 120.50 válidos) |
| CA-05-08 | `UPDATE` de abiertas, estado `formalized`, vista sin liberar | `testFormalizationClosesOtherApplicationsAndFreesOpenSlots`, `testFormalizedAdoptionCannotBeReleased` |
| CA-05-09 | `FollowUpCalendar` | `testFollowUpDatesUseLastDayOfShorterMonths` (31-ago-2027, 31-ene-2027), `testFollowUpDatesUseMadridCalendarDay` (23:30 UTC del 31-mar) |
| CA-05-10 | `FOR UPDATE`, únicos, ajuste de `cancelReservation` | `testConcurrentFormalizationsCreateOneAdoption`, `testConcurrentReleaseAndFormalizationKeepConsistentState` (`tests/Support/adoption-race.php`) |
| CA-05-11 | `IsGranted`, CSRF, comprobación de titular | `testOnlyCoordinatorFormalizesAndListsFollowUps`, `testContractVisibleOnlyToCoordinatorAndAdopter`, `testFormalizationRequiresCsrf` |

La regresión ejecuta la suite completa: Spec003 (abiertas) y Spec004 (liberación) dependen de los cambios.
La prueba de concurrencia sigue el patrón de `tests/Support/submit-application.php`: dos procesos con
inicio sincronizado. Al terminar se comprueban una sola adopción, dos seguimientos y un estado coherente
(adopción + animal adoptado + solicitud formalizada, o ninguna adopción + animal disponible + solicitud cancelada).

## Verificación manual prevista

Recorrido HU-03 → HU-04 → HU-05 con datos ficticios en escritorio y móvil (≤ 400 px): solicitar,
entrevistar, aprobar, formalizar, consultar el contrato como adoptante e imprimirlo, ver los
seguimientos y comprobar que el animal ya no está en el catálogo. Navegación por teclado del formulario
de formalización. Recorrido con error: formalizar sin confirmar el abono (422, el animal sigue reservado).

## Riesgos y coordinación

- **Cambio en el CHECK común de estados:** afecta a HU-03 y HU-04. Ninguna consulta existente lista
  estados de forma exhaustiva salvo `STATUS_LABELS`, que se amplía.
- **Ajuste de `cancelReservation` (código de `abcb5-ua`):** se limita a releer el estado bajo bloqueo
  y a capturar el conflicto. Debe pasar la suite Spec004 sin cambiar sus pruebas.
- **Base `_test` local antigua:** usar una base `_test` recién migrada (véase README).

## Estado

Plan redactado el 2026-11-18. Tareas en [tasks.md](tasks.md).
