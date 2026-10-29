# HU-01 · Registrar un animal y gestionar su cuarentena

Carpeta: `spec-001`. Estado: historia inicial para revisión conjunta. Responsable: pendiente de acuerdo.

## Historia de usuario

Como voluntario que recibe animales, quiero abrir la ficha de cada animal y reflejar su cuarentena y revisión veterinaria para conservar su información y ofrecerlo en adopción cuando haya pasado el periodo establecido.

## Alcance de la historia

- Ficha con código propio, especie, edad aproximada, procedencia y fotografías.
- Cuarentena inicial de diez días y revisión por el veterinario colaborador durante ese periodo.
- Disponibilidad para adopción al finalizar la cuarentena; durante ella el animal permanece fuera de la web pública.

## Criterios de aceptación iniciales

Estos criterios concretan la narrativa para su revisión. Las decisiones abiertas se resolverán en `clarify` antes de cerrar la especificación.

### CA-01-01

Dados los datos de un animal recién llegado, cuando el voluntario abre su ficha, entonces quedan registrados su código propio, especie, edad aproximada, procedencia y fotografías.

Traza: SI-30, p. 1: recepción y ficha del animal.

### CA-01-02

Dado un animal recién llegado, cuando se registra su entrada, entonces queda en cuarentena y no se ofrece en adopción durante los diez días establecidos.

Traza: SI-30, p. 1: cuarentena de diez días.

### CA-01-03

Dado un animal en cuarentena, cuando se consulta el catálogo público o su ficha pública, entonces no se muestra al visitante.

Traza: SI-30, p. 1: exclusión de la web durante la cuarentena.

### CA-01-04

Dado un animal en cuarentena, cuando se registra la revisión realizada por el veterinario colaborador, entonces la revisión queda asociada a su ficha. Los datos mínimos del registro se concretarán en clarify.

Traza: SI-30, p. 1: revisión veterinaria; registro propuesto para hacerla verificable.

### CA-01-05

Dado un animal que ha completado la cuarentena, cuando finaliza ese periodo, entonces queda disponible para adopción. El mecanismo de transición se concretará en clarify.

Traza: SI-30, p. 1: disponibilidad tras la cuarentena.

## Dependencias y coordinación

Proporciona la ficha y la situación del animal que utilizan HU-02 a HU-06 y HU-08.

## Cuestiones pendientes para clarify

1. ¿Los diez días se cuentan por días naturales o por periodos de veinticuatro horas desde la entrada? ¿El paso a disponible es automático o lo confirma una persona?
2. ¿Qué información mínima se registra de la revisión veterinaria? Si no se ha realizado al finalizar los diez días, ¿cómo se resuelve esa situación? La narrativa no define ese caso.
3. ¿Cómo se genera el código propio y cómo se recoge el tamaño que necesitará el filtro público de HU-02?

## Límites

Esta historia no define un historial clínico completo ni tratamientos veterinarios.

## Fuente

SI-30, p. 1: párrafo de recepción, ficha, cuarentena y revisión veterinaria.

## Encaje en el trabajo grupal

Esta ficha corresponde a la definición inicial de historias de la guía (paso 1). No sustituye la especificación completa ni acredita haber ejecutado comandos de Spec Kit. Tras acordar la Constitution, el equipo desarrollará la spec correspondiente y su ciclo `specify → clarify → plan → tasks → analyze → implement → converge`, con plan y modelo de datos compartidos. La carpeta no es una rama Git; las ramas y el reparto de cuatro historias por persona se establecerán en el paso indicado por la guía.
