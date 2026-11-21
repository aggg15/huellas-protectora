# Especificación HU-08 · spec-008

Fecha: 2026-11-05 (especificación inicial); concretada el 2026-11-19 por encargo de `abcb5-ua`.
Responsable GitHub: `abcb5-ua`. Revisión prevista: `aggg15` (pendiente).
Estado: especificada, aclarada ([clarify](clarify.md)) y planificada ([plan](plan.md)) en la rama `spec-008`,
creada desde `main` (5e379e6, con HU-01 a HU-07 integradas). Con [tareas](tasks.md), [analyze](analyze.md),
[implementación](implement.md) y [converge](converge.md). Pendiente la revisión de `aggg15`.
Estado final: integrada en `main` con la PR #12 (2026-11-20); aprobada por `aggg15` en GitHub el 2026-11-20 (actualización 2026-11-21: antes constaba como no registrada).

## Historia de usuario

Como padrino o madrina, quiero apoyar a un animal concreto con una aportación mensual y recibir sus novedades para ayudar a su cuidado y decidir cómo continuar mi colaboración cuando sea adoptado.

## Alcance de la historia

- Apadrinamiento vinculado a un animal concreto y a una aportación mensual mínima de diez euros.
- Recepción de novedades del animal apadrinado.
- Propuesta de otro animal o finalización de la colaboración cuando el animal encuentra familia.

Concreción (D34 a D41 de [decisiones](../../docs/decisiones.md)):

- Una persona con cuenta pública (registro de HU-03) apadrina desde la ficha pública de un animal disponible,
  indicando su aportación mensual (de 10,00 € a 9 999,99 €). El personal y las familias de acogida no apadrinan
  con su cuenta de trabajo.
- La coordinadora publica novedades para los padrinos de cada animal; cada padrino las ve en «Mis apadrinamientos».
- Cuando el animal se adopta (HU-05), el apadrinamiento queda pendiente de la decisión del padrino, con la
  aportación en pausa: elige otro animal disponible (con la misma aportación) o termina su colaboración.

## Criterios de aceptación iniciales

Se conservan los cinco criterios iniciales con su redacción. Las decisiones que los concretan están en
[clarify](clarify.md).

### CA-08-01

Dada una persona que desea apadrinar, cuando establece una aportación mensual de al menos diez euros para un animal, entonces queda registrado el vínculo entre la persona, el animal y la aportación.

Traza: SI-30, p. 2: animal concreto y aportación mensual mínima. Concreción: D34, D35, D36.

- **Dado** un animal disponible (también si está en casa de acogida) y una persona con cuenta pública,
- **Cuando** abre su ficha pública, pulsa «Apadrinar», indica 15,50 € y envía el formulario,
- **Entonces** queda un apadrinamiento activo de esa cuenta para ese animal, con 15,50 € al mes y la fecha de inicio,
- **Y** «Mis apadrinamientos» lo muestra como «Activo» con el importe y la fecha. Una aportación de 10 € exactos
  también se admite.

### CA-08-02

Dada una aportación mensual inferior a diez euros, cuando se intenta establecer el apadrinamiento, entonces no se admite con ese importe.

Traza: SI-30, p. 2: mínimo de diez euros. Concreción: D36.

- **Dado** el formulario de apadrinamiento,
- **Cuando** se envía 9,99 € (o un importe vacío, no numérico, con más de dos decimales, negativo o de 10 000 € o más),
- **Entonces** se responde con el error del importe (422), se conserva lo escrito y no se registra nada.

### CA-08-03

Dado un apadrinamiento y nuevas noticias del animal, cuando esas novedades están disponibles para compartir, entonces el padrino las recibe por el mecanismo que se acuerde en clarify.

Traza: SI-30, p. 2: recepción de novedades. Concreción: D37.

- **Dado** un animal con un apadrinamiento activo,
- **Cuando** la coordinadora publica una novedad en «Apadrinamientos»,
- **Entonces** el padrino la ve en «Mis apadrinamientos», con su fecha, de la más reciente a la más antigua,
- **Y** no la ven otras cuentas, ni el padrino ve las novedades escritas antes de empezar su apadrinamiento o después
  de terminarlo. Las notas de acogida de HU-06 no se muestran a los padrinos.

### CA-08-04

Dado un animal apadrinado que ha sido adoptado, cuando se trata la continuidad del apadrinamiento, entonces se ofrece al padrino apoyar a otro animal o terminar su colaboración.

