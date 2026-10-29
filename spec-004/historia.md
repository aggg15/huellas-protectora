# HU-04 · Evaluar solicitudes, entrevistar y reservar el animal

Carpeta: `spec-004`. Estado: historia inicial para revisión conjunta. Responsable: pendiente de acuerdo.

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

## Cuestiones pendientes para clarify

1. ¿Qué datos mínimos se necesitan para concertar la entrevista y cómo se registra que se ha realizado?
2. Si la adopción no llega a formalizarse, ¿quién libera la reserva y cómo se retoman las solicitudes en espera?
3. ¿Se admiten nuevas solicitudes mientras el animal está reservado? ¿Qué sucede con las solicitudes en espera cuando la adopción se formaliza?

## Límites

No se establece una puntuación automática de candidatos ni una agenda externa de entrevistas.

## Fuente

SI-30, p. 1: actuación de Nuria, entrevistas, decisiones, reserva y espera.

## Encaje en el trabajo grupal

Esta ficha corresponde a la definición inicial de historias de la guía (paso 1). No sustituye la especificación completa ni acredita haber ejecutado comandos de Spec Kit. Tras acordar la Constitution, el equipo desarrollará la spec correspondiente y su ciclo `specify → clarify → plan → tasks → analyze → implement → converge`, con plan y modelo de datos compartidos. La carpeta no es una rama Git; las ramas y el reparto de cuatro historias por persona se establecerán en el paso indicado por la guía.
