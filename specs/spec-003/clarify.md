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

## Ronda de clarificación previa a la entrega (2026-11-21)

Historia: HU-03 · Enviar una solicitud de adopción completa. Responsable: `aggg15`. Revisión de `abcb5-ua` de
esta ronda: pendiente. Se parte de `specs/spec-003/spec.md`, de la ficha inicial `spec-003/historia.md`, de SI-30
(p. 1), de [decisiones](../../docs/decisiones.md) y de las once pruebas de `tests/Spec003Test.php` en el commit
a74f85f. La «Concreción del 2026-11-17» de arriba fija los campos y las respuestas HTTP, pero sin la tabla de
preguntas, respuestas y criterios ni las diferencias con la ficha inicial: se añaden aquí.

### 1. Tres situaciones de prueba

**Situación 1 · Recorrido habitual**

- CA-03-01. **Dado** un animal disponible y una persona con sesión y menos de dos solicitudes abiertas, **cuando**
  abre «Iniciar solicitud de adopción» desde la ficha pública y envía las seis respuestas del cuestionario,
  **entonces** la respuesta redirige (303) a «Mis solicitudes», que muestra «Solicitud enviada», «Pendiente de
  valoración» y «1 de 2 solicitudes abiertas», y la solicitud queda ligada a esa cuenta y a ese animal sin cambiar
  el estado del animal.
  Prueba: `Spec003Test::testApplicantSubmitsCompleteQuestionnaireForAvailableAnimal`.
- CA-03-04. **Dado** una solicitud admitida, **cuando** la coordinadora abre el listado y la ficha de la solicitud,
  **entonces** ve las seis respuestas del cuestionario completas.
  Prueba: `Spec003Test::testCoordinatorAccessesReceivedApplicationWithCompleteQuestionnaire`.

**Situación 2 · Entrada inválida o caso límite**

- CA-03-02 (entrada inválida). **Dado** un cuestionario con la descripción de la vivienda formada por espacios, la
  explicación de la experiencia vacía y un tiempo diario no previsto («siempre»), **cuando** se envía, **entonces**
  la respuesta es 422, se enumeran todos los campos pendientes, se conservan las respuestas válidas y no se crea
  ninguna solicitud.
  Prueba: `Spec003Test::testIncompleteQuestionnaireIsNotAdmittedAndListsPendingData`.
- CA-03-02 (caso límite). **Dado** un cuestionario con experiencia «Ninguna» y sin explicación, **cuando** se
  envía, **entonces** se admite, porque en ese caso la explicación es opcional.
  Prueba: `Spec003Test::testNoPreviousExperienceIsAValidAnswer`.
- Cuenta nueva (D06). **Dado** el alta pública con contraseña de menos de 12 caracteres y un email ya registrado,
  **cuando** se envía el formulario, **entonces** la respuesta es 422 con un error por campo y no se crea otra
  cuenta; con datos válidos, la cuenta solo recibe `ROLE_USER` aunque el formulario pida otro rol.
  Prueba: `Spec003Test::testPublicRegistrationCreatesApplicantAccountAndReturnsToApplication`.

**Situación 3 · Permisos o estado**

- CA-03-03. **Dado** una persona con una solicitud pendiente y otra en espera, **cuando** envía una tercera,
  **entonces** la respuesta es 409 con «Ya tienes dos solicitudes abiertas» y las dos existentes no cambian; una
  aprobada cuenta como abierta y una rechazada o cancelada no.
  Pruebas: `Spec003Test::testThirdOpenApplicationIsRejectedAndExistingOnesAreKept` y
  `Spec003Test::testApprovedCountsAsOpenWhileRejectedAndCancelledDoNot`.
- CA-03-05. **Dado** un animal en cuarentena, reservado o adoptado, o una solicitud abierta de la misma cuenta
  para ese animal, **cuando** la persona intenta enviar otra, **entonces** el formulario responde 404 (animal no
  disponible) o 409 (duplicado) y no se registra nada; si el animal se reserva tras cargar el formulario, el
  servicio rechaza el envío.
  Pruebas: `Spec003Test::testAnimalsThatAreNotAvailableDoNotReceiveApplications` y
  `Spec003Test::testDuplicateOpenApplicationForSameAnimalIsRejected`.
