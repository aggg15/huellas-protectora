# Analyze · spec-002

Revisión de solo lectura de `spec.md`, `plan.md`, `data-model.md` y `tasks.md` antes de iniciar la implementación.

| Criterio | Plan / Tarea | Comprobación prevista |
|---|---|---|
| CA-02-01 | T203, T204, T205; `PublicAnimalController::catalog()` | Acceso anónimo GET a `/animales`, respuesta 200 y visualización de animales disponibles sin autenticación. |
| CA-02-02 | T203, T204, T205; `AnimalService::findAvailableAnimals()` | Peticiones con filtros `species`, `size`, `age` combinados con AND; verificación de filtrado exacto y mensaje ante ausencia de coincidencias. |
| CA-02-03 | T203, T204; exclusión en query y 404 en `PublicAnimalController::show()` | Animales en cuarentena, reservados y adoptados ausentes del catálogo; petición anónima a `/animales/{id}` devuelve 404; fotos en `/fotos/{id}` devuelven 404. |
| CA-02-04 | T205, T206; vistas Twig con Bulma | Animal disponible con `fostered=true` visible en catálogo y ficha pública con distintivo visual "En casa de acogida". |

## Hallazgos de coherencia
- No se detectan criterios sin tarea asignada.
- No se requieren nuevas tablas ni migraciones de base de datos; la entidad `Animal` ya contiene los campos `status`, `species`, `size`, `ageMonths` y `fostered` con sus restricciones de integridad en PostgreSQL (`Version20261105150000`).
- Las rutas `/animales` y `/animales/{id}` complementan limpiamente las rutas administrativas `/admin/animales` y `/admin/animales/{id}` de `AnimalController` (que está protegido bajo `ROLE_STAFF`).
- La desclasificación previa obligatoria (`releaseDueAnimals()`) garantiza la coherencia con HU-01 (D01).

## Análisis previo a la entrega (2026-11-21)

Revisión de solo lectura de spec.md, clarify.md, plan.md, tasks.md, converge.md, `tests/Spec002Test.php`,
../../data-model.md y la Constitution, realizada por Claude Code el 2026-11-21 sobre el commit a74f85f. Se sigue el
método de `.agents/skills/speckit-analyze/SKILL.md` (inventario, pasadas de duplicación, ambigüedad,
infraespecificación, Constitution, cobertura e incoherencia; gravedades CRITICAL/HIGH/MEDIUM/LOW), pero la skill
`$speckit-analyze` no se ha invocado como comando. No existe `.specify/extensions.yml`, así que no hay hooks. El
análisis anterior (arriba) se conserva sin cambios. Los hallazgos de código C1-0X proceden de la revisión de código
del mismo día; se registran en `tasks.md` y `converge.md` por el equipo que los corrige, y aquí solo se enlazan.

### Informe inicial

| ID | Categoría | Gravedad | Ubicación | Hallazgo | Recomendación |
|---|---|---|---|---|---|
| A1 | Incoherencia | MEDIUM | clarify.md | Las cuatro secciones de `clarify.md` describen el resultado, pero no hay registro de preguntas, respuestas y criterios ni diferencias con la ficha inicial, que el enunciado §5.1 exige; las dos preguntas de la ficha inicial quedaban sin respuesta localizable. | Añadir la tabla y las diferencias en `clarify.md` (hecho en la ronda del 2026-11-21). |
| A2 | Incoherencia | LOW | plan.md §3 | La lista de pruebas prevista usa nombres que no existen: `testFilterBySpeciesSizeAndAge`, `testFilterWithoutMatchesShowsEmptyStateMessage`, `testQuarantineReservedAndAdoptedAnimalsAreExcluded` y `testNonAvailableAnimalPublicDetailReturns404` (los reales son `testFilterBySpecies` y `testFilterBySizeAndAgeCombined`, `testFilterWithoutMatchesShowsEmptyState`, `testQuarantineReservedAndAdoptedAnimalsExcludedFromCatalog` y `testNonAvailableAnimalDetailReturns404`). El propio plan lo avisa con una nota del 2026-11-18 que remite a converge. | Aceptar la nota; la matriz `docs/trazabilidad.md` usa los nombres reales. |
| A3 | Cobertura | LOW | CA-02-02; D05 | Las pruebas de edad usan 6, 24 y 96 meses: ninguna comprueba las fronteras de D05 (11 y 12 meses; 83 y 84 meses), donde un `<` o un `<=` equivocado pasaría desapercibido. | Añadir una prueba con animales de 11, 12, 83 y 84 meses. |
| A4 | Infraespecificación | LOW | CA-02-02; `PublicAnimalController::catalog` | La spec solo define el filtro vacío o no enviado. Con un valor no previsto (`species=bird`) el servicio lo ignora y lista todo, sin que la spec ni ninguna prueba lo recojan. | Decidir el comportamiento (clarify Q5) y cubrirlo con una prueba. |
| A5 | Cobertura | MEDIUM | CA-02-03, escenario 3.2 | El escenario exige que `/fotos/{id}` responda 404 para los animales no disponibles, pero `Spec002Test` no lo prueba. Se cubre fuera: cuarentena en `Spec001Test` (con un fichero inexistente, véase C1-05) y adoptado en `Spec005Test`; reservado y cuarentena con fichero real no tienen prueba. | Ampliar la prueba de fichas (ver C1-05) con fotografías reales de un animal reservado y de uno en cuarentena. |
| A6 | Cobertura | LOW | Constitution V; tasks.md | Ninguna tarea de HU-02 registra la verificación en móvil, escritorio y teclado. La cubre en parte `docs/evidencias/spec-005-manual` (recorrido 1: visita anónima a `/animales` a 1280 y 390 px), sin recorrer filtros ni la ficha. | Registrar un recorrido propio del catálogo y sus filtros, o citar el existente con su alcance. |

