# Tareas spec-003

- [x] T301: concretar decisiones y criterios de HU-03.
- [x] T302: implementar dominio, persistencia y permisos según plan.md.
- [x] T303: implementar interfaz Twig/Bulma y comportamiento Hotwire.
- [x] T304: validar los criterios de aceptación con PostgreSQL y pruebas HTTP.
- [x] T305: registrar analyze, converge, resultados y commit.

Responsable académico y revisor: pendientes de identificación; desarrollo asistido por Codex a solicitud del usuario. No se atribuye revisión humana realizada. Actualización 2026-11-21: revisión de la PR #6 aprobada por `abcb5-ua` (según `docs/agrupamiento.md`).

## Ejecución del 2026-11-17

Encargo expreso del usuario (`aggg15`) de desarrollar y publicar spec-003; desarrollo asistido por
Claude Code. Desarrollada sobre `spec-002` (96f1929) y rebasada sobre `main` (890d2b3) tras integrar
la PR #5, sin conflictos; las pruebas se repitieron sobre la nueva base. Revisión de `abcb5-ua`: pendiente. Actualización 2026-11-21: integrada en `main` con la PR #6; aprobada por `abcb5-ua`.

- T301 → [clarify](clarify.md) y [spec](spec.md): identidad por cuenta (D06), abiertas (D07),
  seis respuestas del cuestionario (D08), animales disponibles (D10); CA-03-05 y CA-03-06 derivados.
- T302 → `AdoptionApplication`, migración `Version20261117090718` con CHECK, `ApplicationService`
  con `FOR UPDATE` de cuenta y animal, `AccountService`, controladores con `IsGranted` y CSRF.
- T303 → `application/*.html.twig`, `security/register.html.twig`, enlace desde la ficha pública
  de HU-02, navegación por rol y controlador Stimulus `questionnaire`.
- T304 → `tests/Spec003Test.php`: 11 pruebas / 127 aserciones correctas; regresión Spec001 7 / 32.
  Véase ../../docs/evidencias/spec-003-phpunit.txt.
- T305 → [analyze](analyze.md), [implement](implement.md), [converge](converge.md) y CI.

Resultado T304: correcto. La prueba Spec002 de promoción falla ya en `main` (890d2b3); se comunica
al responsable de HU-02 sin modificarla aquí. La comprobación visual móvil/escritorio queda pendiente.

## Phase 3: Revisión previa a la entrega (2026-11-21)

Origen: revisión de código previa a la entrega, hallazgos C1-04, C1-09, C1-10, C1-20c y C2-03 (informe en
[converge](converge.md), «Pasada de revisión previa a la entrega»). Las secciones anteriores se cuentan como
fases 1 y 2. Responsable de la ejecución: `aggg15` (asistido por Claude Code); revisión: pendiente en la PR de `revision-entrega`.

- [x] T306 C1-04: `ApplicationController` responde 409 con un mensaje en español cuando el animal pasa a reservado o adoptado entre abrir y enviar el formulario (solo con un token CSRF válido, que solo obtiene quien abrió el formulario); cuarentena, inexistente y petición sin token siguen dando 404. `ApplicationService::refusalReason` nombra el motivo (reservado o adoptado); `Spec003Test::testAnimalReservedOrAdoptedWhileTheFormIsOpenAnswersConflict`
- [x] T307 C1-09: nuevo `App\Service\TextNormalizer` (recorta `[\s\p{Z}]` al inicio y al final; rechaza el byte NUL y el UTF-8 inválido con un error de validación 422) aplicado a vivienda, experiencia y disponibilidad; `Spec003Test::testQuestionnaireTextsAreNormalizedAndRejectNulAndInvalidUtf8`
- [x] T308 C1-10: migración `Version20261121100000` con el índice único parcial `uniq_application_open_applicant_animal (applicant_id, animal_id) WHERE status IN ('pending','waiting','approved')`, declarado también en `AdoptionApplication`; la migración aborta si hay datos que lo violen; `Spec003Test::testDatabaseRejectsTwoOpenApplicationsOfTheSameApplicantForTheSameAnimal`
- [x] T309 C1-20c: pruebas del límite de 2000 caracteres (admite 2000, rechaza 2001 y conserva lo escrito) en vivienda, experiencia y disponibilidad; `Spec003Test::testQuestionnaireTextsAreLimitedToTwoThousandCharacters`
- [x] T310 C2-03: `ApplicationService::submit` bloquea la cuenta con `FOR NO KEY UPDATE` (no choca con la inserción con clave foránea de la formalización y sigue serializando los envíos de la cuenta); `Spec003Test::testSubmissionDoesNotWaitForForeignKeyInsertsOnTheSameAccount`, y `testConcurrentSubmissionsNeverExceedTwoOpenApplications` sigue pasando
- [x] T311 C1-09 y C1-18 (parte de HU-01, ejecutada aquí porque `AnimalService` y `animal/show.html.twig` están en este ámbito): `AnimalService` normaliza y valida con `TextNormalizer` la procedencia de la ficha y las observaciones de la revisión veterinaria, y la ficha repuebla el formulario de revisión tras un 422; `Spec003Test::testAnimalOriginIsNormalizedAndRejectsNulAndInvalidUtf8` y `testVeterinaryReviewKeepsTypedValuesAndRejectsNulAndInvalidUtf8`. Debe registrarse también en `specs/spec-001/`.
