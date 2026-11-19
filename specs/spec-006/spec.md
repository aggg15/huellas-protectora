# Especificación HU-06 · spec-006

Fecha: 2026-11-05 (especificación inicial); concretada el 2026-11-19 por encargo de `abcb5-ua`.
Responsable GitHub: `abcb5-ua`. Revisión prevista: `aggg15` (pendiente).
Estado: especificada, aclarada ([clarify](clarify.md)) y planificada ([plan](plan.md)) en la rama `spec-006`,
creada desde `main` (87a78a0, con HU-01 a HU-05 integradas). Con [tareas](tasks.md), [analyze](analyze.md),
[implementación](implement.md) (T605–T623) y verificación manual; [converge](converge.md) con T626–T627 completadas
y convergido. Pendiente la revisión de `aggg15`.

## Historia de usuario

Como familia de acogida, quiero indicar mi disponibilidad y capacidad, y registrar las novedades de los animales que acojo para prestarles cuidados temporales mientras siguen buscando una familia definitiva.

## Alcance de la historia

- Información de cada casa de acogida: disponibilidad y número máximo de animales que puede atender.
- Vinculación del animal con su acogida temporal, respetando disponibilidad y capacidad.
- Anotaciones de la familia sobre el carácter y la evolución; mantenimiento de la oferta de adopción cuando corresponda.

Concreción (D19 a D25 de [decisiones](../../docs/decisiones.md)):

- La familia de acogida es una cuenta con el rol propio de familia de acogida, creada por la protectora.
  Registra su capacidad y sus periodos de disponibilidad y anota novedades de los animales que acoge.
- La coordinadora registra cada acogida (casa y fecha de fin prevista) y la finaliza.
- El personal consulta en la ficha interna del animal su acogida actual y el historial de novedades.

## Criterios de aceptación iniciales

Se conservan los cinco criterios iniciales con su redacción. Las decisiones que los concretan están en
[clarify](clarify.md).

### CA-06-01

Dada una casa de acogida, cuando la familia informa de sus condiciones, entonces quedan recogidos sus periodos de disponibilidad y el número máximo de animales que puede atender.

Traza: SI-30, p. 1: disponibilidad y capacidad de cada casa. Concreción: D19, D20.

- **Dada** una cuenta de familia de acogida sin condiciones registradas,
- **Cuando** indica una capacidad de 2 animales y añade los periodos del 1 al 15 de diciembre y del 10 al 31 de enero,
- **Entonces** su casa queda registrada con capacidad 2 y esos dos periodos, ordenados por fecha,
- **Y** la coordinadora la ve en el listado de casas con su capacidad, su ocupación (0 de 2) y sus periodos.

### CA-06-02

Dada una casa disponible y con capacidad, cuando se registra una acogida, entonces el animal queda vinculado a esa casa durante la acogida.

Traza: SI-30, p. 1: estancia temporal en familias. Concreción: D21.

- **Dada** una casa con capacidad 2, sin animales y disponible desde hoy hasta dentro de 30 días,
- **Cuando** la coordinadora registra la acogida de un animal en esa casa con fin previsto dentro de 20 días,
- **Entonces** existe una acogida activa del animal en esa casa que empieza hoy (día de Europe/Madrid) y con ese fin previsto,
- **Y** el animal figura «en acogida» en su ficha interna, con la familia y las fechas,
- **Y** la familia ve el animal entre los que acoge.

### CA-06-03

Dada una casa no disponible para el periodo solicitado o cuya capacidad se superaría, cuando se intenta registrar una acogida, entonces no se confirma esa asignación.

Traza: SI-30, p. 1: disponibilidad y máximo; restricción derivada. Concreción: D20, D21.

- **Dada** una casa disponible hasta dentro de 10 días, **cuando** se registra una acogida con fin
  previsto dentro de 15 días, **entonces** se rechaza indicando que la casa no está disponible para todo el periodo.
- **Dada** una casa cuya disponibilidad empieza mañana, **cuando** se registra hoy una acogida, **entonces** se rechaza.
- **Dada** una casa con capacidad 1 que ya acoge un animal, **cuando** se registra otra acogida,
  **entonces** se rechaza indicando que la casa ya atiende su máximo.
- **Y** en todos los casos no se crea la acogida y el animal no cambia.
- **Dados** dos periodos consecutivos (uno termina el día anterior al inicio del otro), **cuando** el
  periodo solicitado los abarca, **entonces** la casa se considera disponible.

### CA-06-04

Dado un animal en acogida disponible para adopción, cuando se consulta la web pública, entonces sigue visible con la indicación de que está en acogida. La acogida no elimina la cuarentena ni cambia por sí sola una reserva o adopción.

