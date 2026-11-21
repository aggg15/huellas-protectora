# Clarify · spec-001

Base aceptada por el usuario el 2026-11-05. Registro de decisiones: ../../docs/decisiones.md. Las preguntas iniciales se conservan en la ficha antecedente de la raíz.

Esta fase se aplica por Codex a partir de las fuentes vigentes; no se presenta como ejecución de un comando slash instalado.

## Ronda de clarificación previa a la entrega (2026-11-21)

Historia: HU-01 · Registrar un animal y gestionar su cuarentena. Responsable: `aggg15`. Revisión de
`abcb5-ua` de esta ronda: pendiente. Se parte de `specs/spec-001/spec.md`, de la ficha inicial
`spec-001/historia.md`, de SI-30 (p. 1), de [decisiones](../../docs/decisiones.md) y de las siete pruebas de
`tests/Spec001Test.php` en el commit a74f85f. Las decisiones D01 a D04 ya respondían a las tres preguntas de la
ficha inicial, pero este archivo no las registraba como pregunta, respuesta y criterio: se hace aquí.

### 1. Tres situaciones de prueba

**Situación 1 · Recorrido habitual**

- CA-01-01 y CA-01-02. **Dado** un voluntario con sesión (`ROLE_VOLUNTEER`) y los datos de un animal recién
  llegado (perro, 8 meses, tamaño pequeño, procedencia y una fotografía válida), **cuando** envía el formulario de
  alta con su token CSRF, **entonces** se crea la ficha con un código `HUE-…`, queda en estado «cuarentena» y
  conserva la procedencia y la fotografía.
  Prueba: `Spec001Test::testVolunteerCreatesIdentityAndPhotoInQuarantine`.
- CA-01-05. **Dado** un animal en cuarentena recibido hace 240 horas o más, **cuando** se consulta el sistema o se
  ejecuta la liberación, **entonces** pasa a «disponible» y los animales reservados no cambian.
  Pruebas: `Spec001Test::testBoundaryIsExactlyTenPeriodsOf24Hours` y
  `Spec001Test::testExpiredQuarantineReleasesAutomaticallyWithoutChangingReservations`.

**Situación 2 · Entrada inválida o caso límite**

- CA-01-01 (entrada inválida). **Dado** un voluntario con sesión, **cuando** envía una especie no admitida
  («bird»), una edad negativa y una recepción futura, o una fotografía con extensión `.jpg` y contenido que no es
  una imagen, **entonces** la respuesta es 422, conserva lo escrito y no se crea ninguna ficha ni fichero.
  Prueba: `Spec001Test::testInvalidAnimalAndSpoofedPhotoLeaveNoPartialWrites`.
- CA-01-02 y CA-01-05 (caso límite). **Dado** un animal recibido hace 239 h 59 min 59 s, **cuando** se evalúa su
  liberación, **entonces** sigue en cuarentena; **cuando** se cumplen 240 h exactas, **entonces** queda disponible.
  Prueba: `Spec001Test::testBoundaryIsExactlyTenPeriodsOf24Hours`.

**Situación 3 · Permisos o estado**

- CA-01-03. **Dado** un animal en cuarentena, **cuando** una persona anónima pide su ficha pública, su foto o su
  ficha interna, **entonces** recibe 404 en las dos primeras y una redirección a `/acceso` en la tercera.
  Prueba: `Spec001Test::testAnonymousCannotSeeQuarantineOrPhotos` (la comprobación de la foto tiene una
  limitación, véase [analyze](analyze.md), A4 y C1-05).
- CA-01-04. **Dado** un animal en cuarentena, **cuando** el veterinario guarda una revisión con fecha real y
  observaciones, **entonces** queda una revisión asociada a la ficha; **cuando** un voluntario sin `ROLE_VET`
  envía la misma petición, **entonces** recibe 403.
  Prueba: `Spec001Test::testVeterinaryReviewAndRoleValidation` (véase analyze, A5 y C1-07).
- Permisos generales. **Dado** un voluntario sin token CSRF o una cuenta solicitante sin rol interno, **cuando**
  intentan crear una ficha, **entonces** reciben 403.
  Prueba: `Spec001Test::testCsrfRequiredAndApplicantCannotCreateAnimals`.

### 2. Ambigüedades detectadas

Se contrastan `spec.md` y SI-30. Las filas «PENDIENTE» llevan una recomendación como propuesta, no como
decisión: la toma y la explica el responsable.

