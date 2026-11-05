# Matriz de trazabilidad de las ocho historias

Fecha: 2026-11-05. El alcance implementado es HU-01 a HU-04: 18 criterios comprobados. Los otros 20 se registran como pendientes. Responsable y revisor académicos: pendientes en agrupamiento.md.

| Historia / criterio | Decisión del plan y código | Tareas | Prueba / estado |
|---|---|---|---|
| HU-01 / CA-01-01 | plan.md, decisiones D01-D11; AnimalService / AnimalPhoto | T102/T103/T104 | Spec001Test::testVolunteerCreatesIdentityAndPhotoInQuarantine; correcto en phpunit-final.xml. |
| HU-01 / CA-01-02 | plan.md, decisiones D01-D11; AnimalService / Animal | T102/T103/T104 | Spec001Test::testVolunteerCreatesIdentityAndPhotoInQuarantine; correcto en phpunit-final.xml. |
| HU-01 / CA-01-03 | plan.md, decisiones D01-D11; MediaController / permisos | T102/T103/T104 | Spec001Test::testAnonymousCannotSeeQuarantineOrPhotos; correcto en phpunit-final.xml. |
| HU-01 / CA-01-04 | plan.md, decisiones D01-D11; AnimalController / VeterinaryReview | T102/T103/T104 | Spec001Test::testVeterinaryReviewAndRoleValidation; correcto en phpunit-final.xml. |
| HU-01 / CA-01-05 | plan.md, decisiones D01-D11; AnimalService / reloj | T102/T103/T104 | Spec001Test::testBoundaryIsExactlyTenPeriodsOf24Hours; correcto en phpunit-final.xml. |
| HU-02 / CA-02-01 | plan.md, decisiones D01-D11; CatalogController | T202/T203/T204 | Spec002Test::testAnonymousCatalogContainsOnlyRealAvailability; correcto en phpunit-final.xml. |
| HU-02 / CA-02-02 | plan.md, decisiones D01-D11; CatalogController / filtros | T202/T203/T204 | Spec002Test::testFiltersCombineWithAndAndHandleAgeBoundaries; correcto en phpunit-final.xml. |
| HU-02 / CA-02-03 | plan.md, decisiones D01-D11; CatalogController / MediaController | T202/T203/T204 | Spec002Test::testAnonymousCatalogContainsOnlyRealAvailability; correcto en phpunit-final.xml. |
| HU-02 / CA-02-04 | plan.md, decisiones D01-D11; catalog/index y show Twig | T202/T203/T204 | Spec002Test::testFosteredAnimalRemainsVisibleAndMarked; correcto en phpunit-final.xml. |
| HU-03 / CA-03-01 | plan.md, decisiones D01-D11; ApplicationService / cuenta | T302/T303/T304 | Spec003Test::testCompleteQuestionnaireRetainsApplicantAndAnimal; correcto en phpunit-final.xml. |
| HU-03 / CA-03-02 | plan.md, decisiones D01-D11; ApplicationService / validación | T302/T303/T304 | Spec003Test::testIncompleteQuestionnaireIsNotAccepted; correcto en phpunit-final.xml. |
| HU-03 / CA-03-03 | plan.md, decisiones D01-D11; ApplicationService / bloqueos PostgreSQL | T302/T303/T304 | Spec003Test::testConcurrentSubmissionsCannotExceedTwo; correcto en phpunit-final.xml. |
| HU-03 / CA-03-04 | plan.md, decisiones D01-D11; ReviewController / ROLE_COORDINATOR | T302/T303/T304 | Spec003Test::testCoordinatorReceivesCompleteQuestionnaireButVolunteerCannotReadIt; correcto en phpunit-final.xml. |
| HU-04 / CA-04-01 | plan.md, decisiones D01-D11; ReviewService / entrevista | T402/T403/T404 | Spec004Test::testCoordinatorRecordsInterviewAndApplicantSeesAppointment; correcto en phpunit-final.xml. |
| HU-04 / CA-04-02 | plan.md, decisiones D01-D11; ReviewService / rechazo | T402/T403/T404 | Spec004Test::testRejectionRequiresReasonAndPersistsIt; correcto en phpunit-final.xml. |
| HU-04 / CA-04-03 | plan.md, decisiones D01-D11; ReviewService / reserva | T402/T403/T404 | Spec004Test::testApprovalAtomicallyReservesAndWaitsOtherOpenRequests; correcto en phpunit-final.xml. |
| HU-04 / CA-04-04 | plan.md, decisiones D01-D11; ReviewService / estados de espera | T402/T403/T404 | Spec004Test::testApprovalAtomicallyReservesAndWaitsOtherOpenRequests; correcto en phpunit-final.xml. |
| HU-04 / CA-04-05 | plan.md, decisiones D01-D11; ReviewService / índice único / bloqueos | T402/T403/T404 | Spec004Test::testTwoConcurrentApprovalsHaveOneWinnerAndOneWaitingCandidate; correcto en phpunit-final.xml. |
| HU-05 / CA-05-01 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-05 / CA-05-02 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-05 / CA-05-03 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-05 / CA-05-04 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-05 / CA-05-05 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-06 / CA-06-01 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-06 / CA-06-02 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-06 / CA-06-03 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-06 / CA-06-04 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-06 / CA-06-05 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-07 / CA-07-01 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-07 / CA-07-02 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-07 / CA-07-03 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-07 / CA-07-04 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-07 / CA-07-05 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-08 / CA-08-01 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-08 / CA-08-02 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-08 / CA-08-03 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-08 / CA-08-04 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |
| HU-08 / CA-08-05 | Punto de integración del modelo común; diseño específico pendiente | Pendientes | No implementado ni verificado en esta iteración. |

La suite añade además roles, CSRF, formularios reales, filtros inválidos, persistencia, límite de fotos, datos malformados, versión obsoleta y liberación de reserva. Ver verificacion.md y evidencias/browser/resultados.json.
