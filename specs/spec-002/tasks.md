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

## Phase 2: Revisión previa a la entrega (2026-11-21)

Origen: revisión de código previa a la entrega, hallazgos C1-06 (transversal, registrado aquí), C1-15 y C1-20.
Responsable de la ejecución: `aggg15` (asistido por Claude Code); revisión: pendiente en la PR de `revision-entrega`.

- [x] **T210**: Crear las páginas de error propias `templates/bundles/TwigBundle/Exception/error.html.twig`, `error403.html.twig` y `error404.html.twig` en español, sin detalles internos, y fijar `framework.default_locale: es` (C1-06, transversal a todas las historias). Pruebas: `testCustomErrorTemplatesRenderInSpanish` y `testErrorPagesAreServedInSpanishWithoutDebug` (kernel con `debug=false`).
- [x] **T211**: Ordenar las fotos por `id ASC` en `PublicAnimalController` para que la foto principal sea estable (C1-15). Prueba: `testPrimaryPhotoIsStableByIdAscending`.
- [x] **T212**: Añadir casos límite: edades 11/12 y 83/84 meses, filtros inválidos ignorados y ausencia del distintivo de acogida en animales no acogidos (C1-20b/d). Pruebas: `testAgeBoundariesAt12And84Months`, `testInvalidFiltersAreIgnored`, `testNonFosteredAnimalShowsNoFosterBadge`.
- [x] **T213**: Reformular en la ficha pública el compromiso «Protocolo veterinario y desparasitación completos», que el sistema no registra, por «Ha completado la cuarentena de diez días antes de publicarse» (C1-17).
