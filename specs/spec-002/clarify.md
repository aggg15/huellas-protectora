# Clarify · spec-002

Fecha: 2026-11-16.
Historia: HU-02 · Consultar y filtrar el catálogo público de adopción.
Estado: Clarificaciones acordadas y documentadas en `docs/decisiones.md`.

## 1. Categorías de filtros y combinación (D03, D05)

* **Especie (`species`):**
  * `dog`: Perro
  * `cat`: Gato
  * Valor vacío o no seleccionado: Todos los animales.
* **Tamaño (`size`):**
  * `small`: Pequeño
  * `medium`: Mediano
  * `large`: Grande
  * Valor vacío o no seleccionado: Todos los tamaños.
* **Rango de edad (`age`):**
  * `<12`: Cachorro / Joven (menos de 12 meses, `ageMonths < 12`).
  * `12-83`: Adulto (de 1 a casi 7 años, `ageMonths BETWEEN 12 AND 83`).
  * `>=84`: Senior (7 o más años, `ageMonths >= 84`).
  * Valor vacío o no seleccionado: Todas las edades.
* **Regla de combinación:**
  * Los filtros seleccionados se combinan conjuntivamente mediante **AND**.
  * Si un filtro no se envía o está vacío, no restringe los resultados.
  * Si ningún animal cumple los filtros aplicados, se muestra un mensaje informativo claro en español indicando que no se encontraron animales con ese criterio, junto a un botón para restablecer los filtros.

## 2. Visibilidad y exclusión estricta de estados (CA-02-01, CA-02-03)

* **Animales visibles en catálogo:**
  * Exclusivamente aquellos cuyo estado sea exactamente `available` (`status = 'available'`).
* **Exclusión de animales no disponibles:**
  * Los animales en estado `quarantine`, `reserved` o `adopted` **no** aparecen en el catálogo público ni en los resultados filtrados.
  * Si una persona sin rol interno (`ROLE_STAFF`) intenta acceder directamente a la URL de la ficha pública de un animal en cuarentena, reservado o adoptado (`/animales/{id}`), el sistema responde con **HTTP 404 No Encontrado**.
* **Fotografías protegidas:**
  * Las fotografías servidas a través de `/fotos/{id}` siguen protegidas por `MediaController`: una foto de un animal que no esté en estado `available` devuelve HTTP 404 para usuarios anónimos o sin rol de personal.
* **Desclasificación previa automática (D01):**
  * Toda consulta al catálogo público (`/animales`) o a una ficha (`/animales/{id}`) invoca previamente el método de desclasificación automática (`$animals->releaseDueAnimals()`) para asegurar que cualquier animal cuya cuarentena de 240 horas haya vencido se promocione a `available` antes de listar o resolver la petición.

## 3. Animales en casa de acogida (CA-02-04)

* **Indicador en catálogo y ficha:**
  * Los animales con `fostered = true` que sigan en estado `available` se listan normalmente en el catálogo.
  * Se incluye un distintivo visual claro con Bulma (`tag is-warning` o `badge`) indicando **"En casa de acogida"**.
  * En la ficha pública individual del animal (`/animales/{id}`), se muestra igualmente la indicación de que el animal se encuentra temporalmente en una familia de acogida mientras busca hogar definitivo.

## 4. Acceso público y rutas

* **Rutas públicas:**
  * `GET /animales` (`name: 'public_catalog'`): Catálogo público con barra de filtros, rejilla de tarjetas con fotos y estado vacío.
  * `GET /animales/{id}` (`name: 'public_animal_show'`): Ficha pública detallada del animal con galería de fotos, edad formateada (años y meses), especie, tamaño, procedencia e indicación de acogida.
* **Navegación:**
  * El enlace principal "Buscar un compañero" en el encabezado de [`templates/base.html.twig`](../../templates/base.html.twig) apunta directamente a la ruta `public_catalog` (`/animales`).
* **Anonimato y seguridad:**
  * No se requiere inicio de sesión para consultar el catálogo ni la ficha pública.
  * Los accesos anónimos nunca revelan notas veterinarias, revisiones ni datos privados de la gestión interna.

## Ronda de clarificación previa a la entrega (2026-11-21)

Historia: HU-02 · Consultar y filtrar el catálogo público de adopción. Responsable: `abcb5-ua`. Revisión de
`aggg15` de esta ronda: pendiente. Se parte de `specs/spec-002/spec.md`, de la ficha inicial `spec-002/historia.md`,
de SI-30 (p. 1), de [decisiones](../../docs/decisiones.md) y de las ocho pruebas de `tests/Spec002Test.php` en el
commit a74f85f. Las secciones 1 a 4 de arriba concretan filtros, exclusiones, acogida y rutas, pero sin
la tabla de preguntas, respuestas y criterios ni las diferencias con la ficha inicial: se añaden aquí.

### 1. Tres situaciones de prueba