Sin hallazgos CRITICAL ni HIGH propios. No hay conflictos con la Constitution salvo lo señalado en A6: verificación
de recorrido normal, entradas inválidas y permisos o estado en T208.

### Cobertura

| Requisito | ¿Tarea? | Tareas | Pruebas (`tests/Spec002Test.php`) | Nota |
|---|---|---|---|---|
| CA-02-01 consulta pública | Sí | T203, T204, T205, T207, T208 | `testAnonymousCanViewPublicCatalogWithAvailableAnimals`, `testExpiredQuarantinePromotesAutomaticallyOnCatalogAccess` | |
| CA-02-02 filtros | Sí | T203, T204, T205, T208 | `testFilterBySpecies`, `testFilterBySizeAndAgeCombined`, `testFilterWithoutMatchesShowsEmptyState` | Véanse A3 y A4. |
| CA-02-03 exclusión | Sí | T203, T204, T208 | `testQuarantineReservedAndAdoptedAnimalsExcludedFromCatalog`, `testNonAvailableAnimalDetailReturns404` | Véase A5. |
| CA-02-04 acogida | Sí | T205, T206, T208 | `testFosteredAnimalShowsFosterBadgeInCatalogAndDetail` | |

Tareas sin requisito: ninguna. T201, T202 y T209 corresponden a obligaciones del proceso (Constitution II y V).

Métricas (commit a74f85f): 4 criterios (0 derivados); 9 tareas (T201–T209); 8 pruebas; criterios sin tarea 0;
criterios sin prueba 0, con una cobertura parcial (A5); ambigüedades 1 en los criterios (A4) y 3 decisiones
pendientes de confirmar en clarify (Q4–Q6); duplicaciones 0; críticos 0.

### Registro de hallazgos

La gravedad de los C1-0X de la tabla es la provisional de la revisión; la fijada al corregir consta en la tabla de la «Pasada de revisión previa a la entrega» de `converge.md`. Estados: Corregido (con su tarea), Decidido (con su Dxx o Qn), Aceptado (con su justificación) y Abierto (nada hecho). Actualizado el 2026-11-21 contra `tasks.md`, `converge.md`, `clarify.md` y `docs/decisiones.md` de la rama `revision-entrega`.

