# Especificación HU-02 · spec-002

Fecha: 2026-11-05. Implementación secuencial autorizada en esta sesión.

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

## Clarificaciones aplicables

Las preguntas de la ficha inicial quedan resueltas para esta iteración en ../../docs/decisiones.md. Se aplican D01 a D11 según la historia, con aceptación de la base por el usuario.

## Límites

No se exige registro para consultar. Las cuentas, si fueran necesarias para otros procesos, se decidirán en la base común.

## Fuente

SI-30, p. 1: párrafo de la web pública y párrafo de las casas de acogida.


## Base compartida

Ver ../../plan.md (desde la raíz: plan.md), data-model.md y docs/decisiones.md. Los criterios CA originales se conservan. La ficha de la raíz se mantiene como antecedente inicial, sin presentarla como especificación implementada.

## Requisitos de calidad y permisos

Validación en servidor; formularios CSRF; datos ficticios; errores sin cambios parciales. Catálogo sin cuenta, operaciones internas según los roles del plan común. La verificación incluye caso normal, datos inválidos y permisos o estado.

## Reglas concretadas para aceptación

Filtros combinados con AND. Edad: menos de 12 meses, de 12 a 83 y desde 84. Un filtro desconocido o compuesto no se ignora: devuelve formulario con error 422. Solo status available aparece en catálogo y ficha pública. Cuarentena, reservado y adoptado devuelven 404 también por URL directa de foto para usuarios anónimos. La acogida se representa con un indicador de datos; no se implementa su gestión en esta historia.
