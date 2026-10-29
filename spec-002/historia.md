# HU-02 · Consultar y filtrar el catálogo público de adopción

Carpeta: `spec-002`. Estado: historia inicial para revisión conjunta. Responsable: pendiente de acuerdo.

## Historia de usuario

Como persona interesada en adoptar, quiero consultar sin registrarme los animales que buscan hogar y filtrarlos por especie, tamaño y edad para encontrar uno que encaje con mi situación.

## Alcance de la historia

- Consulta pública de los animales que están realmente disponibles para adopción.
- Filtros por especie, tamaño y edad.
- Visibilidad de los animales en acogida que siguen buscando familia, con indicación de esa situación.

## Criterios de aceptación iniciales

Estos criterios concretan la narrativa para su revisión. Las decisiones abiertas se resolverán en `clarify` antes de cerrar la especificación.

### CA-02-01

Dada una persona sin registro, cuando accede a la web pública, entonces puede consultar los animales disponibles para adopción.

Traza: SI-30, p. 1: web pública sin registro.

### CA-02-02

Dados animales con distintas especies, tamaños y edades, cuando la persona aplica un filtro por cualquiera de esos atributos, entonces se muestran los animales disponibles que cumplen el filtro.

Traza: SI-30, p. 1: filtros por especie, tamaño y edad.

### CA-02-03

Dados animales en cuarentena, reservados o adoptados, cuando se consulta el catálogo o su ficha pública, entonces esos animales no se presentan como disponibles ni se incluyen entre los animales en adopción.

Traza: SI-30, p. 1: solo animales realmente en adopción.

### CA-02-04

Dado un animal en acogida que sigue disponible para adopción, cuando se consulta el catálogo y su ficha, entonces continúa visible y se indica que está en acogida.

Traza: SI-30, p. 1: acogida compatible con visibilidad pública.

## Dependencias y coordinación

Usa la ficha y la cuarentena de HU-01, la reserva de HU-04, la adopción de HU-05 y la acogida de HU-06. Da paso a HU-03.

## Cuestiones pendientes para clarify

1. ¿Qué categorías de tamaño y qué rangos de edad se utilizarán? ¿Cómo se combinan los filtros?
2. ¿Qué información y fotografías de la ficha se muestran públicamente, además de la indicación de acogida?

## Límites

No se exige registro para consultar. Las cuentas, si fueran necesarias para otros procesos, se decidirán en la base común.

## Fuente

SI-30, p. 1: párrafo de la web pública y párrafo de las casas de acogida.

## Encaje en el trabajo grupal

Esta ficha corresponde a la definición inicial de historias de la guía (paso 1). No sustituye la especificación completa ni acredita haber ejecutado comandos de Spec Kit. Tras acordar la Constitution, el equipo desarrollará la spec correspondiente y su ciclo `specify → clarify → plan → tasks → analyze → implement → converge`, con plan y modelo de datos compartidos. La carpeta no es una rama Git; las ramas y el reparto de cuatro historias por persona se establecerán en el paso indicado por la guía.
