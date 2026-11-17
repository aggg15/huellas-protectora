# Huellas · spec-001

Publicación vigente: **únicamente HU-01 / spec-001**, por indicación del usuario tras consultar al
profesor el 5 de noviembre de 2026. Incluye registro de animales, fotografías protegidas, cuarentena
de diez periodos de 24 horas y revisión veterinaria, junto con la base técnica común necesaria.

**Trabajo en equipo preparado:** Spec Kit 1.0.11 inicializado para Codex, skills compartidas,
selección de contexto y CI. Consulta [CONTRIBUTING](CONTRIBUTING.md) para clonar y trabajar con el
compañero; [AGENTS.md](AGENTS.md) recoge las instrucciones comunes del proyecto.

Repositorio: [aggg15/huellas-protectora](https://github.com/aggg15/huellas-protectora).
La rama `spec-001` contiene esta implementación y su [PR #1](https://github.com/aggg15/huellas-protectora/pull/1)
propone integrarla en `main`, pendiente de revisión del compañero. Las PR #2, #3 y #4 se retiran y
sus ramas remotas se eliminan. El trabajo posterior se conserva como copia local de recuperación.

HU-02 a HU-08 permanecen como documentación de planificación; su funcionalidad no está implementada
en esta rama. La portada pública es una página de entrada: el catálogo de adopción corresponde a HU-02.

## Código y documentación

- [Especificación HU-01](specs/spec-001/spec.md), [tareas](specs/spec-001/tasks.md),
  [implementación](specs/spec-001/implement.md) y [validación](docs/evidencias/spec-001-phpunit.txt).
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

Abre http://127.0.0.1:8086. Esta versión proporciona PostgreSQL en Compose y ejecuta PHP localmente;
no incluye el servicio web Docker de la implementación posterior retirada. No se cargan cuentas
ni animales automáticamente. Las cuentas internas se crean mediante un comando con contraseña oculta:

```sh
php bin/console app:create-user voluntario@example.test "Voluntario ficticio" --role=ROLE_VOLUNTEER
php bin/console app:create-user veterinario@example.test "Veterinario ficticio" --role=ROLE_VET
php bin/console app:create-user coordinadora@example.test "Coordinadora ficticia" --role=ROLE_COORDINATOR
```

## Comprobación de spec-001

Configura `.env.test.local` con `APP_SECRET` de pruebas y `DATABASE_URL` para una base separada cuyo
nombre termine en `_test`, usando el servidor PostgreSQL local. Las pruebas comprueban ese sufijo
antes de limpiar datos; no deben apuntar a la base normal de la aplicación.

El asistente de configuración ya prepara este archivo si no existe; no crea ni limpia bases.

```sh
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:migrations:migrate --env=test --no-interaction
vendor/bin/phpunit --filter Spec001 --testdox
php bin/console lint:container
php bin/console lint:twig templates
```

El hito inicial y la [verificación del alcance corregido](docs/evidencias/spec-001-alcance-corregido.txt)
pasan **7 pruebas y 32 aserciones**. Comprueban alta con fotografía real, ocultación de
cuarentena y fotos al público, límite exacto de 240 horas, promoción sin modificar reservas, revisión
veterinaria por su rol, entradas inválidas sin escrituras parciales, CSRF y permisos de creación.
Las respuestas 403/404 esperadas se registran como errores HTTP comprobados; no son fallos de PHPUnit.

La revisión del compañero, la ratificación de la Constitution y el reparto nominal permanecen pendientes.

## spec-003 · solicitudes de adopción (HU-03)

La rama `spec-003`, construida sobre `main` con HU-02 ya integrada, añade la solicitud con cuestionario completo, el
máximo de dos solicitudes abiertas por cuenta y la consulta de candidaturas por la coordinadora.
Las personas solicitantes crean su cuenta en `/registro`; los roles internos siguen creándose por
comando. Documentación: [spec](specs/spec-003/spec.md), [plan](specs/spec-003/plan.md),
[tareas](specs/spec-003/tasks.md) y [evidencia](docs/evidencias/spec-003-phpunit.txt).

```sh
php bin/console doctrine:migrations:migrate --env=test --no-interaction
vendor/bin/phpunit --filter Spec003 --testdox
```

Resultado del 2026-11-17: **11 pruebas y 127 aserciones** correctas, incluida una prueba con cuatro
procesos simultáneos. La revisión del compañero queda pendiente.
