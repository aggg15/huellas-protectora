# Especificación HU-06 · spec-006

Fecha: 2026-11-05. Especificación inicial; fuera de la implementación actual.

## Historia de usuario

Como familia de acogida, quiero indicar mi disponibilidad y capacidad, y registrar las novedades de los animales que acojo para prestarles cuidados temporales mientras siguen buscando una familia definitiva.

## Alcance de la historia

- Información de cada casa de acogida: disponibilidad y número máximo de animales que puede atender.
- Vinculación del animal con su acogida temporal, respetando disponibilidad y capacidad.
- Anotaciones de la familia sobre el carácter y la evolución; mantenimiento de la oferta de adopción cuando corresponda.

## Criterios de aceptación iniciales

Estos criterios concretan la narrativa para su revisión. Las decisiones abiertas se resolverán en `clarify` antes de cerrar la especificación.

### CA-06-01

Dada una casa de acogida, cuando la familia informa de sus condiciones, entonces quedan recogidos sus periodos de disponibilidad y el número máximo de animales que puede atender.

Traza: SI-30, p. 1: disponibilidad y capacidad de cada casa.

### CA-06-02

Dada una casa disponible y con capacidad, cuando se registra una acogida, entonces el animal queda vinculado a esa casa durante la acogida.

Traza: SI-30, p. 1: estancia temporal en familias.

### CA-06-03

Dada una casa no disponible para el periodo solicitado o cuya capacidad se superaría, cuando se intenta registrar una acogida, entonces no se confirma esa asignación.

Traza: SI-30, p. 1: disponibilidad y máximo; restricción derivada.

### CA-06-04

Dado un animal en acogida disponible para adopción, cuando se consulta la web pública, entonces sigue visible con la indicación de que está en acogida. La acogida no elimina la cuarentena ni cambia por sí sola una reserva o adopción.

Traza: SI-30, p. 1: acogida y adopción; coherencia con cuarentena y reserva.

### CA-06-05

Dada una familia que cuida a un animal en acogida, cuando anota novedades sobre su carácter o evolución, entonces esas novedades quedan asociadas al animal.

Traza: SI-30, p. 1: novedades anotadas por la familia.

## Dependencias y coordinación

Comparte el animal de HU-01 y coordina su visibilidad con HU-02. Usa la reserva de HU-04 y la adopción de HU-05 para mantener coherentes las situaciones. Las novedades se coordinan con HU-08.

## Cuestiones pendientes para clarify

1. ¿Quién autoriza, inicia y finaliza cada acogida? ¿Cómo se representan los periodos de disponibilidad y se calcula la ocupación cuando varias acogidas coinciden?
2. ¿Qué ocurre si cambia la disponibilidad de una casa con animales acogidos? ¿Cómo se cierra la acogida al producirse una adopción?
3. ¿Qué novedades son públicas y cuáles se comparten con los padrinos?

## Límites

La acogida es temporal y no convierte al animal en adoptado. No se añaden compensaciones económicas a las familias.

## Fuente

SI-30, p. 1: casas de acogida, disponibilidad, capacidad, visibilidad y novedades.


## Base compartida

Ver ../../plan.md (desde la raíz: plan.md), data-model.md y docs/decisiones.md. Los criterios CA originales se conservan. La ficha de la raíz se mantiene como antecedente inicial, sin presentarla como especificación implementada.