**Situación 1 · Recorrido habitual**

- CA-02-01. **Dado** que hay un perro y un gato en estado `available`, uno de ellos con una fotografía,
  **cuando** una persona anónima hace `GET /animales`, **entonces** la respuesta es 200 sin pedir acceso y la página
  muestra el código de ambos y la fotografía principal del que la tiene.
  Prueba: `Spec002Test::testAnonymousCanViewPublicCatalogWithAvailableAnimals`.
- CA-02-02. **Dado** un perro cachorro pequeño, un perro adulto mediano y un perro senior pequeño, **cuando** se
  filtra `species=dog&size=small&age=<12`, **entonces** solo aparece el cachorro; con `age=>=84` solo el senior y
  con `size=medium&age=12-83` solo el adulto.
  Pruebas: `Spec002Test::testFilterBySpecies` y `Spec002Test::testFilterBySizeAndAgeCombined`.
- CA-02-04. **Dado** un animal disponible con `fostered = true`, **cuando** se consulta el catálogo y su ficha,
  **entonces** ambos muestran «En casa de acogida» y la ficha explica que es un cuidado temporal.
  Prueba: `Spec002Test::testFosteredAnimalShowsFosterBadgeInCatalogAndDetail`.

**Situación 2 · Entrada inválida o caso límite**

- CA-02-02 (sin coincidencias). **Dado** un catálogo sin gatos grandes, **cuando** se filtra
  `species=cat&size=large`, **entonces** la respuesta es 200 con el mensaje «No se encontraron animales» y un
  enlace para restablecer los filtros.
  Prueba: `Spec002Test::testFilterWithoutMatchesShowsEmptyState`.
- CA-02-03 (identificador inexistente). **Dado** un identificador de animal que no existe, **cuando** se pide
  `GET /animales/{id}`, **entonces** la respuesta es 404.
  Prueba: `Spec002Test::testNonAvailableAnimalDetailReturns404` (última petición de la prueba).
- Caso límite no cubierto: los valores de filtro que no figuran en la spec (por ejemplo `species=bird`) y las
  fronteras exactas de edad (11/12 y 83/84 meses) no tienen prueba; el primero se registra como Q5 de la sección 2
  y ambos en [analyze](analyze.md), A3 y A4.

**Situación 3 · Permisos o estado**

- CA-02-03. **Dado** un animal en cuarentena, uno reservado y uno adoptado, **cuando** se consulta el catálogo,
  **entonces** ninguno aparece; **cuando** se pide la ficha de cada uno, **entonces** la respuesta es 404.
  Pruebas: `Spec002Test::testQuarantineReservedAndAdoptedAnimalsExcludedFromCatalog` y
  `Spec002Test::testNonAvailableAnimalDetailReturns404`.
- CA-02-01 y D01 (estado temporal). **Dado** un animal en cuarentena con 11 días desde su recepción, **cuando**
  alguien consulta el catálogo, **entonces** se promociona a disponible antes de listar y aparece.
  Prueba: `Spec002Test::testExpiredQuarantinePromotesAutomaticallyOnCatalogAccess`.
- Permisos: no aplica un rol, porque la historia es pública y consultar no exige cuenta (SI-30: «sin registrarse»).
  La privacidad de las fotografías de animales no disponibles se comprueba fuera de esta clase, en
  `Spec001Test::testAnonymousCannotSeeQuarantineOrPhotos` y `Spec005Test::testAdoptedAnimalDisappearsFromPublicCatalogDetailAndPhotos`.

### 2. Ambigüedades detectadas

Las filas «PENDIENTE» llevan una recomendación como propuesta, no como decisión: la toma y la explica el
responsable.

