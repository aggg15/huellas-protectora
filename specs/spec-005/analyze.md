# Analyze · spec-005

Revisión de solo lectura de spec.md, clarify.md, plan.md, tasks.md, ../../data-model.md y la
Constitution, realizada por Claude Code el 2026-11-18 antes de implementar (T504). Se sigue el método
de `.agents/skills/speckit-analyze/SKILL.md` (inventario, pasadas de duplicación, ambigüedad,
infraespecificación, Constitution, cobertura e incoherencia; gravedades CRITICAL/HIGH/MEDIUM/LOW),
pero la skill `$speckit-analyze` no se ha invocado como comando. No existe `.specify/extensions.yml`,
así que no hay hooks. `check-prerequisites.sh --require-spec --require-tasks` es correcto para spec-005.
Contexto revisado: commit af378a7.

## Informe inicial

| ID | Categoría | Gravedad | Ubicación | Hallazgo | Recomendación |
|---|---|---|---|---|---|
| A1 | Incoherencia | MEDIUM | plan.md «Rutas y controladores»; tasks.md T513 | La formalización se sitúa en `AdoptionController`, pero sus errores (422/409) deben volver a mostrar la ficha de la solicitud (`admin_show`), cuyos datos construye `ApplicationReviewController::show`. Habría que duplicar esa preparación o redirigir y perder los errores por campo. | Poner la acción de formalizar en `ApplicationReviewController`, que ya agrupa las decisiones sobre una solicitud. `AdoptionController` solo conserva las lecturas. |
| A2 | Cobertura | MEDIUM | spec.md CA-05-08 (escenario); tasks.md T519; `templates/application/mine.html.twig` | «Mis solicitudes» no muestra el motivo de ninguna cancelada. Una cancelada cae en la rama por defecto: «Pendiente de revisión por coordinación» o la fecha de entrevista, lo que es engañoso. Ya ocurre con las canceladas por D11 (HU-04). T519 solo cubre las canceladas por adopción. | Ampliar T519 a toda solicitud cancelada (motivo) y a la formalizada (contrato). Señalarlo como cambio en una vista compartida con HU-04. |
| A3 | Infraespecificación | LOW | spec.md CA-05-11; tasks.md T523 | «Se le niega el acceso» no distingue la persona anónima (el firewall redirige a `/acceso`) de la cuenta sin rol (403). | Precisar en T523 la respuesta esperada: anónimo → redirección a `/acceso`; cuenta sin rol → 403; en ambos casos sin cambios. |
| A4 | Infraespecificación | LOW | plan.md «Riesgos»; spec.md CA-05-10 | `reject()` y `recordInterview()` (HU-04) no bloquean filas. Si la coordinadora rechaza una solicitud en espera mientras otra sesión formaliza la adopción, el rechazo puede sobrescribir la cancelación automática. El estado final sigue siendo no abierto y la adopción no se ve afectada; CA-05-10 no lo contempla. | Registrarlo como riesgo aceptado en el plan, sin tarea: no rompe ninguna regla de HU-05 y cambiarlo ampliaría el alcance en código de HU-04. |
| A5 | Cobertura | LOW | tasks.md T516; `admin_show.html.twig` | El mapa de clases de estado de la ficha de solicitud no contempla `formalized` (quedaría con el estilo por defecto). | Incluirlo en T516. |

Sin hallazgos CRITICAL ni HIGH. No hay conflictos con la Constitution: verificación de recorrido normal,
entradas inválidas y permisos o estado (V) en T520–T523; uso móvil, escritorio y teclado en T525;
coordinación de código común (IV) en T512, T515, T516 y T527.

### Cobertura

