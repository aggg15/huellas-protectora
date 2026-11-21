# Analyze · spec-001

Revisión de solo lectura de spec.md, plan.md, data-model.md y tasks.md antes de cerrar la implementación.

| Criterio | Plan/tarea | Comprobación prevista |
|---|---|---|
| CA-01-01/02 | T102/T103; AnimalService y fotos | Alta HTTP, identidad persistida, estado cuarentena. |
| CA-01-03 | T102; permisos de página y MediaController | Anónimo: redirección interna y 404 en ficha/foto. |
| CA-01-04 | T102/T103; VeterinaryReview y ROLE_VET | Guardar revisión y negar rol voluntario. |
| CA-01-05 | T102; reloj y promoción | 240 horas exactas, conservación de reservas. |

No se detectan criterios sin tarea. Se ha aclarado el nombre de columna generado por Doctrine antes de validar la promoción. Las restricciones de especie/tamaño/edad/estado se incorporan en migración adicional.

## Análisis previo a la entrega (2026-11-21)

Revisión de solo lectura de spec.md, clarify.md, plan.md, tasks.md, `tests/Spec001Test.php`, ../../data-model.md y la
Constitution, realizada por Claude Code el 2026-11-21 sobre el commit a74f85f. Se sigue el método de
`.agents/skills/speckit-analyze/SKILL.md` (inventario, pasadas de duplicación, ambigüedad, infraespecificación,
Constitution, cobertura e incoherencia; gravedades CRITICAL/HIGH/MEDIUM/LOW), pero la skill `$speckit-analyze` no
se ha invocado como comando. No existe `.specify/extensions.yml`, así que no hay hooks. El análisis anterior
(arriba) se conserva sin cambios. Los hallazgos de código C1-0X proceden de la revisión de código del mismo día;
se registran en `tasks.md` y `converge.md` por el equipo que los corrige, y aquí solo se enlazan.

### Informe inicial

