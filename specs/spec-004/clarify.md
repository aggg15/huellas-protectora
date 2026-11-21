# Clarify · spec-004

Fecha: 2026-11-17.
Historia: HU-04 · Evaluar solicitudes, entrevistar y reservar el animal.
Estado: Precisiones consensuadas e incorporadas al plan y modelo común (`docs/decisiones.md`).

## 1. Gestión de entrevistas (D09, CA-04-01)

* **Campos requeridos:**
  * `interviewAt`: Fecha y hora de la entrevista (zona horaria de entrada `Europe/Madrid`, persistida en `UTC`).
  * `interviewNotes`: Notas de la conversación y valoración cualitativa (texto no vacío, máximo 5000 caracteres).
* **Momento y obligatoriedad de la entrevista:**
  * Para **aprobar** una solicitud, es condición sine qua non que la entrevista esté registrada y que su fecha `interviewAt` sea menor o igual al momento actual (`interviewAt <= now()`), asegurando que la entrevista haya tenido lugar efectivamente antes de reservar.
  * Para **rechazar**, no es obligatorio que haya habido entrevista; la coordinadora puede descartar una solicitud directamente por incompatibilidad evidente del cuestionario.

## 2. Decisión de rechazo (CA-04-02)

* **Motivo obligatorio:**
  * El formulario de rechazo exige obligatoriamente un texto justificativo (`rejectionReason`, no vacío, máximo 2000 caracteres).
  * Si el motivo está en blanco o solo contiene espacios, la solicitud no se rechaza y se responde con HTTP 422.
* **Efectos:**
  * La solicitud pasa a `rejected`.
  * No se altera el estado del animal ni el resto de solicitudes.
  * Una solicitud rechazada no cuenta como solicitud abierta para el límite de 2 por adoptante (D07).

## 3. Aprobación, reserva y lista de espera (D10, CA-04-03, CA-04-04)

* **Aprobación de la candidatura:**
  * Se verifica que el animal esté en estado `available` (bajo bloqueo de fila `FOR UPDATE`).
  * La solicitud pasa a `approved`.
  * El animal pasa a `status = 'reserved'`.
  * La fecha de decisión (`decisionAt`) queda registrada.
* **Transición de las demás solicitudes a espera:**
  * Todas las solicitudes del mismo animal que estuvieran en estado `pending` pasan atómicamente a `waiting` (`En espera`).
  * Las solicitudes en estado `rejected` o `cancelled` no sufren cambios.
  * Los adoptantes con solicitud en `waiting` ven su nuevo estado reflejado en «Mis solicitudes».

## 4. Prevención de reservas incompatibles y concurrencia (D10, CA-04-05)

* **Control de concurrencia y atomicidad:**
  * La transacción bloquea la fila del animal: `SELECT status FROM animal WHERE id = ? FOR UPDATE`.
  * Si el animal no está en estado `available`, la operación lanza una excepción de negocio y no se aplica.
* **Restricción a nivel de base de datos:**
  * Se añade un índice único parcial en PostgreSQL:
    `CREATE UNIQUE INDEX uniq_application_approved_animal ON AdoptionApplication (animal_id) WHERE status = 'approved';`
  * Esto asegura matemáticamente que ningún error de aplicación o carrera concurrente pueda dejar dos solicitudes aprobadas para el mismo animal.

## 5. Liberación de reserva en caso de fallo (D11)

* Si una familia aprobada renuncia o no formaliza la entrega:
  * La coordinadora puede liberar la reserva con un motivo obligatorio (`cancellationReason`).
  * La solicitud aprobada pasa a `cancelled`.
  * El animal vuelve de `reserved` a `available`.
  * Las solicitudes de ese animal que estaban en `waiting` vuelven automáticamente a `pending` para que la coordinadora pueda evaluarlas.

## Ronda de clarificación previa a la entrega (2026-11-21)

Historia: HU-04 · Evaluar solicitudes, entrevistar y reservar el animal. Responsable: `abcb5-ua`. Revisión de
`aggg15` de esta ronda: pendiente. Se parte de `specs/spec-004/spec.md`, de la ficha inicial `spec-004/historia.md`,
de SI-30 (p. 1), de [decisiones](../../docs/decisiones.md) y de las trece pruebas de `tests/Spec004Test.php` en el
commit a74f85f. Las cinco secciones de arriba recogen las precisiones de entrevista, rechazo, aprobación,
concurrencia y liberación, pero sin la tabla de preguntas, respuestas y criterios ni las diferencias con la ficha
inicial: se añaden aquí.

