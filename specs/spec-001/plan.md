# Plan de implementación · HU-01

Adaptación de la documentación existente para Spec Kit, 16 de noviembre de 2026. No se reinicia
la implementación ni se cambian los cinco criterios de aceptación.

## Base común y alcance

El [plan común](../../plan.md), [modelo de datos](../../data-model.md),
[decisiones](../../docs/decisiones.md) y [Constitution](../../.specify/memory/constitution.md)
son las fuentes compartidas. Este documento añade el contexto de HU-01 sin duplicar esos acuerdos.
Solo se implementa spec-001; catálogo, solicitudes, reservas y las historias posteriores quedan fuera.

## Technical Context

- Lenguaje: PHP 8.3; Symfony 7.4, Twig y Doctrine ORM.
- Interfaz: Turbo, Stimulus y Bulma, con recursos npm servidos localmente.
- Persistencia: PostgreSQL 16 y migraciones versionadas.
- Entidades de esta historia: User, Animal, AnimalPhoto y VeterinaryReview.
- Pruebas: PHPUnit con HTTP simulado y PostgreSQL separado, terminado en `_test`.

## Diseño y trazabilidad

| Criterio | Implementación | Verificación |
|---|---|---|
| CA-01-01 | AnimalController, AnimalService, PhotoStorage y entidades | Alta con foto real e identidad persistida |
| CA-01-02 | Cuarentena inicial, recepción UTC y límite de 240 horas | Estado inicial y comprobación del límite exacto |
| CA-01-03 | Autorización interna y MediaController | Acceso anónimo a cuarentena/fotos rechazado |
| CA-01-04 | VeterinaryReview y rol ROLE_VET | Revisión vinculada y permiso veterinario |
| CA-01-05 | Promoción de cuarentenas vencidas y comando de liberación | Disponible al límite sin alterar otros estados |

El controlador transforma HTTP; los servicios validan y persisten. Las fotos se almacenan fuera
de `public/`, con nombres generados y tipos reales. Las operaciones usan permisos y CSRF; los errores
de creación no dejan filas o ficheros parciales. Los cambios del modelo común se coordinan con el compañero.

## Estado y cierre

Código y migraciones ya implementados en el hito 7bcea03. La [evidencia del alcance corregido](../../docs/evidencias/spec-001-alcance-corregido.txt)
registra 7 pruebas y 32 aserciones correctas. Las [tareas](tasks.md) y los informes existentes conservan
su historia; la revisión humana de la PR #1 permanece pendiente. Actualización 2026-11-21: la PR #1 está integrada en `main` y consta aprobada por `abcb5-ua` (según `docs/agrupamiento.md`). La puesta en marcha está en el
[README](../../README.md) y el flujo colaborativo en [CONTRIBUTING](../../CONTRIBUTING.md).
