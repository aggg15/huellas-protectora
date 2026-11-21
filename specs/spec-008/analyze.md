# Analyze · spec-008

Revisión de solo lectura de spec.md, clarify.md, plan.md, tasks.md, ../../data-model.md y la Constitution,
realizada por Claude Code el 2026-11-19 (T804). Se sigue el método de `.agents/skills/speckit-analyze/SKILL.md`
(inventario, pasadas de duplicación, ambigüedad, infraespecificación, Constitution, cobertura e incoherencia;
gravedades CRITICAL/HIGH/MEDIUM/LOW), pero la skill `$speckit-analyze` no se ha invocado como comando. No existe
`.specify/extensions.yml`, así que no hay hooks. `check-prerequisites.sh --json --require-spec --require-tasks
--include-tasks` devuelve código 0 para spec-008.

Contexto: en esta sesión el código de HU-08 se redactó en paralelo a los documentos; el análisis se hizo sobre los
documentos (con la primera versión de las pruebas para contrastar la cobertura) antes de la regresión completa, la
verificación manual y converge. No se presenta como un análisis previo a cualquier línea de código.

## Informe inicial

| ID | Categoría | Gravedad | Ubicación | Hallazgo | Recomendación |
|---|---|---|---|---|---|
| A1 | Cobertura | MEDIUM | spec.md CA-08-03 («Las notas de acogida de HU-06 no se muestran a los padrinos»); tasks.md T814–T815 | La spec garantiza que las notas internas de acogida (D24) no llegan a los padrinos, pero ninguna tarea de verificación lo comprueba. Es la frontera entre HU-06 y HU-08 que la ficha inicial pedía no asumir. | Añadir a la prueba de CA-08-03 un animal en acogida con una nota interna y comprobar que el padrino no la ve. |
| A2 | Cobertura | LOW | spec.md casos límite («el animal pasa a reservado… sigue activo»; «novedad publicada en el mismo segundo…»); tasks.md T814–T815 | Dos casos límite especificados no tienen comprobación asignada. | Incluirlos en T814 (reservado → «Activo», sin decisión) y T815 (novedad en el mismo segundo del inicio → visible). |
| A3 | Terminología | LOW | plan.md «Vistas» (`sponsorship/admin_index`); primera versión de la vista | La vista de la coordinadora usaba «Padrino o madrina» y «N padrinos pendientes de decidir». La cuenta no registra el género y el resto de la interfaz evita esas formas (HU-07, analyze A3). | Usar «Persona que apadrina» y «N apadrinamientos pendientes de decisión». |

Sin hallazgos CRITICAL ni HIGH. No hay conflictos con la Constitution: verificación de recorrido normal, entradas
inválidas y permisos o estado (V) en T814–T817; uso en móvil, escritorio y teclado en T818; coordinación de los
archivos comunes (IV) en T812, T813 y T821.

### Cobertura

| Requisito | ¿Tarea? | Tareas | Nota |
|---|---|---|---|
| CA-08-01 alta | Sí | T805, T807, T810, T812, T814 | |
| CA-08-02 mínimo | Sí | T807, T812, T815 | |
| CA-08-03 novedades | Sí | T809, T811, T813, T814, T815 | Véanse A1 y A2. |
| CA-08-04 pendiente | Sí | T809, T813, T814 | Véase A2. |
| CA-08-05 elección | Sí | T808, T810, T813, T814 | |
| CA-08-06 cardinalidad | Sí | T805, T807, T815 | |
| CA-08-07 disponibles | Sí | T807, T810, T815 | |
| CA-08-08 elecciones no válidas | Sí | T808, T810, T815 | |
| CA-08-09 concurrencia | Sí | T807, T808, T816 | |
| CA-08-10 permisos | Sí | T810, T811, T812, T817 | Véase A3 (texto de la vista). |
| R1–R10 | Sí | Cubiertas por las tareas de sus criterios | |

Tareas sin requisito: ninguna. T801–T804 y T818–T821 corresponden a obligaciones del proceso
(Constitution II y V, enunciado §5.2–5.4).

Métricas: 10 criterios y 10 reglas; 21 tareas; cobertura 100 % de criterios (una frontera y dos casos límite sin
prueba, A1–A2); ambigüedades 0; infraespecificaciones 0; duplicaciones 0 (las reglas R resumen los criterios a
propósito); críticos 0.

Siguiente acción: corregir A1 a A3 en sus fuentes antes de cerrar la verificación.
Actualización 2026-11-21: A1–A3 se corrigieron en T802–T804 (véase tasks.md). Estado final: integrada en `main` con la PR #12 (2026-11-20).

## Registro de hallazgos

| ID | Historia / criterio | Gravedad | Evidencia | Decisión | Responsable | Estado |
|---|---|---|---|---|---|---|
| A1 | HU-08 / CA-08-03 | MEDIUM | Frontera con D24 sin prueba | Nota interna de acogida en la prueba de CA-08-03 | `abcb5-ua` | Corregido en la prueba |
| A2 | HU-08 / CA-08-03, CA-08-04 | LOW | Casos límite sin prueba | Ampliar las pruebas de T814 y T815 | `abcb5-ua` | Corregido en las pruebas |
| A3 | HU-08 / CA-08-04 | LOW | Textos con género en la vista de la coordinadora | «Persona que apadrina», «apadrinamientos pendientes de decisión» | `abcb5-ua` | Corregido en la vista |

## Correcciones y repetición del análisis

Correcciones aplicadas el 2026-11-19 después de redactar el informe inicial anterior, que se conserva sin cambios:

- A1: `testSponsorReceivesUpdatesPublishedByCoordinator` crea una acogida con una nota interna y comprueba que no aparece.
- A2: `testAdoptedAnimalOffersSponsorToContinueOrFinish` comprueba que un animal reservado sigue «Activo» sin decisión;
  `testUpdatesAreValidatedAndLimitedToTheSponsorshipPeriod`, la novedad escrita en el mismo segundo del inicio.
- A3: `sponsorship/admin_index.html.twig` usa «Persona que apadrina» y «N apadrinamientos pendientes de decisión».

### Informe final

Se repiten las mismas pasadas sobre los documentos y las pruebas corregidos. Resultado: sin hallazgos nuevos. Las rutas
del plan coinciden con T810–T811; cada vista nombrada en el plan tiene tarea (T812–T813); los casos límite de la spec
tienen comprobación asignada. Cobertura 100 % (10/10 criterios, 10/10 reglas), 0 críticos, 0 altos y 0 ambigüedades
abiertas. `Spec008` repetida tras las correcciones: 12/12 (253 aserciones).
