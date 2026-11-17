# Especificación HU-03 · spec-003

Fecha: 2026-11-05; concretada el 2026-11-17 por encargo expreso del usuario (`aggg15`).
Responsable GitHub: `aggg15`. Revisión del compañero (`abcb5-ua`): pendiente.
Estado: implementada en la rama `spec-003`, construida sobre `main` (890d2b3) tras integrar HU-02 (PR #5).

## Historia de usuario

Como persona que quiere adoptar un animal, quiero enviar una solicitud con un cuestionario completo sobre mi vivienda, experiencia y disponibilidad para que la protectora pueda valorar mi candidatura.

## Alcance de la historia

- Solicitud vinculada a la persona solicitante y al animal elegido.
- Cuestionario completo sobre vivienda, experiencia y disponibilidad.
- Límite de dos solicitudes abiertas por persona.

## Criterios de aceptación iniciales

Estos criterios concretan la narrativa para su revisión. Las decisiones abiertas se resuelven en [clarify](clarify.md).

### CA-03-01

Dada una persona con menos de dos solicitudes abiertas y un animal disponible, cuando envía el cuestionario completo, entonces se registra una solicitud asociada a esa persona y a ese animal.

Traza: SI-30, p. 1: solicitud para un animal y límite por persona.

### CA-03-02

Dado un cuestionario con información obligatoria sin completar, cuando se intenta enviar, entonces no se admite la solicitud a medias y se indican los datos pendientes.

Traza: SI-30, p. 1: no se admite un cuestionario incompleto.

### CA-03-03

Dada una persona con dos solicitudes abiertas, cuando intenta enviar otra, entonces se rechaza la nueva solicitud y se conservan las dos existentes.

Traza: SI-30, p. 1: máximo de dos solicitudes abiertas.

### CA-03-04

Dada una solicitud admitida, cuando la coordinadora consulta las candidaturas recibidas, entonces puede acceder a la solicitud y a su cuestionario completo.

Traza: SI-30, p. 1: las solicitudes llegan a la coordinadora.

## Criterios derivados de clarify

Se añaden con identificadores nuevos; no sustituyen a los cuatro iniciales.

### CA-03-05

Dado un animal que no está disponible (en cuarentena, reservado o adoptado), o una persona que ya tiene una solicitud abierta para ese mismo animal, cuando intenta enviar una solicitud para él, entonces no se registra ninguna solicitud y se explica el motivo.

Traza: D07 y D10 de [decisiones](../../docs/decisiones.md).

### CA-03-06

Dada una persona con menos de dos solicitudes abiertas, cuando envía varias solicitudes simultáneamente para animales distintos, entonces solo se admiten las necesarias para llegar a dos y el resto se rechaza.

Traza: SI-30, p. 1 (máximo de dos) y plan común: «HU-03 bloqueará la fila de la persona y del animal».

## Reglas de negocio

| Regla | Contenido | Fuente |
|---|---|---|
| R1 | La persona se identifica por su cuenta (email único normalizado y contraseña). Consultar el catálogo no exige cuenta; enviar una solicitud sí. | D06 |
| R2 | Cuentan como abiertas las solicitudes pendientes, en espera y aprobadas; las rechazadas y canceladas no. | D07 |
| R3 | No se admite una segunda solicitud abierta de la misma cuenta para el mismo animal. | D07 |
| R4 | Solo reciben solicitudes los animales disponibles; no los que están en cuarentena, reservados o adoptados. | D10 |
| R5 | El cuestionario completo incluye tipo y descripción de la vivienda, experiencia previa (se admite «ninguna») y tiempo y organización diarios. El nombre procede de la cuenta. | D08, [clarify](clarify.md) |
| R6 | Toda solicitud nueva nace en estado pendiente. Las decisiones (entrevista, aprobación, rechazo, espera) corresponden a HU-04. | Plan común |
| R7 | Solo la coordinadora consulta las candidaturas recibidas. | Plan común, roles |

## Casos límite

- Cuestionario vacío o con respuestas solo de espacios: se rechaza y se señalan todos los campos pendientes.
- Experiencia «ninguna» sin comentario: es una respuesta completa. Cualquier otra experiencia requiere explicación.
- Valores de selección no previstos (p. ej. tiempo diario «siempre»): se tratan como dato pendiente.
- El animal se reserva entre que la persona abre el formulario y lo envía: se rechaza al enviar.
- Dos o más envíos simultáneos de la misma cuenta: no superan el máximo de dos (CA-03-06).
- Persona sin sesión que pulsa «Acceder para adoptar»: se le pide acceder o crear cuenta y vuelve al formulario.
- El alta de cuenta ignora cualquier rol enviado en el formulario.

## Dependencias y coordinación

Parte del animal mostrado en HU-02 (ficha pública `/animales/{id}`) y entrega la candidatura a HU-04. Comparte la identidad de la persona y la definición de solicitud abierta con HU-04 y HU-05. Introduce la entidad común `AdoptionApplication`, que HU-04 ampliará con entrevista y motivo.

## Clarificaciones aplicables

Las preguntas de la ficha inicial se resuelven en [clarify](clarify.md) con D06 a D08 y D10 de ../../docs/decisiones.md.

## Límites

No se añaden listas de favoritos, recomendaciones automáticas, verificación de email, recuperación de contraseña ni valoración automática de candidaturas. El alta de cuenta es el soporte mínimo de D06, sin datos adicionales a email, nombre y contraseña. No se implementan decisiones sobre las solicitudes (HU-04).

## Fuente

SI-30, p. 1: solicitud, cuestionario completo y máximo de dos solicitudes abiertas.

## Base compartida

Ver ../../plan.md (desde la raíz: plan.md), data-model.md y docs/decisiones.md. Los criterios CA originales se conservan. La ficha de la raíz se mantiene como antecedente inicial, sin presentarla como especificación implementada.

## Requisitos de calidad y permisos

Validación en servidor; formularios CSRF; datos ficticios; errores sin cambios parciales. Catálogo sin cuenta, operaciones internas según los roles del plan común. La verificación incluye caso normal, datos inválidos y permisos o estado.