### 1. Tres situaciones de prueba

**Situación 1 · Recorrido habitual**

- CA-04-01. **Dado** una solicitud pendiente, **cuando** la coordinadora abre su ficha y envía una fecha y unas
  notas de entrevista válidas, **entonces** los datos quedan guardados en la solicitud y la ficha los muestra.
  Prueba: `Spec004Test::testCoordinatorCanViewApplicationAndRecordInterview`.
- CA-04-02. **Dado** una solicitud pendiente, **cuando** la coordinadora la rechaza con un motivo escrito,
  **entonces** pasa a `rejected`, el motivo queda guardado y el animal sigue disponible.
  Prueba: `Spec004Test::testRejectionWithReasonIsRecordedAndDoesNotAlterAnimalAvailability`.
- CA-04-03 y CA-04-04. **Dado** un animal disponible con tres solicitudes y entrevista no futura en la que se
  aprueba, **cuando** la coordinadora aprueba una de ellas, **entonces** esta pasa a `approved`, el animal a
  `reserved` y las otras dos pendientes a `waiting`, sin rechazarse.
  Prueba: `Spec004Test::testApprovalAfterValidInterviewReservesAnimalAndMovesOtherApplicationsToWaiting`.
- D11. **Dado** una solicitud aprobada, **cuando** la coordinadora libera la reserva con motivo, **entonces** la
  solicitud pasa a `cancelled`, el animal vuelve a `available` y las esperas vuelven a `pending`.
  Prueba: `Spec004Test::testReleaseReservationRestoresAnimalToAvailableAndWaitingToPending`.

**Situación 2 · Entrada inválida o caso límite**

- CA-04-01 (entrada inválida). **Dado** una solicitud pendiente, **cuando** se envía una entrevista con notas en
  blanco o con una fecha que no es válida, **entonces** la respuesta es 422, el error aparece junto al campo y la
  solicitud no cambia.
  Prueba: `Spec004Test::testInterviewRequiresValidDateAndNotes`.
- CA-04-02 (entrada inválida). **Dado** una solicitud pendiente, **cuando** se rechaza con el motivo en blanco,
  **entonces** la respuesta es 422 y la solicitud sigue `pending`, sin motivo guardado.
  Prueba: `Spec004Test::testRejectionWithoutReasonIsNotAdmitted`.
- CA-04-03 (caso límite). **Dado** una solicitud sin entrevista, o con una entrevista de fecha futura, **cuando**
  se intenta aprobar, **entonces** la operación se rechaza y ni la solicitud ni el animal cambian.
  Pruebas: `Spec004Test::testApprovalWithoutInterviewIsNotAdmitted` y
  `Spec004Test::testApprovalWithFutureInterviewIsNotAdmitted`.
- D11 (entrada inválida). **Dado** una solicitud aprobada, **cuando** se libera la reserva con el motivo en
  blanco, **entonces** la respuesta es 422 y la reserva se mantiene.
  Prueba: `Spec004Test::testReleaseReservationRequiresReason`.

**Situación 3 · Permisos o estado**

- CA-04-05. **Dado** un animal ya reservado por otra solicitud aprobada, **cuando** se intenta aprobar una segunda
  por la interfaz, **entonces** la operación se rechaza con un mensaje y las dos solicitudes y el animal conservan
  su estado; además, la base de datos rechaza dos solicitudes `approved` del mismo animal.
  Pruebas: `Spec004Test::testCannotApproveApplicationForAlreadyReservedAnimal` y
  `Spec004Test::testDatabaseRejectsTwoApprovedApplicationsForSameAnimal`.
- Estado de la solicitud. **Dado** una solicitud ya aprobada, **cuando** se abre su ficha o se fuerza un rechazo,
  **entonces** la ficha no ofrece aprobar, rechazar ni modificar la entrevista y el rechazo forzado responde 422
  sin tocar la reserva.
  Prueba: `Spec004Test::testDecidedApplicationAdmitsNoFurtherDecisions`.
- Permisos. **Dado** una cuenta solicitante o de voluntario sin `ROLE_COORDINATOR`, **cuando** pide el listado o
  la ficha de una solicitud, **entonces** recibe 403.
  Prueba: `Spec004Test::testRoleAccessControl` (solo cubre peticiones GET: las rutas POST de decisión no tienen
  prueba de rol ni de CSRF, véase [analyze](analyze.md), A4 y C1-08).

### 2. Ambigüedades detectadas

Las decisiones abiertas llevaban una recomendación como propuesta; el grupo las adoptó el 2026-11-21 y la explicación de cada responsable figura más abajo.

