# Analyze · spec-006

Revisión de solo lectura de spec.md, clarify.md, plan.md, tasks.md, ../../data-model.md y la
Constitution, realizada por Claude Code el 2026-11-19 antes de implementar (T604). Se sigue el método
de `.agents/skills/speckit-analyze/SKILL.md` (inventario, pasadas de duplicación, ambigüedad,
infraespecificación, Constitution, cobertura e incoherencia; gravedades CRITICAL/HIGH/MEDIUM/LOW),
pero la skill `$speckit-analyze` no se ha invocado como comando. No existe `.specify/extensions.yml`,
así que no hay hooks. `check-prerequisites.sh --json --require-spec --require-tasks --include-tasks`
devuelve código 0 para spec-006. Contexto revisado: documentos de la rama `spec-006` antes de implementar.

## Informe inicial

| ID | Categoría | Gravedad | Ubicación | Hallazgo | Recomendación |
|---|---|---|---|---|---|
| A1 | Incoherencia | MEDIUM | spec.md R10 y CA-06-08; clarify.md Q10; decisiones D24 | R10, Q10 y D24 dicen que las novedades las ven «el personal y la familia autora», sin límite temporal. CA-06-08 dice que, al finalizar la acogida, la familia deja de ver el animal. El plan solo muestra a la familia sus acogidas activas. Una implementación fiel a R10 tendría que mostrar a la familia también las acogidas finalizadas. | Precisar R10, Q10 y D24: la familia ve las novedades de sus acogidas activas; una vez finalizada la acogida, solo el personal conserva el historial. Es lo que ya describen CA-06-08 y el plan. |
| A2 | Ambigüedad | LOW | spec.md R2; plan.md «Vistas» (`foster/home`); tasks.md T615 | Los periodos ya terminados siguen guardados (no se borran solos), pero ni la spec ni el plan dicen cómo se muestran a la familia y a la coordinadora. `covers` los ignora correctamente porque empieza en hoy. | Mostrar los periodos terminados con la etiqueta «Terminado», tras los vigentes, y permitir retirarlos. Añadirlo al plan y a T615/T616. |
| A3 | Incoherencia | LOW | plan.md tabla de `FosterService`, fila `addPeriod` | El solapamiento aparece en la columna «Transacción y reglas (409)», pero el texto dice que se informa en el campo con 422. CA-06-06 lo trata como dato no válido (422). | Dejar explícito en el plan que el solapamiento se comprueba bajo bloqueo y responde 422 en el campo `startsOn`. |
| A4 | Infraespecificación | LOW | plan.md «Vistas»; tasks.md T612, T613; Constitution V | La Constitution exige estados de éxito, error y ausencia de resultados. El plan detalla errores y estados vacíos, pero no los mensajes de éxito tras guardar condiciones, registrar o finalizar una acogida o anotar una novedad. | Añadir un mensaje flash de éxito por operación (patrón existente `addFlash('success', …)`) a T612 y T613. |
| A5 | Cobertura | LOW | spec.md «Casos límite» (novedad con etiquetas HTML); tasks.md T618–T621 | El caso límite no tiene comprobación asignada. El autoescape de Twig lo cubre, pero el panel del animal usa `white-space: pre-wrap` y podría cambiarse a `nl2br`/`raw` por error. | Incluir en T621 una novedad con `<script>` y comprobar que aparece escapada en la ficha interna. |

Sin hallazgos CRITICAL ni HIGH. No hay conflictos con la Constitution: verificación de recorrido normal,
entradas inválidas y permisos o estado (V) en T618–T621; uso en móvil, escritorio y teclado en T623;
coordinación del código común (IV) en T611, T614, T617 y T625.

### Cobertura

| Requisito | ¿Tarea? | Tareas | Nota |
|---|---|---|---|
| CA-06-01 condiciones | Sí | T605, T606, T608, T612, T615, T616, T618 | Véase A2. |
| CA-06-02 acogida | Sí | T605, T607, T609, T613, T617, T618 | Día de Madrid con `MockClock`. |
| CA-06-03 disponibilidad y capacidad | Sí | T607, T609, T613, T617, T618 | |
| CA-06-04 web pública y coherencia | Sí | T609, T611, T618 | HU-02 sin cambios de código. |
| CA-06-05 novedades | Sí | T605, T610, T612, T615, T617, T618 | Véase A1. |
| CA-06-06 validación | Sí | T608, T609, T610, T615, T619 | Véase A3. |
| CA-06-07 cambios con acogidas | Sí | T608, T619 | |
| CA-06-08 finalización | Sí | T610, T613, T617, T619 | Véase A1. |
| CA-06-09 concurrencia | Sí | T605, T609, T611, T620 | |
| CA-06-10 permisos | Sí | T612, T613, T614, T621 | Véase A5. |
| R1–R11 | Sí | Cubiertas por las tareas de sus criterios | R11 = T611. |

Tareas sin requisito: ninguna. T601–T604 y T622–T625 corresponden a obligaciones del proceso
(Constitution II y V, enunciado §5.2–5.4).

Métricas: 10 criterios y 11 reglas; 25 tareas; cobertura 100 %; ambigüedades 1 (A2); duplicaciones 0
(las reglas R repiten criterios a propósito, como resumen); críticos 0.

Siguiente acción: se puede implementar después de corregir A1 a A5 en sus fuentes.

## Registro de hallazgos

| ID | Historia / criterio | Gravedad | Evidencia | Decisión | Responsable | Estado |
|---|---|---|---|---|---|---|
| A1 | HU-06 / CA-06-05, CA-06-08 | MEDIUM | R10 frente a CA-06-08; plan «Vistas» | Precisar R10, Q10 y D24 | `abcb5-ua` | Corregido en spec, clarify y decisiones |
| A2 | HU-06 / CA-06-01 | LOW | R2 y plan sin regla de presentación | Etiqueta «Terminado» y retirada permitida | `abcb5-ua` | Corregido en plan y tareas |
| A3 | HU-06 / CA-06-06 | LOW | Fila `addPeriod` del plan | Precisar 422 en `startsOn` | `abcb5-ua` | Corregido en el plan |
| A4 | HU-06 / Constitution V | LOW | Plan «Vistas»; T612–T613 | Mensajes flash de éxito | `abcb5-ua` | Corregido en plan y tareas |
| A5 | HU-06 / CA-06-10 | LOW | Caso límite sin prueba | Ampliar T621 | `abcb5-ua` | Corregido en tareas |

## Correcciones y repetición del análisis

Correcciones aplicadas el 2026-11-19 después de redactar el informe inicial anterior, que se conserva sin cambios:

- A1: spec.md R10, clarify.md Q10 y D24 en decisiones.md indican que la familia ve las novedades de sus
  acogidas activas y que el historial completo es del personal.
- A2: plan «Vistas» y T615/T616 muestran los periodos terminados con la etiqueta «Terminado».
- A3: la fila `addPeriod` del plan indica que el solapamiento responde 422 en `startsOn`.
- A4: plan «Rutas y controladores» y T612/T613 incluyen el mensaje de éxito de cada operación.
- A5: T621 incluye una novedad con `<script>` que debe mostrarse escapada.

### Informe final

Se repiten las mismas pasadas sobre los documentos corregidos. Resultado: sin hallazgos nuevos. Las rutas
del plan coinciden con T612–T613; cada vista nombrada en el plan tiene tarea (T615–T617); R10 ya no
contradice CA-06-08. Cobertura 100 % (10/10 criterios, 11/11 reglas), 0 críticos, 0 altos y 0
ambigüedades abiertas. Se puede pasar a implement.
