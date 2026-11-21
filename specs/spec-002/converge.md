# Converge · spec-002

Contraste posterior del código desarrollado con los cuatro criterios de aceptación de `spec.md`, el plan y las tareas:

| Criterio | Estado | Comprobación en `Spec002Test.php` | Resultado |
|---|---|---|---|
| **CA-02-01** | Cumplido | `testAnonymousCanViewPublicCatalogWithAvailableAnimals()` | Acceso anónimo a `/animales`, respuesta 200, visualización de fichas y fotos de animales disponibles. |
| **CA-02-02** | Cumplido | `testFilterBySpecies()`, `testFilterBySizeAndAgeCombined()`, `testFilterWithoutMatchesShowsEmptyState()` | Filtros por especie, tamaño y rangos de edad combinados con AND; respuesta adecuada ante filtros sin coincidencias con mensaje amigable y enlace de reinicio. |
| **CA-02-03** | Cumplido | `testQuarantineReservedAndAdoptedAnimalsExcludedFromCatalog()`, `testNonAvailableAnimalDetailReturns404()` | Exclusión estricta de cuarentena, reserva y adopción en catálogo; respuestas HTTP 404 en `/animales/{id}` para animales no disponibles. |
| **CA-02-04** | Cumplido | `testFosteredAnimalShowsFosterBadgeInCatalogAndDetail()` | Distintivo visual "En casa de acogida" en tarjetas del catálogo y banner informativo en la ficha de detalle pública. |

## Hallazgos de convergencia
* Se conserva total compatibilidad con la implementación previa de HU-01 (`Spec001Test.php` y `MediaController`).
* El flujo de promoción de cuarentena vencida (`releaseDueAnimals()`) opera de forma automática e integrada al consultar el catálogo (`testExpiredQuarantinePromotesAutomaticallyOnCatalogAccess()`).
* Todas las tareas de `tasks.md` (T201 a T209) han quedado cubiertas y cerradas satisfactoriamente.

## Pasada de revisión previa a la entrega (2026-11-21)

| Hallazgo | Gravedad | Corrección | Prueba | Resultado antes → después |
|---|---|---|---|---|
| C1-06 (transversal a todas las historias, registrado aquí) | media | Plantillas `error`, `error403` y `error404` en español bajo `templates/bundles/TwigBundle/Exception/`; `framework.default_locale: es` | `testCustomErrorTemplatesRenderInSpanish`, `testErrorPagesAreServedInSpanishWithoutDebug` (kernel con `debug=false`) | Antes: páginas por defecto de Symfony en inglés. Sin las plantillas, ambas pruebas fallan con error; con ellas pasan |
| C1-15 | baja | Fotos ordenadas por `id ASC` en el catálogo y la ficha | `testPrimaryPhotoIsStableByIdAscending` | Pasa tras la corrección; no se ejecutó sobre el código anterior |
| C1-20b/d | baja | Casos límite añadidos | `testAgeBoundariesAt12And84Months`, `testInvalidFiltersAreIgnored`, `testNonFosteredAnimalShowsNoFosterBadge` | Cobertura nueva; pasan (no había defecto de código) |
| C1-17 (ficha pública) | baja | Compromiso reformulado a algo que el sistema sí registra | (texto) | Verificado por lectura |
