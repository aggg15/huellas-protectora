# Instrucciones del proyecto Huellas

## Alcance y fuentes

- La implementación publicada comprende únicamente HU-01 / `spec-001`. No recuperar las ramas
  locales `spec-002`, `spec-003` o `spec-004` ni desarrollar otra historia sin un encargo expreso.
- Usar exclusivamente la documentación de Práctica Grupal. Leer la Constitution en
  `.specify/memory/constitution.md`, `plan.md`, `data-model.md` y `docs/decisiones.md`.
- `specs/spec-001/spec.md` es la especificación vigente de esta historia. Las ocho carpetas
  `spec-00N/` de la raíz y el LaTeX/PDF conservan las historias iniciales.
- Mantener HU-XX y CA-XX-YY, reglas de la protectora y documentación académica en español.

## Spec Kit y contexto compartido

- Spec Kit 1.0.11 está inicializado para Codex mediante skills en `.agents/skills/`.
  Invocar `$speckit-analyze`, `$speckit-converge`, etc.; no afirmar ejecuciones no realizadas.
- Al cambiar de rama, ejecutar `bash tools/spec-context.sh`. El puntero `.specify/feature.json`
  es local e ignorado por Git. No reutilizar el contexto de otra historia.
- Para verificar esta entrega, usar `spec-001` y `specs/spec-001`. Las ramas del curso no se renombran
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
  Ejecutar `vendor/bin/phpunit --filter Spec001 --testdox` y los validadores apropiados al cambio.
- Versionar migraciones nuevas; no reescribir migraciones aplicadas a otros integrantes.
- Conservar las evidencias anteriores y registrar fecha, comando, resultado y alcance real.
  `converge` informa y añade tareas: las correcciones de código se ejecutan después mediante implement.
- `main` requiere una revisión. Trabajar en `spec-001` para esta entrega y publicar en su PR #1.
  No hacer push directo a main, fusionar sin revisión humana ni restaurar las otras ramas remotas.
- Los usuarios GitHub son `aggg15` y `abcb5-ua`. No inventar sus nombres académicos, reparto de las
  siete historias pendientes, ratificación de la Constitution ni aprobaciones del compañero.

Consultar `CONTRIBUTING.md` para clonar, seleccionar contexto y revisar los cambios.
