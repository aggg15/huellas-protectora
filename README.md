# Huellas · Gestión de adopciones

Proyecto SDD de Ingeniería de Requisitos. Esta iteración implementa **spec-001 a spec-004**:
registro y cuarentena, catálogo público, solicitudes y decisiones de adopción. HU-05 a HU-08
conservan su especificación inicial y no se presentan como implementadas.

Repositorio: [aggg15/huellas-protectora](https://github.com/aggg15/huellas-protectora).
Los hitos se conservan en las ramas `spec-001`, `spec-002`, `spec-003` y `spec-004`, con dependencias acumulativas. La integración en `main` y la revisión del
compañero siguen pendientes. El repositorio es público por decisión expresa del usuario;
los secretos y datos de ejecución se excluyen de Git.

## Documentación vigente

- [Constitution 1.1.0](.specify/memory/constitution.md), [plan común](plan.md) y [modelo de datos](data-model.md).
- [Decisiones y clarificaciones](docs/decisiones.md), [agrupamiento](docs/agrupamiento.md).
- [Memoria](docs/memoria.md), [trazabilidad](docs/trazabilidad.md) y [verificación](docs/verificacion.md).
- Fuentes: `SI-30-protectora-animales-descripcion.pdf`, `guia_proyecto_SDD_parte_I.pdf` y
  **`Practica-SDD-enunciado_v1_IR.pdf`**. Se usa exclusivamente el contexto de Práctica Grupal.

El enunciado v1 mezcla dos/cuatro integrantes; se mantiene la guía específica de dos personas y
cuatro historias por persona, sin inventar nombres, ratificación ni revisión del compañero.

| Spec | Necesidad | Estado |
|---|---|---|
| [spec-001](specs/spec-001/spec.md) | Ficha, fotos, cuarentena y veterinario | Implementada y validada. |
| [spec-002](specs/spec-002/spec.md) | Catálogo público y filtros | Implementada y validada. |
| [spec-003](specs/spec-003/spec.md) | Cuestionario y dos solicitudes como máximo | Implementada y validada. |
| [spec-004](specs/spec-004/spec.md) | Entrevistas, decisiones, reserva y espera | Implementada y validada. |
| [spec-005](specs/spec-005/spec.md) | Formalización y seguimientos | Pendiente. |
| [spec-006](specs/spec-006/spec.md) | Acogidas y novedades | Pendiente. |
| [spec-007](specs/spec-007/spec.md) | Turnos | Pendiente. |
| [spec-008](specs/spec-008/spec.md) | Apadrinamientos | Pendiente. |

Las carpetas `spec-001/` a `spec-008/` de la raíz conservan las fichas iniciales de octubre.
El [LaTeX](historias_usuario.tex), [PDF](output/pdf/historias_usuario.pdf) y el
[informe inicial](docs/revision_enunciado_general.md) son antecedentes fechados. Los resultados
actuales del ciclo están en `specs/`, conforme al enunciado vigente.

## Arranque con Docker

Requisitos: Docker Desktop y PHP 8.3 para generar la configuración local. También puede crearse
`.env` y `.env.local` manualmente desde `.env.example`, sustituyendo los valores de ejemplo.

```sh
php tools/configure.php
docker compose --env-file .env.local up -d --build --wait
```

Abre **http://127.0.0.1:8086**. Las migraciones se aplican automáticamente. No se cargan cuentas
ni animales de demostración. PostgreSQL y las fotos se conservan en volúmenes separados. Cambia
`APP_HTTP_PORT` en `.env.local` para elegir otro puerto.

Si el generador de compilación de Compose falla por las tildes de la ruta, la alternativa
comprobada es:

```sh
docker build -t huellas-web .
docker compose --env-file .env.local up -d --wait --no-build
```

Las cuentas internas se crean por comando, que solicita una contraseña oculta:

```sh
docker compose --env-file .env.local exec web php bin/console app:create-user voluntario@example.test "Voluntario ficticio" --role=ROLE_VOLUNTEER
docker compose --env-file .env.local exec web php bin/console app:create-user veterinario@example.test "Veterinario ficticio" --role=ROLE_VET
docker compose --env-file .env.local exec web php bin/console app:create-user coordinadora@example.test "Coordinadora ficticia" --role=ROLE_COORDINATOR
```

Los solicitantes crean su cuenta en `/registro`; no pueden elegir roles internos. Usa solo datos
ficticios. `docker compose --env-file .env.local stop` detiene la aplicación; `down` conserva los
volúmenes. No uses `down -v` si quieres conservar datos.

## Desarrollo con PHP local

Requisitos: PHP 8.3 con las extensiones exigidas por Composer (incluidas PDO PostgreSQL y GD),
Composer, Node.js y Docker para PostgreSQL.

```sh
php tools/configure.php --test
composer install
npm ci
npm run build
docker compose --env-file .env.local up -d --wait database
php bin/console doctrine:migrations:migrate --no-interaction
php -d upload_max_filesize=5M -d post_max_size=32M -d max_file_uploads=20 -S 127.0.0.1:8086 -t public public/router.php
```

Si Docker ya ocupa el puerto 8086, usa otro para PHP. Los recursos Turbo, Stimulus y Bulma se
sirven localmente; no se necesita una CDN durante la ejecución.

## Verificación reproducible

Las pruebas usan una base separada terminada en `_test`. Los scripts impiden limpiar la base
normal de la aplicación.

```sh
php tools/configure.php --test
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:migrations:migrate --env=test --no-interaction
vendor/bin/phpunit --testdox
php bin/console doctrine:schema:validate
php bin/console lint:container
php bin/console lint:twig templates
```

Para el recorrido de navegador:

```sh
npx playwright install chromium
php tools/prepare-browser.php
APP_ENV=e2e APP_DEBUG=1 php -S 127.0.0.1:8087 -t public public/router.php
```

En otra terminal ejecuta `node tests/browser.cjs`. El entorno `e2e` usa la base de pruebas y
sesiones HTTP nativas; `test` usa sesiones simuladas de PHPUnit. No ejecutes ambas suites a la
vez: preparan la misma base. Los datos y cuentas de `tools/prepare-browser.php` son ficticios y
exclusivos de `_test`. Las ilustraciones de sus fichas son imágenes de prueba generadas por código.
El navegador guarda capturas y `resultados.json` en `docs/evidencias/browser/`.

## Recorrido de las cuatro historias

1. El voluntario registra un animal con fotos, procedencia, especie, edad, tamaño y recepción.
   Se muestran los diez días de cuarentena; una recepción de hace más de diez días ya puede
   estar disponible. El veterinario registra la revisión desde su propia cuenta.
2. Cualquier persona consulta y filtra el catálogo por especie, tamaño y edad. Solo aparecen
   animales disponibles, también si están en acogida. Los estados no públicos tampoco exponen fotos.
3. Un solicitante se registra, accede y completa vivienda, experiencia y disponibilidad.
   Pendientes, esperas y aprobadas cuentan para el máximo de dos solicitudes abiertas.
4. La coordinadora lee el cuestionario, registra la entrevista y decide. Aprobar requiere entrevista
   no futura y reserva exclusiva; las otras candidaturas activas esperan. Rechazar exige motivo.
   Una reserva fallida puede liberarse con motivo, reactivando las candidaturas en espera.

Contrato, cuota, seguimientos, gestión completa de acogidas, turnos y padrinos quedan para las
siguientes historias. Es una aplicación académica local; no se afirma aptitud para producción.
