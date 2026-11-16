# Especificación HU-08 · spec-008

Fecha: 2026-11-05. Especificación inicial; fuera de la implementación actual.

## Historia de usuario

Como padrino o madrina, quiero apoyar a un animal concreto con una aportación mensual y recibir sus novedades para ayudar a su cuidado y decidir cómo continuar mi colaboración cuando sea adoptado.

## Alcance de la historia

- Apadrinamiento vinculado a un animal concreto y a una aportación mensual mínima de diez euros.
- Recepción de novedades del animal apadrinado.
- Propuesta de otro animal o finalización de la colaboración cuando el animal encuentra familia.

## Criterios de aceptación iniciales

Estos criterios concretan la narrativa para su revisión. Las decisiones abiertas se resolverán en `clarify` antes de cerrar la especificación.

### CA-08-01

Dada una persona que desea apadrinar, cuando establece una aportación mensual de al menos diez euros para un animal, entonces queda registrado el vínculo entre la persona, el animal y la aportación.

Traza: SI-30, p. 2: animal concreto y aportación mensual mínima.

### CA-08-02

Dada una aportación mensual inferior a diez euros, cuando se intenta establecer el apadrinamiento, entonces no se admite con ese importe.

Traza: SI-30, p. 2: mínimo de diez euros.

### CA-08-03

Dado un apadrinamiento y nuevas noticias del animal, cuando esas novedades están disponibles para compartir, entonces el padrino las recibe por el mecanismo que se acuerde en clarify.

Traza: SI-30, p. 2: recepción de novedades.

### CA-08-04

Dado un animal apadrinado que ha sido adoptado, cuando se trata la continuidad del apadrinamiento, entonces se ofrece al padrino apoyar a otro animal o terminar su colaboración.

Traza: SI-30, p. 2: opciones tras la adopción.

### CA-08-05

Dada esa propuesta de continuidad, cuando el padrino elige otro animal o finalizar, entonces queda reflejada su elección sin asignarle otro animal de forma unilateral.

Traza: SI-30, p. 2: elección del padrino.

## Dependencias y coordinación

Usa la ficha de HU-01 y la adopción de HU-05. Comparte con HU-06 el tratamiento de las novedades de acogida, sin asumir que todas son públicas.

## Cuestiones pendientes para clarify

1. ¿Por qué canal reciben los padrinos las novedades y quién las prepara o valida? La narrativa no concreta el mecanismo de entrega.
2. ¿Cómo se gestiona la aportación mensual y qué constancia debe guardar el sistema, sin convertirlo en contabilidad general?
3. ¿Qué animales pueden ser apadrinados y se permite más de un padrino por animal o más de un animal por padrino? ¿Qué ocurre con la aportación mientras se decide la continuidad tras una adopción?

## Límites

No se prescribe un servicio externo de cobro o mensajería. La contabilidad general y las campañas de recogida de pienso quedan fuera del encargo.

## Fuente

SI-30, p. 2: padrinos, aportación, novedades y continuidad tras la adopción.


## Base compartida

Ver ../../plan.md (desde la raíz: plan.md), data-model.md y docs/decisiones.md. Los criterios CA originales se conservan. La ficha de la raíz se mantiene como antecedente inicial, sin presentarla como especificación implementada.
