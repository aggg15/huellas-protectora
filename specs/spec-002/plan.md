# Plan técnico · spec-002

Fecha: 2026-11-16.
Historia: HU-02 · Consultar y filtrar el catálogo público de adopción.
Arquitectura: Symfony 7.4, Twig, Hotwire (Turbo/Stimulus), Bulma CSS y Doctrine ORM sobre PostgreSQL.

## 1. Arquitectura y componentes

### 1.1. Controladores
* Creación de `App\Controller\PublicAnimalController`:
  * Ruta `GET /animales` (`name: 'public_catalog'`):
    * Llama a `$animalService->releaseDueAnimals()` para garantizar la promoción automática de animales cuya cuarentena haya finalizado (D01).
    * Extrae y sanitiza los parámetros GET: `species`, `size`, `age`.
    * Invoca al servicio para obtener los animales disponibles filtrados.
    * Renderiza `public/catalog.html.twig` con los resultados y los valores activos de los filtros.
  * Ruta `GET /animales/{id}` (`name: 'public_animal_show'`):
    * Llama a `$animalService->releaseDueAnimals()`.
    * Busca el animal por su identificador UUID.
    * Si no existe o su estado no es `available`, lanza `NotFoundHttpException` (HTTP 404), garantizando la exclusión estricta de cuarentenas, reservas y adopciones (CA-02-03).
    * Recupera las fotos del animal (`AnimalPhoto`) mediante el repositorio.
    * Renderiza `public/show.html.twig`.

### 1.2. Servicios y consultas (Doctrine)
* Extensión de `App\Service\AnimalService`:
  * Método `findAvailableAnimals(array $filters = []): array`:
    * Realiza consulta con Doctrine `QueryBuilder` filtrando obligatoriamente por `a.status = 'available'`.
    * Aplica filtros condicionales validados:
      * `species`: `'dog'` o `'cat'`.
      * `size`: `'small'`, `'medium'` o `'large'`.
      * `age`:
        * `'<12'`: `a.ageMonths < 12`
        * `'12-83'`: `a.ageMonths BETWEEN 12 AND 83`
        * `'>=84'`: `a.ageMonths >= 84`
    * Ordenación: `a.receivedAt DESC`.
  * Método `getPublicAnimal(string $id): ?Animal`:
    * Retorna el `Animal` si existe y `status === 'available'`; en caso contrario retorna `null`.

### 1.3. Presentación e interfaz (Twig + Bulma)
* `templates/public/catalog.html.twig`:
  * Sección hero con título "Animales en adopción".
  * Barra de filtros estilizada con Bulma:
    * Selectores para especie, tamaño y rango de edad.
    * Botón "Filtrar" y botón "Limpiar".
  * Rejilla de tarjetas (`columns is-multiline`):
    * Imagen del animal (`/fotos/{photo.id}`).
    * Código, especie, edad legible y tamaño.
    * Etiqueta destacada "En casa de acogida" si `animal.fostered`.
    * Enlace "Ver ficha".
  * Mensaje de estado vacío amigable si ningún animal coincide.
* `templates/public/show.html.twig`:
  * Vista detallada con galería de fotos, datos públicos del animal e indicación de acogida si aplica.
* `templates/base.html.twig`:
  * Actualización del enlace "Buscar un compañero" hacia `path('public_catalog')`.

---

## 2. Coherencia y modelo de datos

* Se mantiene la entidad `Animal` existente sin alterar la estructura de la base de datos ni requerir migraciones adicionales:
  * `status`: solo se consultan registros con `'available'`.
  * `fostered`: se lee directamente para mostrar el indicador visual.
  * `species`, `size`, `ageMonths`: campos utilizados para los filtros.
* Compatibilidad total con `MediaController`: `/fotos/{id}` ya comprueba que el animal esté en `available` para usuarios sin sesión.

---

## 3. Plan de verificación y pruebas

* Creación de `tests/Spec002Test.php` extendiendo de `WebTestBase`:
  * `testAnonymousCanViewPublicCatalogWithAvailableAnimals()` (CA-02-01)
  * `testFilterBySpeciesSizeAndAge()` (CA-02-02)
  * `testFilterWithoutMatchesShowsEmptyStateMessage()` (CA-02-02)
  * `testQuarantineReservedAndAdoptedAnimalsAreExcluded()` (CA-02-03)
  * `testNonAvailableAnimalPublicDetailReturns404()` (CA-02-03)
  * `testFosteredAnimalShowsFosterBadgeInCatalogAndDetail()` (CA-02-04)
  * `testExpiredQuarantinePromotesAutomaticallyOnCatalogAccess()` (D01 / CA-02-01)
