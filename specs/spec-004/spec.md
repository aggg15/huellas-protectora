# Especificación HU-04 · spec-004

Fecha: 2026-11-17.
Historia: HU-04 · Evaluar solicitudes, entrevistar y reservar el animal.
Responsable GitHub: `abcb5-ua` (autoría de la PR #8); revisión: `aggg15` (aprobación de la PR #8).
Referencia base: `SI-30-protectora-animales-descripcion.pdf` (pág. 1), `plan.md`, `data-model.md` y `docs/decisiones.md` (D07, D09, D10, D11).

## Historia de usuario

Como coordinadora de adopciones, quiero revisar las solicitudes, concertar entrevistas y decidir sobre las candidaturas para reservar cada animal a una familia y conservar las alternativas en espera.

## Alcance de la historia

- Revisión del cuestionario de adopción completo por parte de la coordinadora (`ROLE_COORDINATOR`).
- Registro y actualización de entrevistas (fecha/hora y notas de la conversación) vinculadas a la solicitud.
- Decisión de aprobación de la candidatura:
  - Exige haber registrado previamente una entrevista cuya fecha no sea futura (D09).
  - Pasa el estado de la solicitud a `approved`.
  - Pasa el estado del animal a `reserved`.
  - Pasa el resto de solicitudes activas (`pending`) sobre ese mismo animal a estado `waiting` (en espera), sin rechazarlas (CA-04-04, D10).
  - Bloqueo y garantía de no duplicidad de reserva incluso ante peticiones concurrentes (CA-04-05, D10, índice parcial en base de datos).
- Decisión de rechazo de la candidatura:
  - Admite rechazo antes o después de la entrevista (D09).
  - Exige obligatoriamente un motivo justificado por escrito (CA-04-02).
  - Registra el motivo y pasa la solicitud a `rejected` sin eliminarla ni modificar la disponibilidad del animal.
- Liberación de reserva en caso de fallo o desistimiento (D11):
  - Permite a la coordinadora cancelar una reserva activa con motivo registrado.
  - La solicitud pasa a `cancelled`, el animal vuelve a `available` y las solicitudes en `waiting` se reactivan a `pending`.

---

## Criterios de aceptación y escenarios Gherkin

### CA-04-01: Revisión y concertación de entrevista
Dada una solicitud recibida en estado pendiente o en espera,
cuando la coordinadora consulta la ficha de la solicitud,
entonces puede revisar las seis respuestas del cuestionario y registrar una entrevista con fecha, hora y notas.

#### Escenario 1.1: Registro de entrevista con datos válidos
- **Dado** que existe una solicitud en estado `pending`,
- **Cuando** la coordinadora accede a `/admin/solicitudes/{id}` y envía una fecha y notas de entrevista,
- **Entonces** los datos de la entrevista quedan guardados en la solicitud,
- **Y** la página muestra el mensaje de confirmación y el detalle de la entrevista registrada.

#### Escenario 1.2: Validación de datos de entrevista
- **Dado** una solicitud pendiente,
- **Cuando** la coordinadora intenta registrar una entrevista sin notas u omitiendo la fecha,
- **Entonces** la solicitud no se modifica, se responde con código HTTP 422 y se indica el error correspondiente.

---

### CA-04-02: Rechazo con motivo obligatorio
Dada una solicitud de adopción abierta,
cuando la coordinadora decide rechazarla,
entonces solo se admite el rechazo si se aporta un motivo explicativo, quedando registrado junto a la fecha de decisión.

#### Escenario 2.1: Rechazo con motivo aportado
- **Dado** una solicitud en estado `pending`,
- **Cuando** la coordinadora envía el formulario de rechazo aportando el motivo "No dispone del tiempo mínimo requerido",
- **Entonces** el estado de la solicitud pasa a `rejected`,
- **Y** el motivo queda almacenado y visible en la ficha.

#### Escenario 2.2: Rechazo sin motivo no admitido
- **Dado** una solicitud en estado `pending`,
- **Cuando** la coordinadora intenta rechazarla con el campo de motivo vacío,
- **Entonces** la petición se rechaza con código HTTP 422,
- **Y** la solicitud permanece en estado `pending`.

---

### CA-04-03: Aprobación y reserva del animal
Dada una solicitud recibida sobre un animal disponible con entrevista previa no futura registrada,
cuando la coordinadora confirma su aprobación,
entonces la solicitud queda en estado `approved` y el animal pasa a estar reservado (`status = 'reserved'`).

#### Escenario 3.1: Aprobación exitosa tras entrevista válida
- **Dado** una solicitud para un animal disponible con entrevista registrada en fecha pasada o presente,
- **Cuando** la coordinadora confirma la aprobación,
- **Entonces** la solicitud pasa a `approved`,
- **Y** el animal pasa inmediatamente a `reserved`.

#### Escenario 3.2: Intento de aprobación sin entrevista previa no admitido
- **Dado** una solicitud pendiente para la cual no se ha registrado entrevista o cuya entrevista es futura,
- **Cuando** se intenta aprobar la candidatura,
- **Entonces** la operación es rechazada y ni la solicitud ni el animal cambian de estado.

---

### CA-04-04: Paso a espera de las demás solicitudes del animal
Dadas varias solicitudes abiertas sobre un mismo animal,
cuando la coordinadora aprueba una de ellas,
entonces todas las demás solicitudes que se encontraban en estado `pending` pasan a estado `waiting`, conservándose como alternativas activas.

#### Escenario 4.1: Múltiples candidaturas ante una aprobación
- **Dado** un animal disponible con tres solicitudes pendientes ($S_1$, $S_2$, $S_3$),
- **Cuando** la coordinadora aprueba $S_1$ (tras su entrevista),
- **Entonces** $S_1$ pasa a `approved`,
- **Y** $S_2$ y $S_3$ pasan automáticamente a `waiting`,
- **Y** ninguna de ellas es rechazada ni eliminada.

---

### CA-04-05: Garantía de no doble reserva y exclusión concurrente
Dado un animal que ya se encuentra en estado `reserved`,
cuando se intenta aprobar otra solicitud para ese mismo animal (por interfaz o petición concurrente),
entonces el sistema impide que existan dos reservas o dos solicitudes aprobadas simultáneamente sobre dicho animal.

#### Escenario 5.1: Bloqueo de aprobación sobre animal ya reservado
- **Dado** un animal reservado derivado de una solicitud aprobada previa,
- **Cuando** se intenta aprobar otra solicitud sobre ese animal,
- **Entonces** la operación se rechaza con un mensaje claro de que el animal ya está reservado,
- **Y** la integridad se garantiza mediante transacción con bloqueo de fila y restricción única parcial en la base de datos.

---

## Dependencias y coordinación

- **HU-01 y HU-02:** El animal proviene de HU-01 y su disponibilidad pública en HU-02 se actualiza automáticamente al pasar a `reserved` (dejando de mostrarse como disponible).
- **HU-03:** Consume las solicitudes creadas en HU-03 (`AdoptionApplication`) y actualiza su estado.
- **HU-05:** La reserva formalizada en HU-04 es la precondición obligatoria para la entrega y contrato de adopción en HU-05.
