# Implement · spec-002

Fecha: 2026-11-16.
Historia: HU-02 · Consultar y filtrar el catálogo público de adopción.

## Componentes implementados
- **Servicios (`App\Service\AnimalService`):**
  - Incorporados los métodos `findAvailableAnimals(array $filters = [])` y `getPublicAnimal(string $id)`.
  - Desclasificación previa obligatoria mediante `releaseDueAnimals()` (D01).
  - Filtro estricto por `status = 'available'`.
  - Filtros combinados AND para `species` (`dog`, `cat`), `size` (`small`, `medium`, `large`) y `age` (`<12`, `12-83`, `>=84`) (D03, D05).
- **Controlador (`App\Controller\PublicAnimalController`):**
  - Ruta pública `GET /animales` (`public_catalog`) con obtención y sanitización de filtros GET, mapeo de fotografías primarias y renderizado de catálogo.
  - Ruta pública `GET /animales/{id}` (`public_animal_show`) con verificación de disponibilidad del animal, galería de fotos y lanzamiento de `NotFoundHttpException` (404) para animales no disponibles o inexistentes.
- **Vistas Twig con Bulma CSS:**
  - `templates/public/catalog.html.twig`: formulario de filtros con selectores, rejilla de tarjetas con fotos, badges de atributos, distintivo "En casa de acogida" y estado vacío descriptivo.
  - `templates/public/show.html.twig`: ficha detallada pública, galería de fotografías, atributos del animal, notificación contextual de casa de acogida y llamada a la acción de adopción.
  - `templates/base.html.twig`: actualizado el enlace de navegación "Buscar un compañero" a `public_catalog`.
  - `public/assets/app.css`: estilos para miniaturas de catálogo, tarjetas y badges.
- **Pruebas automatizadas (`tests/Spec002Test.php`):**
  - Suite completa de 8 pruebas verificando CA-02-01, CA-02-02, CA-02-03, CA-02-04 y la promoción automática de cuarentena.
