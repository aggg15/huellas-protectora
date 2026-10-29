# HU-08 · Apadrinar un animal y recibir sus novedades

Carpeta: `spec-008`. Estado: historia inicial para revisión conjunta. Responsable: pendiente de acuerdo.

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

## Encaje en el trabajo grupal

Esta ficha corresponde a la definición inicial de historias de la guía (paso 1). No sustituye la especificación completa ni acredita haber ejecutado comandos de Spec Kit. Tras acordar la Constitution, el equipo desarrollará la spec correspondiente y su ciclo `specify → clarify → plan → tasks → analyze → implement → converge`, con plan y modelo de datos compartidos. La carpeta no es una rama Git; las ramas y el reparto de cuatro historias por persona se establecerán en el paso indicado por la guía.
