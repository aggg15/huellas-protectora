# Clarify · spec-005

Fecha: 2026-11-18. Historia: HU-05 · Formalizar la adopción y programar los seguimientos.
Responsable: `aggg15`. Revisión de `abcb5-ua`: pendiente.

Aplicado por Claude Code a petición del usuario sobre las fuentes de la Práctica Grupal. No se ha
ejecutado la skill `$speckit-clarify`. Las preguntas proceden de la ficha inicial y de la revisión de
HU-01 a HU-04 ([evidencia](../../docs/evidencias/revision-previa-spec-005-2026-11-18.txt)). Las
respuestas son propuestas aceptadas por el usuario el 2026-11-18 y se registran como D12 a D18 en
[decisiones](../../docs/decisiones.md). Se distingue lo que dice SI-30 de lo que decide el equipo.

## Registro de preguntas

| # | Pregunta | Respuesta | Justificación | Criterios |
|---|---|---|---|---|
| Q1 | ¿Qué datos y formato tiene el contrato? | Documento HTML imprimible generado por el sistema: número único, fecha de entrega, animal (código, especie, edad aproximada, tamaño), adoptante (nombre y email de la cuenta), cuota abonada y compromiso de los dos seguimientos. No cambia tras generarse. Sin PDF ni firma electrónica. (D13) | SI-30 solo dice «se genera un contrato». La ficha excluye la firma electrónica; un PDF añadiría una dependencia sin necesidad funcional. La cuenta no acredita identidad legal (D06): el contrato no lo afirma. | CA-05-01 |
| Q2 | ¿Quién puede ver el contrato? | La coordinadora y el adoptante titular. Nadie más. (D13) | Contiene datos personales; Constitution V: lo público no hace públicas las operaciones internas. | CA-05-11 |
| Q3 | ¿Cuál es la cuota y cómo consta su abono? | SI-30 no fija importe. La coordinadora indica el importe abonado (0 < importe ≤ 9 999,99 €, dos decimales como máximo) y confirma expresamente el abono. (D14) | No inventar una tarifa ni contabilidad (excluida). El límite superior solo evita errores de tecleo, no es una regla de la protectora. | CA-05-02, CA-05-07 |
| Q4 | ¿Qué fecha tiene la entrega? | El momento de la formalización; no hay entregas futuras ni retroactivas. (D12) | SI-30: «se formaliza el día de la entrega». | CA-05-02 |
| Q5 | ¿Cómo se calculan el mes y los seis meses si el día no existe en el mes de destino? | Meses naturales sobre la fecha de entrega en Europe/Madrid; si el día no existe, último día del mes de destino. (D16) | Resultado inequívoco y verificable. Sumar meses sin ajustar desborda (31-ene + 1 mes daría 3-mar). | CA-05-03, CA-05-09 |
| Q6 | ¿Quién se responsabiliza de los seguimientos y qué se registra? | La coordinadora de adopciones. Nacen en estado «programado» y se listan por fecha. Registrar las visitas y su resultado queda fuera. (D16) | SI-30: «dejar programados»; la ficha excluye un módulo de visitas. | CA-05-03 |
| Q7 | ¿Qué hace falta para cerrar la formalización? | Solicitud aprobada, animal reservado, importe válido y confirmación del abono; todo se aplica en una única operación o no se aplica. (D12, D14, D17) | La adopción parte de la reserva de HU-04; plan común: sin escrituras parciales. | CA-05-06, CA-05-07 |
| Q8 | ¿Qué pasa con la solicitud aprobada? | Pasa a un estado nuevo «formalizada», que no cuenta como abierta. Ya no admite liberar la reserva. (D15) | D07 («la aprobada cuenta hasta que HU-05 formalice»). Dejarla aprobada mantendría ocupado el máximo de dos y permitiría «Liberar reserva» sobre un animal adoptado. | CA-05-08 |
| Q9 | ¿Qué pasa con las solicitudes en espera? | Se cancelan con el motivo «El animal ha sido adoptado» y dejan de contar como abiertas. (D15) | SI-30: quedan en espera «por si la adopción no llega a buen término»; si llega, la espera pierde sentido y bloquearía el máximo de dos. Se cancelan, no se rechazan, porque no hay valoración negativa de la coordinadora. | CA-05-08 |
| Q10 | ¿Y una acogida activa? | El animal adoptado deja de figurar en acogida. La gestión de casas de acogida es de HU-06. (D15) | SI-30: la acogida dura mientras el animal busca familia definitiva. | CA-05-02 |
| Q11 | ¿Cómo se evita una doble adopción o deshacer una adopción? | Una adopción como máximo por animal y por solicitud; formalizaciones repetidas o simultáneas y una liberación simultánea no pueden dejar datos incoherentes. (D17) | Plan común: concurrencia y coherencia. La revisión previa detectó que la liberación de HU-04 comprueba el estado antes del bloqueo. | CA-05-10 |
| Q12 | ¿Cómo se verifica CA-05-05 sin HU-08? | HU-05 deja la adopción registrada e identificable por animal, con adoptante y fecha. La propuesta al padrino se verifica en HU-08. (D18) | No adelantar HU-08 ni eliminar el criterio inicial. | CA-05-05 |

## Diferencias con la especificación inicial

- Se conservan CA-05-01 a CA-05-05 con su redacción y se añaden escenarios «Dado… Cuando… Entonces…».
- CA-05-05 se acota a lo verificable en HU-05 (Q12), sin cambiar su texto.
- Se añaden CA-05-06 a CA-05-11 (precondiciones, abono, cierre de solicitudes, fin de mes,
  concurrencia y permisos), reglas R1 a R9 y casos límite.
- La historia, el alcance inicial y los límites se mantienen; los límites se amplían con exclusiones
  explícitas (PDF, correos, devoluciones, padrinos y acogidas).

## Pendiente para plan

El modelo de datos (adopción, contrato y seguimientos), el nuevo estado de la solicitud en el CHECK
común, el reloj controlable para las pruebas de fechas y el ajuste de la liberación de reservas de
HU-04 se deciden en `plan.md`, coordinando con `abcb5-ua` los cambios en código común.