Traza: SI-30, p. 1: acogida y adopción; coherencia con cuarentena y reserva. Concreción: D25.

- **Dado** un animal disponible al que se registra una acogida, **cuando** una persona anónima consulta
  el catálogo y la ficha pública, **entonces** el animal aparece con la indicación «En casa de acogida».
- **Dado** un animal en cuarentena, **cuando** se registra su acogida, **entonces** sigue en cuarentena y
  no aparece en la web pública hasta que termine la cuarentena.
- **Dado** un animal reservado en acogida, **cuando** se registra o finaliza la acogida, o se libera la
  reserva, **entonces** la reserva y la acogida no se modifican entre sí.
- **Dado** un animal en acogida, **cuando** se formaliza su adopción (HU-05), **entonces** la acogida
  se finaliza ese día con el motivo «adopción» y el animal deja de figurar en acogida.

### CA-06-05

Dada una familia que cuida a un animal en acogida, cuando anota novedades sobre su carácter o evolución, entonces esas novedades quedan asociadas al animal.

Traza: SI-30, p. 1: novedades anotadas por la familia. Concreción: D24.

- **Dada** una familia con una acogida activa,
- **Cuando** anota «Duerme toda la noche y ya pasea sin miedo» sobre ese animal,
- **Entonces** la novedad queda guardada con su fecha y su autora, asociada al animal y a esa acogida,
- **Y** aparece en el historial de novedades de la ficha interna del animal y en la página de la familia,
- **Y** no aparece en la web pública.

## Criterios derivados de clarify

Se añaden con identificadores nuevos; no sustituyen a los cinco iniciales.

### CA-06-06 · Datos válidos

Dada una familia o una coordinadora que envía datos no válidos (capacidad vacía, no entera, menor que 1
o mayor que 10; periodo con fechas inexistentes, fin anterior al inicio, inicio anterior a hoy, fin
posterior a dos años desde hoy o que se solapa con otro periodo de la casa; acogida sin casa o con fin
previsto anterior a hoy o inexistente; novedad vacía o de más de 2000 caracteres), cuando envía el
formulario, entonces no se guarda nada, se indica el dato que falta corregir y se conservan los valores escritos.

Traza: Constitution V (validación en el servidor); D20, D21, D24.

### CA-06-07 · Cambios en las condiciones con animales acogidos

Dada una casa con acogidas activas, cuando la familia reduce la capacidad por debajo del número de animales
que atiende o retira un periodo necesario para cubrir el fin previsto de una acogida activa, entonces el
cambio se rechaza explicando el motivo. Los cambios que respetan las acogidas activas se aplican a las
acogidas que se registren después y no finalizan las existentes.

Traza: ficha inicial, pregunta 2; D23.

### CA-06-08 · Finalización de la acogida

Dada una acogida activa, cuando la coordinadora la finaliza, entonces consta la fecha de fin (hoy) y el
motivo «regreso al refugio», el animal deja de figurar en acogida, la plaza queda libre en la casa, la
familia deja de ver el animal y ya no puede anotar novedades sobre él, y las novedades anteriores siguen
en el historial del animal. Una acogida ya finalizada no se puede finalizar otra vez. Una acogida cuyo fin
previsto ya ha pasado sigue activa hasta que se finaliza y se señala como «fin previsto superado».

Traza: ficha inicial, preguntas 1 y 2; D22.

### CA-06-09 · Coherencia ante concurrencia

Dada una casa con una sola plaza libre, cuando se registran a la vez dos acogidas en ella, entonces como
máximo se confirma una. Dado un animal, cuando se registran a la vez dos acogidas suyas en casas distintas,
entonces como máximo se confirma una. Dado un animal en acogida, cuando se formaliza su adopción a la vez
que se registra o finaliza su acogida, entonces no queda un animal adoptado con una acogida activa.

Traza: plan común (bloqueos y coherencia ante concurrencia); D21, D25.

### CA-06-10 · Permisos

Dada una persona anónima, cuando intenta acceder a la página de la familia o a la gestión de acogidas,
entonces se le dirige a la página de acceso. Dada una cuenta sin el rol necesario, cuando intenta registrar
condiciones o novedades (sin rol de familia de acogida) o registrar, finalizar o listar acogidas (sin rol de
coordinadora), entonces recibe un acceso denegado. Una familia no puede modificar periodos ni anotar novedades
en acogidas de otra familia (respuesta «no encontrado»). Un formulario sin token CSRF válido no cambia nada.
En ningún caso cambia ningún dato.

Traza: plan común (roles, POST y CSRF); Constitution V; D19.

## Reglas de negocio

