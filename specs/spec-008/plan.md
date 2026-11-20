# Plan de implementación · HU-08

Fecha: 2026-11-19. Rama `spec-008`, creada desde `main` (5e379e6), que integra HU-01 a HU-07.
Responsable: `abcb5-ua`. Revisión prevista: `aggg15`. Spec: [spec.md](spec.md); decisiones: [clarify](clarify.md).

## Base común y alcance

El [plan común](../../plan.md), [modelo de datos](../../data-model.md),
[decisiones](../../docs/decisiones.md) y [Constitution](../../.specify/memory/constitution.md)
son las fuentes compartidas. Este documento añade el contexto de HU-08 sin duplicar esos acuerdos.
Se implementan el apadrinamiento desde la ficha pública, la página «Mis apadrinamientos» (novedades, decisión tras
la adopción y fin voluntario) y la página de la coordinadora para consultar los apadrinamientos activos y publicar
novedades. No se implementan cobros, recibos, cambios de importe, correos ni publicación de novedades.

## Technical Context

- Lenguaje: PHP 8.3; Symfony 7.4, Twig y Doctrine ORM 3. Sin dependencias nuevas.
- Interfaz: Turbo y Bulma servidos localmente; sin controladores Stimulus nuevos.
- Persistencia: PostgreSQL 16; migración nueva `Version20261119190000` (no modifica las aplicadas).
- Entidades nuevas: `Sponsorship` y `SponsorUpdate`. `User`, `Animal` y `Adoption` no cambian; no hay roles nuevos.
- Pruebas: PHPUnit con HTTP simulado y PostgreSQL `_test`; procesos PHP independientes para la concurrencia.

## Constitution Check

| Principio | Cumplimiento |
|---|---|
| I. Fidelidad y alcance | Animal concreto, aportación mínima de diez euros, novedades y elección tras la adopción (SI-30, p. 2). Sin cobros, contabilidad ni mensajería. |
| II. Trazabilidad | CA-08-01 a CA-08-10 → componentes → pruebas `Spec008Test` (tabla de trazabilidad). |
| III. Base compartida | Una aplicación; reutiliza `User` y el registro de HU-03, la disponibilidad pública de HU-01/02 (`AnimalService::getPublicAnimal`), la adopción de HU-05 (`AdoptionService::findByAnimal`, D18), CSRF, `ValidationError`, instantes UTC mostrados en Madrid y el criterio de bloqueo y relectura; amplía `data-model.md`. |
| IV. Coordinación | Archivos comunes tocados: enlaces de `base.html.twig`, recuadro de `public/show.html.twig` y estilos de `app.css`. Se señalan en la PR para `aggg15`. HU-05 y HU-06 no cambian. |
| V. Calidad | Recorrido normal, importes inválidos, estados (animal no disponible, ya apadrinado, pendiente, terminado), permisos, CSRF, concurrencia y uso en móvil y por teclado. |

## Modelo de datos

Migración nueva `Version20261119190000`, generada con `doctrine:migrations:diff` sobre una base `_test` recién
migrada, completada a mano con los CHECK, como en HU-05 a HU-07, y renombrada para ordenar después de la de HU-07
(véase [implement](implement.md)):

| Tabla | Columnas | Restricciones |
|---|---|---|
| `Sponsorship` | `id` UUID; `animal_id` FK; `sponsor_id` FK `app_user`; `monthlyCents` int; `startedAt`, `endedAt` (UTC, nulo si activo); `endReason` (`finished`/`moved`); `previous_id` FK propia, única | Único parcial `uniq_sponsorship_active` (`animal_id`, `sponsor_id`) `WHERE endedAt IS NULL`, declarado en el mapeo (D35, D41). `CHECK (monthlyCents BETWEEN 1000 AND 999999)` (D36); fin y motivo nulos a la vez y motivo válido; `endedAt >= startedAt`. |
| `SponsorUpdate` | `id` UUID; `animal_id` FK; `author_id` FK `app_user`; `writtenAt` (UTC); `body` texto | `CHECK (char_length(body) BETWEEN 1 AND 2000)` (D37). Índice (`animal_id`, `writtenAt`) declarado en el mapeo. |

- «Pendiente de decisión» no se guarda: se deriva de un apadrinamiento activo cuyo animal está adoptado (D38). Así
  HU-05 no cambia y no hay tareas programadas.
