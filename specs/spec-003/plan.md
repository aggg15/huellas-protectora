# Plan de implementación · HU-03

Fecha: 2026-11-17. Rama `spec-003`, construida sobre `main` (890d2b3), que ya integra HU-02 (PR #5),
según la secuencia del plan común: «cada rama spec-00N se desarrollará sobre la anterior». Spec: [spec.md](spec.md).

## Base común y alcance

El [plan común](../../plan.md), [modelo de datos](../../data-model.md),
[decisiones](../../docs/decisiones.md) y [Constitution](../../.specify/memory/constitution.md)
son las fuentes compartidas. Este documento añade el contexto de HU-03 sin duplicar esos acuerdos.
Se implementan la presentación y la consulta de solicitudes; las decisiones sobre ellas son HU-04.

## Technical Context

- Lenguaje: PHP 8.3; Symfony 7.4, Twig y Doctrine ORM 3.
- Interfaz: Turbo, Stimulus y Bulma servidos localmente. Stimulus `questionnaire` hace opcional la
  explicación de experiencia cuando la respuesta es «Ninguna»; el servidor aplica la misma regla.
- Persistencia: PostgreSQL 16; migración nueva `Version20261117090718` (no modifica las aplicadas).
- Entidades: nueva `AdoptionApplication`; reutiliza `User` y `Animal` sin cambiar sus columnas.
- Pruebas: PHPUnit con HTTP simulado y PostgreSQL separado terminado en `_test`; procesos PHP
  independientes para los envíos simultáneos.

## Constitution Check

| Principio | Cumplimiento |
|---|---|
| I. Fidelidad y alcance | Solo solicitud, cuestionario, límite y consulta de la coordinadora. Alta de cuenta mínima exigida por D06. |
| II. Trazabilidad | CA-03-01 a CA-03-06 → componentes → pruebas (tabla siguiente) → [evidencia](../../docs/evidencias/spec-003-phpunit.txt). |
| III. Base compartida | Una aplicación; estados comunes de `data-model.md`; HU-04 amplía la misma entidad. |
| IV. Coordinación | Cambios compartidos (modelo, `security.yaml`, ficha pública de HU-02, CI) señalados en la PR. |
| V. Calidad | Recorrido normal, entradas inválidas, permisos, CSRF, estado del animal y concurrencia. |

## Diseño

- `ApplicationService`: valida el cuestionario y presenta la solicitud en `wrapInTransaction`.
  Bloquea `app_user` y `animal` con `FOR UPDATE`, relee el estado del animal y comprueba duplicado
  y límite antes de insertar. Lanza `ValidationError` (422) o `ApplicationRefused` (409).
- `AccountService` y `AccountController` (`/registro`): alta con email normalizado único,
  nombre y contraseña ≥ 12 caracteres; roles siempre vacíos (`ROLE_USER`). Tras el alta inicia
  sesión y vuelve a la página pendiente (formulario de solicitud) mediante el gestor de `form_login`.
- `ApplicationController` (`ROLE_USER`): `GET|POST /animales/{id}/solicitud` y `GET /mis-solicitudes`.
  Un animal no público devuelve 404 (reutiliza `AnimalService::getPublicAnimal` de HU-02).
- `ApplicationReviewController` (`ROLE_COORDINATOR`): `GET /admin/solicitudes` y `/admin/solicitudes/{id}`.
- `SecurityController::dashboard` (`/panel`): destino tras acceder; personal → refugio, solicitante → sus solicitudes.
  Sustituye a `admin_animals` como `default_target_path`, que devolvía 403 a los solicitantes.
- PostgreSQL: CHECK de estados comunes y de respuestas obligatorias como defensa adicional.

## Trazabilidad

| Criterio | Implementación | Verificación (`tests/Spec003Test.php`) |
|---|---|---|
| CA-03-01 | `ApplicationService::submit`, `application/new.html.twig`, enlace desde la ficha pública | `testApplicantSubmitsCompleteQuestionnaireForAvailableAnimal` |
| CA-03-02 | `ApplicationService::validate`, errores por campo y 422 | `testIncompleteQuestionnaireIsNotAdmittedAndListsPendingData`, `testNoPreviousExperienceIsAValidAnswer` |
| CA-03-03 | Recuento de abiertas bajo bloqueo; aviso previo en el formulario | `testThirdOpenApplicationIsRejectedAndExistingOnesAreKept`, `testApprovedCountsAsOpenWhileRejectedAndCancelledDoNot` |
| CA-03-04 | `ApplicationReviewController` y vistas `admin_*` | `testCoordinatorAccessesReceivedApplicationWithCompleteQuestionnaire` |
| CA-03-05 | 404 para animales no públicos; relectura del estado bajo bloqueo; duplicados | `testAnimalsThatAreNotAvailableDoNotReceiveApplications`, `testDuplicateOpenApplicationForSameAnimalIsRejected` |
| CA-03-06 | `SELECT … FOR UPDATE` de la cuenta | `testConcurrentSubmissionsNeverExceedTwoOpenApplications` |
| Permisos / D06 | `IsGranted`, CSRF, alta sin roles | `testPermissionsAndCsrfProtectApplications`, `testPublicRegistrationCreatesApplicantAccountAndReturnsToApplication` |

## Estado

Implementado y comprobado el 2026-11-17: ver [tareas](tasks.md), [implement](implement.md) y
[converge](converge.md). Revisión humana del compañero pendiente. Actualización 2026-11-21: integrada en `main` con la PR #6; aprobada por `abcb5-ua` (según `docs/agrupamiento.md`).