- CA-03-06. **Dado** una cuenta sin solicitudes abiertas y cuatro animales disponibles, **cuando** se envían las
  cuatro solicitudes a la vez desde procesos independientes, **entonces** se admiten exactamente dos y se rechazan
  las otras dos.
  Prueba: `Spec003Test::testConcurrentSubmissionsNeverExceedTwoOpenApplications`.
- Permisos. **Dado** una persona anónima, un solicitante sin token CSRF y cuentas de voluntario y veterinario,
  **cuando** piden el formulario, envían la solicitud o abren una candidatura, **entonces** reciben redirección a
  `/acceso` o 403 y no cambia nada.
  Prueba: `Spec003Test::testPermissionsAndCsrfProtectApplications`.

### 2. Ambigüedades detectadas

Las decisiones abiertas llevaban una recomendación como propuesta; el grupo las adoptó el 2026-11-21 y la explicación de cada responsable figura más abajo.

| # | Pregunta | Opciones | Recomendación | Justificación | CA afectado | Estado |
|---|---|---|---|---|---|---|
| Q1 | ¿Cómo se identifica a una persona para aplicar el máximo de dos? | Email escrito en cada formulario; cuenta con email único y contraseña. | Cuenta con email normalizado único; el catálogo sigue abierto. | Un email libre permitiría eludir el límite; la cuenta no acredita identidad legal. | CA-03-01, CA-03-03 | decidida previamente (D06 de docs/decisiones.md) |
| Q2 | ¿Qué estados cuentan como solicitud abierta? | Solo pendientes; pendientes y en espera; también aprobadas. | Pendiente, en espera y aprobada cuentan; rechazada y cancelada no. | SI-30 limita las «abiertas»; la aprobada sigue abierta hasta formalizar. | CA-03-03, CA-03-06 | decidida previamente (D07) |
| Q3 | ¿Qué campos son obligatorios en el cuestionario? | Texto libre único; campos por tema. | Seis respuestas: tipo y descripción de la vivienda, experiencia y explicación (opcional con «Ninguna»), tiempo y organización diarios; sin puntuación automática. | SI-30 pide vivienda, experiencia y disponibilidad completas y no admite medias. | CA-03-01, CA-03-02 | decidida previamente (D08 y concreción de clarify, 2026-11-17) |
| Q4 | ¿Se admiten dos solicitudes abiertas de la misma persona para el mismo animal? | Sí; no. | No; tras un rechazo o cancelación sí puede volver a solicitarlo. | Respeta el máximo de dos sin acumular reservas. | CA-03-05 | decidida previamente (D07) |
| Q5 | ¿Se reciben solicitudes de animales no disponibles? | Sí, en espera; no. | No: solo los disponibles; el resto no tiene formulario. | La web ofrece solo animales realmente disponibles. | CA-03-05 | decidida previamente (D10) |
| Q6 | ¿Cómo se crea la cuenta si D06 la exige y SI-30 habla de web «sin registrarse»? | Alta pública mínima; cuentas creadas por la protectora. | Alta pública de nombre, email y contraseña de al menos 12 caracteres, solo `ROLE_USER`, sin verificación ni recuperación. | D06 exige cuenta; consultar sigue sin registro. | CA-03-01 | decidida previamente (D06 y actualización del 2026-11-17 de decisiones.md) |
| Q7 | ¿Qué código HTTP recibe quien envía una solicitud de un animal que se reservó mientras tenía abierto el formulario? | 404 (animal no disponible) o 409 (rechazo por estado). | 409, como fija la «Concreción del 2026-11-17». | Es un rechazo por estado de una petición válida, no un recurso inexistente. | CA-03-05 | decidida previamente (clarify de spec-003, sección 4); el código responde 404, véase analyze A4 y C1-04 |
| Q8 | ¿Puede el personal (coordinadora, voluntarios, veterinario) enviar solicitudes con su cuenta de trabajo? | (a) Sí (hoy toda cuenta tiene `ROLE_USER`); (b) no, solo cuentas públicas, como en D26 y D34; (c) sí, pero quien decide la solicitud no puede ser su autora. | (b) | La coordinadora decidiría sobre su propia candidatura; D26 y D34 ya excluyen al personal de turnos y apadrinamientos, pero D06 no dice nada. | CA-03-01, CA-03-04 (y CA-04-03) | Decidida el 2026-11-21 por el grupo: (b) el personal no solicita adopciones con su cuenta de trabajo; las cuentas públicas y las familias de acogida sí (se aplica en el código: D45). |
| Q9 | ¿Puede la persona retirar una solicitud abierta para liberar una de sus dos plazas? | (a) No: solo la coordinadora la cierra (hoy); (b) sí, con un estado nuevo que no cuente como abierta. | (a), sin ampliar el alcance, salvo que el equipo lo pida. | SI-30 limita las solicitudes abiertas y atribuye las decisiones a la coordinadora; no menciona la retirada. | CA-03-03 | Decidida el 2026-11-21 por el grupo: (a) no hay retirada; solo la coordinadora cierra la solicitud, salvo que el equipo lo pida. |

