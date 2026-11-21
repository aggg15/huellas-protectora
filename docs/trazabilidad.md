# Matriz de trazabilidad de las ocho historias

Fecha: 2026-11-21. Versión del código: commit a74f85f (matriz original) más las tareas y pruebas de la revisión previa a la entrega, integradas en la rama `revision-entrega` (HEAD 114b74d). Elaborada por Claude Code a partir de las especificaciones vigentes
(`specs/spec-00N/spec.md`), de las tablas «Trazabilidad» de los `plan.md`, de las tablas «Cobertura» de los `analyze.md`
y de `tests/Spec00NTest.php`. Enlaza criterio, decisión o sección del plan, tareas y prueba para las ocho historias
(enunciado §5.2). La columna «Revisado por» queda vacía: la completa el otro integrante tras revisar la fila, y hasta
entonces no consta ninguna revisión cruzada.

Reparto observado ([agrupamiento](agrupamiento.md)): `aggg15` HU-01, HU-03, HU-05 y HU-07; `abcb5-ua` HU-02, HU-04,
HU-06 y HU-08. Las decisiones D01 a D45 están en [decisiones](decisiones.md). Una prueba se cita como
`archivo::método`; cada método existe en el archivo indicado (comprobado con `grep` sobre `tests/` en `revision-entrega`, incluidos los
métodos nuevos de la revisión).
Las tareas y pruebas de la revisión previa a la entrega (T108–T115, T210–T213, T306–T312, T414–T421, T531, T723–T726 y
T824–T827; hallazgos C1 y C2 de la rama `revision-entrega`) se han añadido a las filas de los criterios que cubren; las
que no tienen prueba nueva (T114, T213, T531 sobre una prueba ampliada, T725, T726, T825) constan solo en la columna de
tareas. Las pruebas de HU-01 que se escribieron al corregir HU-03 (T311, T115) figuran con el prefijo `Spec003Test`.

## Matriz criterio → decisión → tarea → prueba