Traza: SI-30, p. 2: opciones tras la adopción. Concreción: D38, D39.

- **Dado** un apadrinamiento activo cuyo animal se adopta con HU-05,
- **Cuando** el padrino consulta «Mis apadrinamientos»,
- **Entonces** el apadrinamiento figura «Pendiente de tu decisión», con la fecha de la adopción y el aviso de que la
  aportación está en pausa desde entonces,
- **Y** se le ofrecen la lista de animales disponibles que todavía no apadrina, para seguir con la misma aportación, y
  el botón «Terminar mi colaboración». La coordinadora ve ese apadrinamiento «En pausa».

### CA-08-05

Dada esa propuesta de continuidad, cuando el padrino elige otro animal o finalizar, entonces queda reflejada su elección sin asignarle otro animal de forma unilateral.

Traza: SI-30, p. 2: elección del padrino. Concreción: D39.

- **Dado** un apadrinamiento pendiente de decisión,
- **Cuando** el padrino elige otro animal, **entonces** el apadrinamiento anterior termina con el motivo «Cambio a otro
  animal» y se crea uno nuevo para el animal elegido con la misma aportación, enlazado con el anterior.
- **Cuando** elige terminar, **entonces** el apadrinamiento termina con el motivo «Fin de la colaboración».
- **Y** mientras no decide, la adopción no termina el apadrinamiento ni crea otro.

## Criterios derivados de clarify

Se añaden con identificadores nuevos; no sustituyen a los cinco iniciales.

### CA-08-06 · Varios apadrinamientos y fin voluntario

Dado un animal apadrinado por una persona, cuando otra lo apadrina, entonces ambos apadrinamientos quedan activos;
una persona puede apadrinar varios animales. Dado un apadrinamiento activo, cuando la misma cuenta intenta apadrinar
otra vez el mismo animal, entonces se rechaza (409) y sigue habiendo uno. Dado un apadrinamiento activo, cuando el
padrino pulsa «Terminar mi colaboración» en cualquier momento, entonces termina con «Fin de la colaboración» y
puede volver a apadrinar después.

Traza: ficha inicial, pregunta 3; D35, D39.

### CA-08-07 · Solo animales disponibles

Dado un animal en cuarentena, reservado o adoptado, cuando se abre su formulario de apadrinamiento, entonces no se
encuentra (404), como su ficha pública. Dado un formulario abierto cuando el animal estaba disponible, cuando se envía
después de reservarse, entonces se rechaza (409) sin registrar nada. Un animal con la cuarentena vencida se
promociona antes de comprobarlo (D01).

Traza: ficha inicial, pregunta 3; D35.

### CA-08-08 · Elecciones de continuidad no válidas

Dado un apadrinamiento pendiente de decisión, cuando se envía el cambio sin elegir animal o con uno inexistente,
entonces se responde 422; cuando el animal elegido no está disponible, es el mismo adoptado o ya lo apadrina,
entonces se rechaza (409). Dado un apadrinamiento cuyo animal no se ha adoptado, cuando se intenta cambiar de animal,
entonces se rechaza (409). Terminar un apadrinamiento ya terminado se rechaza (409). En ningún caso cambia nada.

Traza: D39; Constitution V (validación en el servidor y sin escrituras parciales).

### CA-08-09 · Coherencia ante concurrencia

Dadas dos peticiones simultáneas de la misma cuenta para apadrinar el mismo animal, entonces se confirma como máximo
una. Dadas varias decisiones simultáneas sobre el mismo apadrinamiento pendiente (cambiar a dos animales distintos y
terminar), entonces solo se aplica una.

Traza: plan común (bloqueos y relectura); D41.

### CA-08-10 · Permisos

Dada una persona anónima, cuando intenta apadrinar o abrir «Mis apadrinamientos», entonces se le dirige a la página de
acceso. Dada una cuenta de personal (voluntario, veterinario o coordinadora) o de familia de acogida, cuando intenta
apadrinar o abrir «Mis apadrinamientos», entonces recibe un acceso denegado y la ficha pública no le ofrece apadrinar.
Una cuenta no puede terminar ni cambiar el apadrinamiento de otra (404). Solo la coordinadora consulta los
apadrinamientos y publica novedades (403 para el resto). Un formulario sin token CSRF válido no cambia nada.

Traza: plan común (roles, POST y CSRF); Constitution V; D34, D40.

## Reglas de negocio