| # | Pregunta | Opciones | Recomendación | Justificación | CA afectado | Estado |
|---|---|---|---|---|---|---|
| Q1 | ¿Los diez días son naturales o periodos de 24 horas, y la liberación es automática o la confirma una persona? | (a) Diez días naturales por fecha; (b) 240 horas desde la recepción, automático. | (b) | SI-30 dice «diez días» sin hora de corte; 240 horas UTC evitan medianoches y cambios horarios. | CA-01-02, CA-01-05 | decidida previamente (D01 de docs/decisiones.md) |
| Q2 | ¿Qué se registra de la revisión veterinaria y qué pasa si falta el día diez? | (a) La falta bloquea la liberación; (b) no bloquea. | (b): veterinario, fecha real y notas. | SI-30 solo exige que se revise durante la cuarentena y que después quede disponible. | CA-01-04, CA-01-05 | decidida previamente (D02) |
| Q3 | ¿Cómo se genera el código y cómo se recoge el tamaño que filtra HU-02? | Código libre o generado; tamaño libre o en categorías. | Código `HUE-` generado; especie perro o gato; edad en meses; tamaño pequeño, mediano o grande. | Identidad y filtros sin datos personales. | CA-01-01 | decidida previamente (D03) |
| Q4 | ¿Cuántas fotografías, de qué tipo y tamaño, y quién las ve? | Sin límite o con límite; públicas o protegidas. | De una a seis, JPEG, PNG o WebP, 5 MiB como máximo, comprobadas por contenido y servidas con permiso. | Hacer verificables las fotografías sin publicar la cuarentena. | CA-01-01, CA-01-03 | decidida previamente (D04) |
| Q5 | ¿Se admite registrar un animal con recepción anterior a hoy y qué ocurre si ya han pasado 240 horas? | (a) Admitirla y dejarlo disponible al crearlo (comportamiento actual de `AnimalService::create`); (b) admitirla pero exigir diez días desde el registro; (c) limitar cuántos días hacia atrás. | (a), acotando el retroceso si el equipo lo estima. | SI-30 dice que «ningún animal recién llegado» se ofrece de inmediato; una ficha tardía de un animal que lleva semanas en el refugio no es «recién llegado», pero la narrativa no lo cubre. | CA-01-02, CA-01-05 | PENDIENTE de confirmación por el responsable (`aggg15`) |
| Q6 | ¿Se admite registrar la revisión de un animal que ya no está en cuarentena, y más de una por animal? | (a) Una sola, solo en cuarentena; (b) varias, en cualquier momento (comportamiento actual); (c) una, en cualquier momento. | (b), porque D02 hace la revisión informativa y no bloqueante. | CA-01-04 se redacta para un animal en cuarentena, pero ni la spec ni D02 limitan el estado ni el número de revisiones. | CA-01-04 | PENDIENTE de confirmación por el responsable (`aggg15`) |
| Q7 | ¿Qué límites tienen la edad (0 a 600 meses) y la procedencia (1000 caracteres)? | Sin límite; límites generosos; límites estrictos. | Los actuales, documentados como protección frente a errores de tecleo. | D03 fija la unidad (meses) pero no los límites; otras decisiones análogas (D14, D20, D27, D36) sí los registran. | CA-01-01 | PENDIENTE de confirmación por el responsable (`aggg15`) |

Cada decisión «PENDIENTE» necesita, además, una explicación breve del responsable (enunciado §5.1): *pendiente de
redactar por `aggg15`*.

### 3. Diferencias con la especificación inicial

Comparación de `spec-001/historia.md` (ficha inicial de la raíz) con `specs/spec-001/spec.md` (vigente).
`git log` registra una sola versión de `spec.md`, creada en 7bcea03 (2026-11-05) y sin cambios posteriores.

- Los cinco criterios CA-01-01 a CA-01-05 se conservan con la redacción idéntica, incluidas sus trazas; no se
  añaden criterios derivados.
- Cambia la cabecera: del título «HU-01 · Registrar un animal y gestionar su cuarentena» y el estado «historia
  inicial para revisión conjunta, responsable pendiente de acuerdo» a «Especificación HU-01 · spec-001», con la
  fecha 2026-11-05 y la nota «implementación secuencial autorizada en esta sesión». La spec no consigna
  responsable ni revisor.
- La sección «Cuestiones pendientes para clarify» (tres preguntas) se sustituye por «Clarificaciones aplicables»,
  que remite a D01 a D11 de `docs/decisiones.md`. Las respuestas siguen fuera de la spec: viven en esas
  decisiones y, desde esta ronda, en la tabla de la sección 2.
- CA-01-04 y CA-01-05 conservan «se concretará en clarify»; la concreción efectiva es D02 y D01.
- Se elimina «Encaje en el trabajo grupal» y se añaden «Base compartida» (plan y modelo comunes) y «Requisitos de
  calidad y permisos» (validación en servidor, CSRF, datos ficticios, sin cambios parciales y verificación de
  caso normal, datos inválidos y permisos o estado).
- No cambian la historia de usuario, el alcance, las dependencias, los límites ni la fuente.

### 4. Nota de método

Ronda realizada con Claude Code siguiendo el procedimiento de `.agents/skills/speckit-clarify/SKILL.md`; no se ha
ejecutado la skill de Codex. No se han tomado decisiones nuevas: las filas PENDIENTE esperan al responsable.
