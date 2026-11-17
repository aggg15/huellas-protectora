# Clarify · spec-003

Base aceptada por el usuario el 2026-11-05. Registro de decisiones: ../../docs/decisiones.md. Las preguntas iniciales se conservan en la ficha antecedente de la raíz.

Esta fase se aplica por Codex a partir de las fuentes vigentes; no se presenta como ejecución de un comando slash instalado.

## Concreción del 2026-11-17

Aplicada por Claude Code a petición del usuario, siguiendo la secuencia de Spec Kit sobre las fuentes
de la Práctica Grupal. No se ha ejecutado la skill `$speckit-clarify`; las respuestas proceden de las
decisiones ya aceptadas (D06 a D10) y de la concreción de campos que se indica a continuación.

### 1. Identificación de la persona (pregunta 1 de la ficha)

- Se aplica D06: cada solicitud pertenece a una cuenta con email único normalizado y contraseña.
  El límite de dos se cuenta por cuenta, no por un email escrito en el formulario.
- La consulta del catálogo sigue siendo anónima. Al pulsar «Acceder para adoptar» se pide acceder;
  quien no tiene cuenta la crea en `/registro` (nombre, email y contraseña de al menos 12 caracteres).
- El alta pública solo crea cuentas `ROLE_USER`: los roles internos siguen creándose por comando.
- La cuenta no acredita la identidad legal de la persona (D06); la coordinadora la valora en HU-04.

### 2. Solicitudes abiertas (pregunta 2)

- Se aplica D07: abiertas = `pending` (pendiente), `waiting` (en espera) y `approved` (aprobada);
  no abiertas = `rejected` (rechazada) y `cancelled` (cancelada).
- Una solicitud aprobada sigue contando hasta que HU-05 formalice la adopción o se cancele (D11).
- Duplicados: no se admite otra solicitud abierta de la misma cuenta para el mismo animal (D07).
  Tras un rechazo o cancelación sí puede volver a solicitarlo si el animal sigue disponible.
- Animales: solo los disponibles reciben solicitudes (D10). El resto devuelve 404 en el formulario.

### 3. Cuestionario completo (pregunta 3)

D08 fija tres temas. Se concretan seis respuestas obligatorias, sin puntuación automática:

| Tema | Campo | Respuesta admitida |
|---|---|---|
| Vivienda | Tipo de vivienda | Piso o apartamento · Casa con patio o jardín · Casa sin espacio exterior · Otro tipo |
| Vivienda | Descripción | Texto libre de 1 a 2000 caracteres: espacio, convivientes y permiso si es alquiler |
| Experiencia | Experiencia con animales | Ninguna · Con perros · Con gatos · Con perros y gatos |
| Experiencia | Explicación | Obligatoria salvo «Ninguna» (entonces opcional); máximo 2000 caracteres |
| Disponibilidad | Tiempo diario | Menos de 2 h · Entre 2 y 4 h · Más de 4 h |
| Disponibilidad | Organización diaria | Texto libre de 1 a 2000 caracteres: horarios y cuidado en ausencias |

El nombre y el email proceden de la cuenta. Las respuestas formadas solo por espacios se consideran
vacías. Si falta algún dato no se guarda nada y se enumeran todos los campos pendientes.

### 4. Concurrencia y respuestas HTTP

- El plan común exige bloquear la fila de la persona y la del animal: la transacción hace
  `SELECT … FOR UPDATE` sobre ambas y vuelve a comprobar disponibilidad, duplicado y límite.
- Formulario incompleto: 422. Rechazo por estado (límite, duplicado, animal reservado durante el envío): 409.
  Envío correcto: redirección 303 a «Mis solicitudes».
