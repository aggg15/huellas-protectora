# Especificación HU-01 · spec-001

Fecha: 2026-11-05. Implementación secuencial autorizada en esta sesión.

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

## Clarificaciones aplicables

Las preguntas de la ficha inicial quedan resueltas para esta iteración en ../../docs/decisiones.md. Se aplican D01 a D11 según la historia, con aceptación de la base por el usuario.

## Límites

Esta historia no define un historial clínico completo ni tratamientos veterinarios.

## Fuente

SI-30, p. 1: párrafo de recepción, ficha, cuarentena y revisión veterinaria.


## Base compartida

Ver ../../plan.md (desde la raíz: plan.md), data-model.md y docs/decisiones.md. Los criterios CA originales se conservan. La ficha de la raíz se mantiene como antecedente inicial, sin presentarla como especificación implementada.

## Requisitos de calidad y permisos

Validación en servidor; formularios CSRF; datos ficticios; errores sin cambios parciales. Catálogo sin cuenta, operaciones internas según los roles del plan común. La verificación incluye caso normal, datos inválidos y permisos o estado.

## Reglas concretadas para aceptación

Las fechas se guardan en UTC y se muestran en Madrid. La liberación ocurre a las 240 horas exactas y no modifica reservas/adopciones. Especie perro/gato; tamaño pequeño/mediano/grande; edad entera de 0 a 600 meses; procedencia obligatoria hasta 1000 caracteres. Se adjuntan de una a seis imágenes reales JPEG/PNG/WebP de hasta 5 MiB. Identidad UUID y código HUE únicos, sin entrada manual. Revisión con veterinario, fecha real entre recepción y ahora y notas de hasta 5000 caracteres. Solo voluntario/coordinadora registran animales y solo veterinario registra revisiones. No se publica el origen ni las notas veterinarias.