| HU | CA | Decisión o sección del plan | Tareas | Prueba | Revisado por |
|---|---|---|---|---|---|
| HU-01 | CA-01-01 · Ficha del animal | D03, D04; plan.md «Diseño y trazabilidad» | T102, T103, T104, T114, T115 | `tests/Spec001Test.php::testVolunteerCreatesIdentityAndPhotoInQuarantine`<br>`tests/Spec001Test.php::testInvalidAnimalAndSpoofedPhotoLeaveNoPartialWrites`<br>`tests/Spec003Test.php::testAnimalOriginIsNormalizedAndRejectsNulAndInvalidUtf8` | |
| HU-01 | CA-01-02 · Cuarentena de diez días | D01; plan.md «Diseño y trazabilidad» | T102, T104, T110, T113 | `tests/Spec001Test.php::testBoundaryIsExactlyTenPeriodsOf24Hours`<br>`tests/Spec001Test.php::testVolunteerCreatesIdentityAndPhotoInQuarantine`<br>`tests/Spec001Test.php::testPhpTimezoneIsUtcAndStoredDatesAreUtc`<br>`tests/Spec001Test.php::testReleaseDueAnimalsSqlBoundaryAt240Hours` | |
| HU-01 | CA-01-03 · Fuera de la web pública | D01, D04; plan.md «Diseño y trazabilidad» | T102, T104, T108, T112 | `tests/Spec001Test.php::testAnonymousCannotSeeQuarantineOrPhotos`<br>`tests/Spec001Test.php::testPrivatePhotosAreOnlyServedForAvailableAnimalsOrStaff` | |
| HU-01 | CA-01-04 · Revisión veterinaria | D02; plan.md «Diseño y trazabilidad» | T102, T103, T104, T109, T115 | `tests/Spec001Test.php::testVeterinaryReviewAndRoleValidation`<br>`tests/Spec001Test.php::testVetCannotCreateAnimals`<br>`tests/Spec003Test.php::testVeterinaryReviewKeepsTypedValuesAndRejectsNulAndInvalidUtf8` | |
| HU-01 | CA-01-05 · Disponible al acabar la cuarentena | D01, D02; plan.md «Diseño y trazabilidad» | T102, T104, T111, T113 | `tests/Spec001Test.php::testBoundaryIsExactlyTenPeriodsOf24Hours`<br>`tests/Spec001Test.php::testExpiredQuarantineReleasesAutomaticallyWithoutChangingReservations`<br>`tests/Spec001Test.php::testReleaseDueAnimalsSqlBoundaryAt240Hours` | |
| HU-02 | CA-02-01 · Consulta pública sin registro | D01; plan.md §1.1 y §1.3 | T203, T204, T205, T207, T208, T211, T213 | `tests/Spec002Test.php::testAnonymousCanViewPublicCatalogWithAvailableAnimals`<br>`tests/Spec002Test.php::testExpiredQuarantinePromotesAutomaticallyOnCatalogAccess`<br>`tests/Spec002Test.php::testPrimaryPhotoIsStableByIdAscending` | |
| HU-02 | CA-02-02 · Filtros por especie, tamaño y edad | D03, D05; plan.md §1.2 | T203, T204, T205, T208, T212 | `tests/Spec002Test.php::testFilterBySpecies`<br>`tests/Spec002Test.php::testFilterBySizeAndAgeCombined`<br>`tests/Spec002Test.php::testFilterWithoutMatchesShowsEmptyState`<br>`tests/Spec002Test.php::testAgeBoundariesAt12And84Months`<br>`tests/Spec002Test.php::testInvalidFiltersAreIgnored` | |
| HU-02 | CA-02-03 · Exclusión de cuarentena, reservados y adoptados | D01, D10; plan.md §1.1 y §2 | T203, T204, T208, T108, T210 | `tests/Spec002Test.php::testQuarantineReservedAndAdoptedAnimalsExcludedFromCatalog`<br>`tests/Spec002Test.php::testNonAvailableAnimalDetailReturns404`<br>`tests/Spec001Test.php::testPrivatePhotosAreOnlyServedForAvailableAnimalsOrStaff`<br>`tests/Spec002Test.php::testCustomErrorTemplatesRenderInSpanish`<br>`tests/Spec002Test.php::testErrorPagesAreServedInSpanishWithoutDebug` | |
| HU-02 | CA-02-04 · Animal en acogida visible con indicación | plan.md §1.3 | T205, T206, T208, T212 | `tests/Spec002Test.php::testFosteredAnimalShowsFosterBadgeInCatalogAndDetail`<br>`tests/Spec002Test.php::testNonFosteredAnimalShowsNoFosterBadge` | |
| HU-03 | CA-03-01 · Envío de la solicitud | D06, D08; plan.md «Diseño» | T302, T303, T304, T312 | `tests/Spec003Test.php::testApplicantSubmitsCompleteQuestionnaireForAvailableAnimal`<br>`tests/Spec003Test.php::testStaffCannotApplyButFosterFamiliesCan` | |
| HU-03 | CA-03-02 · Cuestionario incompleto | D08; plan.md «Diseño» | T302, T303, T304, T307, T309 | `tests/Spec003Test.php::testIncompleteQuestionnaireIsNotAdmittedAndListsPendingData`<br>`tests/Spec003Test.php::testNoPreviousExperienceIsAValidAnswer`<br>`tests/Spec003Test.php::testQuestionnaireTextsAreNormalizedAndRejectNulAndInvalidUtf8`<br>`tests/Spec003Test.php::testQuestionnaireTextsAreLimitedToTwoThousandCharacters` | |
| HU-03 | CA-03-03 · Máximo de dos solicitudes abiertas | D07; plan.md «Diseño» | T302, T304, T308 | `tests/Spec003Test.php::testThirdOpenApplicationIsRejectedAndExistingOnesAreKept`<br>`tests/Spec003Test.php::testApprovedCountsAsOpenWhileRejectedAndCancelledDoNot`<br>`tests/Spec003Test.php::testDatabaseRejectsTwoOpenApplicationsOfTheSameApplicantForTheSameAnimal` | |
| HU-03 | CA-03-04 · La coordinadora ve las candidaturas | D06, D07; plan.md «Diseño» | T302, T303, T304 | `tests/Spec003Test.php::testCoordinatorAccessesReceivedApplicationWithCompleteQuestionnaire` | |
| HU-03 | CA-03-05 · Animal no disponible o duplicado (derivado) | D07, D10; plan.md «Diseño» | T301, T302, T304, T306, T308 | `tests/Spec003Test.php::testAnimalsThatAreNotAvailableDoNotReceiveApplications`<br>`tests/Spec003Test.php::testDuplicateOpenApplicationForSameAnimalIsRejected`<br>`tests/Spec003Test.php::testAnimalReservedOrAdoptedWhileTheFormIsOpenAnswersConflict` | |
| HU-03 | CA-03-06 · Envíos simultáneos (derivado) | D07; plan.md «Diseño» (`SELECT … FOR UPDATE`) | T301, T302, T304, T310 | `tests/Spec003Test.php::testConcurrentSubmissionsNeverExceedTwoOpenApplications`<br>`tests/Spec003Test.php::testSubmissionDoesNotWaitForForeignKeyInsertsOnTheSameAccount` | |
| HU-04 | CA-04-01 · Revisión y entrevista | D09; plan.md §2 (`recordInterview`), §3 y §4 | T403, T404, T405, T406, T407, T408, T409, T414, T416, T417, T419, T421 | `tests/Spec004Test.php::testCoordinatorCanViewApplicationAndRecordInterview`<br>`tests/Spec004Test.php::testInterviewRequiresValidDateAndNotes`<br>`tests/Spec004Test.php::testInterviewFormStaysOpenWithTypedValuesAfterValidationError`<br>`tests/Spec004Test.php::testInterviewCannotPrecedeTheSubmission`<br>`tests/Spec004Test.php::testMyApplicationsShowsPastInterviewAsDone`<br>`tests/Spec004Test.php::testReviewTextsAreNormalizedAndRejectNulAndInvalidUtf8` | |
| HU-04 | CA-04-02 · Rechazo con motivo obligatorio | D09; plan.md §2 (`reject`) | T404, T405, T406, T407, T409, T414, T416, T421 | `tests/Spec004Test.php::testRejectionWithoutReasonIsNotAdmitted`<br>`tests/Spec004Test.php::testRejectionWithReasonIsRecordedAndDoesNotAlterAnimalAvailability`<br>`tests/Spec004Test.php::testRejectionAndReleaseFormsKeepTypedValuesAfterValidationError` | |
| HU-04 | CA-04-03 · Aprobación y reserva | D09, D10; plan.md §2 (`approve`) | T404, T405, T406, T407, T409, T412, T414, T417 | `tests/Spec004Test.php::testApprovalWithoutInterviewIsNotAdmitted`<br>`tests/Spec004Test.php::testApprovalWithFutureInterviewIsNotAdmitted`<br>`tests/Spec004Test.php::testApprovalAfterValidInterviewReservesAnimalAndMovesOtherApplicationsToWaiting`<br>`tests/Spec004Test.php::testDecisionsReReadTheStateUnderLock`<br>`tests/Spec004Test.php::testStateConflictsAnswerConflict` | |
| HU-04 | CA-04-04 · Las demás solicitudes pasan a espera | D10; plan.md §2 (`approve`) | T405, T406, T409 | `tests/Spec004Test.php::testApprovalAfterValidInterviewReservesAnimalAndMovesOtherApplicationsToWaiting` | |
| HU-04 | CA-04-05 · Una sola reserva por animal | D10; plan.md §1 (índice parcial) y §2 | T403, T405, T409, T411, T414, T415 | `tests/Spec004Test.php::testCannotApproveApplicationForAlreadyReservedAnimal`<br>`tests/Spec004Test.php::testDatabaseRejectsTwoApprovedApplicationsForSameAnimal`<br>`tests/Spec004Test.php::testSimultaneousApproveAndRejectOfTheSameApplicationNeverMix`<br>`tests/Spec004Test.php::testApprovalRefusalNamesTheAnimalStateInSpanish` | |
| HU-05 | CA-05-01 · Contrato | D13; plan.md «Formalización» y «Vistas» | T505, T510, T514, T516, T518, T520, T529 | `tests/Spec005Test.php::testFormalizationGeneratesContractLinkedToAnimalAndAdopter`<br>`tests/Spec005Test.php::testContractKeepsDeliveryDataAfterLaterChanges` | |
| HU-05 | CA-05-02 · Cuota y animal adoptado | D12, D14, D15; plan.md «Formalización» | T509, T510, T517, T520 | `tests/Spec005Test.php::testFormalizationRecordsFeeAndMarksAnimalAdopted`<br>`tests/Spec005Test.php::testFosteredAnimalLeavesFosterWhenAdopted` | |
| HU-05 | CA-05-03 · Dos seguimientos | D16; plan.md «Calendario de seguimientos» | T505, T508, T513, T517, T520 | `tests/Spec005Test.php::testTwoFollowUpsAreScheduledAtOneAndSixMonths`<br>`tests/Spec005Test.php::testFollowUpListIsOrderedByDate` | |
| HU-05 | CA-05-04 · Desaparece de la web pública | D15; plan.md «Formalización» | T510, T520 | `tests/Spec005Test.php::testAdoptedAnimalDisappearsFromPublicCatalogDetailAndPhotos` | |
| HU-05 | CA-05-05 · Adopción identificable para HU-08 | D18; plan.md «Modelo de datos» | T505, T511, T520 | `tests/Spec005Test.php::testAdoptionIsIdentifiableFromTheAnimal` | |
| HU-05 | CA-05-06 · Precondiciones (derivado) | D12, D17; plan.md «Formalización» | T510, T521, T531 | `tests/Spec005Test.php::testFormalizationRequiresApprovedApplicationAndReservedAnimal` | |
| HU-05 | CA-05-07 · Abono obligatorio (derivado) | D14; plan.md «Formalización» | T509, T516, T521 | `tests/Spec005Test.php::testFormalizationRequiresValidFeeAndPaymentConfirmation` | |
| HU-05 | CA-05-08 · Cierre de las solicitudes del animal (derivado) | D15; plan.md «Formalización» y «Vistas» | T506, T510, T516, T519, T521, T528 | `tests/Spec005Test.php::testFormalizationClosesOtherApplicationsAndFreesOpenSlots`<br>`tests/Spec005Test.php::testFormalizedAdoptionCannotBeReleased` | |
| HU-05 | CA-05-09 · Seguimientos a fin de mes (derivado) | D16; plan.md «Calendario de seguimientos» | T508, T521 | `tests/Spec005Test.php::testFollowUpDatesUseLastDayOfShorterMonths`<br>`tests/Spec005Test.php::testFollowUpDatesUseMadridCalendarDay` | |
| HU-05 | CA-05-10 · Una sola adopción ante concurrencia (derivado) | D17; plan.md «Ajuste coordinado de HU-04» | T505, T510, T512, T515, T522 | `tests/Spec005Test.php::testConcurrentFormalizationsCreateOneAdoption`<br>`tests/Spec005Test.php::testReleaseAfterConcurrentFormalizationDoesNotUndoAdoption` | |
| HU-05 | CA-05-11 · Permisos (derivado) | D13; plan.md «Rutas y controladores» | T513, T514, T523 | `tests/Spec005Test.php::testOnlyCoordinatorFormalizesAndListsFollowUps`<br>`tests/Spec005Test.php::testContractVisibleOnlyToCoordinatorAndAdopter`<br>`tests/Spec005Test.php::testFormalizationRequiresCsrf` | |
| HU-06 | CA-06-01 · Condiciones de la casa | D19, D20; plan.md «Servicio (`FosterService`)» y «Calendario de disponibilidad» | T605, T606, T608, T612, T615, T616, T618 | `tests/Spec006Test.php::testFamilyRecordsCapacityAndAvailabilityPeriods` | |
| HU-06 | CA-06-02 · Registro de la acogida | D21, D22; plan.md «Servicio (`FosterService`)» | T605, T607, T609, T613, T617, T618 | `tests/Spec006Test.php::testCoordinatorPlacesAnimalInAvailableHomeWithCapacity`<br>`tests/Spec006Test.php::testPlacementStartsOnMadridCalendarDay` | |
| HU-06 | CA-06-03 · Disponibilidad y capacidad | D21; plan.md «Calendario de disponibilidad» | T607, T609, T613, T617, T618 | `tests/Spec006Test.php::testPlacementRefusedWhenHomeIsNotAvailableForThePeriod`<br>`tests/Spec006Test.php::testPlacementRefusedWhenCapacityWouldBeExceeded`<br>`tests/Spec006Test.php::testConsecutivePeriodsCoverThePlacement` | |
| HU-06 | CA-06-04 · Web pública y coherencia con HU-05 | D25; plan.md «Ajuste coordinado de HU-05» | T609, T611, T618 | `tests/Spec006Test.php::testFosteredAvailableAnimalStaysPublicWithFosterBadge`<br>`tests/Spec006Test.php::testPlacementDoesNotChangeQuarantineOrReservation`<br>`tests/Spec006Test.php::testAdoptionEndsActivePlacement` | |
| HU-06 | CA-06-05 · Novedades de la familia | D24; plan.md «Servicio (`FosterService`)» | T605, T610, T612, T615, T617, T618 | `tests/Spec006Test.php::testFamilyNotesAreAssociatedWithTheAnimal` | |
| HU-06 | CA-06-06 · Datos válidos (derivado) | D20, D24; plan.md «Servicio (`FosterService`)» | T608, T609, T610, T615, T619 | `tests/Spec006Test.php::testInvalidConditionsAreRejected`<br>`tests/Spec006Test.php::testInvalidPlacementAndNoteAreRejected` | |
| HU-06 | CA-06-07 · Cambios con acogidas activas (derivado) | D23; plan.md «Servicio (`FosterService`)» | T608, T619 | `tests/Spec006Test.php::testConditionChangesRespectActivePlacements` | |
| HU-06 | CA-06-08 · Finalización de la acogida (derivado) | D22; plan.md «Servicio (`FosterService`)» | T610, T613, T617, T619 | `tests/Spec006Test.php::testCoordinatorEndsPlacement`<br>`tests/Spec006Test.php::testOverduePlacementStaysActiveAndIsFlagged` | |
| HU-06 | CA-06-09 · Coherencia ante concurrencia (derivado) | D21, D25; plan.md «Servicio (`FosterService`)» | T605, T609, T611, T620 | `tests/Spec006Test.php::testConcurrentPlacementsDoNotExceedCapacity`<br>`tests/Spec006Test.php::testConcurrentPlacementsOfOneAnimalCreateOne`<br>`tests/Spec006Test.php::testConcurrentAdoptionAndPlacementLeaveNoActivePlacement` | |
| HU-06 | CA-06-10 · Permisos (derivado) | D19, D24; plan.md «Rutas y controladores» | T612, T613, T614, T621 | `tests/Spec006Test.php::testOnlyFosterFamiliesManageConditionsAndNotes`<br>`tests/Spec006Test.php::testOnlyCoordinatorPlacesEndsAndListsPlacements`<br>`tests/Spec006Test.php::testFamilyCannotTouchAnotherHome`<br>`tests/Spec006Test.php::testNotesAreNotPublic`<br>`tests/Spec006Test.php::testFosterFormsRequireCsrf` | |
| HU-07 | CA-07-01 · Consulta de turnos e inscripciones | D27, D32; plan.md «Cobertura» y «Vistas» | T705, T709, T710, T711, T712, T713 | `tests/Spec007Test.php::testShiftShowsScheduleLimitsAndSignups` | |
| HU-07 | CA-07-02 · Inscripción | D26, D29; plan.md «Servicio (`ShiftService`)» | T709, T710, T711, T713 | `tests/Spec007Test.php::testVolunteerSignsUpForShiftWithPlaces` | |
| HU-07 | CA-07-03 · Máximo de personas | D29; plan.md «Servicio (`ShiftService`)» | T709, T711, T713 | `tests/Spec007Test.php::testFullShiftRejectsAnotherSignup` | |
| HU-07 | CA-07-04 · Turno falto de voluntarios | D31; plan.md «Cobertura» | T707, T711, T713, T724 | `tests/Spec007Test.php::testUndercoveredShiftIsHighlightedWithin48Hours`<br>`tests/Spec007Test.php::testHighlightWindowIsExactAndUsesRealHours`<br>`tests/Spec007Test.php::testEndedShiftIsShownAsEnded` | |
| HU-07 | CA-07-05 · Mínimo cubierto | D31; plan.md «Cobertura» | T707, T711, T713 | `tests/Spec007Test.php::testShiftReachingMinimumIsNoLongerHighlighted` | |
| HU-07 | CA-07-06 · Alta y cambios por la coordinadora (derivado) | D27, D28; plan.md «Servicio (`ShiftService`)» | T708, T710, T712, T713 | `tests/Spec007Test.php::testCoordinatorCreatesShift`<br>`tests/Spec007Test.php::testCoordinatorChangesLimitsWithinRules` | |
| HU-07 | CA-07-07 · Datos válidos (derivado) | D27; plan.md «Servicio (`ShiftService`)» | T708, T712, T714, T723, T725 | `tests/Spec007Test.php::testInvalidShiftDataIsRejected`<br>`tests/Spec007Test.php::testShiftFormsWithMissingFieldAreRejectedWith422` | |
| HU-07 | CA-07-08 · Baja y turnos empezados (derivado) | D28, D30, D32; plan.md «Servicio (`ShiftService`)» | T709, T710, T711, T714 | `tests/Spec007Test.php::testVolunteerLeavesShiftAndHighlightReturns`<br>`tests/Spec007Test.php::testStartedShiftRejectsSignupLeaveAndChanges` | |
| HU-07 | CA-07-09 · Doble inscripción y turnos coincidentes (derivado) | D29; plan.md «Servicio (`ShiftService`)» | T705, T707, T709, T714 | `tests/Spec007Test.php::testDuplicateAndOverlappingSignupsAreRejected` | |
| HU-07 | CA-07-10 · Coherencia ante concurrencia (derivado) | D33; plan.md «Servicio (`ShiftService`)» | T709, T715, T726 | `tests/Spec007Test.php::testConcurrentSignupsDoNotExceedMaximum`<br>`tests/Spec007Test.php::testConcurrentOverlappingSignupsCreateOne`<br>`tests/Spec007Test.php::testConcurrentMaximumChangeAndSignupStayWithinLimit` | |
| HU-07 | CA-07-11 · Permisos (derivado) | D26, D27; plan.md «Rutas y controlador» | T710, T716 | `tests/Spec007Test.php::testOnlyVolunteersSeeAndJoinShifts`<br>`tests/Spec007Test.php::testOnlyCoordinatorCreatesAndEditsShifts`<br>`tests/Spec007Test.php::testShiftFormsRequireCsrf` | |
| HU-08 | CA-08-01 · Apadrinar un animal | D34, D36; plan.md «Servicio (`SponsorshipService`)» | T805, T807, T810, T812, T814 | `tests/Spec008Test.php::testSponsorRegistersMonthlyContributionForAnimal` | |
| HU-08 | CA-08-02 · Aportación mínima de 10 € | D36; plan.md «Servicio (`SponsorshipService`)» | T807, T812, T815, T824 | `tests/Spec008Test.php::testContributionBelowTenEurosIsRejected`<br>`tests/Spec008Test.php::testSponsorFormWithoutAmountFieldIsRejectedWith422` | |
| HU-08 | CA-08-03 · Novedades para los padrinos | D37; plan.md «Servicio (`SponsorshipService`)» | T809, T811, T813, T814, T815 | `tests/Spec008Test.php::testSponsorReceivesUpdatesPublishedByCoordinator`<br>`tests/Spec008Test.php::testUpdatesAreValidatedAndLimitedToTheSponsorshipPeriod` | |
| HU-08 | CA-08-04 · Animal adoptado: pendiente de decisión | D38; plan.md «Servicio (`SponsorshipService`)» | T809, T813, T814 | `tests/Spec008Test.php::testAdoptedAnimalOffersSponsorToContinueOrFinish` | |
| HU-08 | CA-08-05 · Elección del padrino | D39; plan.md «Servicio (`SponsorshipService`)» | T808, T810, T813, T814, T827 | `tests/Spec008Test.php::testSponsorChoiceIsRecordedWithoutUnilateralAssignment`<br>`tests/Spec008Test.php::testAnimalWhoseQuarantineJustEndedIsOfferedAsTarget` | |
| HU-08 | CA-08-06 · Varios apadrinamientos y fin voluntario (derivado) | D35, D39; plan.md «Servicio (`SponsorshipService`)» | T805, T807, T815, T826 | `tests/Spec008Test.php::testSponsorshipCardinalityAndVoluntaryEnd`<br>`tests/Spec008Test.php::testFinishingAnEndedSponsorshipShowsTheRefusal` | |
| HU-08 | CA-08-07 · Solo animales disponibles (derivado) | D35; plan.md «Servicio (`SponsorshipService`)» | T807, T810, T815 | `tests/Spec008Test.php::testOnlyAvailableAnimalsCanBeSponsored` | |
| HU-08 | CA-08-08 · Elecciones de continuidad no válidas (derivado) | D39; plan.md «Servicio (`SponsorshipService`)» | T808, T810, T815 | `tests/Spec008Test.php::testInvalidContinuityChoicesAreRejected` | |
| HU-08 | CA-08-09 · Coherencia ante concurrencia (derivado) | D41; plan.md «Servicio (`SponsorshipService`)» | T807, T808, T816, T825 | `tests/Spec008Test.php::testConcurrentSponsorshipsOfSameAnimalCreateOne`<br>`tests/Spec008Test.php::testConcurrentContinuityChoicesApplyOnlyOne` | |
| HU-08 | CA-08-10 · Permisos (derivado) | D34, D37, D40; plan.md «Rutas y controladores» | T810, T811, T812, T817, T822 | `tests/Spec008Test.php::testOnlyPublicAccountsSponsorAndOnlyCoordinatorPublishes` | |

