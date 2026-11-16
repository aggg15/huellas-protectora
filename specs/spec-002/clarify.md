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
  * El enlace principal "Buscar un compañero" en el encabezado de [`templates/base.html.twig`](file:///c:/Users/34635/Desktop/ingenieria_requisitos/huellas-protectora/templates/base.html.twig) apunta directamente a la ruta `public_catalog` (`/animales`).
* **Anonimato y seguridad:**
  * No se requiere inicio de sesión para consultar el catálogo ni la ficha pública.
  * Los accesos anónimos nunca revelan notas veterinarias, revisiones ni datos privados de la gestión interna.
