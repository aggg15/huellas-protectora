# Analyze · spec-007

Revisión de solo lectura de spec.md, clarify.md, plan.md, tasks.md, ../../data-model.md y la Constitution,
realizada por Claude Code el 2026-11-19 antes de implementar (T704). Se sigue el método de
`.agents/skills/speckit-analyze/SKILL.md` (inventario, pasadas de duplicación, ambigüedad, infraespecificación,
Constitution, cobertura e incoherencia; gravedades CRITICAL/HIGH/MEDIUM/LOW), pero la skill `$speckit-analyze`
no se ha invocado como comando. No existe `.specify/extensions.yml`, así que no hay hooks.
`check-prerequisites.sh --json --require-spec --require-tasks --include-tasks` devuelve código 0 para spec-007.
Contexto revisado: documentos de la rama `spec-007` antes de implementar.

## Informe inicial

| ID | Categoría | Gravedad | Ubicación | Hallazgo | Recomendación |
|---|---|---|---|---|---|
| A1 | Infraespecificación | MEDIUM | spec.md CA-07-09; plan.md «Vistas» (`shift/index`); tasks.md T711 | La spec rechaza apuntarse a un turno coincidente, pero el plan no dice cómo lo presenta el listado. Si muestra «Apuntarme» en un turno que coincide con otro de la persona, cada intento acaba en un 409 evitable. El listado ya conoce los turnos de quien consulta y sus horas. | Mostrar en esos turnos la indicación «Coincide con otro de tus turnos», sin botón «Apuntarme»; el servidor lo sigue comprobando bajo bloqueo. Añadirlo al plan y a T711. |
| A2 | Cobertura | LOW | spec.md CA-07-08 («darse de baja de un turno en el que no se figura»); casos límite «bajar el máximo hasta el número de inscritos» y «hora repetida en octubre»; tasks.md T713–T714 | Tres comportamientos especificados no tienen comprobación asignada en las tareas de verificación. | Incluirlos en T713 (máximo igual a las inscripciones → «Completo») y T714 (baja sin figurar → 409; hora repetida de octubre → primera de las dos). |
| A3 | Terminología | LOW | spec.md CA-07-02; plan.md «Vistas»; tasks.md T711 | La marca «Estás apuntado» presupone el género de la persona, y la cuenta no lo registra. El resto de la interfaz evita esas formas (p. ej., «Ya figuras»). | Usar «Te has apuntado» en la spec, el plan y la tarea. |
| A4 | Incoherencia | LOW | plan.md tabla de `ShiftService`, fila `update`; spec.md R4 | El plan exige «inicio > ahora» solo si cambian la fecha o las horas, pero no dice qué pasa con el límite de un año en ese caso, ni en qué orden se comprueban la validación (422) y el turno empezado (409). Con la fecha sin cambios, un turno que ha empezado entre la carga del formulario y el envío debe responder 409 (estado), no 422. | Precisar en el plan que las dos condiciones temporales (después de ahora y hasta un año) solo se validan si cambian la fecha o las horas, y que el turno empezado se comprueba bajo bloqueo con 409. |

Sin hallazgos CRITICAL ni HIGH. No hay conflictos con la Constitution: verificación de recorrido normal, entradas
inválidas y permisos o estado (V) en T713–T716; uso en móvil, escritorio y teclado en T718; coordinación del
archivo común (IV) en T712 y T720.

### Cobertura

| Requisito | ¿Tarea? | Tareas | Nota |
|---|---|---|---|
| CA-07-01 consulta | Sí | T705, T709, T710, T711, T712, T713 | |
| CA-07-02 inscripción | Sí | T709, T710, T711, T713 | Véase A3. |
| CA-07-03 máximo | Sí | T709, T711, T713 | |
| CA-07-04 destacado | Sí | T707, T711, T713 | Ventana exacta y cambio de hora con `MockClock`. |
| CA-07-05 mínimo cubierto | Sí | T707, T711, T713 | |
| CA-07-06 gestión | Sí | T708, T710, T712, T713 | Véanse A2 y A4. |
| CA-07-07 validación | Sí | T708, T712, T714 | Véase A2. |
| CA-07-08 baja y empezados | Sí | T709, T710, T711, T714 | Véase A2. |
| CA-07-09 doble y coincidentes | Sí | T705, T707, T709, T714 | Véase A1. |
| CA-07-10 concurrencia | Sí | T709, T715 | |
| CA-07-11 permisos | Sí | T710, T716 | |
| R1–R10 | Sí | Cubiertas por las tareas de sus criterios | |

Tareas sin requisito: ninguna. T701–T704 y T717–T720 corresponden a obligaciones del proceso
(Constitution II y V, enunciado §5.2–5.4).

Métricas: 11 criterios y 10 reglas; 20 tareas; cobertura 100 % de criterios (tres casos límite sin prueba, A2);
ambigüedades 0; infraespecificaciones 1 (A1); duplicaciones 0 (las reglas R resumen los criterios a propósito);
críticos 0.

Siguiente acción: se puede implementar después de corregir A1 a A4 en sus fuentes.

## Registro de hallazgos

| ID | Historia / criterio | Gravedad | Evidencia | Decisión | Responsable | Estado |
|---|---|---|---|---|---|---|
| A1 | HU-07 / CA-07-09 | MEDIUM | Plan «Vistas» sin presentación de coincidencias | Indicación «Coincide con otro de tus turnos» sin botón | `aggg15` | Corregido en plan y tareas |
| A2 | HU-07 / CA-07-06, CA-07-07, CA-07-08 | LOW | Casos sin prueba en T713–T714 | Ampliar T713 y T714 | `aggg15` | Corregido en tareas |
| A3 | HU-07 / CA-07-02 | LOW | «Estás apuntado» | «Te has apuntado» | `aggg15` | Corregido en spec, plan y tareas |
| A4 | HU-07 / CA-07-06 | LOW | Fila `update` del plan | Validar lo temporal solo si cambia; 409 si empezó | `aggg15` | Corregido en el plan |

## Correcciones y repetición del análisis

Correcciones aplicadas el 2026-11-19 después de redactar el informe inicial anterior, que se conserva sin cambios:

- A1: plan «Vistas» y T711 muestran «Coincide con otro de tus turnos» sin botón «Apuntarme».
- A2: T713 incluye bajar el máximo hasta las inscripciones; T714, la baja sin figurar y la hora repetida de octubre.
- A3: spec.md CA-07-02, plan «Vistas» y T711 usan «Te has apuntado».
- A4: la fila `update` del plan precisa las condiciones temporales y el 409 del turno empezado.

### Informe final

Se repiten las mismas pasadas sobre los documentos corregidos. Resultado: sin hallazgos nuevos. Las rutas del plan
coinciden con T710; cada vista nombrada en el plan tiene tarea (T711–T712); los casos límite de la spec tienen
comprobación asignada. Cobertura 100 % (11/11 criterios, 10/10 reglas), 0 críticos, 0 altos y 0 ambigüedades
abiertas. Se puede pasar a implement.