## Comportamientos con tarea y prueba sin criterio propio

No figuran en la matriz anterior porque su `spec.md` no les asigna un identificador CA. Se listan para que no queden
fuera de la trazabilidad; véase `specs/spec-004/analyze.md`, A2.

| HU | Comportamiento | Decisión o sección del plan | Tareas | Prueba | Revisado por |
|---|---|---|---|---|---|
| HU-01 | Permisos y CSRF del alta de fichas | plan.md «Diseño y trazabilidad»; spec «Requisitos de calidad y permisos» | T102, T104, T109 | `tests/Spec001Test.php::testCsrfRequiredAndApplicantCannotCreateAnimals`<br>`tests/Spec001Test.php::testVetCannotCreateAnimals` | |
| HU-03 | Alta pública de cuenta (sin roles internos) | D06; plan.md «Diseño» (`AccountService`) | T302, T304 | `tests/Spec003Test.php::testPublicRegistrationCreatesApplicantAccountAndReturnsToApplication` | |
| HU-03 | Permisos y CSRF de las solicitudes (R7) | D06; plan.md «Diseño» (`IsGranted`) | T302, T304 | `tests/Spec003Test.php::testPermissionsAndCsrfProtectApplications` | |
| HU-04 | Liberación de la reserva con motivo | D11; plan.md §2 (`cancelReservation`) | T405, T406, T407, T413, T416, T418, T421 | `tests/Spec004Test.php::testReleaseReservationRestoresAnimalToAvailableAndWaitingToPending`<br>`tests/Spec004Test.php::testReleaseReservationRequiresReason`<br>`tests/Spec004Test.php::testRejectionAndReleaseFormsKeepTypedValuesAfterValidationError` | |
| HU-04 | Una solicitud decidida no admite más decisiones | plan.md §2; T412 | T412, T414 | `tests/Spec004Test.php::testDecidedApplicationAdmitsNoFurtherDecisions`<br>`tests/Spec004Test.php::testStateConflictsAnswerConflict` | |
| HU-04 | Permisos de la coordinadora | plan.md §3 | T406, T409, T420 | `tests/Spec004Test.php::testRoleAccessControl`<br>`tests/Spec004Test.php::testReviewActionsRequireValidCsrfTokenAndCoordinatorRole` | |
| HU-08 | Estados vacíos de «Mis apadrinamientos» y del listado de la coordinadora | plan.md «Vistas»; Constitution V | T823 | `tests/Spec008Test.php::testEmptyStatesAreExplained` | |