### Explicación de las decisiones por sus responsables

**Q8 · Solicitudes del personal.** Hemos decidido que el personal de la protectora (coordinadora, voluntarios y veterinario) no pueda enviar solicitudes de adopción con su cuenta de trabajo: la coordinadora no debe decidir sobre su propia solicitud y una cuenta de trabajo no representa a una familia adoptante. Las familias de acogida sí pueden solicitar la adopción, porque es habitual que quieran quedarse con el animal que acogen; su solicitud sigue el mismo procedimiento que la de cualquier persona. Se aplica en el código como D45. — Antonio García Gómez (`aggg15`)

**Q9 · Retirada de una solicitud.** Hemos decidido no permitir que la persona retire una solicitud abierta para liberar una de sus plazas: de momento solo la coordinadora cierra solicitudes. SI-30 limita las solicitudes abiertas y atribuye las decisiones a la coordinadora, pero no menciona la retirada, así que no ampliamos el alcance salvo que el equipo lo pida. — Antonio García Gómez (`aggg15`)

### 3. Diferencias con la especificación inicial

Comparación de `spec-003/historia.md` (ficha inicial de la raíz) con `specs/spec-003/spec.md` (vigente).
`git log` registra dos versiones de `spec.md`: 7bcea03 (2026-11-05), una copia de la ficha inicial con la cabecera
«Especificación», y 5ac5c68 (2026-11-17), la versión vigente; b001d3f solo tocó `converge.md`.

- CA-03-01 a CA-03-04 se conservan con la redacción idéntica. Se añaden dos criterios derivados de clarify:
  CA-03-05 (animal no disponible o duplicado) y CA-03-06 (envíos simultáneos).
- Se añaden las reglas de negocio R1 a R7 (identidad por cuenta, abiertas, duplicados, solo animales disponibles,
  campos del cuestionario, estado inicial pendiente y solo la coordinadora consulta) y siete casos límite.
- Cabecera: de «historia inicial, responsable pendiente de acuerdo» a una spec concretada el 2026-11-17 por encargo
  del usuario (`aggg15`), con revisión de `abcb5-ua` pendiente y estado «implementada en la rama `spec-003`».
- Límites: la ficha excluía «un proceso de registro no definido en la narrativa»; la spec admite el alta de cuenta
  como soporte mínimo de D06 (nombre, email y contraseña) y excluye además verificación de email, recuperación de
  contraseña y valoración automática de candidaturas.
- Dependencias: se añade la ruta `/animales/{id}` de HU-02 y que `AdoptionApplication` es una entidad común que
  HU-04 amplía con entrevista y motivo.
- La sección «Cuestiones pendientes para clarify» (tres preguntas) pasa a «Clarificaciones aplicables» con D06 a D08
  y D10; se elimina «Encaje en el trabajo grupal» y se añaden «Base compartida» y «Requisitos de calidad y
  permisos».
- No cambian la historia de usuario ni el alcance.

### 4. Nota de método

Ronda realizada con Claude Code siguiendo el procedimiento de `.agents/skills/speckit-clarify/SKILL.md`; no se ha
ejecutado la skill de Codex. Las decisiones abiertas se cerraron el 2026-11-21 (ver la tabla y las explicaciones).
