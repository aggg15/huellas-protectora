# Clarify · spec-007

Fecha: 2026-11-19. Historia: HU-07 · Inscribirse en turnos y detectar falta de voluntarios.
Responsable: `aggg15`. Revisión de `abcb5-ua`: pendiente.

Aplicado por Claude Code a petición de `aggg15` sobre las fuentes de la Práctica Grupal. No se ha ejecutado la
skill `$speckit-clarify`. Las preguntas proceden de la ficha inicial y de la revisión del código integrado de
HU-01 a HU-06. Tres decisiones (quién gestiona los turnos, si se admite la baja y si se admiten turnos
coincidentes) se plantearon como opciones y `aggg15` eligió la recomendada en cada una el 2026-11-19. El resto
son concreciones que se derivan de ellas y del plan común. Se registran como D26 a D33 en
[decisiones](../../docs/decisiones.md). Se distingue lo que dice SI-30 de lo que decide el equipo.

## Registro de preguntas

| # | Pregunta | Respuesta | Justificación | Criterios |
|---|---|---|---|---|
| Q1 | ¿Quién es voluntario en la aplicación? | Las cuentas con `ROLE_VOLUNTEER`, creadas con `app:create-user` como en HU-01. La coordinadora hereda el rol y también puede apuntarse. Veterinario, familias de acogida y solicitantes no acceden. (D26) | El rol ya existe en el plan común; la historia está escrita desde el voluntario. Un alta pública permitiría que cualquiera ocupase plazas del refugio. | CA-07-02, CA-07-11 |
| Q2 | ¿Quién crea y modifica los turnos y sus mínimos y máximos? (pregunta 1 de la ficha) | Solo la coordinadora. (D27, elegido por `aggg15`) | Plan común: las decisiones de organización corresponden a la coordinadora. Si cualquier voluntario pudiera bajar el mínimo, ocultaría un turno sin cubrir. | CA-07-06, CA-07-11 |
| Q3 | ¿Qué datos tiene un turno? | Tipo (paseo o limpieza), fecha, hora de inicio y hora de fin del mismo día en Europe/Madrid (guardadas en UTC), mínimo y máximo con 1 ≤ mínimo ≤ máximo ≤ 20. Se crea para empezar después de ahora y como máximo dentro de un año. (D27) | SI-30 nombra los dos tipos, el mínimo y el máximo; la hora de fin permite detectar coincidencias (Q6). Los límites solo evitan errores de tecleo; un turno de paseo o limpieza no pasa de medianoche. | CA-07-01, CA-07-07 |
| Q4 | ¿Qué se puede cambiar de un turno con inscripciones? | Antes de su inicio, el mínimo y el máximo; el máximo no puede quedar por debajo de las inscripciones. El tipo, la fecha y las horas solo si no tiene inscripciones. No se borran ni se cancelan turnos. (D28) | Cambiar la hora de un turno con personas apuntadas alteraría su compromiso y podría crear coincidencias (Q6). La ficha excluye la cancelación automática; la manual no se pide. | CA-07-06 |
| Q5 | ¿Puede un voluntario darse de baja de un turno? (pregunta 1 de la ficha) | Sí, hasta el inicio del turno; la inscripción se elimina y la plaza queda libre. Si el turno baja de su mínimo dentro de las 48 horas, vuelve a destacarse. (D30, elegido por `aggg15`) | Refleja la realidad: si alguien no puede ir, es mejor que lo diga y el aviso lo muestre. Impedir la baja ocultaría la falta de personas. | CA-07-08 |
| Q6 | ¿Se permiten inscripciones en turnos coincidentes y cómo se evita que una persona cuente dos veces? (pregunta 3 de la ficha) | No se admite apuntarse a un turno que coincide con otro en el que ya figura (cada uno empieza antes de que termine el otro; los contiguos no coinciden). Una cuenta se inscribe una sola vez en cada turno (índice único). (D29, elegido por `aggg15`) | Una persona no puede estar en dos sitios: contarla en dos turnos a la vez falsearía la cobertura que la historia quiere vigilar. | CA-07-09 |
| Q7 | ¿El destacado se mantiene durante las 48 horas previas mientras falten personas? ¿Qué zona horaria se usa? (pregunta 2 de la ficha) | Sí: un turno es «falto de voluntarios» desde que faltan 48 horas o menos para su inicio hasta que empieza, mientras tenga menos inscripciones que su mínimo. Se calcula con instantes UTC (48 horas reales, también con cambio de hora) en cada consulta; las horas se muestran en Europe/Madrid. (D31) | SI-30: «aparezcan destacados cuando falten cuarenta y ocho horas». Calcularlo al consultar evita tareas programadas y sigue el criterio de la cuarentena (240 horas UTC, D01). | CA-07-04, CA-07-05 |
| Q8 | ¿Dónde y a quién se muestra el destacado? | En el listado interno de turnos, para voluntarios y coordinadora: etiqueta «Faltan voluntarios» con cuántas personas faltan y un aviso al principio con el número de turnos así. Sin correos ni avisos. (D31) | La ficha pide un destacado visual y excluye los recordatorios por correo. | CA-07-04 |
| Q9 | ¿Qué turnos se listan y qué se ve de las inscripciones? | Los que no han terminado, por hora de inicio, con mínimo, máximo, inscripciones, plazas libres y la marca «Estás apuntado». La ficha del turno muestra los nombres de las personas inscritas. Un turno empezado figura «En curso» y no admite inscripciones, bajas ni cambios. (D32) | CA-07-01 pide conocer las inscripciones registradas; los nombres solo los ve el personal con acceso a los turnos. El historial de turnos pasados no se pide. | CA-07-01, CA-07-08 |
| Q10 | ¿Cómo se evita superar el máximo o duplicar inscripciones con peticiones simultáneas? | Inscribirse bloquea la fila de la cuenta y después la del turno y vuelve a comprobar todo bajo bloqueo; modificar y darse de baja bloquean el turno. Índice único (turno, cuenta). (D33) | Plan común: releer después del bloqueo. Bloquear la cuenta serializa sus inscripciones (coincidencias); bloquear el turno, sus plazas. Mismo orden que HU-03 (persona y después el recurso). | CA-07-10 |

## Diferencias con la especificación inicial

- Se conservan CA-07-01 a CA-07-05 con su redacción y se añaden escenarios «Dado… Cuando… Entonces…».
- Se añaden CA-07-06 a CA-07-11 (gestión por la coordinadora, validación, baja y turnos empezados, doble
  inscripción y coincidencias, concurrencia y permisos), reglas R1 a R10 y casos límite.
- La historia, el alcance inicial y los límites se mantienen; los límites se amplían con exclusiones
  explícitas (borrado o cancelación manual, turnos periódicos o nocturnos, historial, inscripción por terceros).

## Pendiente para plan

El modelo de datos (turno e inscripción), las restricciones, el orden de bloqueos, el cálculo del destacado y
las rutas se deciden en `plan.md`. HU-07 no modifica código de otras historias salvo la cabecera común.
