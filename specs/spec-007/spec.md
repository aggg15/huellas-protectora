# Especificación HU-07 · spec-007

Fecha: 2026-11-05. Especificación inicial; fuera de la implementación actual.

## Historia de usuario

Como voluntario del refugio, quiero consultar los turnos de paseo y limpieza e inscribirme en los que tienen plazas para contribuir al trabajo diario y detectar con antelación los turnos que necesitan más personas.

## Alcance de la historia

- Turnos de paseo y limpieza con fecha, hora, mínimo y máximo de personas.
- Inscripción libre de voluntarios respetando el máximo de cada turno.
- Destacado de los turnos que no cubren el mínimo cuando faltan cuarenta y ocho horas.

## Criterios de aceptación iniciales

Estos criterios concretan la narrativa para su revisión. Las decisiones abiertas se resolverán en `clarify` antes de cerrar la especificación.

### CA-07-01

Dado un turno de paseo o limpieza, cuando se consulta, entonces se conocen su fecha y hora, el mínimo necesario, el máximo admitido y las inscripciones registradas.

Traza: SI-30, p. 1: turnos con mínimos y máximos; fecha y hora derivadas del aviso.

### CA-07-02

Dado un turno con plazas, cuando un voluntario se apunta, entonces su inscripción queda asociada al turno y se actualiza el número de personas inscritas.

Traza: SI-30, p. 1: inscripción libre.

### CA-07-03

Dado un turno que ya alcanza el máximo, cuando otro voluntario intenta apuntarse, entonces no se admite una inscripción que supere ese máximo.

Traza: SI-30, p. 1: máximo para no estorbarse.

### CA-07-04

Dado un turno con menos personas inscritas que su mínimo, cuando faltan cuarenta y ocho horas para su inicio, entonces aparece destacado como insuficientemente cubierto.

Traza: SI-30, pp. 1-2: destacado con cuarenta y ocho horas de antelación.

### CA-07-05

Dado un turno que alcanza su mínimo de personas, cuando se consulta su cobertura, entonces no se presenta como un turno con falta de voluntarios.

Traza: SI-30, pp. 1-2: condición de mínimo no cubierto.

## Dependencias y coordinación

Comparte la identidad del voluntario que se acuerde en la base común. No depende del proceso de adopción; requiere acordar el criterio temporal para el aviso.

## Cuestiones pendientes para clarify

1. ¿Quién crea y modifica los turnos y sus mínimos y máximos? ¿Puede un voluntario darse de baja de un turno?
2. ¿El destacado se mantiene durante las cuarenta y ocho horas previas mientras falten personas? ¿Qué zona horaria se usa?
3. ¿Se permiten inscripciones en turnos coincidentes y cómo se evita que una misma persona cuente dos veces en un turno?

## Límites

La narrativa pide un destacado visual. No se añaden recordatorios por correo, control de asistencia, sanciones ni cancelación automática de turnos.

## Fuente

SI-30, pp. 1-2: turnos de paseo y limpieza, inscripción, mínimos, máximos y aviso.


## Base compartida

Ver ../../plan.md (desde la raíz: plan.md), data-model.md y docs/decisiones.md. Los criterios CA originales se conservan. La ficha de la raíz se mantiene como antecedente inicial, sin presentarla como especificación implementada.