| Requisito | ¿Tarea? | Tareas | Nota |
|---|---|---|---|
| CA-05-01 contrato | Sí | T505, T510, T514, T516, T518, T520 | |
| CA-05-02 cuota y adoptado | Sí | T509, T510, T517, T520 | Incluye acogida (D15). |
| CA-05-03 dos seguimientos | Sí | T505, T508, T513, T517, T520 | `MockClock`. |
| CA-05-04 web pública | Sí | T510, T520 | Reutiliza HU-02. |
| CA-05-05 HU-08 | Sí | T505, T511, T520 | Acotado por D18. |
| CA-05-06 precondiciones | Sí | T510, T521 | |
| CA-05-07 abono obligatorio | Sí | T509, T516, T521 | |
| CA-05-08 cierre de solicitudes | Sí | T506, T510, T516, T519, T521 | Véase A2. |
| CA-05-09 fin de mes | Sí | T508, T521 | |
| CA-05-10 concurrencia | Sí | T505, T510, T512, T515, T522 | Véase A4. |
| CA-05-11 permisos | Sí | T513, T514, T523 | Véase A3. |
| R1–R9 | Sí | Cubiertas por las tareas de sus criterios | R9 = CA-05-04. |

Tareas sin requisito: ninguna. T501–T503 y T524–T527 corresponden a obligaciones del proceso
(Constitution II y V, enunciado §5.2–5.4).

Métricas: 11 criterios y 9 reglas; 27 tareas; cobertura 100 %; ambigüedades 1 (A3); duplicaciones 0
(las reglas R repiten criterios a propósito, como resumen); críticos 0.

Siguiente acción: se puede implementar. Antes, se corrigen A1, A2, A3 y A5 en plan y tareas y A4 se
registra como riesgo aceptado.

## Registro de hallazgos

| ID | Historia / criterio | Gravedad | Evidencia | Decisión | Responsable | Estado |
|---|---|---|---|---|---|---|
| A1 | HU-05 / CA-05-07, CA-05-06 | MEDIUM | `ApplicationReviewController::show` prepara `otherApplications`, tipos y errores | Mover formalizar a `ApplicationReviewController` (plan y T513) | `aggg15` | Corregido en plan y tareas |
| A2 | HU-05 / CA-05-08; HU-04 / D11 | MEDIUM | Rama por defecto de `mine.html.twig` (líneas 45–55) | Ampliar T519; avisar a `abcb5-ua` | `aggg15` (revisa `abcb5-ua`) | Corregido en plan y tareas |
| A3 | HU-05 / CA-05-11 | LOW | `security.yaml`: `form_login` redirige a la persona anónima | Precisar T523 | `aggg15` | Corregido en plan y tareas |
| A4 | HU-05 / CA-05-10; HU-04 | LOW | `ApplicationService::reject()` sin `FOR UPDATE` | Riesgo aceptado en el plan; sin tarea | `aggg15` | Aceptado y documentado |
| A5 | HU-05 / CA-05-08 | LOW | Mapa `statusClass` de `admin_show.html.twig` | Ampliar T516 | `aggg15` | Corregido en plan y tareas |

## Correcciones y repetición del análisis

Correcciones aplicadas el 2026-11-18 tras conservar el informe inicial (commit 0958eff):

- A1: plan «Rutas y controladores» y T513 sitúan formalizar en `ApplicationReviewController`.
- A2: plan «Vistas» y T519 cubren el motivo de toda solicitud cancelada; T519 queda marcada como
  coordinación con HU-04.
- A3: T523 fija la redirección a `/acceso` para la persona anónima y 403 para las cuentas sin rol.
- A4: añadido a «Riesgos y coordinación» del plan como riesgo aceptado.
- A5: plan «Vistas» y T516 incluyen `formalized` en el mapa de clases.

### Informe final

Se repiten las mismas pasadas sobre los documentos corregidos. Resultado: sin hallazgos nuevos. Las
rutas del plan coinciden con T513–T514, y cada vista nombrada en el plan tiene tarea (T516–T519).
Cobertura 100 % (11/11 criterios, 9/9 reglas), 0 críticos, 0 altos, 0 ambigüedades abiertas y un
riesgo aceptado (A4). Se puede pasar a implement.
