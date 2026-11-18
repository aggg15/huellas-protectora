# Instrucciones del proyecto Huellas

## Alcance y fuentes

- Estado de las historias: HU-01, HU-02 y HU-03 están integradas en `main` (PR #1, #5 y #6).
  HU-04 está implementada en la rama `spec-004`, pendiente de PR y revisión. HU-05 a HU-08 solo
  tienen `specs/spec-00N/spec.md`; no implementarlas sin un encargo expreso.
- Usar exclusivamente la documentación de Práctica Grupal. Leer la Constitution en
  `.specify/memory/constitution.md`, `plan.md`, `data-model.md` y `docs/decisiones.md`.
- `specs/spec-00N/spec.md` es la especificación vigente de cada historia. Las ocho carpetas
  `spec-00N/` de la raíz y el LaTeX/PDF conservan las historias iniciales.
- Mantener HU-XX y CA-XX-YY, reglas de la protectora y documentación académica en español.

## Spec Kit y contexto compartido

- Spec Kit 1.0.11 está inicializado para Codex mediante skills en `.agents/skills/`.
  Invocar `$speckit-analyze`, `$speckit-converge`, etc.; no afirmar ejecuciones no realizadas.
- Al cambiar de rama, ejecutar `bash tools/spec-context.sh`. El puntero `.specify/feature.json`
  es local e ignorado por Git. No reutilizar el contexto de otra historia.
- Para trabajar o verificar una historia, usar su rama `spec-00N` y `specs/spec-00N`. Las ramas no se renombran
  al formato genérico de Spec Kit ni se generan números nuevos para historias existentes.
- La Constitution existente se conserva. No ejecutar `specify init --force` en una copia ya
  inicializada ni regenerar la spec, las tareas completadas o la planificación para instalar skills.
- El plan de la historia referencia el plan y el modelo comunes; evitar modelos incompatibles o
  una aplicación independiente por historia. El flujo docente incluye clarify, analyze y converge.
- Las skills oficiales se conservan sin modificaciones. Los acuerdos locales están en este archivo
  y `CONTRIBUTING.md`; una actualización del CLI debe revisar compatibilidad antes de regenerarlas.

## Código, verificación y coordinación

- Symfony, Twig, Turbo/Stimulus, Doctrine, PostgreSQL y Bulma. PHP estricto, PSR-4, clases PascalCase,
  variables y métodos camelCase, cuatro espacios y vistas en español.
- Separar controladores, servicios y entidades. Validar en servidor, comprobar permisos y CSRF,
  conservar fotos privadas y evitar escrituras parciales.
- Preparar entorno con `php tools/configure.php --test`; instalar con `composer install` y `npm ci`.
  Consultar README para PostgreSQL, migraciones y arranque. No sobrescribir configuración local.
- Probar únicamente contra bases terminadas en `_test`; nunca limpiar o degradar la base normal.
  Ejecutar `vendor/bin/phpunit --filter Spec00N --testdox` de la historia y la suite completa
  (`vendor/bin/phpunit --testdox`) para detectar regresiones, además de `doctrine:schema:validate`
  y los linters del CI. Toda restricción de una migración (p. ej. índices parciales) debe declararse
  también en el mapeo de la entidad. Sin PHP local, usar contenedores `php:8.3-cli` y
  `postgres:16-alpine` equivalentes al CI. No registrar como ejecutadas pruebas no ejecutadas.
- Versionar migraciones nuevas; no reescribir migraciones aplicadas a otros integrantes.
- Conservar las evidencias anteriores y registrar fecha, comando, resultado y alcance real.
  `converge` informa y añade tareas: las correcciones de código se ejecutan después mediante implement.
- `main` requiere una revisión. Trabajar en la rama de cada historia y publicar mediante una PR
  hacia `main` que siga `.github/PULL_REQUEST_TEMPLATE.md`. No hacer push directo a main ni
  fusionar sin revisión humana. El CI (`.github/workflows/ci.yml`) detecta solo las ramas
  `spec-00*`, las historias con `plan.md` y los ficheros `tests/Spec00NTest.php`; respetar esos nombres.
- Los usuarios GitHub son `aggg15` y `abcb5-ua`. No inventar sus nombres académicos, reparto de las
  historias pendientes, ratificación de la Constitution ni aprobaciones del compañero.

Consultar `CONTRIBUTING.md` para clonar, seleccionar contexto y revisar los cambios.