| Regla | Contenido | Fuente |
|---|---|---|
| R1 | Una cuenta de familia de acogida corresponde a una casa. La protectora crea estas cuentas; el registro público solo crea solicitantes. | D19 |
| R2 | La capacidad es un entero entre 1 y 10. Los periodos de disponibilidad son días completos de Europe/Madrid (inicio y fin incluidos), no se solapan, empiezan hoy o después y terminan como máximo dos años después de hoy. | D20 |
| R3 | Solo la coordinadora registra y finaliza acogidas. La acogida empieza el día en que se registra y tiene un fin previsto (hoy o posterior). | D21, D22 |
| R4 | La casa debe estar disponible todos los días desde hoy hasta el fin previsto; los periodos consecutivos se unen. | D20, D21 |
| R5 | Las acogidas activas de una casa no superan su capacidad. Ocupación = acogidas activas. | SI-30, D21 |
| R6 | Un animal tiene como máximo una acogida activa, y un animal adoptado no puede iniciar una acogida. | D21, D25 |
| R7 | La acogida no cambia el estado del animal (cuarentena, disponible, reservado). El indicador «en acogida» refleja la existencia de una acogida activa. | SI-30, D25 |
| R8 | La acogida sigue activa hasta que la coordinadora la finaliza o se formaliza la adopción; un fin previsto superado no la cierra. | D22 |
| R9 | La capacidad no puede quedar por debajo de las acogidas activas, ni retirarse disponibilidad necesaria para una acogida activa. | D23 |
| R10 | Las novedades las escribe la familia de una acogida activa; no se editan ni se borran y permanecen en el historial del animal. Son internas: el personal ve el historial completo y la familia, las de sus acogidas activas. | D24 |
| R11 | Formalizar la adopción finaliza la acogida activa con motivo «adopción», en la misma operación. | D15, D25 |

## Casos límite

- Fin previsto igual a hoy: se admite si la casa está disponible hoy.
- Periodos consecutivos (15-dic y 16-dic): cubren el intervalo sin hueco. Periodos con un día de hueco: no.
- Acogida registrada a las 00:30 de Madrid (23:30 UTC del día anterior): empieza el día de Madrid.
- Doble envío del formulario de acogida: el segundo se rechaza porque el animal ya está en acogida.
- La familia intenta bajar la capacidad a 1 mientras acoge dos animales: se rechaza.
- La familia retira un periodo que ya no cubre ninguna acogida activa: se admite.
- Acogida con fin previsto superado: sigue activa, ocupa plaza y se señala a la coordinadora y a la familia.
- Animal reservado: puede entrar o salir de acogida; liberar la reserva no finaliza la acogida.
- Animal adoptado: no admite acogida (409).
- Novedad con etiquetas HTML: se muestra como texto escapado.

## Dependencias y coordinación

- **HU-01** (`aggg15`): la ficha interna del animal (`AnimalController::show`, `animal/show.html.twig`)
  muestra la acogida y su historial; la gestión de cuentas (`app:create-user`) admite el nuevo rol.
- **HU-02** (`abcb5-ua`): ya muestra «En casa de acogida» con el indicador `fostered`; HU-06 lo mantiene.
- **HU-04** (`abcb5-ua`): la liberación de una reserva no afecta a la acogida.
- **HU-05** (`aggg15`): la formalización ya pone `fostered = false`; HU-06 añade, en la misma transacción,
  el cierre de la acogida activa con motivo «adopción» (cambio en `AdoptionService::formalize`).
- **HU-08**: decidirá qué novedades se comparten con los padrinos; HU-06 las deja asociadas al animal.

## Clarificaciones aplicables

Las tres preguntas de la ficha inicial se resuelven en [clarify](clarify.md) con D19 a D25.

## Límites

La acogida es temporal y no convierte al animal en adoptado. No se añaden compensaciones económicas a las familias.
Tampoco se añaden: alta pública de familias de acogida, acogidas que empiecen en el futuro, avisos o correos,
fotografías de las novedades, publicación de novedades en la web ni su envío a padrinos (HU-08).

## Fuente

SI-30, p. 1: casas de acogida, disponibilidad, capacidad, visibilidad y novedades.

## Base compartida

Ver ../../plan.md (desde la raíz: plan.md), data-model.md y docs/decisiones.md. Los criterios CA originales se conservan. La ficha de la raíz se mantiene como antecedente inicial, sin presentarla como especificación implementada.

## Requisitos de calidad y permisos

Validación en servidor; formularios CSRF; datos ficticios; errores sin cambios parciales. Catálogo sin cuenta, operaciones internas según los roles del plan común. La verificación incluye caso normal, datos inválidos y permisos o estado.
