# Tareas spec-002

Historia: HU-02 · Consultar y filtrar el catálogo público de adopción.
Metodología: Spec-Driven Development.

## Tareas de especificación y planificación
- [x] **T201**: Concretar decisiones de negocio D03 y D05 en `clarify.md` y redactar escenarios Gherkin para CA-02-01 a CA-02-04 en `spec.md`.
- [x] **T202**: Diseñar arquitectura técnica en `plan.md` (rutas, controlador público, métodos de servicio y plantillas Twig).

## Tareas de implementación
- [x] **T203**: Extender `AnimalService` con métodos de consulta filtrada `findAvailableAnimals()` y `getPublicAnimal()`, respetando exclusión de estados y desclasificación previa de cuarentena.
- [x] **T204**: Crear `PublicAnimalController` con las rutas públicas `GET /animales` y `GET /animales/{id}`, gestionando respuestas 200 y 404 para animales no disponibles.
- [x] **T205**: Diseñar e implementar plantilla Twig `templates/public/catalog.html.twig` con Bulma CSS: barra de filtros, tarjetas de animales con fotos, badges de especie/tamaño/edad y distintivo de acogida.
- [x] **T206**: Diseñar e implementar plantilla Twig `templates/public/show.html.twig` con galería fotográfica, detalle del animal y mensaje de acogida si aplica.
- [x] **T207**: Actualizar el menú de navegación en `templates/base.html.twig` para enlazar "Buscar un compañero" con el catálogo público.

## Tareas de verificación y convergencia
- [x] **T208**: Implementar suite de pruebas automatizadas `tests/Spec002Test.php` verificando todos los criterios de aceptación (CA-02-01, CA-02-02, CA-02-03, CA-02-04 y casos límite).
- [x] **T209**: Redactar informes de evaluación `analyze.md` y `converge.md`, asegurando trazabilidad y ausencia de regresiones.
