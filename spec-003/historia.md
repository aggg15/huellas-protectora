# HU-03 · Enviar una solicitud de adopción completa

Carpeta: `spec-003`. Estado: historia inicial para revisión conjunta. Responsable: pendiente de acuerdo.

## Historia de usuario

Como persona que quiere adoptar un animal, quiero enviar una solicitud con un cuestionario completo sobre mi vivienda, experiencia y disponibilidad para que la protectora pueda valorar mi candidatura.

## Alcance de la historia

- Solicitud vinculada a la persona solicitante y al animal elegido.
- Cuestionario completo sobre vivienda, experiencia y disponibilidad.
- Límite de dos solicitudes abiertas por persona.

## Criterios de aceptación iniciales

Estos criterios concretan la narrativa para su revisión. Las decisiones abiertas se resolverán en `clarify` antes de cerrar la especificación.

### CA-03-01

Dada una persona con menos de dos solicitudes abiertas y un animal disponible, cuando envía el cuestionario completo, entonces se registra una solicitud asociada a esa persona y a ese animal.

Traza: SI-30, p. 1: solicitud para un animal y límite por persona.

### CA-03-02

Dado un cuestionario con información obligatoria sin completar, cuando se intenta enviar, entonces no se admite la solicitud a medias y se indican los datos pendientes.

Traza: SI-30, p. 1: no se admite un cuestionario incompleto.

### CA-03-03

Dada una persona con dos solicitudes abiertas, cuando intenta enviar otra, entonces se rechaza la nueva solicitud y se conservan las dos existentes.

Traza: SI-30, p. 1: máximo de dos solicitudes abiertas.

### CA-03-04

Dada una solicitud admitida, cuando la coordinadora consulta las candidaturas recibidas, entonces puede acceder a la solicitud y a su cuestionario completo.

Traza: SI-30, p. 1: las solicitudes llegan a la coordinadora.

## Dependencias y coordinación

Parte del animal mostrado en HU-02 y entrega la candidatura a HU-04. Comparte la identidad de la persona y la definición de solicitud abierta con HU-04 y HU-05.

## Cuestiones pendientes para clarify

1. ¿Cómo se identifica de forma consistente a una misma persona para aplicar el límite de dos solicitudes? La consulta pública sin registro no resuelve la identificación al solicitar.
2. ¿Qué estados cuentan como solicitud abierta, en particular las solicitudes en espera y la candidatura aprobada mientras no se formalice la adopción?
3. ¿Qué preguntas y campos exactos son obligatorios en el cuestionario? ¿Se permite más de una solicitud de la misma persona para el mismo animal?

## Límites

No se añaden listas de favoritos, recomendaciones automáticas ni un proceso de registro no definido en la narrativa.

## Fuente

SI-30, p. 1: solicitud, cuestionario completo y máximo de dos solicitudes abiertas.

## Encaje en el trabajo grupal

Esta ficha corresponde a la definición inicial de historias de la guía (paso 1). No sustituye la especificación completa ni acredita haber ejecutado comandos de Spec Kit. Tras acordar la Constitution, el equipo desarrollará la spec correspondiente y su ciclo `specify → clarify → plan → tasks → analyze → implement → converge`, con plan y modelo de datos compartidos. La carpeta no es una rama Git; las ramas y el reparto de cuatro historias por persona se establecerán en el paso indicado por la guía.