| # | Pregunta | Opciones | Recomendación | Justificación | CA afectado | Estado |
|---|---|---|---|---|---|---|
| Q1 | ¿Qué datos mínimos tiene la entrevista y cómo consta que se ha realizado? | Solo fecha; fecha y notas; agenda externa. | Fecha y hora con notas; para aprobar, una entrevista registrada con fecha no futura. | SI-30: la coordinadora «concierta una entrevista» y decide; la ficha excluye una agenda externa. | CA-04-01, CA-04-03 | decidida previamente (D09 de docs/decisiones.md) |
| Q2 | Si la adopción no llega a formalizarse, ¿quién libera la reserva y qué pasa con las solicitudes en espera? | Liberación automática; liberación por la coordinadora con motivo. | La coordinadora, con motivo: candidata cancelada, esperas a pendientes y animal disponible. | SI-30: las demás quedan en espera «por si la adopción no llega a buen término». | CA-04-04 (D11) | decidida previamente (D11) |
| Q3 | ¿Se reciben solicitudes nuevas con el animal reservado y qué pasa con las en espera al formalizar? | Sí o no; esperas rechazadas o canceladas. | No se reciben; las otras pendientes pasan a espera y las rechazadas no se reabren; al formalizar se cancelan. | El catálogo ofrece solo animales realmente disponibles. | CA-04-03, CA-04-04 | decidida previamente (D10 y D15) |
| Q4 | ¿Se puede rechazar antes de la entrevista? | Solo después; en cualquier momento. | En cualquier momento, siempre con motivo. | SI-30 exige motivo escrito, no entrevista previa. | CA-04-02 | decidida previamente (D09) |
| Q5 | ¿Cómo se evita una doble reserva por aprobaciones simultáneas? | Solo comprobación de aplicación; bloqueo de fila y restricción en base de datos. | Transacción con `SELECT … FOR UPDATE` del animal e índice único parcial de solicitud aprobada por animal. | La ficha dejaba «la forma de resolver el intento» para clarify; el plan común pide releer tras el bloqueo. | CA-04-05 | decidida previamente (clarify de spec-004, sección 4, y D10; D17 para la formalización) |
| Q6 | ¿Qué notas exige una entrevista que todavía no se ha celebrado? | (a) Notas obligatorias en cualquier caso (hoy); (b) notas opcionales si la fecha es futura y obligatorias para aprobar. | (b), porque «concertar» una entrevista futura no puede tener aún una valoración. | CA-04-01 pide «registrar una entrevista con fecha, hora y notas» y D09 exige la entrevista realizada para aprobar, pero una entrevista futura con notas obligatorias obliga a inventar la valoración. | CA-04-01, CA-04-03 | Decidida el 2026-11-21 por el grupo: (b) notas opcionales si la fecha es futura; obligatorias para aprobar. |
| Q7 | ¿Puede registrarse una entrevista con fecha anterior a la de la solicitud? | (a) Admitirla (hoy); (b) exigir fecha igual o posterior a la solicitud. | (b) | Una entrevista anterior a la candidatura no puede referirse a ella; la spec solo exige que no sea futura. Surgió en la revisión de código (C1-12). | CA-04-01, CA-04-03 | Decidida el 2026-11-21 por el grupo: (b) fecha igual o posterior a la solicitud (aplicada: D42). |
| Q8 | ¿Reprogramar la entrevista sobrescribe la anterior o se conserva el historial? | (a) Sobrescribir mientras la solicitud no esté decidida (hoy); (b) conservar todas las entrevistas. | (a), porque la historia habla de «una entrevista» por solicitud. | La spec dice «registrar y actualizar» sin precisar el historial. | CA-04-01 | Decidida el 2026-11-21 por el grupo: (a) sobrescribir mientras la solicitud no esté decidida; sin historial. |
| Q9 | ¿Qué ve la persona solicitante de la decisión? | (a) Estado, fecha de la entrevista y motivos de rechazo o de liberación (hoy en «Mis solicitudes»); (b) solo el estado. | (a) | SI-30 exige dejar escrito el motivo del rechazo, sin decir quién lo lee; los motivos pueden contener valoraciones. | CA-04-02 (y D11) | Decidida el 2026-11-21 por el grupo: (a) la persona ve estado, fecha de entrevista y motivos de rechazo o liberación. |

### Explicación de las decisiones por sus responsables

