# Huellas · gestión de adopciones

Estado a 21 de noviembre de 2026: `main` integra **HU-01 a HU-08** (PR #1, #5, #6, #8, #9, #10, #11 y #12). Las
PR #1, #5, #6, #8, #9, #11 y #12 tienen aprobación formal en GitHub del otro integrante; la PR #10 (HU-06) fue revisada
e integrada por `aggg15` y GitHub no registra una aprobación formal (véase [revisión cruzada](docs/revision-cruzada.md)).

| Historia | Contenido implementado | PR | Autoría observada en GitHub |
|---|---|---|---|
| HU-01 | Ficha, fotografías protegidas, cuarentena de 240 horas y revisión veterinaria | #1 | `aggg15` |
| HU-02 | Catálogo público y filtros por especie, tamaño y edad | #5 | `abcb5-ua` |
| HU-03 | Solicitud con cuestionario completo y máximo de dos abiertas | #6 | `aggg15` |
| HU-04 | Entrevista, rechazo motivado, aprobación con reserva y espera | #8 | `abcb5-ua` |
| HU-05 | Formalización con contrato y cuota, adopción y dos seguimientos | #9 | `aggg15` |
| HU-06 | Casas de acogida: capacidad, periodos, acogidas y novedades | #10 | `abcb5-ua` |
| HU-07 | Turnos de paseo y limpieza, inscripción y destacado a 48 horas | #11 | `aggg15` |
| HU-08 | Apadrinamiento de animales, novedades para padrinos y decisión tras la adopción | #12 | `abcb5-ua` |

El 5 de noviembre se limitó temporalmente la publicación a HU-01 tras consultar al profesor y se
cerraron las PR #2 a #4. Las historias siguientes se han publicado después por encargo expreso del
usuario, sobre `main`; la cronología está en [decisiones](docs/decisiones.md).

**Trabajo en equipo:** Spec Kit 1.0.11 inicializado para Codex, skills compartidas, selección de
contexto y CI. Consulta [CONTRIBUTING](CONTRIBUTING.md) para clonar y trabajar con el compañero;
[AGENTS.md](AGENTS.md) recoge las instrucciones comunes del proyecto.

Repositorio: [aggg15/huellas-protectora](https://github.com/aggg15/huellas-protectora).

## Código y documentación

- Especificaciones y ciclo SDD de cada historia en `specs/spec-00N/` (spec, clarify, plan, tasks,
  analyze, implement y converge cuando se han elaborado); evidencias en `docs/evidencias/`.
- [Constitution](.specify/memory/constitution.md), [plan común](plan.md), [modelo de datos](data-model.md),
  [decisiones](docs/decisiones.md) y [agrupamiento](docs/agrupamiento.md).
- Código Symfony en `src/`, vistas Twig en `templates/`, recursos Hotwire/Bulma en `public/` y
  migraciones PostgreSQL en `migrations/`. Las fotografías se guardan fuera del directorio público.

Las ocho fichas iniciales están en `spec-001/` a `spec-008/`. El [LaTeX](historias_usuario.tex) y el
[PDF](output/pdf/historias_usuario.pdf) conservan ese documento inicial; no son evidencia de implementación.
Las fuentes vigentes son `SI-30-protectora-animales-descripcion.pdf`, `guia_proyecto_SDD_parte_I.pdf`
y `Practica-SDD-enunciado_v1_IR.pdf`. Se utiliza exclusivamente la documentación de Práctica Grupal.

## Desarrollo local

Requisitos: PHP 8.3 con las extensiones exigidas por Composer, incluidas GD y PDO PostgreSQL;
Composer, Node.js y Docker para la base de datos.

Ejecuta `php tools/configure.php --test` para crear `.env`, `.env.local` y `.env.test.local` si no
existen. Genera secretos aleatorios sin mostrarlos y conserva los archivos existentes. La URL local
apunta a la base `huellas` y al puerto configurado de PostgreSQL; la de pruebas usa otra base terminada
en `_test`. Estos archivos son privados y están excluidos de Git. También puedes prepararlos
manualmente desde `.env.example`, cambiando los valores ficticios y las URLs correspondientes.

```sh
php tools/configure.php --test
composer install
npm ci
npm run build
docker compose --env-file .env.local up -d --wait database
php bin/console doctrine:migrations:migrate --no-interaction
php -d upload_max_filesize=5M -d post_max_size=32M -d max_file_uploads=20 -S 127.0.0.1:8086 -t public public/router.php
```

**Límites de subida.** Cada fotografía admite como máximo 5 MiB y cada alta entre 1 y 6 fotos. Los valores
`-d` del servidor integrado deben cubrirlo: `upload_max_filesize` ≥ 5M, `post_max_size` ≥ 32M (6 × 5 MiB más el
formulario) y `max_file_uploads` ≥ 6. Con PHP-FPM o Apache hay que fijarlos en `php.ini`. Si se superan,
PHP descarta los ficheros antes de llegar a la aplicación (con `post_max_size` superado, también los campos
del formulario y el token CSRF): la aplicación responde con un error de validación o de formulario caducado
y no guarda nada; con los valores por defecto de PHP (`upload_max_filesize=2M`) las fotos de entre 2 y 5 MiB
se rechazarán aunque el límite de la aplicación sea mayor.

Abre http://127.0.0.1:8086. Esta versión proporciona PostgreSQL en Compose y ejecuta PHP localmente. No se cargan cuentas
ni animales automáticamente. Las cuentas internas se crean mediante un comando con contraseña oculta:

```sh
php bin/console app:create-user voluntario@example.test "Voluntario ficticio" --role=ROLE_VOLUNTEER
php bin/console app:create-user veterinario@example.test "Veterinario ficticio" --role=ROLE_VET
php bin/console app:create-user coordinadora@example.test "Coordinadora ficticia" --role=ROLE_COORDINATOR
php bin/console app:create-user familia@example.test "Familia de acogida ficticia" --role=ROLE_FOSTER   # HU-06
```

## Pruebas

Configura `.env.test.local` con `APP_SECRET` de pruebas y `DATABASE_URL` para una base separada cuyo
nombre termine en `_test`, usando el servidor PostgreSQL local. Las pruebas comprueban ese sufijo
antes de limpiar datos; no deben apuntar a la base normal de la aplicación.

El asistente de configuración ya prepara este archivo si no existe; no crea ni limpia bases.

```sh
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:migrations:migrate --env=test --no-interaction
php bin/console doctrine:schema:validate --env=test
vendor/bin/phpunit --testdox                 # todas las historias
vendor/bin/phpunit --filter Spec004 --testdox  # una sola historia
php bin/console lint:container
php bin/console lint:twig templates
```

Con HU-01 a HU-08 y la revisión previa a la entrega, la suite completa pasa **145 pruebas y 1680 aserciones** en la rama
`revision-entrega` (2026-11-21, Docker con php:8.3 y postgres:16; [evidencia](docs/evidencias/revision-entrega-2026-11-21/suite-final.txt)); antes de la revisión, `main` (a74f85f) pasaba 112 pruebas y 1301 aserciones. La evidencia registrada en el repositorio es la del 2026-11-19, en bases `_test` recién
migradas, sobre la rama `spec-008` ([evidencia](docs/evidencias/spec-008-phpunit.txt)). Si una base `_test` antigua
conserva tablas de las implementaciones retiradas el 5 de noviembre, las migraciones fallan con
«relation already exists»: crea otra base terminada en `_test` en lugar de limpiar la existente.
Las respuestas 403/404/409/422 esperadas se registran como errores HTTP comprobados; no son fallos de PHPUnit.

El CI ejecuta las pruebas de cada historia (`tests/Spec00NTest.php`) en las ramas `spec-00*` y en
las PR hacia `main`. Integrantes (G27): Antonio García Gómez (`aggg15`) y Ana Bernabeu Castillo (`abcb5-ua`). La Constitution quedó ratificada por ambos el 2026-11-21.

## Notas por historia

- HU-03: las personas solicitantes crean su cuenta en `/registro` (solo `ROLE_USER`); los roles
  internos siguen creándose por comando. Detalle en [spec-003](specs/spec-003/spec.md).
- HU-04: la coordinadora gestiona entrevistas, decisiones y liberación de reservas en
  `/admin/solicitudes`. Detalle en [spec-004](specs/spec-004/spec.md).
- HU-05: la coordinadora formaliza la entrega desde la solicitud
  aprobada (cuota y confirmación del abono), genera el contrato imprimible y consulta los seguimientos en
  `/admin/seguimientos`. Detalle en [spec-005](specs/spec-005/spec.md).
- HU-06: las familias de acogida (`ROLE_FOSTER`) indican capacidad y periodos y anotan novedades en `/acogida`;
  la coordinadora registra y finaliza acogidas desde la ficha del animal. Detalle en [spec-006](specs/spec-006/spec.md).
- HU-07: la coordinadora crea los turnos de paseo y limpieza y los
  voluntarios se apuntan o se dan de baja en `/admin/turnos`, que destaca los turnos sin el mínimo cuando faltan
  48 horas o menos. Detalle en [spec-007](specs/spec-007/spec.md).
- HU-08 (PR #12, integrada en `main`; aprobada por `aggg15`): las personas con cuenta pública apadrinan un animal disponible
  desde su ficha (aportación mensual desde 10 €) y ven sus novedades y la decisión tras la adopción en
  `/mis-apadrinamientos`; la coordinadora publica las novedades en `/admin/apadrinamientos`. Detalle en
  [spec-008](specs/spec-008/spec.md).
