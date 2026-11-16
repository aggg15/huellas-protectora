# Trabajo en equipo

Fecha: 16 de noviembre de 2026. Usuarios GitHub: `aggg15` y `abcb5-ua`, con acceso de escritura.
Solo se publica la implementación de **spec-001**. La PR #1 sigue pendiente de revisión humana;
las PR #2 a #4 están cerradas y sus ramas remotas retiradas. No se recuperan esas implementaciones.

## Preparar una copia propia

Cada integrante trabaja en su clon y con sus secretos locales. Hasta integrar la PR #1, la base
ejecutable y la configuración compartida están en `spec-001`; `main` conserva la base documental.

```sh
git clone --branch spec-001 https://github.com/aggg15/huellas-protectora.git
cd huellas-protectora
php tools/configure.php --test
composer install
npm ci
npm run build
docker compose --env-file .env.local up -d --wait database
php bin/console doctrine:migrations:migrate --no-interaction
```

Arranque web, cuentas internas y pruebas: [README](README.md). No se copian contraseñas de otro
integrante ni se cargan datos reales de animales o personas para las comprobaciones.

## Spec Kit ya está inicializado

El repositorio incluye las skills, scripts, plantillas y manifiestos de Spec Kit **1.0.11**.
No hay que ejecutar init después de clonar. Para utilizar también el CLI, con Python 3.11+ y uv:

```sh
uv tool install 'specify-cli==1.0.11'
specify integration status
bash tools/spec-context.sh
bash .specify/scripts/bash/check-prerequisites.sh --json --require-spec --require-tasks --include-tasks
```

Abrir esta carpeta en Codex. Las skills del repositorio se detectan en `.agents/skills`; si no
aparecen, reabrir el proyecto. Se invocan escribiendo `$speckit-analyze`, `$speckit-converge`, etc.
Esto sigue la [documentación oficial de skills de Codex](https://learn.chatgpt.com/docs/build-skills).

Para la historia actual, un ejemplo de solicitud es:

```text
$speckit-analyze Revisa specs/spec-001 con su plan y tareas, siguiendo AGENTS.md.
Conserva los CA-01-01 a CA-01-05 y el plan común. Registra únicamente hallazgos reales.
```

La secuencia docente sigue siendo specify → clarify → plan → tasks → analyze → implement → converge.
Las primeras fases de HU-01 ya tienen documentos y código: inicializar la herramienta no significa
volver a escribirlos ni atribuir retroactivamente ejecuciones automáticas a los informes anteriores.
Converge puede añadir tareas correctivas justificadas, sin borrar las tareas completadas.

## Selección de historia

`bash tools/spec-context.sh` detecta la rama `spec-00N` y guarda el directorio correspondiente
en `.specify/feature.json`. Este archivo es propio de cada clon y no se sube a GitHub. El script
rechaza ramas ajenas al convenio y carpetas sin spec; no crea ramas, no borra documentos y no autoriza
a implementar historias futuras. En CI con HEAD separado se indica `spec-001` explícitamente.

El plan y el modelo comunes están en la raíz. `specs/spec-001/plan.md` incorpora el contexto de la
historia y los referencia para que los scripts de Spec Kit encuentren los documentos esperados.
La instalación usa el núcleo de Spec Kit; Git se gestiona con los comandos normales del equipo,
sin hooks que creen ramas numeradas genéricas o commits de forma automática.

## Publicar y revisar

1. Antes de editar, coordinar quién toca los archivos comunes y revisar `git status`.
2. Actualizar la rama con `git pull --ff-only origin spec-001` cuando la copia esté limpia.
3. Hacer un cambio delimitado, revisar `git diff`, pasar las comprobaciones afectadas y crear un
   commit descriptivo, por ejemplo `fix(spec-001): validar la fecha de recepción`.
4. Publicar con `git push origin spec-001`. Si Git rechaza el push porque hay trabajo del compañero,
   recuperar y conciliar los cambios; no usar force push ni sobrescribir commits ajenos.
5. El compañero revisa el diff de la [PR #1](https://github.com/aggg15/huellas-protectora/pull/1),
   comprueba al menos un criterio de aceptación y registra su revisión en GitHub.
6. Integrar solo con la aprobación exigida y las comprobaciones correctas. Después, ambos
   actualizan su `main`. Las siguientes historias se preparan cuando se autoricen y se acuerde su base.

`main` exige al menos una aprobación, también al administrador. Se bloquean force push y borrado;
no se cambia la configuración para eludir la revisión. No se crea una aprobación atribuida a Codex.

## Comprobaciones automáticas

La acción `CI de Huellas` comprueba instalación con lockfiles, assets, contexto Spec Kit,
sintaxis PHP, contenedor, Twig, esquema y las pruebas de HU-01 contra PostgreSQL efímero.
Se ejecuta al publicar `spec-001` y en PR hacia `main`. No utiliza secretos del ordenador de un
integrante, no despliega la aplicación y no aplica migraciones a una base compartida.

La plantilla de PR recoge historia, criterios, pruebas y revisión pendiente. Los nombres académicos,
el reparto de las otras siete historias y la ratificación de la Constitution se completarán por
ambos integrantes; los permisos GitHub no acreditan esos acuerdos docentes.

## Archivos compartidos y archivos locales

Se versionan código, migraciones, lockfiles, `AGENTS.md`, `.agents/skills`, infraestructura `.specify`,
especificaciones e informes. Se excluyen `.env*` privados, `.specify/feature.json`, dependencias,
cachés, fotos y copias de recuperación bajo `var/`. Las skills son instrucciones compartidas;
no se guardan sesiones ni credenciales de Codex en el repositorio.
