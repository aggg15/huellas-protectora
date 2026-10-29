# HU-05 · Formalizar la adopción y programar los seguimientos

Carpeta: `spec-005`. Estado: historia inicial para revisión conjunta. Responsable: pendiente de acuerdo.

## Historia de usuario

Como coordinadora de adopciones, quiero formalizar la entrega mediante el contrato y el abono de la cuota y dejar programados los dos seguimientos para registrar la adopción y mantener la atención posterior al animal.

## Alcance de la historia

- Formalización de la adopción el día de la entrega: generación del contrato y constancia del abono de la cuota.
- Cambio del animal a adoptado.
- Programación de dos seguimientos: uno al mes y otro a los seis meses de la adopción.

## Criterios de aceptación iniciales

Estos criterios concretan la narrativa para su revisión. Las decisiones abiertas se resolverán en `clarify` antes de cerrar la especificación.

### CA-05-01

Dado un animal reservado para una familia, cuando se formaliza su entrega, entonces se genera un contrato vinculado al animal y a la familia adoptante.

Traza: SI-30, p. 1: contrato el día de la entrega.

### CA-05-02

Dada la formalización de una adopción, cuando se completa, entonces consta el abono de la cuota y el animal figura como adoptado desde esa entrega.

Traza: SI-30, p. 1: abono de cuota y condición de adoptado.

### CA-05-03

Dada una adopción formalizada, cuando se consulta su planificación, entonces existen dos seguimientos vinculados a ella: uno al mes y otro a los seis meses desde la entrega.

Traza: SI-30, p. 1: dos seguimientos posteriores.

### CA-05-04

Dado un animal adoptado, cuando se consulta la web pública de adopciones, entonces ya no aparece entre los animales disponibles.

Traza: SI-30, p. 1: solo animales realmente en adopción.

### CA-05-05

Dado un animal adoptado con padrinos, cuando se actualiza su situación, entonces esa adopción queda disponible para el proceso de propuesta de otro animal o finalización de HU-08.

Traza: SI-30, p. 2: opciones del padrino cuando el animal es adoptado.

## Dependencias y coordinación

Parte de la reserva de HU-04 y la ficha de HU-01. Comunica la adopción a HU-02 y HU-08. Debe coordinar con HU-06 la situación de una acogida que siga activa.

## Cuestiones pendientes para clarify

1. ¿Qué datos y formato debe contener el contrato? ¿Cuál es la cuota de adopción y cómo se deja constancia de su abono?
2. ¿Cómo se calculan las fechas al mes y a los seis meses cuando no existe el mismo día en el mes de destino? ¿Quién se responsabiliza de esos seguimientos?
3. ¿Qué se considera necesario para cerrar la formalización y cómo se actualizan las solicitudes en espera y una posible acogida activa?

## Límites

Se programa el seguimiento; no se añade un módulo de visitas, una pasarela de pago, firma electrónica ni contabilidad general.

## Fuente

SI-30, p. 1: entrega, contrato, cuota y seguimientos; p. 2: efecto en el apadrinamiento.

## Encaje en el trabajo grupal

Esta ficha corresponde a la definición inicial de historias de la guía (paso 1). No sustituye la especificación completa ni acredita haber ejecutado comandos de Spec Kit. Tras acordar la Constitution, el equipo desarrollará la spec correspondiente y su ciclo `specify → clarify → plan → tasks → analyze → implement → converge`, con plan y modelo de datos compartidos. La carpeta no es una rama Git; las ramas y el reparto de cuatro historias por persona se establecerán en el paso indicado por la guía.