## Resumen

| HU | Historia | CA vigentes | Iniciales | Derivados | CA con tarea | CA con prueba | Pruebas de la clase | Pruebas citadas |
|---|---|---|---|---|---|---|---|---|
| HU-01 | Registrar un animal y gestionar su cuarentena | 5 | 5 | 0 | 5/5 | 5/5 | 11 | 11 |
| HU-02 | Consultar y filtrar el catálogo público | 4 | 4 | 0 | 4/4 | 4/4 | 14 | 14 |
| HU-03 | Enviar una solicitud de adopción completa | 6 | 4 | 2 | 6/6 | 6/6 | 19 | 19 |
| HU-04 | Evaluar solicitudes, entrevistar y reservar | 5 | 5 | 0 | 5/5 | 5/5 | 23 | 23 |
| HU-05 | Formalizar la adopción y programar los seguimientos | 11 | 5 | 6 | 11/11 | 11/11 | 19 | 19 |
| HU-06 | Casas de acogida | 10 | 5 | 5 | 10/10 | 10/10 | 23 | 23 |
| HU-07 | Turnos de paseo y limpieza | 11 | 5 | 6 | 11/11 | 11/11 | 20 | 20 |
| HU-08 | Apadrinar animales | 10 | 5 | 5 | 10/10 | 10/10 | 16 | 16 |
| **Total** | | **62** | **38** | **24** | **62/62** | **62/62** | **145** | **145** |

