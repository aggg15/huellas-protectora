# Clarify · spec-006

Fecha: 2026-11-19. Historia: HU-06 · Gestionar casas de acogida y la evolución del animal.
Responsable: `abcb5-ua`. Revisión de `aggg15`: pendiente.

Aplicado por Claude Code a petición de `abcb5-ua` sobre las fuentes de la Práctica Grupal. No se ha
ejecutado la skill `$speckit-clarify`. Las preguntas proceden de la ficha inicial y de la revisión del
código integrado de HU-01 a HU-05. Las cuatro decisiones principales (rol de la familia, quién gestiona la
acogida, modelo de periodos y visibilidad de las novedades) se plantearon como opciones y `abcb5-ua` eligió
la recomendada en cada una el 2026-11-19. El resto son concreciones que se derivan de ellas. Se registran
como D19 a D25 en [decisiones](../../docs/decisiones.md). Se distingue lo que dice SI-30 de lo que decide el equipo.

## Registro de preguntas

| # | Pregunta | Respuesta | Justificación | Criterios |
|---|---|---|---|---|
| Q1 | ¿Cómo actúa la familia de acogida en la aplicación? | Cuenta propia con el rol `ROLE_FOSTER`, creada por la protectora con `app:create-user`. No es personal (`ROLE_STAFF`) ni se crea desde el registro público. Una cuenta corresponde a una casa. (D19, elegido por `abcb5-ua`) | La historia está escrita desde la familia («quiero indicar mi disponibilidad… y registrar las novedades»). La alta pública permitiría que cualquiera se declarase casa de acogida. | CA-06-01, CA-06-10 |
| Q2 | ¿Quién autoriza, inicia y finaliza cada acogida? | La coordinadora registra y finaliza las acogidas. La familia solo informa de sus condiciones y anota novedades. (D21, D22, elegido por `abcb5-ua`) | Plan común: las decisiones sobre el animal corresponden a la coordinadora. La familia no decide sola qué animal sale del refugio. | CA-06-02, CA-06-08 |
| Q3 | ¿Cómo se representan los periodos de disponibilidad? | Lista de periodos de días completos de Europe/Madrid (inicio y fin incluidos), sin solapes, que empiezan hoy o después y terminan como máximo dos años después. Los periodos consecutivos se consideran continuos. (D20) | SI-30 habla de «periodos de disponibilidad». Los días completos evitan horas sin significado para una familia. El límite de dos años solo evita errores de tecleo. | CA-06-01, CA-06-06 |
| Q4 | ¿Cuál es el periodo de una acogida y cómo se calcula la ocupación cuando varias coinciden? | La acogida empieza el día en que se registra y tiene una fecha de fin prevista. La casa debe estar disponible todos los días de ese intervalo. Toda acogida activa ha empezado como tarde hoy, así que todas coinciden hoy: ocupación = número de acogidas activas, que debe ser menor que la capacidad. (D21, elegido por `abcb5-ua`) | Cálculo exacto y verificable sin tareas programadas. Las acogidas futuras obligarían a calcular solapes día a día y a actualizar el indicador público con el paso del tiempo. | CA-06-02, CA-06-03 |
| Q5 | ¿Qué capacidad máxima se admite? | Un entero de 1 a 10. (D20) | SI-30 no fija un máximo. El límite superior solo evita errores de tecleo, no es una regla de la protectora. | CA-06-01, CA-06-06 |
| Q6 | ¿Qué ocurre si cambia la disponibilidad de una casa con animales acogidos? | Los cambios se aplican a las acogidas nuevas. No se admite bajar la capacidad por debajo de las acogidas activas ni retirar un periodo necesario para cubrir, desde hoy, el fin previsto de una acogida activa: antes hay que finalizar la acogida. (D23) | Pregunta 2 de la ficha. Cerrar acogidas automáticamente sacaría al animal de la casa sin decisión de la coordinadora; ignorar el cambio dejaría datos incoherentes. | CA-06-07 |
| Q7 | ¿Qué pasa cuando se supera el fin previsto? | La acogida sigue activa hasta que la coordinadora la finaliza; se señala como «fin previsto superado» a la coordinadora y a la familia. (D22) | El animal sigue físicamente en la casa hasta que vuelve al refugio. Cerrarla de forma automática no reflejaría la realidad. | CA-06-08 |
| Q8 | ¿Cómo se cierra la acogida al producirse una adopción? | Al formalizar la adopción (HU-05) se finaliza la acogida activa ese día, con motivo «adopción», en la misma transacción que deja el animal adoptado. (D25) | D15 ya deja el animal fuera de acogida; faltaba cerrar el registro de la acogida para liberar la plaza de la casa. | CA-06-04, CA-06-09 |
| Q9 | ¿Afecta la acogida a la cuarentena, la reserva o la adopción? | No: el estado del animal no cambia. Pueden acogerse animales en cuarentena, disponibles o reservados; no los adoptados. La web pública sigue mostrando solo los disponibles (HU-02), con la indicación «En casa de acogida». Liberar una reserva no finaliza la acogida. (D25) | Texto de CA-06-04. | CA-06-04 |
| Q10 | ¿Qué novedades son públicas y cuáles se comparten con los padrinos? | Ninguna es pública. Las ve el personal de la protectora (historial completo en la ficha interna del animal) y la familia, mientras dura su acogida (corrección de analyze A1). Compartirlas con los padrinos se decidirá en HU-08. (D24, elegido por `abcb5-ua`) | Constitution V: lo público no hace públicas las operaciones internas. Publicarlas exigiría moderación y ampliaría HU-02. HU-08 todavía no existe. | CA-06-05, CA-06-10 |
| Q11 | ¿Qué contiene una novedad y quién la escribe? | Texto de 1 a 2000 caracteres, con fecha y hora de registro y autora. Solo la familia de la acogida activa del animal. No se edita ni se borra. (D24) | La historia asigna las novedades a la familia. Conservarlas sin cambios mantiene la evolución del animal como historial. | CA-06-05, CA-06-06 |
| Q12 | ¿Cómo se evita superar la capacidad o duplicar acogidas ante peticiones simultáneas? | Registrar una acogida bloquea el animal y después la casa, y vuelve a comprobar bajo bloqueo el estado, la acogida activa del animal, la ocupación y la disponibilidad. Un índice único parcial impide dos acogidas activas del mismo animal. (D21, D25) | Plan común: releer el estado después de obtener el bloqueo. Mismo orden (animal primero) que la formalización de HU-05. | CA-06-09 |

## Diferencias con la especificación inicial

- Se conservan CA-06-01 a CA-06-05 con su redacción y se añaden escenarios «Dado… Cuando… Entonces…».
- Se añaden CA-06-06 a CA-06-10 (validación, cambios de condiciones, finalización, concurrencia y
  permisos), reglas R1 a R11 y casos límite.
- La historia, el alcance inicial y los límites se mantienen; los límites se amplían con exclusiones
  explícitas (alta pública de familias, acogidas futuras, avisos, fotografías y publicación de novedades).

## Pendiente para plan

El modelo de datos (casa, periodos, acogida y novedades), el rol nuevo, el índice parcial, el orden de
bloqueos, el ajuste de la formalización de HU-05 y los cambios en la ficha interna del animal de HU-01 se
deciden en `plan.md`, coordinando con `aggg15` los cambios en su código.
