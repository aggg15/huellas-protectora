# Especificación HU-05 · spec-005

Fecha: 2026-11-05 (especificación inicial); concretada el 2026-11-18 por encargo del usuario.
Responsable GitHub: `aggg15`. Revisión prevista: `abcb5-ua` (pendiente).
Estado: especificada, aclarada y planificada ([plan](plan.md)) en la rama `spec-005`, creada desde
`main` (cdb0416). Con [tareas](tasks.md) e [implementación](implement.md) (T505–T524); verificada manualmente y con [converge](converge.md); tareas de converge T528–T530 completadas; pendiente la revisión.

## Historia de usuario

Como coordinadora de adopciones, quiero formalizar la entrega mediante el contrato y el abono de la cuota y dejar programados los dos seguimientos para registrar la adopción y mantener la atención posterior al animal.

## Alcance de la historia

- Formalización de la adopción el día de la entrega: generación del contrato y constancia del abono de la cuota.
- Cambio del animal a adoptado.
- Programación de dos seguimientos: uno al mes y otro a los seis meses de la adopción.
- Cierre de la solicitud formalizada y de las demás solicitudes abiertas del animal (D15).
- Consulta del contrato por la coordinadora y por el adoptante titular, y de los seguimientos
  programados por la coordinadora.

## Criterios de aceptación iniciales

Se conservan los cinco criterios iniciales. Las decisiones que los concretan están en [clarify](clarify.md)
(D12 a D18 de [decisiones](../../docs/decisiones.md)).

### CA-05-01

Dado un animal reservado para una familia, cuando se formaliza su entrega, entonces se genera un contrato vinculado al animal y a la familia adoptante.

Traza: SI-30, p. 1: contrato el día de la entrega. Concreción: D12, D13.

- **Dado** un animal reservado por la solicitud aprobada de una cuenta,
- **Cuando** la coordinadora formaliza la entrega indicando la cuota abonada y confirmando el abono,
- **Entonces** existe un contrato con número único, fecha de entrega, código del animal y nombre y
  email de la cuenta adoptante,
- **Y** el contrato puede consultarse e imprimirse desde la adopción.

### CA-05-02

Dada la formalización de una adopción, cuando se completa, entonces consta el abono de la cuota y el animal figura como adoptado desde esa entrega.

Traza: SI-30, p. 1: abono de cuota y condición de adoptado. Concreción: D12, D14, D15.

- **Dado** un animal reservado con su solicitud aprobada,
- **Cuando** la coordinadora formaliza la entrega con una cuota de 120,00 € y confirma el abono,
- **Entonces** la adopción registra 120,00 € abonados y la fecha de entrega es el momento de formalizar,
- **Y** el animal figura como adoptado,
- **Y** si el animal estaba en una casa de acogida, deja de figurar en acogida.

### CA-05-03

Dada una adopción formalizada, cuando se consulta su planificación, entonces existen dos seguimientos vinculados a ella: uno al mes y otro a los seis meses desde la entrega.

Traza: SI-30, p. 1: dos seguimientos posteriores. Concreción: D16.

- **Dada** una adopción con entrega el 10 de marzo de 2027 (Europe/Madrid),
- **Cuando** la coordinadora consulta su planificación,
- **Entonces** hay exactamente dos seguimientos programados: el 10 de abril de 2027 y el 10 de septiembre de 2027,
- **Y** ambos figuran en el listado de seguimientos programados de la coordinadora, ordenados por fecha.

### CA-05-04

Dado un animal adoptado, cuando se consulta la web pública de adopciones, entonces ya no aparece entre los animales disponibles.

Traza: SI-30, p. 1: solo animales realmente en adopción. Integración con HU-02.

- **Dado** un animal cuya adopción se acaba de formalizar,
- **Cuando** una persona anónima consulta el catálogo público, la ficha pública o sus fotografías,
- **Entonces** el animal no aparece en el catálogo y su ficha y sus fotografías responden «no encontrado».

### CA-05-05

Dado un animal adoptado con padrinos, cuando se actualiza su situación, entonces esa adopción queda disponible para el proceso de propuesta de otro animal o finalización de HU-08.