- Criterios totales: 62 (38 iniciales de las fichas de la raíz y 24 derivados en clarify).
- Criterios con tarea: 62/62.
- Criterios con prueba: 62/62.
- Criterios sin prueba: ninguno.
- Pruebas: 145 métodos de prueba en `tests/Spec00NTest.php` (11 + 14 + 19 + 23 + 19 + 23 + 20 + 16), todos citados en la matriz o en la sección de comportamientos sin criterio. El CI de la rama integrada (76223b2, [evidencias](evidencias/revision-entrega-2026-11-21/ci-rama-integrada/)) ejecutó 144 pruebas y 1659 aserciones, OK; la prueba 145 (`Spec003Test::testStaffCannotApplyButFosterFamiliesCan`, D45, T312) se añadió después y está verificada en la suite de Spec003. Hasta que exista `docs/evidencias/revision-entrega-2026-11-21/suite-final.txt` no se afirma una ejecución completa de 145.
- Criterios con revisión cruzada registrada (columna «Revisado por»): 0/62. Está pendiente de los dos integrantes.

### Criterios con prueba parcial

Antes de la revisión previa a la entrega, ocho criterios tenían una prueba que no demostraba todo lo que dice el criterio
(informe inicial de los `analyze.md`, a74f85f). Con las pruebas nuevas, siete pasan a prueba completa y uno sigue parcial.
Los detalles y su estado están en el `analyze.md` de cada historia, sección «Análisis previo a la entrega (2026-11-21)».