- Que el animal esté disponible al apadrinar no se puede expresar como restricción: se comprueba bajo el bloqueo del animal.
- `down()` borra las dos tablas; ninguna otra historia depende de ellas.

## Diseño

### Servicio (`App\Service\SponsorshipService`)

Recibe `EntityManagerInterface`, `ClockInterface` y `AdoptionService`. Lanza `ValidationError` (422) para datos no
válidos y la excepción nueva `SponsorshipRefused` (409) para reglas de estado. Las decisiones se toman con lecturas
DBAL después del bloqueo (plan común).

| Método | Validación (422) | Transacción y reglas (409) |
|---|---|---|
| `sponsor(Animal, User, array)` | `amount`: hasta cuatro cifras y dos decimales, con coma o punto, de 10,00 € a 9 999,99 € (D36), convertido sin coma flotante | Rechaza cuentas con roles de trabajo (D34). Bloquea la cuenta y después el animal; rechaza si no está disponible o si la cuenta ya lo apadrina (D35). Violación del único → `SponsorshipRefused`. |
| `finish(Sponsorship, User)` | — | Bloquea la cuenta y el apadrinamiento; rechaza si no es de la cuenta o ya terminó; fin = ahora, motivo `finished` (D39). |
| `moveTo(Sponsorship, Animal, User)` | El controlador responde 422 si no se elige animal o no existe | Bloquea la cuenta y el apadrinamiento; rechaza si terminó o su animal no está adoptado; bloquea el animal elegido y rechaza si es el mismo, no está disponible o ya lo apadrina; termina el anterior (`moved`) y crea el nuevo con el mismo importe y `previous` (D39). |
| `addUpdate(Animal, User, array)` | `body` de 1 a 2000 caracteres tras recortar espacios | Bloquea el animal; rechaza si no tiene apadrinamientos activos (D37). |

- Orden de bloqueo: cuenta → apadrinamiento → animal. La formalización de HU-05 bloquea animal → solicitud y no la
  cuenta; publicar una novedad solo bloquea el animal. Sin ciclos de espera.
- Consultas: `mine(User)` (apadrinamientos de la cuenta con estado `active`/`pending`/`ended`, adopción si está
  pendiente, novedades de su periodo y el apadrinamiento siguiente si cambió de animal), `updatesFor(Sponsorship)`
  (novedades entre inicio y fin), `availableFor(User)` (animales disponibles que la cuenta no apadrina) y
  `overview()` (activos agrupados por animal, con padrinos, pendientes y novedades).
- El rol de la cuenta se comprueba en el controlador (403) y también en `sponsor`, para que el servicio no admita
  un apadrinamiento de una cuenta de trabajo aunque se llame directamente.

### Rutas y controladores

| Ruta | Nombre | Acceso | Uso |
|---|---|---|---|
| `GET, POST /animales/{id}/apadrinar` | `sponsorship_new` | Cuenta pública, CSRF `sponsorship-new-{id}` | Formulario y alta; 404 si el animal no es público (GET); 303 a «Mis apadrinamientos» / 422 / 409 |
| `GET /mis-apadrinamientos` | `sponsorship_mine` | Cuenta pública | Apadrinamientos, novedades, decisión tras la adopción |
| `POST /mis-apadrinamientos/{id}/terminar` | `sponsorship_finish` | Titular, CSRF `sponsorship-finish-{id}` | Fin voluntario; 303 / 409; 404 si es ajeno |
| `POST /mis-apadrinamientos/{id}/cambiar` | `sponsorship_move` | Titular, CSRF `sponsorship-move-{id}` | Otro animal; 303 / 422 / 409; 404 si es ajeno |
| `GET /admin/apadrinamientos` | `admin_sponsorships` | `ROLE_COORDINATOR` | Apadrinamientos activos por animal y novedades |
| `POST /admin/apadrinamientos/{id}/novedades` | `admin_sponsor_update_add` | `ROLE_COORDINATOR`, CSRF `sponsor-update-{id}` | Publicar novedad; 303 / 422 / 409 |

- `SponsorshipController` exige `ROLE_USER` (el anónimo va a `/acceso` y vuelve después) y responde 403 a las cuentas
  con `ROLE_STAFF` o `ROLE_FOSTER`. La propiedad se comprueba antes que el token (404 sin revelar apadrinamientos ajenos).
