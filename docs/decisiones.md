# Decisiones funcionales y alcance de Huellas

Fecha: 2026-11-05. El usuario ha aceptado como base catálogo público, cuentas con email/contraseña,
roles internos y diez periodos de 24 horas de cuarentena. Las precisiones restantes son decisiones
de implementación documentadas, no frases atribuidas al enunciado ni acuerdos inventados del compañero.

Después de consultar al profesor, el usuario limita esta publicación a **spec-001**. Las decisiones
de HU-02 a HU-04 se conservan como planificación futura, sin publicar su implementación. Se retiran
sus ramas remotas y se cierran sus PR; el trabajo posterior se conserva únicamente en local.

| ID | Decisión | Motivo / historias |
|---|---|---|
| D01 | Cuarentena de 240 horas desde recepción UTC; promoción automática al consultar o por comando. | Evitar ambigüedad de medianoches y cambios horarios; HU-01/02. |
| D02 | Revisión: veterinario, fecha real y notas. No bloquea la liberación al día diez si falta el registro. | El enunciado ordena disponibilidad pasada la cuarentena, sin definir otra condición; HU-01. |
| D03 | Código HUE generado; especie perro/gato; edad aproximada en meses, tamaño pequeño/mediano/grande. | Permite identidad y filtros sin usar datos personales; HU-01/02. |
| D04 | Una a seis fotos JPEG/PNG/WebP, máximo 5 MiB cada una; ficheros protegidos y comprobados por contenido. | Verificar las fotografías requeridas sin publicar la cuarentena; HU-01. |
| D05 | Filtros combinados con AND; edad en intervalos: menos de 12 meses, 12 a 83 y desde 84. | Concreción verificable del catálogo; HU-02. |
| D06 | Cuenta con email único normalizado y contraseña; el catálogo sigue abierto. Identifica una cuenta, no acredita identidad legal de una persona. | Aplicar el máximo sin confiar en un email libre en cada formulario; HU-03. |
| D07 | Pendiente, en espera y aprobada cuentan como abiertas; rechazada y cancelada no. Duplicados abiertos del mismo animal no se admiten. | Respeta el máximo de dos sin favorecer acumulación por reservas; HU-03/04. |
| D08 | Cuestionario obligatorio: vivienda, experiencia previa (incluida ninguna) y disponibilidad diaria; nombre en la cuenta. | Tres temas de la narrativa, sin introducir un perfilado automático; HU-03. |
| D09 | Entrevista con fecha/hora y notas; la coordinadora debe haber registrado una entrevista no futura para aprobar. Puede rechazar antes, con motivo. | Representar revisión y entrevista antes de aprobación; HU-04. |
| D10 | No se reciben nuevas solicitudes para un animal reservado. Al aprobar, las otras pendientes pasan a espera; las rechazadas no se reabren. | El catálogo ofrece solo animales realmente disponibles; HU-03/04. |
| D11 | Si falla una reserva, la coordinadora puede liberarla con motivo: candidata cancelada, esperas a pendientes, animal disponible. | Dar sentido a la espera descrita sin implementar la formalización de HU-05; HU-04. |

Actualización del 2026-11-17: el usuario encarga expresamente desarrollar y publicar **spec-003**
(HU-03) a continuación de HU-02, ya integrada en `main` mediante la PR #5. Para aplicar D06 se añade un alta pública mínima
(`/registro`: nombre, email y contraseña) que solo crea cuentas `ROLE_USER`. La concreción de los
campos del cuestionario de D08 está en specs/spec-003/clarify.md. No cambia ninguna decisión anterior.

La autenticación es soporte común, no una novena historia. El alcance no incluye verificación de
email, recuperación de contraseña, servicios externos ni administración pública de roles.
Las cuentas de personal se crean por comando; las credenciales locales se excluyen de Git.

