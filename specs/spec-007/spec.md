# Especificación HU-07 · spec-007

Fecha: 2026-11-05 (especificación inicial); concretada el 2026-11-19 por encargo de `aggg15`.
Responsable GitHub: `aggg15`. Revisión: `abcb5-ua` aprobó la PR #11 el 2026-11-19 y la integró en `main`.
Estado: especificada, aclarada ([clarify](clarify.md)) y planificada ([plan](plan.md)) en la rama `spec-007`,
creada desde `main` (edc7941, con HU-01 a HU-06 integradas). Con [tareas](tasks.md), [analyze](analyze.md),
[implementación](implement.md) (T705–T718) y verificación manual; [converge](converge.md) con T721–T722 completadas
y convergido. Integrada en `main` con la [PR #11](https://github.com/aggg15/huellas-protectora/pull/11)
(estado actualizado el 2026-11-19 en la rama `spec-008`, T801).

## Historia de usuario

Como voluntario del refugio, quiero consultar los turnos de paseo y limpieza e inscribirme en los que tienen plazas para contribuir al trabajo diario y detectar con antelación los turnos que necesitan más personas.

## Alcance de la historia

- Turnos de paseo y limpieza con fecha, hora, mínimo y máximo de personas.
- Inscripción libre de voluntarios respetando el máximo de cada turno.
- Destacado de los turnos que no cubren el mínimo cuando faltan cuarenta y ocho horas.

Concreción (D26 a D33 de [decisiones](../../docs/decisiones.md)):

- La coordinadora crea los turnos (tipo, fecha, horas de inicio y fin, mínimo y máximo) y los modifica.
- Los voluntarios (cuentas con el rol de voluntario, incluida la coordinadora) consultan los turnos, se apuntan
  y se dan de baja hasta que empiezan.
- El listado de turnos destaca los que no cubren su mínimo cuando faltan 48 horas o menos para empezar.

## Criterios de aceptación iniciales

Se conservan los cinco criterios iniciales con su redacción. Las decisiones que los concretan están en
[clarify](clarify.md).

### CA-07-01

Dado un turno de paseo o limpieza, cuando se consulta, entonces se conocen su fecha y hora, el mínimo necesario, el máximo admitido y las inscripciones registradas.

Traza: SI-30, p. 1: turnos con mínimos y máximos; fecha y hora derivadas del aviso. Concreción: D27, D32.

- **Dado** un turno de paseo del sábado de 10:00 a 12:00 (Europe/Madrid), con mínimo 2 y máximo 4 y una
  persona inscrita,
- **Cuando** un voluntario consulta el listado de turnos,
- **Entonces** ve el tipo «Paseo», la fecha, las horas de inicio y fin, el mínimo 2, el máximo 4, 1 persona
  inscrita y 3 plazas libres,
- **Y** en la ficha del turno ve el nombre de la persona inscrita.

### CA-07-02

Dado un turno con plazas, cuando un voluntario se apunta, entonces su inscripción queda asociada al turno y se actualiza el número de personas inscritas.

Traza: SI-30, p. 1: inscripción libre. Concreción: D26, D29.

- **Dado** un turno futuro con máximo 3 y una persona inscrita,
- **Cuando** otro voluntario pulsa «Apuntarme»,
- **Entonces** queda una inscripción de esa cuenta en el turno, con la hora en que se registró,
- **Y** el turno muestra 2 personas inscritas y 1 plaza libre, y el listado lo señala con «Te has apuntado».

### CA-07-03

Dado un turno que ya alcanza el máximo, cuando otro voluntario intenta apuntarse, entonces no se admite una inscripción que supere ese máximo.

Traza: SI-30, p. 1: máximo para no estorbarse. Concreción: D29, D33.

- **Dado** un turno con máximo 2 y dos personas inscritas,
- **Cuando** un tercer voluntario intenta apuntarse (aunque envíe el formulario directamente),
- **Entonces** se rechaza indicando que el turno ya tiene cubierto su máximo, y el turno sigue con 2 inscripciones,
- **Y** el listado muestra el turno como «Completo», sin botón para apuntarse.

### CA-07-04

Dado un turno con menos personas inscritas que su mínimo, cuando faltan cuarenta y ocho horas para su inicio, entonces aparece destacado como insuficientemente cubierto.

Traza: SI-30, pp. 1-2: destacado con cuarenta y ocho horas de antelación. Concreción: D31.

- **Dado** un turno con mínimo 3 y una persona inscrita que empieza dentro de 30 horas,
- **Cuando** un voluntario o la coordinadora consultan el listado,
- **Entonces** el turno aparece destacado con «Faltan voluntarios: 2 para el mínimo», y un aviso al principio
  indica cuántos turnos de las próximas 48 horas no cubren su mínimo.
- **Dado** el mismo turno cuando faltan 48 horas y 1 minuto, **entonces** no aparece destacado (se informa de
  su cobertura); **cuando** faltan exactamente 48 horas, **entonces** aparece destacado.
- **Dado** un turno en esa situación que ya ha empezado, **entonces** figura «En curso» y deja de destacarse.

### CA-07-05

Dado un turno que alcanza su mínimo de personas, cuando se consulta su cobertura, entonces no se presenta como un turno con falta de voluntarios.

Traza: SI-30, pp. 1-2: condición de mínimo no cubierto. Concreción: D31.

- **Dado** un turno destacado con mínimo 2 y una persona inscrita dentro de las 48 horas,
- **Cuando** se apunta una segunda persona,
- **Entonces** el turno deja de aparecer destacado y no cuenta en el aviso de turnos faltos de voluntarios.

## Criterios derivados de clarify

Se añaden con identificadores nuevos; no sustituyen a los cinco iniciales.

### CA-07-06 · Creación y modificación por la coordinadora

Dada la coordinadora, cuando crea un turno con tipo, fecha, horas, mínimo y máximo válidos, entonces el turno
aparece en el listado. Dado un turno que no ha empezado, cuando cambia su mínimo o su máximo, entonces se aplican
los nuevos valores; el máximo no puede quedar por debajo de las personas inscritas. Dado un turno con
inscripciones, cuando intenta cambiar su tipo, su fecha o sus horas, entonces se rechaza explicando el motivo.
Un turno que ya ha empezado no se modifica. En los rechazos el turno no cambia.

Traza: ficha inicial, pregunta 1; D27, D28.

### CA-07-07 · Datos válidos

Dada la coordinadora que envía un turno con datos no válidos (tipo distinto de paseo o limpieza; fecha u hora
inexistentes, incluida una hora que no existe por el cambio de hora; fin no posterior al inicio el mismo día;
inicio anterior al momento actual o posterior a un año; mínimo o máximo vacíos, no enteros, fuera de 1 a 20 o
máximo menor que el mínimo), cuando envía el formulario, entonces no se guarda nada, se indica el dato que hay
que corregir y se conservan los valores escritos.

Traza: Constitution V (validación en el servidor); D27.

### CA-07-08 · Baja y turnos empezados

Dado un voluntario inscrito en un turno que no ha empezado, cuando se da de baja, entonces su inscripción
desaparece, la plaza queda libre y, si el turno baja de su mínimo dentro de las 48 horas, vuelve a destacarse.
Dado un turno que ya ha empezado, cuando alguien intenta apuntarse o darse de baja, entonces se rechaza y la
inscripción no cambia. Darse de baja de un turno en el que no se figura se rechaza.

Traza: ficha inicial, pregunta 1; D30, D32.

### CA-07-09 · Doble inscripción y turnos coincidentes

Dado un voluntario inscrito en un turno, cuando intenta apuntarse otra vez al mismo turno, entonces se rechaza
y sigue contando una sola vez. Dado un voluntario inscrito en un turno de 10:00 a 12:00, cuando intenta
apuntarse a otro que coincide (por ejemplo, de 11:00 a 13:00), entonces se rechaza indicando el turno con el que
coincide; a uno de 12:00 a 13:00 sí puede apuntarse.

Traza: ficha inicial, pregunta 3; D29.

### CA-07-10 · Coherencia ante concurrencia

Dado un turno con una sola plaza libre, cuando dos voluntarios se apuntan a la vez, entonces como máximo se
confirma una inscripción. Dado un voluntario, cuando se apunta a la vez a dos turnos coincidentes, entonces como
máximo se confirma una. Dado un turno, cuando la coordinadora baja su máximo a la vez que alguien se apunta,
entonces el resultado no supera el máximo vigente.

Traza: plan común (bloqueos y relectura); D33.

### CA-07-11 · Permisos

Dada una persona anónima, cuando intenta consultar los turnos, entonces se le dirige a la página de acceso. Dada
una cuenta sin rol de voluntario (solicitante, familia de acogida o veterinario), cuando intenta consultar los
turnos, apuntarse o darse de baja, entonces recibe un acceso denegado. Dado un voluntario sin rol de
coordinadora, cuando intenta crear o modificar un turno, entonces recibe un acceso denegado. Un formulario sin
token CSRF válido no cambia nada. En ningún caso cambia ningún dato.

Traza: plan común (roles, POST y CSRF); Constitution V; D26, D27.

## Reglas de negocio

| Regla | Contenido | Fuente |
|---|---|---|
| R1 | Consultan los turnos, se apuntan y se dan de baja las cuentas con rol de voluntario (incluida la coordinadora). | D26 |
| R2 | Solo la coordinadora crea y modifica turnos. | D27 |
| R3 | Un turno tiene tipo paseo o limpieza, horas de inicio y fin del mismo día en Europe/Madrid con fin posterior al inicio, y 1 ≤ mínimo ≤ máximo ≤ 20. Se crea para empezar después de ahora y como máximo dentro de un año. | D27 |
| R4 | Antes del inicio se pueden cambiar el mínimo y el máximo; el máximo no baja de las inscripciones. Tipo, fecha y horas solo cambian sin inscripciones. Un turno empezado no se modifica. No se borran turnos. | D28 |
| R5 | Una inscripción requiere que el turno no haya empezado y tenga plazas; una por cuenta y turno. | D29 |
| R6 | Nadie figura en dos turnos que coinciden en el tiempo (cada uno empieza antes de que termine el otro). | D29 |
| R7 | La baja es posible hasta el inicio del turno y elimina la inscripción. | D30 |
| R8 | Falto de voluntarios = no ha empezado, inscripciones < mínimo y faltan 48 horas o menos para el inicio (instantes UTC). | D31 |
| R9 | El listado muestra los turnos que no han terminado, por hora de inicio; un turno empezado figura «En curso». | D32 |
| R10 | Las comprobaciones de plazas, coincidencias e inicio se repiten bajo bloqueo (cuenta → turno). | D33 |

## Casos límite

- Faltan exactamente 48 horas: destacado. Faltan 48 horas y 1 minuto: no destacado.
- Cambio de hora: un turno el domingo del cambio de horario de verano se destaca 48 horas reales antes de su
  inicio, aunque en el reloj de Madrid la diferencia aparente sea de 47 o 49 horas.
- Hora inexistente (02:30 del último domingo de marzo en Madrid): no válida (422). Una hora repetida en octubre
  se interpreta como la primera de las dos (horario de verano).
- Turnos contiguos (10:00–12:00 y 12:00–13:00): no coinciden; se admite apuntarse a ambos.
- Mínimo igual al máximo: válido; el turno deja de estar falto exactamente cuando se completa.
- Bajar el máximo hasta el número de inscritos: se admite y el turno queda «Completo».
- Doble envío del formulario «Apuntarme»: el segundo se rechaza (ya figura) y cuenta una vez.
- Baja de un turno destacado que ya había alcanzado el mínimo: vuelve a destacarse.
- Turno que empezó hace un momento: «En curso»; no admite inscripciones, bajas ni cambios.
- Nombre de una persona inscrita con etiquetas HTML: se muestra escapado.

## Dependencias y coordinación

- **Base común**: reutiliza `User`, los roles `ROLE_VOLUNTEER` y `ROLE_COORDINATOR`, el CSRF y `ValidationError`.
  No modifica el código de otras historias salvo el enlace «Turnos» de la cabecera (`base.html.twig`, común).
- **HU-01 a HU-06**: sin dependencia funcional; no depende del proceso de adopción ni de las acogidas.
- **HU-08**: sin relación con los turnos.

## Clarificaciones aplicables

Las tres preguntas de la ficha inicial se resuelven en [clarify](clarify.md) con D26 a D33.

## Límites

La narrativa pide un destacado visual. No se añaden recordatorios por correo, control de asistencia, sanciones ni cancelación automática de turnos.
Tampoco se añaden: borrado o cancelación manual de turnos, turnos periódicos, turnos que pasen de medianoche,
historial de turnos terminados, inscripción de otras personas por la coordinadora ni publicación de los turnos en
la web pública.

## Fuente

SI-30, pp. 1-2: turnos de paseo y limpieza, inscripción, mínimos, máximos y aviso.

## Base compartida

Ver ../../plan.md (desde la raíz: plan.md), data-model.md y docs/decisiones.md. Los criterios CA originales se conservan. La ficha de la raíz se mantiene como antecedente inicial, sin presentarla como especificación implementada.

## Requisitos de calidad y permisos

Validación en servidor; formularios CSRF; datos ficticios; errores sin cambios parciales. Operaciones internas según los roles del plan común. La verificación incluye caso normal, datos inválidos y permisos o estado.