| Regla | Contenido | Fuente |
|---|---|---|
| R1 | Apadrinan las cuentas públicas (solo `ROLE_USER`); no las de personal ni las de familias de acogida. | D34 |
| R2 | Solo se apadrinan animales disponibles (los que muestra la web pública, también en acogida). | D35 |
| R3 | Varios padrinos por animal y varios animales por padrino; un único apadrinamiento activo por cuenta y animal. | D35 |
| R4 | Aportación mensual de 10,00 € a 9 999,99 €, con dos decimales como máximo; se registra el compromiso, sin cobros. El importe no se modifica. | D36 |
| R5 | La coordinadora publica novedades (1 a 2000 caracteres) de animales con apadrinamientos activos; no se editan ni se borran. | D37 |
| R6 | Cada padrino ve las novedades del animal escritas entre el inicio y el fin de su apadrinamiento, también después de terminarlo. | D37 |
| R7 | Un apadrinamiento activo de un animal adoptado está pendiente de decisión, con la aportación en pausa desde la fecha de entrega. | D38 |
| R8 | El cambio a otro animal solo se admite pendiente de decisión, hacia un animal disponible que la cuenta no apadrina: termina el anterior («Cambio a otro animal») y crea el nuevo con la misma aportación, en una transacción. | D39 |
| R9 | El padrino puede terminar un apadrinamiento activo en cualquier momento («Fin de la colaboración»). Nada se asigna ni termina automáticamente. | D39 |
| R10 | Las operaciones bloquean cuenta → apadrinamiento → animal y deciden con lo leído bajo bloqueo; publicar una novedad bloquea el animal. | D41 |

## Casos límite

- Aportación de 10 € exactos o «10,00»: válida. 9,99 €: no válida. Coma o punto decimal: ambos válidos.
- Animal en acogida y disponible: se puede apadrinar (la ficha pública lo muestra).
- El animal pasa a reservado después de apadrinarlo: el apadrinamiento sigue activo (solo la adopción lo deja pendiente).
- Doble envío del formulario de apadrinamiento: el segundo se rechaza (ya lo apadrina).
- Sin animales disponibles para cambiar: la página lo indica y ofrece solo terminar.
- Novedad publicada en el mismo segundo en que empieza o termina un apadrinamiento: la ve ese padrino.
- Texto de la novedad con etiquetas HTML: se muestra escapado.
- Un animal adoptado con padrinos pendientes sigue recibiendo novedades mientras alguno no haya decidido.

## Dependencias y coordinación

- **HU-01/HU-02**: ficha del animal y disponibilidad pública; el formulario de apadrinamiento sigue el criterio de la
  ficha pública (`AnimalService::getPublicAnimal`).
- **HU-03**: cuentas públicas creadas en `/registro`.
- **HU-05**: la adopción registrada (`AdoptionService::findByAnimal`, D18) da la fecha de la pausa. HU-08 no modifica
  la formalización.
- **HU-06**: las novedades de acogida siguen siendo internas (D24); la coordinadora puede basarse en ellas al redactar
  las novedades para padrinos.
- **Archivos comunes**: enlaces de la cabecera (`base.html.twig`), recuadro en la ficha pública (`public/show.html.twig`)
  y estilos (`app.css`).

## Clarificaciones aplicables

Las tres preguntas de la ficha inicial se resuelven en [clarify](clarify.md) con D34 a D41.

## Límites

No se prescribe un servicio externo de cobro o mensajería. La contabilidad general y las campañas de recogida de pienso quedan fuera del encargo.
Tampoco se añaden: cobros, recibos o registro de pagos; modificación del importe (se termina y se apadrina de
nuevo); correos o avisos fuera de la aplicación; publicación de las novedades en la web pública; edición o borrado de
novedades; apadrinamientos registrados por la coordinadora en nombre de otra persona.

## Fuente

SI-30, p. 2: padrinos, aportación, novedades y continuidad tras la adopción.

## Base compartida

Ver ../../plan.md (desde la raíz: plan.md), data-model.md y docs/decisiones.md. Los criterios CA originales se conservan. La ficha de la raíz se mantiene como antecedente inicial, sin presentarla como especificación implementada.

## Requisitos de calidad y permisos

Validación en servidor; formularios CSRF; datos ficticios; errores sin cambios parciales. Operaciones internas según los roles del plan común. La verificación incluye caso normal, datos inválidos y permisos o estado.
