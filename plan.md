# Plan común de Huellas

Fecha: 2026-11-05; actualizado el 2026-11-19. El 2026-11-05 el alcance se limitó a HU-01 tras
consultar al profesor. Después, por encargo expreso del usuario, se han integrado en `main` HU-02 a
HU-05 (PR #5, #6, #8 y #9). HU-06 se planifica en `specs/spec-006/plan.md` sobre este plan común;
HU-07 y HU-08 conservan su documentación inicial y sus puntos de integración.

## Arquitectura y pila

Una aplicación Symfony 7.4 (compatible con PHP 8.3), Twig, Doctrine ORM y PostgreSQL 16.
Hotwire Turbo gestiona navegación y formularios; Stimulus añade previsualización de fotos y
comportamiento de los formularios. Bulma se sirve localmente, sin depender de una CDN al ejecutar.
Los controladores traducen HTTP; los servicios aplican reglas y transacciones; las entidades
y migraciones definen el modelo común. No se crean ocho aplicaciones independientes.

Se usa la estructura estándar de Symfony en la raíz. La referencia a apps/ y packages/ del índice
de memoria del enunciado es un ejemplo para monorepos: no exige crear paquetes vacíos en esta app.
Composer fija las versiones PHP en composer.lock; npm fija los recursos frontend en package-lock.json.
Docker Compose proporciona PostgreSQL persistente; PHP puede ejecutarse localmente para desarrollar
y también habrá un servicio web en Compose para reproducir el arranque.

## Estructura

- src/Entity: User, Animal, AnimalPhoto, VeterinaryReview, AdoptionApplication (HU-03/04), Adoption y
  FollowUp (HU-05); HU-06 añade FosterHome, FosterAvailability, FosterPlacement y FosterNote.
- src/Service: operaciones de cada historia, reloj inyectable y validación central del dominio.
- src/Controller: catálogo público, acceso y administración según roles.
- templates/: vistas Twig en español. public/: entrada web y recursos locales, sin fotos privadas.
- var/uploads/: fotografías protegidas; la entrega de archivos comprueba la disponibilidad y permisos.
- migrations/: cambios acumulativos; no se resetean datos de la aplicación al probar.
- specs/spec-001 a specs/spec-008: especificaciones; HU-01 a HU-05 están implementadas. Cada plan
  local referencia este plan común y permite resolver el contexto mediante Spec Kit.
- tests/: pruebas unitarias y HTTP con PostgreSQL, independientes de la base de demostración.

## Contratos y coherencia

Catálogo y fichas públicas no requieren cuenta. El acceso interno exige ROLE_STAFF, con roles
ROLE_VOLUNTEER, ROLE_VET y ROLE_COORDINATOR. Crear animales corresponde al voluntario o coordinadora;
registrar revisión al veterinario; decisiones de solicitudes y acogidas a la coordinadora. Las cuentas
públicas solo reciben ROLE_USER. Las familias de acogida (HU-06) tienen ROLE_FOSTER, creado por comando y
fuera de ROLE_STAFF. Todas las mutaciones usan POST y CSRF; las redirecciones exitosas usan 303
y los formularios inválidos, 422. Los datos se escapan en Twig y los identificadores son opacos.

La cuarentena se interpreta como diez periodos de 24 horas desde la recepción UTC. La promoción
automática solo afecta animales en cuarentena vencida: no revierte reservas o adopciones.
Se invoca antes de consultar disponibilidad y existe un comando para ejecutarla explícitamente.
Las fotografías usan nombres generados y tipos reales JPEG, PNG o WebP; se mantienen fuera de public/.
Una foto de un animal no público tampoco es accesible por su URL anónimamente.

HU-03 bloqueará la fila de la persona y del animal al presentar una solicitud. HU-04 bloqueará
el animal antes de aprobar y actualizará la candidata y las otras solicitudes activas en una misma
transacción. Las esperas y aprobadas cuentan como abiertas. Conflictos y errores no producen
reservas dobles ni escrituras parciales. El código y los tests concretos se incorporan en cada fase.
Toda operación que cambie el estado de una reserva (aprobar, liberar o formalizar) debe volver a leer
el estado de la solicitud y del animal después de obtener el bloqueo, no antes. Las acogidas (HU-06)
siguen el mismo criterio y el mismo orden: primero el animal, después la casa y por último la acogida.

## Validación y secuencia

Cada rama spec-00N se crea desde `main` después de integrar la historia anterior de la que depende.
main no integra código sin revisión del compañero. Se guardarán commits por historia y el informe
indicará si la revisión humana está pendiente o registrada en la PR. Se ejecutará PHPUnit con PostgreSQL para cada hito y se
repetirá la regresión afectada; al terminar se comprobará el recorrido integrado y móvil/escritorio.
Los informes describen fases SDD aplicadas por Codex, sin afirmar la ejecución de comandos slash
de Spec Kit que no estaban cargados cuando se escribieron. Desde el 2026-11-16, Spec Kit 1.0.11
está inicializado con skills de Codex y scripts oficiales. La configuración no atribuye
retroactivamente esas ejecuciones a los informes anteriores. Véase CONTRIBUTING.md y AGENTS.md.

## Fuentes técnicas

- https://symfony.com/releases/7.4
- https://symfony.com/doc/7.4/security.html
- https://symfony.com/doc/7.4/doctrine.html
- https://symfony.com/bundles/ux-turbo

El alcance docente procede exclusivamente de los tres PDF vigentes de Práctica Grupal.
