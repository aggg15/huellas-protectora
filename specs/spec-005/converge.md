# Converge · spec-005

Contraste del código de la rama `spec-005` (commit ca68017) con spec.md, plan.md, tasks.md y la
Constitution, realizado por Claude Code el 2026-11-19, después de implement y de la verificación manual
(T525). Se sigue el método de `.agents/skills/speckit-converge/SKILL.md`: inventario de obligaciones,
clasificación missing / partial / contradicts / unrequested y tareas añadidas al final de tasks.md sin
modificar las anteriores, la spec ni el plan. La skill `$speckit-converge` no se ha invocado como
comando. `check-prerequisites.sh --require-spec --require-tasks` es correcto. No hay hooks.

Las tareas marcadas no se aceptan como prueba: cada obligación se ha comprobado en el código y con las
pruebas ([evidencia automática](../../docs/evidencias/spec-005-phpunit.txt)) o con el recorrido en
Chrome ([evidencia manual](../../docs/evidencias/spec-005-manual/README.md)).

## Obligaciones contrastadas

| Origen | Estado | Comprobación |
|---|---|---|
| CA-05-01 contrato | Cumplido | `testFormalizationGeneratesContractLinkedToAnimalAndAdopter`; recorrido R1 (capturas 02, 03, 05) |
| CA-05-02 cuota y adoptado | Cumplido | `testFormalizationRecordsFeeAndMarksAnimalAdopted`, `testFosteredAnimalLeavesFosterWhenAdopted` |
| CA-05-03 seguimientos | Cumplido | `testTwoFollowUpsAreScheduledAtOneAndSixMonths`, `testFollowUpListIsOrderedByDate`; R1 |
| CA-05-04 web pública | Cumplido | `testAdoptedAnimalDisappearsFromPublicCatalogDetailAndPhotos`; R1 (visita anónima) |
| CA-05-05 HU-08 | Cumplido en su alcance (D18) | `testAdoptionIsIdentifiableFromTheAnimal` |
| CA-05-06 precondiciones | Cumplido | `testFormalizationRequiresApprovedApplicationAndReservedAnimal` |
| CA-05-07 abono | Cumplido | `testFormalizationRequiresValidFeeAndPaymentConfirmation`; R2 (422 por teclado) |
| CA-05-08 cierre de solicitudes | **Parcial** | Lógica y vista de escritorio correctas (`testFormalizationClosesOtherApplicationsAndFreesOpenSlots`). En móvil, el estado y el motivo quedan ocultos: C1 |
| CA-05-09 fin de mes | Cumplido | `testFollowUpDatesUseLastDayOfShorterMonths`, `testFollowUpDatesUseMadridCalendarDay` |
| CA-05-10 concurrencia | Cumplido | Dos pruebas de carrera; la de liberación falla sin T512 (antes y después registrados) |
| CA-05-11 permisos | Cumplido | Tres pruebas; R1 (adoptante 403 en seguimientos) |
| R1–R2, R4–R9 | Cumplido | Cubiertas por los criterios anteriores |
| R3 contrato inmutable | **Parcial** | Implementado con la copia de datos en `Adoption`, pero ninguna prueba lo verifica: C2 |
| Plan: modelo, migración, únicos en el mapeo | Cumplido | `doctrine:schema:validate` OK; migrate up/down/up |
| Plan: orden de bloqueo y relectura | Cumplido | `AdoptionService::formalize`, `ApplicationService::cancelReservation` |
| Plan: rutas, vistas, impresión, Stimulus `print` | Cumplido | Rutas registradas; R1 (captura 03) |
| Constitution V: móvil, escritorio y teclado | **Parcial** | Teclado y formularios correctos en 390 px (R2); «Mis solicitudes» no es legible en móvil: C1 |
| Constitution V: éxito, error y ausencia de resultados | Cumplido | Mensaje de éxito, 422/409 explicados, estado vacío de seguimientos |
| Constitution II/III: trazabilidad y base común | Cumplido | `data-model.md` y tareas coherentes con el código |

## Hallazgos

| ID | Tipo | Gravedad | Origen | Evidencia | Trabajo pendiente |
|---|---|---|---|---|---|
| C1 | partial | MEDIUM | CA-05-08 (escenario de «Mis solicitudes»); Constitution V (uso en móvil) | `templates/application/mine.html.twig`: a 390 px la tabla se desplaza dentro de `.table-container`; estado cortado («Adopción fo…»), motivo y enlace al contrato fuera de la vista ([captura 04](../../docs/evidencias/spec-005-manual/04-movil-mis-solicitudes.png)). La comprobación automática no lo detecta porque la página no se desplaza | Presentar «Mis solicitudes» en móvil sin desplazamiento interno: estado, motivo y enlace visibles |
| C2 | partial | LOW | R3 / D13 (contrato inmutable) | `Adoption` copia los datos al formalizar y `contract.html.twig` solo usa la copia, pero ninguna prueba cambia la cuenta o el animal después | Prueba que modifique nombre, email y datos del animal tras formalizar y compruebe que el contrato no cambia |
| C3 | unrequested | LOW | plan: estado `formalized` | `AdoptionApplication::isFormalized()` no se usa en ningún sitio (las vistas comparan `status`) | Eliminar el método o justificar su uso |

Fuera del alcance de HU-05, sin tarea: el botón «Liberar reserva (D11)» desborda su tarjeta (capturas 01 y
06). Su marcado no ha cambiado desde cdb0416 (HU-04); se comunica a `abcb5-ua` en la PR.

Métricas: 11 criterios y 9 reglas contrastados; 4 decisiones del plan; 3 principios de la Constitution
con obligaciones comprobables (II, III, V). Hallazgos: 0 missing, 2 partial, 0 contradicts, 1 unrequested.
Por gravedad: 0 críticos, 0 altos, 1 medio y 2 bajos.

Resultado: se añaden T528–T530 en «Phase 8: Convergence» de [tasks.md](tasks.md). Se ejecutarán con
implement y se repetirá converge sobre el resultado. C1 toca una vista compartida con HU-03 y HU-04.