Traza: SI-30, p. 2: opciones del padrino cuando el animal es adoptado. Concreción: D18.

Alcance verificable en HU-05: la adopción queda registrada con su animal, su adoptante y su fecha de
entrega, y puede identificarse a partir del animal. La propuesta al padrino y su respuesta se
verifican en HU-08, que todavía no existe; HU-05 no crea padrinos ni envía avisos.

- **Dado** un animal adoptado,
- **Cuando** se consulta la adopción asociada a ese animal,
- **Entonces** se obtiene una única adopción con su fecha de entrega.

## Criterios derivados de clarify

Se añaden con identificadores nuevos; no sustituyen a los cinco iniciales.

### CA-05-06 · Precondiciones de la formalización

Dada una solicitud que no está aprobada (pendiente, en espera, rechazada, cancelada o ya formalizada),
o un animal que no está reservado, cuando la coordinadora intenta formalizar la entrega, entonces la
operación se rechaza, se explica el motivo y no cambian la solicitud, el animal ni las demás solicitudes,
ni se crean contrato o seguimientos.

Traza: SI-30, p. 1 (la adopción parte de la reserva); D12.

### CA-05-07 · Constancia obligatoria del abono

Dado un animal reservado, cuando la coordinadora intenta formalizar sin confirmar el abono o con un
importe vacío, no numérico, igual o inferior a cero, con más de dos decimales o superior a 9 999,99 €,
entonces no se formaliza, se indica el dato pendiente y el animal sigue reservado.

Traza: SI-30, p. 1 («se abona la cuota»); D14.

### CA-05-08 · Cierre de las solicitudes del animal

Dado un animal reservado con su solicitud aprobada y otras solicitudes en espera, cuando se formaliza
la adopción, entonces la solicitud aprobada pasa a «formalizada», las demás solicitudes abiertas del
animal pasan a canceladas con el motivo «El animal ha sido adoptado», ninguna de ellas cuenta ya para el
máximo de dos solicitudes abiertas y la reserva ya no puede liberarse.

Traza: D07 y D11 (HU-03/04), D15.

- **Dado** que la cuenta adoptante tenía dos solicitudes abiertas, una de ellas la aprobada,
- **Cuando** se formaliza su adopción,
- **Entonces** la cuenta tiene una sola solicitud abierta y puede presentar otra para un animal disponible,
- **Y** cada solicitante cuya solicitud se canceló ve en «Mis solicitudes» el estado cancelada y el motivo.

### CA-05-09 · Seguimientos a fin de mes

Dada una entrega en un día que no existe en el mes de destino, cuando se programan los seguimientos,
entonces cada uno se fija en el último día de ese mes.

Traza: D16.

- **Dada** una entrega el 31 de agosto de 2027, **entonces** los seguimientos son el 30 de septiembre de 2027 y el 29 de febrero de 2028.
- **Dada** una entrega el 31 de enero de 2027, **entonces** el seguimiento al mes es el 28 de febrero de 2027.

### CA-05-10 · Una sola adopción por animal ante concurrencia

Dado un animal reservado, cuando se envían a la vez dos formalizaciones de su solicitud, o una
formalización y una liberación de la reserva, entonces como máximo una operación surte efecto: no
existen dos adopciones del animal ni una adopción con el animal disponible o la solicitud cancelada.

Traza: plan común (bloqueos y coherencia ante concurrencia); D17.

### CA-05-11 · Permisos

Dada una persona sin el rol de coordinadora (anónima, solicitante, voluntario o veterinario), cuando
intenta formalizar una adopción o consultar el listado de seguimientos, entonces se le niega el acceso
y no cambia ningún dato. Dado el contrato de una adopción, cuando lo consulta una cuenta distinta de la
adoptante titular sin el rol de coordinadora, entonces no se le muestra.

Traza: plan común (roles; decisiones de solicitudes a la coordinadora); D13; Constitution, principio V.

## Reglas de negocio