| CA | Estado | Motivo | Hallazgo |
|---|---|---|---|
| CA-01-01 | **Parcial** | La prueba de alta sigue sin aseverar especie, edad ni tamaño persistidos; solo se prueba el rechazo de valores inválidos (`testInvalidAnimalAndSpoofedPhotoLeaveNoPartialWrites`). Aceptado como limitación de baja gravedad | spec-001 A6 |
| CA-01-03 | Completa | T108: `testPrivatePhotosAreOnlyServedForAvailableAnimalsOrStaff` usa PNG reales; anónimo y `ROLE_USER` reciben 404 en cuarentena y reservado, 200 en disponible, y el personal 200 | spec-001 A4, C1-05 |
| CA-01-04 | Completa | T109: la prueba comprueba veterinario, fecha y notas guardados y los rechazos de fecha futura, anterior a la recepción, notas vacías, 5001 caracteres y CSRF inválido | spec-001 A5, C1-07, C1-08 |
| CA-02-02 | Completa | T212: `testAgeBoundariesAt12And84Months` (fronteras 11/12 y 83/84 meses) y `testInvalidFiltersAreIgnored` (Q5) | spec-002 A3, A4 |
| CA-02-03 | Completa | T108 cubre las fotos de reservado y cuarentena con fichero real (el 404 del detalle y de adoptado ya estaba probado); T210 prueba las páginas de error en español | spec-002 A5, C1-05, C1-06 |
| CA-03-05 | Completa | T306: `testAnimalReservedOrAdoptedWhileTheFormIsOpenAnswersConflict` prueba por HTTP el 409 (D43); el código ya no responde 404 | spec-003 A4, C1-04 |
| CA-04-01 | Completa | T416: los formularios salen abiertos y con lo escrito tras un 422 (se prueba el HTML devuelto, no la visualización en un navegador) | spec-004 A6, C1-03 |
| CA-04-05 | Completa | T414 y T415: la carrera aprobar/rechazar se prueba con procesos reales (`tests/Support/application-race.php`) y el mensaje nombra el estado en español | spec-004 A5, A7, C1-01, C1-02 |

Quedan además dos matices que no cambian el recuento: la columna «Revisado por» sigue vacía en las 62 filas, y T725, T726
y T825 no tienen prueba nueva: T726 y T825 se apoyan en las pruebas de concurrencia existentes (los `converge.md` de HU-07
y HU-08 indican que no hay prueba nueva del interbloqueo) y T725 corrige el ayudante de la prueba existente.

### Diferencias entre documentos detectadas al elaborar la matriz

- `specs/spec-005/plan.md` (tabla «Trazabilidad», CA-05-10) cita `testConcurrentReleaseAndFormalizationKeepConsistentState`, que
  no existe; la prueba real es `testReleaseAfterConcurrentFormalizationDoesNotUndoAdoption`. La matriz usa la real.
- `specs/spec-002/plan.md` §3 lista nombres de prueba previstos que difieren de los reales; el propio plan lo avisa con
  una nota del 2026-11-18. La matriz usa los reales.