Actualización del 2026-11-18: HU-02 a HU-04 están integradas en `main` (PR #5, #6 y #8). Las
concreciones de HU-02 y HU-04 están en specs/spec-002/clarify.md y specs/spec-004/clarify.md y
aplican D03, D05 y D07 a D11 sin cambiarlas. El usuario (`aggg15`) asume HU-05; sus decisiones
se registran a partir de D12. Las tablas anteriores se conservan como historial.

## Decisiones de HU-05 (2026-11-18)

Propuestas en la revisión previa (docs/evidencias/revision-previa-spec-005-2026-11-18.txt) y
aceptadas por el usuario (`aggg15`, responsable de HU-05). Concreción en specs/spec-005/clarify.md.

| ID | Decisión | Motivo / historias |
|---|---|---|
| D12 | Formaliza la coordinadora, solo sobre una solicitud aprobada cuyo animal sigue reservado. La entrega se fecha en el momento de formalizar: no se registran entregas futuras ni retroactivas. | SI-30: «se formaliza el día de la entrega»; HU-04/05. |
| D13 | El contrato es un documento HTML imprimible generado por el sistema, con número único, fecha de entrega, datos del animal y del adoptante (cuenta), cuota abonada y compromisos de seguimiento. No cambia después de generarse. Solo lo consultan la coordinadora y el adoptante titular. Sin PDF ni firma electrónica. | Hacer verificable «se genera un contrato» sin servicios ajenos; la ficha excluye la firma electrónica. |
| D14 | SI-30 no fija la cuota: la coordinadora indica el importe abonado (mayor que 0 y hasta 9 999,99 €, con dos decimales como máximo) y confirma expresamente el abono. Sin ambos datos no se formaliza. Sin pasarela de pago ni contabilidad. | Dejar constancia del abono sin inventar una tarifa; contabilidad excluida. |
| D15 | Al formalizar, en una sola operación: la solicitud pasa a «formalizada» (deja de contar como abierta), el animal pasa a adoptado y deja de figurar en acogida, y las demás solicitudes abiertas del animal se cancelan con el motivo «El animal ha sido adoptado». Ya no se puede liberar la reserva. | D07 («hasta que HU-05 formalice»); evitar que esperas sin salida bloqueen el máximo de dos; HU-03/04/06. |
| D16 | Se programan dos seguimientos por adopción, al mes y a los seis meses de la fecha de entrega en Europe/Madrid, sumando meses naturales; si el día no existe en el mes de destino se usa el último día de ese mes. Nacen «programados», a cargo de la coordinadora. Registrar las visitas queda fuera. | Fechas inequívocas (31-ene → 28/29-feb); la ficha excluye un módulo de visitas. |
| D17 | Un animal y una solicitud tienen como máximo una adopción. Formalizaciones repetidas o simultáneas, o una liberación de reserva simultánea, no producen dos adopciones ni deshacen una formalizada; los errores no dejan cambios parciales. | Plan común: concurrencia y coherencia; HU-04/05. |
| D18 | La adopción registrada (animal, adoptante y fecha) es el dato que HU-08 usará para proponer al padrino otro animal o terminar. HU-05 no crea padrinos ni envía avisos. | CA-05-05 sin adelantar HU-08. |

Actualización del 2026-11-19: HU-05 está integrada en `main` (PR #9). `abcb5-ua` asume HU-06 por
encargo expreso; sus decisiones se registran a partir de D19.

## Decisiones de HU-06 (2026-11-19)

Planteadas en clarify y elegidas por `abcb5-ua` (responsable de HU-06): D19, D21, la ocupación de D21 y
D24 se eligieron entre opciones; el resto las concreta. Detalle en specs/spec-006/clarify.md.

| ID | Decisión | Motivo / historias |
|---|---|---|
| D19 | La familia de acogida tiene una cuenta propia con el rol `ROLE_FOSTER`, creada por la protectora con `app:create-user`. No es personal ni se crea desde el registro público. Una cuenta corresponde a una casa. | La historia está escrita desde la familia; evitar que cualquiera se declare casa de acogida; HU-06. |
| D20 | Capacidad entera de 1 a 10. Periodos de disponibilidad en días completos de Europe/Madrid (inicio y fin incluidos), sin solapes, desde hoy y hasta dos años después; los periodos consecutivos se consideran continuos. | SI-30: «periodos de disponibilidad y número máximo»; límites solo contra errores de tecleo. |
| D21 | Solo la coordinadora registra acogidas. Empiezan el día en que se registran y tienen un fin previsto; la casa debe estar disponible todos los días del intervalo y sus acogidas activas no pueden superar la capacidad (ocupación = acogidas activas). Un animal tiene como máximo una acogida activa. Se comprueba todo bajo bloqueo (animal y después casa). | Cálculo exacto sin tareas programadas; plan común: releer tras el bloqueo; HU-06. |
| D22 | La coordinadora finaliza la acogida (fin = hoy, motivo «regreso al refugio»). Un fin previsto superado no la cierra: se señala hasta que se finaliza. Las novedades se conservan. | El animal sigue en la casa hasta que vuelve; HU-06. |
| D23 | Los cambios de condiciones se aplican a las acogidas nuevas. No se admite bajar la capacidad por debajo de las acogidas activas ni retirar disponibilidad necesaria para una acogida activa. | Pregunta 2 de la ficha: sin cierres automáticos ni datos incoherentes; HU-06. |
| D24 | Las novedades las escribe la familia de la acogida activa (texto de 1 a 2000 caracteres, fecha y autora); no se editan ni se borran. Son internas: el personal ve el historial completo y la familia, las de sus acogidas activas. No se publican; su uso con padrinos se decidirá en HU-08. | Constitution V; no adelantar HU-08; HU-06/08. |
| D25 | La acogida no cambia el estado del animal (cuarentena, disponible o reservado) ni la reserva; un animal adoptado no admite acogida. La web pública sigue mostrando solo los disponibles, con «En casa de acogida». Formalizar la adopción finaliza la acogida activa con motivo «adopción» en la misma transacción. | CA-06-04; amplía D15 para liberar la plaza de la casa; HU-02/04/05/06. |

Actualización del 2026-11-19 (tarde): HU-06 está integrada en `main` (PR #10). `aggg15` asume HU-07 por
encargo expreso; sus decisiones se registran a partir de D26.

## Decisiones de HU-07 (2026-11-19)

Planteadas en clarify y elegidas por `aggg15` (responsable de HU-07): D27, D29 (turnos coincidentes) y D30 se
eligieron entre opciones, en cada caso la recomendada; el resto las concreta. Detalle en specs/spec-007/clarify.md.

| ID | Decisión | Motivo / historias |
|---|---|---|
| D26 | Los voluntarios son las cuentas con `ROLE_VOLUNTEER`, creadas por la protectora con `app:create-user` (HU-01). La coordinadora hereda ese rol y también puede apuntarse. Veterinario, familias de acogida y solicitantes no acceden a los turnos. Una inscripción identifica una cuenta. | La historia está escrita desde el voluntario; reutiliza los roles del plan común. |
| D27 | Solo la coordinadora crea y modifica turnos. Cada turno tiene tipo (paseo o limpieza), fecha y horas de inicio y fin del mismo día en Europe/Madrid (guardadas en UTC), y un mínimo y un máximo de personas con 1 ≤ mínimo ≤ máximo ≤ 20. Empieza después del momento en que se crea y como máximo un año después. Una hora inexistente por el cambio de hora no es válida. | SI-30: «cada turno necesita un mínimo… y admite un máximo». Los límites de 20 personas y un año solo evitan errores de tecleo. |
| D28 | Mientras el turno no ha empezado, la coordinadora puede cambiar el mínimo y el máximo; el máximo no puede quedar por debajo de las inscripciones. El tipo, la fecha y las horas solo cambian si el turno no tiene inscripciones. No se borran ni se cancelan turnos. | No alterar el compromiso de quien ya se apuntó ni crear coincidencias; la ficha excluye la cancelación automática. |
| D29 | Un voluntario se apunta a un turno que no ha empezado y tiene plazas (inscripciones < máximo), una sola vez por turno y nunca en un turno que coincida en el tiempo con otro en el que ya figura. Dos turnos coinciden si cada uno empieza antes de que termine el otro; uno que empieza cuando acaba otro no coincide. | SI-30: inscripción libre con un máximo; pregunta 3 de la ficha: nadie cuenta para dos turnos a la vez. |
| D30 | La persona inscrita puede darse de baja hasta el inicio del turno: la inscripción se elimina y la plaza queda libre. Si así el turno baja de su mínimo, vuelve a contar como falto de voluntarios. | Pregunta 1 de la ficha; un aviso útil debe reflejar las bajas. |
| D31 | Un turno está falto de voluntarios cuando todavía no ha empezado, tiene menos inscripciones que su mínimo y faltan 48 horas o menos para su inicio. Se calcula con instantes UTC (48 horas reales, también con cambio de hora) en cada consulta, sin tareas programadas. El destacado se muestra en el listado de turnos a voluntarios y coordinadora; con más de 48 horas se informa de la cobertura sin destacar el turno. | SI-30: «aparezcan destacados cuando falten cuarenta y ocho horas»; pregunta 2 de la ficha. |
| D32 | El listado muestra los turnos que todavía no han terminado, por hora de inicio, con tipo, fecha y horas, mínimo, máximo, inscripciones y plazas libres, y señala aquellos en los que figura quien consulta. La ficha del turno muestra los nombres de las personas inscritas al personal con acceso. Un turno empezado figura «En curso» y no admite inscripciones ni bajas. | CA-07-01: se conocen las inscripciones registradas; información interna, no pública. |
| D33 | Inscribirse bloquea la cuenta del voluntario y después el turno, y vuelve a comprobar bajo bloqueo plazas, inscripción previa, coincidencias e inicio; modificar o darse de baja bloquea el turno. Un índice único impide dos inscripciones de la misma cuenta en un turno. Peticiones simultáneas no superan el máximo ni dejan cambios parciales. | Plan común: releer el estado después del bloqueo; Constitution III. |

Actualización del 2026-11-19 (noche): HU-07 está integrada en `main` (PR #11, aprobada por `abcb5-ua`). `abcb5-ua`
asume HU-08 por encargo expreso; sus decisiones se registran a partir de D34.

## Decisiones de HU-08 (2026-11-19)

Planteadas en clarify y elegidas por `abcb5-ua` (responsable de HU-08): D34, D35, D37 y D38 se eligieron entre
opciones, en cada caso la recomendada; el resto las concreta. Detalle en specs/spec-008/clarify.md.

| ID | Decisión | Motivo / historias |
|---|---|---|
| D34 | Apadrina una persona con cuenta pública (registro de HU-03, solo `ROLE_USER`) desde la ficha pública del animal. Las cuentas de personal y de familias de acogida no apadrinan. Una cuenta identifica, no acredita identidad legal (D06). | La historia está escrita desde el padrino; reutiliza el registro existente. |
| D35 | Solo se apadrinan animales disponibles (también en acogida). Varios padrinos por animal y varios animales por padrino, con un único apadrinamiento activo por cuenta y animal (índice único parcial). | Pregunta 3 de la ficha; la cuarentena no es pública y un animal reservado o adoptado ya tiene familia. |
| D36 | La aportación mensual es un compromiso registrado de 10,00 € a 9 999,99 € (dos decimales como máximo), con fecha de inicio. Sin cobros, recibos ni pasarela; el importe no se modifica (se termina y se apadrina de nuevo). | SI-30: mínimo de diez euros; contabilidad excluida; el máximo solo evita errores de tecleo, como D14. |
| D37 | La coordinadora publica novedades para los padrinos de cada animal con apadrinamientos activos (1 a 2000 caracteres, inmutables). Cada padrino las ve en «Mis apadrinamientos», solo las escritas mientras su apadrinamiento estaba activo, también tras terminarlo. Las notas de acogida siguen siendo internas (D24). Sin correo. | Pregunta 1 de la ficha: la coordinadora prepara y valida lo que se comparte; sin servicio de mensajería. |
| D38 | Un apadrinamiento activo cuyo animal se adopta queda «pendiente de decisión», con la aportación en pausa desde la fecha de entrega (D18). Se calcula al consultar; HU-05 no cambia. | La narrativa pide ofrecer la elección; sin tareas programadas. |
| D39 | El padrino elige: otro animal disponible que no apadrina (el anterior termina con «Cambio a otro animal» y se crea uno nuevo con la misma aportación, enlazado, en una transacción) o terminar («Fin de la colaboración»). Puede terminar en cualquier momento. Nada se asigna ni termina automáticamente. | CA-08-05: elección del padrino sin asignaciones unilaterales; el enlace conserva el historial. |
| D40 | Cada padrino ve sus apadrinamientos; la coordinadora, los activos por animal (persona, importe, desde y pausa) en `/admin/apadrinamientos`. Nadie más. | Preparar las novedades sin publicar datos de los padrinos; Constitution V. |
| D41 | Apadrinar bloquea la cuenta y después el animal; cambiar y terminar bloquean la cuenta, el apadrinamiento y, al cambiar, el animal elegido; publicar una novedad bloquea el animal. Se vuelve a comprobar todo bajo bloqueo. | Plan común: releer después del bloqueo; mismo orden que HU-03 y HU-07; sin ciclos con la formalización de HU-05. |

## Reparto y material vigente

El nuevo enunciado v1 mezcla cuatro integrantes/dos historias con dos integrantes/cuatro historias.
Se conserva la guía específica de dos personas. El reparto observado y los encargos vigentes están en
agrupamiento.md; los nombres académicos siguen pendientes y la revisión cruzada consta en cada PR.
La nueva fuente es Practica-SDD-enunciado_v1_IR.pdf y sustituye al archivo anterior retirado por el
usuario. El informe de octubre y el PDF de historias son antecedentes fechados, no evidencia de la
implementación actual. La revisión actual deja de usar Práctica 1 como fuente.

El enunciado v1 requiere specs/ y docs/agrupamiento.md y docs/memoria.md, además de dos ZIP finales
(proyecto y vídeo, máximo conjunto 100 MB). Esta iteración prepara la aplicación y documentación
de las historias integradas; no declara terminada HU-08 (pendiente de revisión), la memoria ni la demo final.
