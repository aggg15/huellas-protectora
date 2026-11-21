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

## Segunda pasada · 2026-11-19, tras implementar T528–T530

Repetida sobre el código con T528–T530 (publicado en el commit que acompaña a este informe), con el mismo
método y el mismo inventario. Las tres tareas se han comprobado en el código y con pruebas, no por estar
marcadas.

| Hallazgo | Estado | Comprobación |
|---|---|---|
| C1 · CA-05-08, Constitution V | Resuelto | `mine.html.twig` con `stacked-table` y `data-label`; en Chrome a 390 px no hay desplazamiento de la página ni del contenedor, y el estado, el motivo y el enlace al contrato son visibles (6/6, [captura 07](../../docs/evidencias/spec-005-manual/07-movil-mis-solicitudes-apiladas.png)); a 1280 px se mantiene la tabla |
| C2 · R3 / D13 | Resuelto | `testContractKeepsDeliveryDataAfterLaterChanges` (9 aserciones) |
| C3 · unrequested | Resuelto | `isFormalized()` eliminado; sin referencias en `src`, `templates` ni `tests` |

Efectos colaterales revisados: `mine.html.twig` es la vista de HU-03/04 y solo cambian la clase y los
atributos `data-label`; el contenido no varía y Spec003/Spec004 pasan. El CSS se limita a `.stacked-table`
y a anchos de hasta 760 px. Si un navegador no admite `:has()`, el contenedor puede seguir desplazándose,
pero la tabla apilada ya ocupa solo el ancho disponible. Suite completa: 58/58, esquema y linters
correctos ([evidencia](../../docs/evidencias/spec-005-phpunit.txt)).

Hallazgos nuevos: ninguno (0 missing, 0 partial, 0 contradicts, 0 unrequested). Según el método, esta
pasada no añade tareas ni fase a tasks.md. Resultado: **convergido**. Quedan fuera del alcance de HU-05 el
desbordamiento de «Liberar reserva (D11)» (HU-04) y la revisión de `abcb5-ua` (T527).

## Pasada de revisión previa a la entrega (2026-11-21)

Origen: revisión de código previa a la entrega. Ejecutada por `aggg15` asistido por Claude Code; revisión pendiente.

| Hallazgo | Gravedad | Corrección | Prueba | Resultado antes → después |
|---|---|---|---|---|
| C2-12a · la prueba de formalización rechazada no comprobaba efectos colaterales | LOW | Solo se modifica la prueba: crea otra solicitud en espera y, tras cada rechazo, comprueba que sigue en espera, que no hay adopción y que no hay seguimiento | `Spec005Test::testFormalizationRequiresApprovedApplicationAndReservedAnimal` (ampliada; no se renombra ninguna prueba) | Antes y después: pasa. Es una prueba de refuerzo: el código ya se comportaba bien, así que no puede fallar antes de una corrección de código |

Nota del 2026-11-21 (revisión previa a la entrega, C1-01): las decisiones de HU-04 (aprobar, rechazar y entrevista) pasan a bloquear animal y solicitud como la formalización de esta historia; véase `specs/spec-004/tasks.md`, T414. El botón «Liberar reserva» ya no lleva «(D11)» (T418).