| # | Pregunta | Opciones | Recomendación | Justificación | CA afectado | Estado |
|---|---|---|---|---|---|---|
| Q1 | ¿Qué categorías de tamaño y qué rangos de edad hay, y cómo se combinan los filtros? | Categorías libres o fijas; unión (OR) o conjunción (AND). | Perro o gato; pequeño, mediano o grande; menos de 12 meses, de 12 a 83 y desde 84; filtros combinados con AND. | SI-30 pide filtrar por especie, tamaño y edad sin definir categorías; la conjunción es la lectura natural de «filtrar». | CA-02-02 | decidida previamente (D05 y D03 de docs/decisiones.md) |
| Q2 | ¿Qué información y fotografías de la ficha se muestran públicamente? | Todo lo de la ficha interna; solo datos de la oferta. | Foto principal en el listado, galería en la ficha, código, especie, edad, tamaño y procedencia; ni notas veterinarias ni datos de gestión. | Constitution V: lo público no hace públicas las operaciones internas; fotos protegidas por estado (D04). | CA-02-01, CA-02-03 | decidida previamente (D04 y secciones 2 y 4 de este clarify, 2026-11-16) |
| Q3 | ¿Se muestran como «reservado» los animales reservados o se ocultan? | Ocultarlos; mostrarlos con una etiqueta. | Ocultar los reservados, adoptados y en cuarentena (404 en su ficha). | SI-30: «solo aparecen los que están realmente en adopción»; D10 impide nuevas solicitudes sobre un animal reservado. | CA-02-03 | decidida previamente (D10; texto de CA-02-03) |
| Q4 | ¿En qué orden se listan los animales? | Más recientes primero (plan actual: `receivedAt DESC`); por código; por edad; sin orden fijo. | Más recientes primero, hasta que el equipo diga otra cosa. | SI-30 no dice nada del orden; el plan lo fija sin una decisión registrada. | CA-02-01 | PENDIENTE de confirmación por el responsable (`abcb5-ua`) |
| Q5 | ¿Qué ocurre con un valor de filtro no previsto (por ejemplo `species=bird` o `age=abc`)? | (a) Ignorarlo y no restringir (comportamiento actual); (b) mostrar el catálogo vacío con aviso; (c) responder 400. | (a), con el formulario mostrando «Todas». | La spec solo define el filtro vacío o no enviado; la URL se puede escribir a mano. | CA-02-02 | PENDIENTE de confirmación por el responsable (`abcb5-ua`) |
| Q6 | ¿Hace falta paginar el catálogo? | Sin paginar (actual); paginar por tamaño fijo. | Sin paginar mientras el refugio ronde los cien animales, de los que solo una parte está disponible. | SI-30 estima unos 60 perros y 40 gatos; la spec no define paginación. | CA-02-01 | PENDIENTE de confirmación por el responsable (`abcb5-ua`) |

Cada decisión «PENDIENTE» necesita, además, una explicación breve del responsable (enunciado §5.1): *pendiente de
redactar por `abcb5-ua`*.

### 3. Diferencias con la especificación inicial

Comparación de `spec-002/historia.md` (ficha inicial de la raíz) con `specs/spec-002/spec.md` (vigente).
`git log` muestra tres versiones: 7bcea03 (2026-11-05), una copia de la ficha inicial con la cabecera
«Especificación»; 96f1929 (2026-11-16), la reescritura vigente; y b001d3f (2026-11-18), que sustituye
«Responsable: Implementación spec-002» por el responsable y revisor de GitHub.

- Cabecera: de «historia inicial, responsable pendiente de acuerdo» a una spec fechada con responsable
  (`abcb5-ua`), revisor (`aggg15`) y referencia base (SI-30, plan, modelo y decisiones).
- Alcance: de tres líneas genéricas a cinco concreciones: solo `status = 'available'`; filtros con sus valores
  (`dog`/`cat`; `small`/`medium`/`large`; `<12`, `12-83`, `>=84`) combinados con AND; acogida con indicación
  explícita; exclusión estricta de cuarentena, reservados y adoptados también en fichas directas (404); y
  promoción automática previa de cuarentenas vencidas (240 horas UTC, D01).
- CA-02-01 pasa de «puede consultar los animales disponibles» a una dirección (`/animales`), la ausencia de sesión
  y los datos que muestra cada tarjeta (foto principal, código, especie, edad, tamaño y procedencia); se añade el
  escenario 1.1.
- CA-02-02 pasa de «un filtro por cualquiera de esos atributos» a «uno o varios filtros, todos a la vez (AND)»;
  se añaden los escenarios 2.1 (especie), 2.2 (tamaño y edad) y 2.3 (sin coincidencias, con mensaje y enlace de
  restablecer).
- CA-02-03 añade que la ficha de un animal no disponible responde 404 y que sus fotografías también (escenarios
  3.1 y 3.2).
- CA-02-04 concreta el distintivo «En casa de acogida» en el catálogo y en la ficha (escenarios 4.1 y 4.2).
- Los criterios pasan de prosa con «Traza» a un enunciado con escenarios en Dado, Cuando, Entonces, Y; se
  conservan los cuatro identificadores y no se añaden criterios derivados.
- Dependencias: pasan de una línea a una lista que detalla qué toma de HU-01 (código, estados, `releaseDueAnimals()`),
  de HU-04, HU-05 y HU-06 (estados excluidos y `fostered`) y que HU-03 aporta el enlace de solicitud.
- Límites: se sustituye «no se exige registro; las cuentas se decidirán en la base común» por «solo lectura, sin
  sesión y sin notas veterinarias ni datos de gestión».
- Se eliminan «Cuestiones pendientes para clarify» (dos preguntas), «Fuente» y «Encaje en el trabajo grupal»; las
  dos preguntas se responden en este clarify (Q1 y Q2).

### 4. Nota de método

Ronda realizada con Claude Code siguiendo el procedimiento de `.agents/skills/speckit-clarify/SKILL.md`; no se ha
ejecutado la skill de Codex. No se han tomado decisiones nuevas: las filas PENDIENTE esperan al responsable.
