# Converge · spec-003

Contraste del código con los seis criterios de spec.md, el plan y las tareas, realizado por
Claude Code el 2026-11-17 después de implementar. No se ha ejecutado la skill `$speckit-converge`.

| Criterio | Estado | Comprobación en `tests/Spec003Test.php` |
|---|---|---|
| CA-03-01 | Cumplido | `testApplicantSubmitsCompleteQuestionnaireForAvailableAnimal` |
| CA-03-02 | Cumplido | `testIncompleteQuestionnaireIsNotAdmittedAndListsPendingData`, `testNoPreviousExperienceIsAValidAnswer` |
| CA-03-03 | Cumplido | `testThirdOpenApplicationIsRejectedAndExistingOnesAreKept`, `testApprovedCountsAsOpenWhileRejectedAndCancelledDoNot` |
| CA-03-04 | Cumplido | `testCoordinatorAccessesReceivedApplicationWithCompleteQuestionnaire` |
| CA-03-05 | Cumplido | `testAnimalsThatAreNotAvailableDoNotReceiveApplications`, `testDuplicateOpenApplicationForSameAnimalIsRejected` |
| CA-03-06 | Cumplido | `testConcurrentSubmissionsNeverExceedTwoOpenApplications` (falla si se quita el bloqueo) |

Permisos, CSRF y alta sin roles: `testPermissionsAndCsrfProtectApplications` y
`testPublicRegistrationCreatesApplicantAccountAndReturnsToApplication`.

## Hallazgos

- Código, spec y plan coinciden; no se añaden tareas correctivas para spec-003.
- Fuera del alcance de esta rama: `Spec002Test::testExpiredQuarantinePromotesAutomaticallyOnCatalogAccess`
  falla desde 96f1929 (integrado en `main` con la PR #5) por leer una entidad cacheada en el EntityManager;
  se comunica al responsable de HU-02.
- Pendiente: comprobación visual móvil/escritorio y por teclado, y revisión del compañero.

Evidencia: ../../docs/evidencias/spec-003-phpunit.txt.

Nota del 2026-11-18: en `main` (cdb0416) la prueba de promoción de Spec002 pasa (8/8 en Spec002,
39/39 en la suite). Véase ../../docs/evidencias/revision-previa-spec-005-2026-11-18.txt.

## Pasada de revisión previa a la entrega (2026-11-21)

Origen: revisión de código previa a la entrega. Tareas T306 a T311 de [tasks.md](tasks.md). Responsable de la
ejecución: `aggg15` (asistido por Claude Code); revisión: pendiente en la PR de `revision-entrega`. Método de la
columna «Antes / después»: la prueba nueva se ejecutó el 2026-11-21 sobre el código sin corregir (los archivos de
`src/` y `templates/` de `HEAD`, con la migración revertida para C1-10) y después sobre el código corregido.

| Hallazgo | Gravedad | Corrección | Prueba | Antes / después |
|---|---|---|---|---|
| C1-04 | Media | 409 con mensaje en español si el animal pasa a reservado o adoptado entre abrir y enviar; 404 para cuarentena, inexistente o petición sin token válido | `testAnimalReservedOrAdoptedWhileTheFormIsOpenAnswersConflict` | Falla (devolvía 404) / pasa |
| C1-09 | Baja | `TextNormalizer`: recorte de `[\s\p{Z}]`; NUL y UTF-8 inválido dan 422 en vivienda, experiencia y disponibilidad | `testQuestionnaireTextsAreNormalizedAndRejectNulAndInvalidUtf8` | Falla (no se recortaban los espacios Unicode; el rechazo de NUL y UTF-8 inválido, que es lo que evita el 500, se comprueba a continuación en la misma prueba) / pasa |
| C1-10 | Baja | Migración `Version20261121100000` e índice único parcial `uniq_application_open_applicant_animal` declarado en `AdoptionApplication` | `testDatabaseRejectsTwoOpenApplicationsOfTheSameApplicantForTheSameAnimal` | Falla sin la migración / pasa; `doctrine:schema:validate` correcto |
| C1-20c | Baja | Solo prueba: el límite ya existía | `testQuestionnaireTextsAreLimitedToTwoThousandCharacters` | Pasa antes y después (cobertura que faltaba) |
| C2-03 | Baja | `FOR NO KEY UPDATE` sobre la cuenta en `submit()` | `testSubmissionDoesNotWaitForForeignKeyInsertsOnTheSameAccount`; `testConcurrentSubmissionsNeverExceedTwoOpenApplications` | Falla (`lock timeout` esperando una inserción con clave foránea) / pasa; la prueba de concurrencia sigue pasando |
| C1-09 / C1-18 (HU-01) | Baja | `AnimalService` normaliza procedencia y observaciones; la ficha repuebla el formulario de revisión veterinaria | `testAnimalOriginIsNormalizedAndRejectsNulAndInvalidUtf8`, `testVeterinaryReviewKeepsTypedValuesAndRejectsNulAndInvalidUtf8` | Fallan (303 en lugar de 422 con NUL; el formulario de revisión no conservaba lo escrito) / pasan |

Los hallazgos de aprobación, entrevista y revisión (C1-01, C1-02, C1-03, C1-08, C1-12, C1-17 y C1-18) constan en
`specs/spec-004/converge.md`. Resultado de la pasada: sin hallazgos abiertos de HU-03.
