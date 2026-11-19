# Clarify · spec-008

Fecha: 2026-11-19. Historia: HU-08 · Apadrinar un animal y recibir sus novedades.
Responsable: `abcb5-ua`. Revisión de `aggg15`: pendiente.

Aplicado por Claude Code a petición de `abcb5-ua` sobre las fuentes de la Práctica Grupal. No se ha ejecutado la
skill `$speckit-clarify`. Las preguntas proceden de la ficha inicial y de la revisión del código integrado de
HU-01 a HU-07. Cuatro decisiones (quién apadrina, canal de las novedades, animales y cardinalidad, y qué ocurre tras
la adopción) se plantearon como opciones y `abcb5-ua` eligió la recomendada en cada una el 2026-11-19. El resto son
concreciones que se derivan de ellas y del plan común. Se registran como D34 a D41 en
[decisiones](../../docs/decisiones.md). Se distingue lo que dice SI-30 de lo que decide el equipo.

## Registro de preguntas

| # | Pregunta | Respuesta | Justificación | Criterios |
|---|---|---|---|---|
| Q1 | ¿Quién apadrina y cómo se registra? | Una persona con cuenta pública (registro de HU-03, solo `ROLE_USER`) desde la ficha pública de un animal. El personal y las familias de acogida no apadrinan con su cuenta de trabajo. (D34, elegido por `abcb5-ua`) | La historia está escrita desde el padrino. La cuenta identifica a quién corresponden las novedades y la decisión tras la adopción; no acredita identidad legal (D06). | CA-08-01, CA-08-10 |
| Q2 | ¿Qué animales se pueden apadrinar? ¿Más de un padrino por animal o más de un animal por padrino? (pregunta 3 de la ficha) | Solo los disponibles (los de la web pública, también en acogida). Varios padrinos por animal y varios animales por padrino, con un único apadrinamiento activo por cuenta y animal. (D35, elegido por `abcb5-ua`) | Un animal en cuarentena no es público (D01) y uno reservado o adoptado ya tiene familia. Limitar a un padrino por animal reduciría la ayuda sin motivo en la narrativa. | CA-08-06, CA-08-07 |
| Q3 | ¿Cómo se gestiona la aportación y qué constancia se guarda? (pregunta 2 de la ficha) | Se registra el compromiso: importe mensual (10,00 € a 9 999,99 €, dos decimales) y fecha de inicio. Sin cobros, recibos ni pasarela. El importe no se modifica: para cambiarlo, se termina y se apadrina de nuevo. (D36) | SI-30 fija el mínimo de diez euros; la contabilidad queda fuera y la ficha excluye servicios externos de cobro. El máximo, como en la cuota de HU-05 (D14), solo evita errores de tecleo. | CA-08-01, CA-08-02 |
| Q4 | ¿Por qué canal reciben los padrinos las novedades y quién las prepara o valida? (pregunta 1 de la ficha) | La coordinadora redacta novedades para los padrinos de cada animal (1 a 2000 caracteres, inmutables) y cada padrino las ve en «Mis apadrinamientos». Ve las escritas mientras su apadrinamiento estaba activo, también después de terminarlo. Las notas de acogida siguen siendo internas. (D37, elegido por `abcb5-ua`) | D24 dejó las notas de acogida como internas: compartirlas directamente exigiría cambiar HU-06 y publicar textos escritos para el personal. La coordinadora valida lo que se comparte. Sin correo (no hay servicio de mensajería). | CA-08-03 |
| Q5 | ¿Qué ocurre con el apadrinamiento y la aportación cuando el animal se adopta? (pregunta 3 de la ficha) | El apadrinamiento sigue activo pero queda «pendiente de decisión»; la aportación está en pausa desde la fecha de entrega (D18). Se calcula al consultar, sin modificar HU-05. (D38, elegido por `abcb5-ua`) | La narrativa pide ofrecer la elección al padrino, no terminar por él. Calcularlo a partir del estado del animal evita tareas programadas y no toca la formalización. | CA-08-04 |
| Q6 | ¿Cómo se refleja la elección del padrino? | Otro animal disponible que no apadrina: el anterior termina con «Cambio a otro animal» y se crea uno nuevo con la misma aportación, enlazado, en una transacción. O terminar: «Fin de la colaboración». El padrino también puede terminar en cualquier momento. Nada es automático. (D39) | CA-08-05 exige reflejar la elección sin asignaciones unilaterales; el enlace conserva el historial del padrino. Impedir el fin voluntario no tendría justificación. | CA-08-05, CA-08-06, CA-08-08 |
| Q7 | ¿Quién ve los apadrinamientos? | Cada padrino, los suyos. La coordinadora, los activos por animal (padrino, importe, desde y si está en pausa) en `/admin/apadrinamientos`. Nadie más. (D40) | La coordinadora necesita saber qué animales tienen padrinos para preparar sus novedades; los datos de los padrinos no se publican. | CA-08-04, CA-08-10 |
| Q8 | ¿Cómo se evitan duplicados y decisiones contradictorias con peticiones simultáneas? | Apadrinar bloquea la cuenta y después el animal; cambiar y terminar bloquean la cuenta, el apadrinamiento y, al cambiar, el animal elegido; publicar una novedad bloquea el animal. Todo se vuelve a comprobar bajo bloqueo. Índice único parcial (animal, cuenta) para los activos. (D41) | Plan común: releer después del bloqueo; mismo orden que HU-03 y HU-07 (persona y después el recurso). La formalización de HU-05 bloquea el animal y no la cuenta, así que no hay ciclos. | CA-08-09 |

## Diferencias con la especificación inicial

- Se conservan CA-08-01 a CA-08-05 con su redacción y se añaden escenarios «Dado… Cuando… Entonces…».
- Se añaden CA-08-06 a CA-08-10 (cardinalidad y fin voluntario, animales disponibles, elecciones no válidas,
  concurrencia y permisos), reglas R1 a R10 y casos límite.
- La historia, el alcance inicial y los límites se mantienen; los límites se amplían con exclusiones explícitas
  (cobros y recibos, cambio de importe, correos, publicación de novedades, edición de novedades, altas por terceros).

## Pendiente para plan

El modelo (apadrinamiento y novedad), las restricciones, el orden de bloqueos, las rutas y las vistas se deciden en
`plan.md`. HU-08 no modifica el código de otras historias salvo la cabecera, la ficha pública y los estilos comunes.