| Regla | Contenido | Fuente |
|---|---|---|
| R1 | Solo la coordinadora formaliza, y únicamente una solicitud aprobada cuyo animal sigue reservado. | D12 |
| R2 | La fecha de entrega es el momento de la formalización; no hay entregas futuras ni retroactivas. | SI-30, D12 |
| R3 | El contrato es un documento imprimible con número único, inmutable una vez generado, visible solo para la coordinadora y el adoptante titular. | D13 |
| R4 | Formalizar exige el importe abonado (0 < importe ≤ 9 999,99 €, dos decimales como máximo) y la confirmación expresa del abono. | D14 |
| R5 | La formalización cambia a la vez solicitud (formalizada), animal (adoptado, fuera de acogida) y demás solicitudes abiertas (canceladas con motivo); si algo falla, no cambia nada. | D15, D17 |
| R6 | La solicitud formalizada no cuenta como abierta; tampoco las canceladas. Una adopción formalizada no admite «Liberar reserva». | D07, D15 |
| R7 | Dos seguimientos por adopción (un mes y seis meses en meses naturales, último día del mes si no existe), en estado programado y a cargo de la coordinadora. | SI-30, D16 |
| R8 | Un animal y una solicitud tienen como máximo una adopción. | D17 |
| R9 | El animal adoptado no es visible públicamente (catálogo, ficha ni fotografías). | SI-30, HU-02 |

## Casos límite

- Formalizar dos veces la misma solicitud (doble envío del formulario): la segunda se rechaza y no duplica nada.
- La coordinadora libera la reserva justo cuando otra sesión formaliza: solo una surte efecto (CA-05-10).
- Importe «120,5», «120,50» o «120.50»: se aceptan como 120,50 €. Importe «0», «-5», «abc», «12,345»: se rechazan.
- Entrega cerca de medianoche: la fecha de los seguimientos se calcula sobre el día de Europe/Madrid, no el de UTC.
- Animal en acogida al formalizar: queda adoptado y fuera de acogida.
- Una persona solicitante consulta el contrato de otra adopción: no se le muestra.
- Animal adoptado: no recibe solicitudes nuevas (ya cubierto por HU-03, R4).

## Dependencias y coordinación

- **HU-04** (`abcb5-ua`): parte de la solicitud aprobada y del animal reservado. HU-05 añade el estado
  final «formalizada» a la solicitud común y debe impedir que «Liberar reserva» deshaga una adopción,
  incluida la liberación simultánea (CA-05-10). La revisión previa detectó que la liberación comprueba
  el estado antes del bloqueo; el cambio que afecte a ese código se coordinará con su responsable.
- **HU-03** (`aggg15`): el máximo de dos solicitudes abiertas deja de contar las formalizadas.
- **HU-02** (`abcb5-ua`): ya excluye los animales adoptados; CA-05-04 lo comprueba de extremo a extremo.
- **HU-01** (`aggg15`): ficha del animal (código, especie, edad, tamaño) usada en el contrato.
- **HU-06**: el indicador de acogida se pone a «no» al adoptar; la gestión de casas de acogida es de HU-06.
- **HU-08**: consumirá la adopción registrada (D18).

## Clarificaciones aplicables

Las tres preguntas de la ficha inicial se resuelven en [clarify](clarify.md) con D12 a D18.

## Límites

Se programa el seguimiento; no se añade un módulo de visitas, una pasarela de pago, firma electrónica ni contabilidad general. Tampoco se generan PDF, se envían correos, se registran devoluciones de animales adoptados ni se gestionan padrinos o casas de acogida.

## Fuente

SI-30, p. 1: entrega, contrato, cuota y seguimientos; p. 2: efecto en el apadrinamiento.

## Base compartida

Ver ../../plan.md (desde la raíz: plan.md), data-model.md y docs/decisiones.md. Los criterios CA originales se conservan. La ficha de la raíz se mantiene como antecedente inicial, sin presentarla como especificación implementada.

## Requisitos de calidad y permisos

Validación en servidor; formularios CSRF; datos ficticios; errores sin cambios parciales. Catálogo sin cuenta, operaciones internas según los roles del plan común. La verificación incluye caso normal, datos inválidos y permisos o estado.