**Q6 · Notas de una entrevista futura.** Hemos decidido que las notas sean opcionales si la entrevista tiene fecha futura, y obligatorias para aprobar. «Concertar» una entrevista futura no puede tener todavía una valoración, y exigir notas obligaría a inventarla. Para aprobar sigue haciendo falta una entrevista registrada con fecha no futura, como fija D09. — Ana Bernabeu Castillo (`abcb5-ua`)

**Q7 · Fecha anterior a la solicitud.** Hemos decidido exigir que la fecha de la entrevista no sea anterior a la de la solicitud. Una entrevista anterior a la candidatura no puede referirse a ella, y la especificación solo exige que no sea futura; la incidencia surgió en la revisión de código (C1-12). Lo aplicamos en el código como D42. — Ana Bernabeu Castillo (`abcb5-ua`)

**Q8 · Reprogramación de la entrevista.** Hemos decidido que reprogramar la entrevista sobrescriba la anterior mientras la solicitud no esté decidida, como ocurre hoy, sin conservar historial. La historia habla de «una entrevista» por solicitud, y la especificación dice «registrar y actualizar» sin precisar un historial. — Ana Bernabeu Castillo (`abcb5-ua`)

**Q9 · Qué ve la persona solicitante.** Hemos decidido que la persona vea el estado de su solicitud, la fecha de la entrevista y los motivos de rechazo o de liberación, como ocurre hoy en «Mis solicitudes». SI-30 exige dejar escrito el motivo del rechazo, pero no dice quién lo lee; como los motivos pueden contener valoraciones, pedimos cuidado al redactarlos. — Ana Bernabeu Castillo (`abcb5-ua`)

### 3. Diferencias con la especificación inicial

Comparación de `spec-004/historia.md` (ficha inicial de la raíz) con `specs/spec-004/spec.md` (vigente).
`git log` registra tres versiones: 7bcea03 (2026-11-05), una copia de la ficha inicial con la cabecera
«Especificación»; e961e92 (2026-11-18), la reescritura vigente; y b001d3f (2026-11-18), que sustituyó
«Responsable: Implementación spec-004» por el responsable y revisor de GitHub y tradujo al español los pasos
«When/Then/And» del escenario 3.

- Los cinco identificadores CA-04-01 a CA-04-05 se conservan, pero cuatro redacciones cambian de sentido o de
  precisión; no se añaden criterios derivados:
  - CA-04-01: de «consultar el cuestionario y dejar concertada una entrevista» a «revisar las seis respuestas y
    registrar una entrevista con fecha, hora y notas», sobre solicitudes pendientes o en espera.
  - CA-04-02: de «no se admite el rechazo sin motivo» a motivo explicativo registrado junto a la fecha de decisión,
    con 422 y solicitud sin cambios si falta.
  - CA-04-03: de «solicitud aprobada → animal reservado» a «solicitud recibida sobre un animal disponible con
    entrevista previa no futura → `approved` y animal `reserved`» (D09).
  - CA-04-04: precisa que pasan a `waiting` las solicitudes que estaban en `pending`.
  - CA-04-05: «la forma de resolver el intento se concretará en clarify» pasa a un rechazo con mensaje claro,
    transacción con bloqueo de fila y restricción única parcial.
- Se añaden escenarios en Dado, Cuando, Entonces, Y para cada criterio (1.1 a 5.1) y se eliminan las trazas SI-30
  de cada criterio y la sección «Fuente».
- El alcance incorpora la liberación de la reserva (D11), que la ficha inicial no contenía, pero no se le asigna
  ningún criterio: queda sin CA propio (analyze A2).
- Se pierde el límite de la ficha («sin puntuación automática de candidatos ni agenda externa de entrevistas»): la
  spec vigente no tiene sección «Límites».
- Cabecera: de «historia inicial, responsable pendiente de acuerdo» a una spec fechada con responsable
  (`abcb5-ua`), revisor (`aggg15`) y referencia base (SI-30, plan, modelo y decisiones D07, D09, D10 y D11).
- Se eliminan «Cuestiones pendientes para clarify» (tres preguntas) y «Encaje en el trabajo grupal», sin sección
  «Clarificaciones aplicables»: las respuestas están en las secciones de arriba y en la tabla de la sección 2.
- Dependencias: se detalla que HU-02 deja de mostrar el animal al pasar a `reserved`, que se consumen y actualizan
  las solicitudes de HU-03 y que la reserva es la precondición de HU-05.

### 4. Nota de método

Ronda realizada con Claude Code siguiendo el procedimiento de `.agents/skills/speckit-clarify/SKILL.md`; no se ha
ejecutado la skill de Codex. Las decisiones abiertas se cerraron el 2026-11-21 (ver la tabla y las explicaciones).
