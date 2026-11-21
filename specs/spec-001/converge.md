# Converge · spec-001

Contraste posterior del código con los cinco criterios de spec.md y las tareas: todos disponen de comportamiento y comprobación. No se añaden tareas correctivas artificiales. Evidencia: ../../docs/evidencias/spec-001-phpunit.txt.

La revisión cubre alta, persistencia, cuarentena, foto protegida, veterinario y controles de rol/CSRF. La ficha pública y los filtros corresponden a HU-02, que se implementa después. Revisión cruzada del compañero pendiente. Actualización 2026-11-21: integrada en `main` con la PR #1; revisión aprobada por `abcb5-ua` (según `docs/agrupamiento.md`).

## Pasada de revisión previa a la entrega (2026-11-21)

| Hallazgo | Gravedad | Corrección | Prueba | Resultado antes → después |
|---|---|---|---|---|
| C1-05 | media | Prueba con PNG reales en el almacén en lugar de un fichero inexistente | `testPrivatePhotosAreOnlyServedForAvailableAnimalsOrStaff` | Antes: la prueba vacía pasaba por el 404 de un fichero inexistente. Con la comprobación de estado de `MediaController` quitada temporalmente, la nueva falla (200 en lugar de 404 para el anónimo); restaurada, pasa |
| C1-07 | baja | Prueba ampliada (persistencia y todas las ramas de error) y prueba de `ROLE_VET` en el alta | `testVeterinaryReviewAndRoleValidation`, `testVetCannotCreateAnimals` | Cobertura nueva; pasan sobre el código existente (no había defecto de código) |
| C1-14 | baja | Zona PHP fijada a UTC en `Kernel` y `tests/bootstrap.php` | `testPhpTimezoneIsUtcAndStoredDatesAreUtc` | Pasa. No se ejecutó sobre el código anterior: el contenedor ya trabaja en UTC, así que la prueba protege contra regresiones pero allí no habría fallado |
| C1-17 | baja | «Animales liberados: N» en el comando | (cambio de texto, sin prueba específica) | La verificación independiente detectó que faltaba el espacio («Animales liberados:N»); corregido después de la fusión |
| C1-19 | baja | `PhotoStorage` usa `var/uploads_test` en `when@test`, vaciado en `tearDown` | toda la suite | Antes las pruebas escribían en `var/uploads`; después, en `var/uploads_test` |
| C1-20a | baja | Prueba del límite de 240 h en la consulta SQL | `testReleaseDueAnimalsSqlBoundaryAt240Hours` | Cobertura nueva (márgenes de 30 s); pasa |
| C1-09, C1-18 (desde spec-003) | baja | Normalización de procedencia y observaciones y repoblado del formulario de revisión (T115) | `Spec003Test::testAnimalOriginIsNormalizedAndRejectsNulAndInvalidUtf8`, `Spec003Test::testVeterinaryReviewKeepsTypedValuesAndRejectsNulAndInvalidUtf8` | Fallan sobre el código anterior y pasan tras la corrección (ver la evidencia de spec-003) |
