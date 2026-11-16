# Analyze · spec-002

Revisión de solo lectura de `spec.md`, `plan.md`, `data-model.md` y `tasks.md` antes de iniciar la implementación.

| Criterio | Plan / Tarea | Comprobación prevista |
|---|---|---|
| CA-02-01 | T203, T204, T205; `PublicAnimalController::catalog()` | Acceso anónimo GET a `/animales`, respuesta 200 y visualización de animales disponibles sin autenticación. |
| CA-02-02 | T203, T204, T205; `AnimalService::findAvailableAnimals()` | Peticiones con filtros `species`, `size`, `age` combinados con AND; verificación de filtrado exacto y mensaje ante ausencia de coincidencias. |
| CA-02-03 | T203, T204; exclusión en query y 404 en `PublicAnimalController::show()` | Animales en cuarentena, reservados y adoptados ausentes del catálogo; petición anónima a `/animales/{id}` devuelve 404; fotos en `/fotos/{id}` devuelven 404. |
| CA-02-04 | T205, T206; vistas Twig con Bulma | Animal disponible con `fostered=true` visible en catálogo y ficha pública con distintivo visual "En casa de acogida". |

## Hallazgos de coherencia
- No se detectan criterios sin tarea asignada.
- No se requieren nuevas tablas ni migraciones de base de datos; la entidad `Animal` ya contiene los campos `status`, `species`, `size`, `ageMonths` y `fostered` con sus restricciones de integridad en PostgreSQL (`Version20261105150000`).
- Las rutas `/animales` y `/animales/{id}` complementan limpiamente las rutas administrativas `/admin/animales` y `/admin/animales/{id}` de `AnimalController` (que está protegido bajo `ROLE_STAFF`).
- La desclasificación previa obligatoria (`releaseDueAnimals()`) garantiza la coherencia con HU-01 (D01).
