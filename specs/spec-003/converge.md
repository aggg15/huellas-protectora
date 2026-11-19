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