| ID | Historia / criterio | Gravedad | Evidencia | Decisión | Responsable | Estado |
|---|---|---|---|---|---|---|
| A1 | HU-02 / CA-02-01 a CA-02-04 | MEDIUM | `clarify.md` sin tabla de preguntas ni diferencias | Añadir ambas | `abcb5-ua` | Corregido en clarify.md el 2026-11-21; Q4–Q6 decididas el mismo día |
| A2 | HU-02 / documentación | LOW | plan.md §3 frente a `tests/Spec002Test.php` | Aceptar la nota del plan y usar los nombres reales en la matriz | `abcb5-ua` | Aceptado y documentado: la nota del plan remite a converge y la matriz `docs/trazabilidad.md` usa los nombres reales de las pruebas |
| A3 | HU-02 / CA-02-02, D05 | LOW | Pruebas con 6, 24 y 96 meses | Añadir fronteras 11/12 y 83/84 | `abcb5-ua` | Corregido: T212 (`testAgeBoundariesAt12And84Months`, C1-20b) |
| A4 | HU-02 / CA-02-02 | LOW | `AnimalService::findAvailableAnimals` ignora valores no admitidos | Decidir y probar (Q5) | `abcb5-ua` | Decidido: Q5 (2026-11-21), ignorar el valor no previsto y no restringir; probado en T212 (`testInvalidFiltersAreIgnored`) |
| A5 | HU-02 / CA-02-03 | MEDIUM | Escenario 3.2 sin prueba de fotos en `Spec002Test` | Ver C1-05 | `abcb5-ua` | Corregido: T108 (`Spec001Test::testPrivatePhotosAreOnlyServedForAvailableAnimalsOrStaff`: fotos reales de un animal reservado y de uno en cuarentena, 404 para el público y 200 para el personal; la prueba vive en `Spec001Test`) |
| A6 | HU-02 / Constitution V | LOW | tasks.md sin verificación manual | Registrar o citar el recorrido de HU-05 con su alcance | `abcb5-ua` | Aceptado como limitación: no se registró un recorrido manual propio del catálogo; se cita el recorrido 1 de `docs/evidencias/spec-005-manual` (visita anónima a `/animales` a 1280 y 390 px), sin filtros ni ficha. Lo que sí cubren pruebas automáticas es el HTML de filtros, ficha y 404 |
| C1-05 | HU-02 / CA-02-03 (y CA-01-03) | MEDIUM (provisional) | Prueba de fotos privadas vacía (véase A5) | Revisión de código del 2026-11-21 | `abcb5-ua` (prueba de HU-01 de `aggg15`) | Corregido: T108 |
| C1-06 | HU-02 / CA-02-03 | LOW (provisional) | La página 404 de una ficha no disponible, que ve el público, está en inglés | Revisión de código del 2026-11-21 | `abcb5-ua` | Corregido: T210 (`testCustomErrorTemplatesRenderInSpanish`, `testErrorPagesAreServedInSpanishWithoutDebug`) |

### Informe final (2026-11-21)

Informe de cierre de esta pasada, redactado por Claude Code el 2026-11-21 después de integrar las correcciones de la
rama `revision-entrega` (HEAD 114b74d) y de contrastar este registro con `tasks.md` (fase 2: T210–T213), `converge.md`
(«Pasada de revisión previa a la entrega»), `clarify.md` (Q4–Q6, decididas el 2026-11-21) y `docs/decisiones.md`. El
informe inicial de arriba se conserva sin cambios.

| Métrica (rama `revision-entrega`) | Valor |
|---|---|
| Criterios de aceptación | 4 (0 derivados) |
| Tareas | 13 (T201–T213) |
| Pruebas de `tests/Spec002Test.php` | 14 (8 del informe inicial + 6 nuevas: T210 ×2, T211 y T212 ×3) |
| Criterios sin tarea | 0 |
| Criterios sin prueba | 0 |
| Criterios con prueba parcial | 0 (CA-02-02 pasa a completa con T212; CA-02-03 con T108) |
| Hallazgos CRITICAL / HIGH | 0 / 0 |

Hallazgos por estado (8): **Corregido 5** (A1, A3, A5, C1-05, C1-06); **Decidido 1** (A4, Q5); **Aceptado 2** (A2 y A6);
**Abierto 0**. Las preguntas Q4 (orden: más recientes primero) y Q6 (sin paginar) también se decidieron el 2026-11-21 y
no generaron hallazgos propios.

Verificación: el CI de la rama integrada (76223b2) dio `OK (14 tests, 136 assertions)` en esta clase y
`OK (144 tests, 1659 assertions)` en la suite completa, con todos los pasos correctos
([`summary.txt`](../../docs/evidencias/revision-entrega-2026-11-21/ci-rama-integrada/summary.txt), [Spec002](../../docs/evidencias/revision-entrega-2026-11-21/ci-rama-integrada/13-phpunit-Spec002Test.log)). Tras el CI se volvió a ejecutar Spec002 junto con Spec003 y
Spec008 (49 pruebas OK, según el README de la carpeta de evidencias). Según `converge.md`, C1-06 falla sin las plantillas y pasa con ellas; C1-15 y C1-20 se
añadieron como cobertura y no se ejecutaron sobre el código anterior.

Lo que no se declara: la revisión cruzada de los cambios de la rama `revision-entrega` por `aggg15` no consta (la
ejecución de T210–T213 figura a nombre de `aggg15` asistido por Claude Code, mientras que HU-02 es de `abcb5-ua`), y la
matriz mantiene 0/62 revisiones anotadas. El resto del criterio de cierre (0 contradicciones críticas o altas y ningún
criterio sin tarea y prueba) se cumple.

Nota de método: ronda realizada con Claude Code siguiendo el procedimiento de
`.agents/skills/speckit-analyze/SKILL.md`; no se ha ejecutado la skill de Codex.