- `SponsorshipAdminController` está bajo `/admin` (`ROLE_STAFF` en `access_control`) con `IsGranted('ROLE_COORDINATOR')`.
- `base.html.twig`: «Mis apadrinamientos» para cuentas públicas y «Apadrinamientos» para la coordinadora.
- `public/show.html.twig`: recuadro «¿Prefieres apadrinarlo?» con el enlace, salvo para personal y familias.

### Vistas

- `sponsorship/new.html.twig`: datos básicos del animal, campo «Aportación mensual (€)» etiquetado con
  `aria-invalid`/`aria-describedby` en error, ayuda con el mínimo y el aviso de que no se cobra por la web.
- `sponsorship/mine.html.twig`: una tarjeta por apadrinamiento con estado («Activo», «Pendiente de tu decisión»,
  «Terminado»), importe, fecha de inicio y, si terminó, fecha y motivo (con el animal siguiente si cambió). Si está
  pendiente: aviso con la fecha de adopción y la pausa, selector de animales disponibles y «Terminar mi colaboración»;
  si no hay disponibles, se indica. Novedades del periodo. Estado vacío con enlace al catálogo.
- `sponsorship/admin_index.html.twig`: una sección por animal con padrinos (nombre, importe, desde, «En pausa»),
  aviso de adoptado con los pendientes, formulario de novedad y novedades publicadas. Estado vacío.
- Fechas con el filtro `date` de Twig en Europe/Madrid; textos escapados.

## Trazabilidad

| Criterio | Implementación | Verificación (`tests/Spec008Test.php`) |
|---|---|---|
| CA-08-01 | `sponsor`, `sponsorship/new`, `mine`, recuadro de la ficha | `testSponsorRegistersMonthlyContributionForAnimal` |
| CA-08-02 | `amountCents` (422) | `testContributionBelowTenEurosIsRejected` |
| CA-08-03 | `addUpdate`, `updatesFor`, vistas | `testSponsorReceivesUpdatesPublishedByCoordinator`, `testUpdatesAreValidatedAndLimitedToTheSponsorshipPeriod` |
| CA-08-04 | `state`, `mine` (adopción), `overview` | `testAdoptedAnimalOffersSponsorToContinueOrFinish` |
| CA-08-05 | `moveTo`, `finish` | `testSponsorChoiceIsRecordedWithoutUnilateralAssignment` |
| CA-08-06 | Único parcial, `finish` | `testSponsorshipCardinalityAndVoluntaryEnd` |
| CA-08-07 | `getPublicAnimal`, bloqueo del animal | `testOnlyAvailableAnimalsCanBeSponsored` |
| CA-08-08 | `moveTo`, `finish`, controlador (422) | `testInvalidContinuityChoicesAreRejected` |
| CA-08-09 | Bloqueos, índice único | `testConcurrentSponsorshipsOfSameAnimalCreateOne`, `testConcurrentContinuityChoicesApplyOnlyOne` (`tests/Support/sponsorship-race.php`) |
| CA-08-10 | `IsGranted`, `sponsor()` del controlador y del servicio, CSRF | `testOnlyPublicAccountsSponsorAndOnlyCoordinatorPublishes` |

La regresión ejecuta la suite completa; ninguna historia anterior cambia de comportamiento. La prueba de
concurrencia sigue el patrón de `tests/Support/shift-race.php`.

## Verificación manual prevista

Con datos ficticios en escritorio y móvil (≤ 400 px): una cuenta pública apadrina un animal disponible desde su
ficha; la coordinadora publica una novedad y el padrino la ve; tras formalizar la adopción, el padrino ve la
decisión pendiente y elige otro animal. Recorrido con error: importe de 9,99 € (422) y cuenta de personal (403).
Navegación por teclado del formulario de apadrinamiento y de la decisión.

## Riesgos y coordinación

- **Archivos comunes:** dos enlaces en la cabecera y un recuadro en la ficha pública; comprobar que la cabecera en
  móvil no desborda y que la ficha pública de HU-02 sigue pasando sus pruebas.
- **EntityManager cerrado tras un rechazo:** `wrapInTransaction` cierra el EntityManager si la operación lanza una
  excepción. En HTTP cada petición tiene el suyo; en las pruebas de servicio se reordena o se crea uno nuevo.
- **Base `_test` local antigua:** usar una base `_test` recién migrada (véase README).

## Estado

Plan redactado el 2026-11-19. Tareas en [tasks.md](tasks.md).
