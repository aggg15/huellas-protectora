# Especificación HU-03 · spec-003

Fecha: 2026-11-05. Implementación secuencial autorizada en esta sesión.

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

## Clarificaciones aplicables

Las preguntas de la ficha inicial quedan resueltas para esta iteración en ../../docs/decisiones.md. Se aplican D01 a D11 según la historia, con aceptación de la base por el usuario.

## Límites

No se añaden listas de favoritos, recomendaciones automáticas ni un proceso de registro no definido en la narrativa.

## Fuente

SI-30, p. 1: solicitud, cuestionario completo y máximo de dos solicitudes abiertas.


## Base compartida

Ver ../../plan.md (desde la raíz: plan.md), data-model.md y docs/decisiones.md. Los criterios CA originales se conservan. La ficha de la raíz se mantiene como antecedente inicial, sin presentarla como especificación implementada.

## Requisitos de calidad y permisos

Validación en servidor; formularios CSRF; datos ficticios; errores sin cambios parciales. Catálogo sin cuenta, operaciones internas según los roles del plan común. La verificación incluye caso normal, datos inválidos y permisos o estado.