| ID | Categoría | Gravedad | Ubicación | Hallazgo | Recomendación |
|---|---|---|---|---|---|
| A1 | Incoherencia | MEDIUM | clarify.md; spec.md CA-01-04 y CA-01-05 | `clarify.md` era un resumen de tres líneas: sin preguntas, respuestas ni diferencias con la ficha inicial, mientras la spec remite a clarify para concretar el registro veterinario y la transición a disponible. La concreción estaba solo en D01 y D02. | Registrar las preguntas, respuestas y diferencias en `clarify.md` (hecho en la ronda del 2026-11-21). |
| A2 | Cobertura | MEDIUM | tasks.md T101–T107 | Las tareas son de fase (T102 implementa los cinco criterios) y ninguna nombra un criterio. La relación criterio → tarea solo se deduce de la tabla del analyze anterior. | Mantener la matriz `docs/trazabilidad.md` como fuente criterio → decisión → tarea → prueba. |
| A3 | Incoherencia | LOW | tasks.md («Responsable académico y revisor: pendientes… Codex»); plan.md «Estado y cierre» («revisión humana de la PR #1 permanece pendiente»); converge.md; spec.md (sin responsable) | Los textos no recogen lo que consta en `docs/agrupamiento.md`: HU-01 es de `aggg15` (PR #1) y `abcb5-ua` aprobó la PR. | Actualizar las menciones en la pasada final, sin atribuir nada que no conste. |
| A4 | Cobertura | MEDIUM | CA-01-03; `Spec001Test::testAnonymousCannotSeeQuarantineOrPhotos` | La foto de la prueba apunta a `no-existe.png`: `MediaController` devuelve 404 también porque el fichero no existe, de modo que la prueba no demuestra que la regla de privacidad por estado funcione. Origen: C1-05. | Usar un fichero real y comprobar que el personal sí lo recibe y la persona anónima no. |
| A5 | Cobertura | MEDIUM | CA-01-04; `Spec001Test::testVeterinaryReviewAndRoleValidation` | La prueba solo cuenta que existe una revisión: no comprueba veterinario, fecha ni notas, ni la validación de fecha, aunque el nombre promete «Validation». Origen: C1-07. | Ampliar la prueba con los datos guardados y los rechazos de fecha y notas. |
| A6 | Cobertura | LOW | CA-01-01; `Spec001Test::testVolunteerCreatesIdentityAndPhotoInQuarantine` | El criterio lista código, especie, edad, procedencia y fotos, pero la prueba solo comprueba código, estado, foto y procedencia; especie, edad y tamaño (D03) no se aseveran. | Añadir las aserciones o citar la prueba como cobertura parcial. |

Sin hallazgos CRITICAL ni HIGH propios. No hay conflictos con la Constitution: verificación de recorrido normal,
entradas inválidas y permisos (V) en T104; el uso en móvil y teclado no consta en las tareas de HU-01.

### Cobertura

| Requisito | ¿Tarea? | Tareas | Pruebas (`tests/Spec001Test.php`) | Nota |
|---|---|---|---|---|
| CA-01-01 ficha | Sí | T102, T103, T104 | `testVolunteerCreatesIdentityAndPhotoInQuarantine`, `testInvalidAnimalAndSpoofedPhotoLeaveNoPartialWrites` | Véase A6. |
| CA-01-02 cuarentena | Sí | T102, T104 | `testBoundaryIsExactlyTenPeriodsOf24Hours`, `testVolunteerCreatesIdentityAndPhotoInQuarantine` | |
| CA-01-03 fuera de la web | Sí | T102, T104 | `testAnonymousCannotSeeQuarantineOrPhotos` | Véase A4. |
| CA-01-04 revisión | Sí | T102, T103, T104 | `testVeterinaryReviewAndRoleValidation` | Véase A5. |
| CA-01-05 disponible | Sí | T102, T104 | `testBoundaryIsExactlyTenPeriodsOf24Hours`, `testExpiredQuarantineReleasesAutomaticallyWithoutChangingReservations` | |
| Permisos y CSRF | Sí | T102, T104 | `testCsrfRequiredAndApplicantCannotCreateAnimals` | Sin criterio propio: queda en «Requisitos de calidad» de la spec. |

Tareas sin requisito: ninguna. T101 y T105–T107 corresponden a obligaciones del proceso (Constitution II y V,
enunciado §5.2–5.4, preparación del equipo).

Métricas (commit a74f85f): 5 criterios (0 derivados); 7 tareas (T101–T107); 7 pruebas; criterios sin tarea 0;
criterios sin prueba 0, con tres coberturas parciales (A4, A5, A6); ambigüedades 0 en los criterios, 3 decisiones
pendientes de confirmar en clarify (Q5–Q7); duplicaciones 0; críticos 0.

### Registro de hallazgos

La gravedad de los C1-0X es provisional: la fija `converge.md` de la rama `revision-entrega`.

| ID | Historia / criterio | Gravedad | Evidencia | Decisión | Responsable | Estado |
|---|---|---|---|---|---|---|
| A1 | HU-01 / CA-01-04, CA-01-05 | MEDIUM | `clarify.md` de cinco líneas antes de esta ronda | Registrar preguntas, respuestas y diferencias | `aggg15` | Corregido en clarify.md el 2026-11-21 |
| A2 | HU-01 / CA-01-01 a CA-01-05 | MEDIUM | `tasks.md` T101–T107 sin criterio por tarea | Matriz en `docs/trazabilidad.md`; sin tareas nuevas | `aggg15` (revisa `abcb5-ua`) | Mitigado en la matriz; revisión pendiente |
| A3 | HU-01 / documentación | LOW | tasks.md, plan.md y converge.md frente a `docs/agrupamiento.md` | Actualizar las menciones de responsable y revisión | `aggg15` | Abierto |
| A4 | HU-01 / CA-01-03 | MEDIUM | `Spec001Test.php` línea 27: foto `no-existe.png` | Ver C1-05 | `aggg15` | En corrección en la rama `revision-entrega` (C1-05) |
| A5 | HU-01 / CA-01-04 | MEDIUM | `Spec001Test.php` líneas 48–58: solo cuenta revisiones | Ver C1-07 | `aggg15` | En corrección en la rama `revision-entrega` (C1-07) |
| A6 | HU-01 / CA-01-01 | LOW | Aserciones de la prueba de alta | Ampliar aserciones | `aggg15` | Abierto |
| C1-05 | HU-01 / CA-01-03 (y CA-02-03) | MEDIUM (provisional) | Prueba de fotos privadas sin fichero real | Revisión de código del 2026-11-21 | `aggg15` | En corrección en la rama `revision-entrega` |
| C1-06 | HU-01 / CA-01-03 | LOW (provisional) | Las páginas 403 y 404 que ven las personas están en inglés | Revisión de código del 2026-11-21 | `aggg15` | En corrección en la rama `revision-entrega` |
| C1-07 | HU-01 / CA-01-04 | MEDIUM (provisional) | Prueba de revisión veterinaria incompleta (véase A5) | Revisión de código del 2026-11-21 | `aggg15` | En corrección en la rama `revision-entrega` |
| C1-08 | HU-01 / CA-01-04 | MEDIUM (provisional) | Solo `animal_new` se prueba sin token; la ruta `POST /admin/animales/{id}/revision` no tiene prueba de CSRF | Revisión de código del 2026-11-21 | `aggg15` | En corrección en la rama `revision-entrega` |
| C1-09 | HU-01 / CA-01-01 | MEDIUM (provisional) | `AnimalService` valida procedencia y notas con `trim()` de PHP, que no recorta espacios Unicode | Revisión de código del 2026-11-21 | `aggg15` | En corrección en la rama `revision-entrega` |

### Informe final

Este informe se cerrará después de integrar las correcciones de la rama `revision-entrega` y de repetir las
pasadas sobre los documentos y pruebas corregidos. Hoy, 2026-11-21, el estado es: A1 corregido; A2 mitigado en la
matriz; A3 y A6 abiertos; A4, A5 y C1-05 a C1-09 en corrección. No se declara cerrado: el cierre exige 0
contradicciones críticas o altas y ningún criterio sin tarea y prueba, con la revisión cruzada de `abcb5-ua`
(pendiente).

Nota de método: ronda realizada con Claude Code siguiendo el procedimiento de
`.agents/skills/speckit-analyze/SKILL.md`; no se ha ejecutado la skill de Codex.
