# Especificación HU-04 · spec-004

Fecha: 2026-11-05. Implementación secuencial autorizada en esta sesión.

## Historia de usuario

Como coordinadora de adopciones, quiero revisar las solicitudes, concertar entrevistas y decidir sobre las candidaturas para reservar cada animal a una familia y conservar las alternativas en espera.

## Alcance de la historia

- Revisión de las solicitudes y concertación de entrevistas con los candidatos.
- Decisión de aprobación o rechazo, con motivo obligatorio al rechazar.
- Reserva del animal para la familia aprobada y paso a espera de las demás solicitudes sobre ese animal.

## Criterios de aceptación iniciales

Estos criterios concretan la narrativa para su revisión. Las decisiones abiertas se resolverán en `clarify` antes de cerrar la especificación.

### CA-04-01

Dada una solicitud recibida, cuando la coordinadora la revisa, entonces puede consultar el cuestionario y dejar concertada una entrevista con los candidatos.

Traza: SI-30, p. 1: revisión y entrevista.

### CA-04-02

Dada una solicitud que se decide rechazar, cuando no se aporta el motivo, entonces no se admite el rechazo; cuando se aporta, entonces queda registrado junto con la decisión.

Traza: SI-30, p. 1: rechazo con motivo escrito.

### CA-04-03

Dada una solicitud aprobada, cuando se confirma la decisión, entonces el animal queda reservado para esa familia.

Traza: SI-30, p. 1: aprobación y reserva.

### CA-04-04

Dadas otras solicitudes sobre el mismo animal, cuando se aprueba una candidatura, entonces las demás quedan en espera y no se rechazan por esa aprobación.

Traza: SI-30, p. 1: otras solicitudes en espera.

### CA-04-05

Dado un animal reservado para una familia, cuando se intenta aprobar otra candidatura para ese mismo animal, entonces no se generan dos reservas incompatibles. La forma de resolver el intento se concretará en clarify.

Traza: SI-30, p. 1: reserva para una familia; coherencia derivada.

## Dependencias y coordinación

Recibe solicitudes de HU-03, actualiza la disponibilidad que consume HU-02 y proporciona la reserva a HU-05. Comparte con HU-03 los estados de las solicitudes.

## Clarificaciones aplicables

Las preguntas de la ficha inicial quedan resueltas para esta iteración en ../../docs/decisiones.md. Se aplican D01 a D11 según la historia, con aceptación de la base por el usuario.

## Límites

No se establece una puntuación automática de candidatos ni una agenda externa de entrevistas.

## Fuente

SI-30, p. 1: actuación de Nuria, entrevistas, decisiones, reserva y espera.


## Base compartida

Ver ../../plan.md (desde la raíz: plan.md), data-model.md y docs/decisiones.md. Los criterios CA originales se conservan. La ficha de la raíz se mantiene como antecedente inicial, sin presentarla como especificación implementada.

## Requisitos de calidad y permisos

Validación en servidor; formularios CSRF; datos ficticios; errores sin cambios parciales. Catálogo sin cuenta, operaciones internas según los roles del plan común. La verificación incluye caso normal, datos inválidos y permisos o estado.
