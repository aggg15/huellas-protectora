# Trabajo en equipo

Fecha: 16 de noviembre de 2026; estado actualizado el 21 de noviembre de 2026. Usuarios GitHub:
`aggg15` y `abcb5-ua`, con acceso de escritura. `main` integra HU-01 a HU-08 (PR #1, #5, #6, #8, #9, #10, #11 y #12).
Las PR #2 a #4 y #7 están cerradas; sus implementaciones no se recuperan.

## Preparar una copia propia

Cada integrante trabaja en su clon y con sus secretos locales. La base ejecutable y la configuración
compartida están en `main`; cada historia nueva se desarrolla en su rama `spec-00N` creada desde `main`.

```sh
git clone https://github.com/aggg15/huellas-protectora.git
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

Un ejemplo de solicitud, cambiando la historia por la que esté activa, es:

```text
$speckit-analyze Revisa specs/spec-00N con su plan y tareas, siguiendo AGENTS.md.
Conserva los CA-0N-XX existentes y el plan común. Registra únicamente hallazgos reales.
```

La secuencia docente sigue siendo specify → clarify → plan → tasks → analyze → implement → converge.
Las ocho historias (HU-01 a HU-08) ya tienen documentos y código integrados en `main`; inicializar la herramienta no
significa volver a escribirlos ni atribuir retroactivamente ejecuciones automáticas a los informes anteriores.
Converge puede añadir tareas correctivas justificadas, sin borrar las tareas completadas.

## Selección de historia

`bash tools/spec-context.sh` detecta la rama `spec-00N` y guarda el directorio correspondiente
en `.specify/feature.json`. Este archivo es propio de cada clon y no se sube a GitHub. El script
rechaza ramas ajenas al convenio y carpetas sin spec; no crea ramas, no borra documentos y no autoriza
a implementar historias futuras. En CI con HEAD separado se indica cada historia con `plan.md` explícitamente.

La instalación usa el núcleo de Spec Kit; Git se gestiona con los comandos normales del equipo,
sin hooks que creen ramas numeradas genéricas o commits de forma automática. Cada `specs/spec-00N/plan.md`
referencia el plan y el modelo comunes de la raíz.

## Publicar y revisar

1. Antes de editar, coordinar quién toca los archivos comunes y revisar `git status`.
2. Crear la rama de la historia desde `main` actualizado (`git switch -c spec-00N origin/main`) o,
   si ya existe, actualizarla con `git pull --ff-only origin spec-00N` cuando la copia esté limpia.
3. Hacer un cambio delimitado, revisar `git diff`, pasar las comprobaciones afectadas y crear un
   commit descriptivo, por ejemplo `fix(spec-005): calcular el seguimiento a fin de mes`.
4. Publicar con `git push -u origin spec-00N`. Si Git rechaza el push porque hay trabajo del compañero,
   recuperar y conciliar los cambios; no usar force push ni sobrescribir commits ajenos.
5. Abrir una PR hacia `main` con la plantilla del repositorio. El compañero revisa el diff, comprueba
   al menos un criterio de aceptación y registra su revisión en GitHub.
6. Integrar solo con la aprobación exigida y las comprobaciones correctas. Después, ambos
   actualizan su `main` antes de empezar la historia siguiente.

El acuerdo del equipo es que cada cambio llega a `main` mediante una PR revisada por el otro integrante,
y que la protección de `main` exige al menos una aprobación, también al administrador. Se bloquean force push
y borrado; no se cambia la configuración para eludir la revisión. No se crea una aprobación atribuida a Codex.
La regla de GitHub se relajó el 2026-11-19: según la comprobación de la API de ese día, la protección de `main`
exigía 0 aprobaciones y no se aplicaba al administrador, y la PR #10 se integró sin ninguna revisión registrada.
La configuración actual no consta en el repositorio. Ambos integrantes deben restablecer la regla de una
aprobación; este documento no la da por cumplida mientras tanto.

## Comprobaciones automáticas

La acción `CI de Huellas` comprueba instalación con lockfiles, assets, contexto Spec Kit,
sintaxis PHP, contenedor, Twig, esquema y las pruebas `tests/Spec00NTest.php` de todas las historias
contra PostgreSQL efímero. Se ejecuta al publicar `main` o una rama `spec-001` a `spec-008` y en
PR hacia `main`. No utiliza secretos del ordenador de un
integrante, no despliega la aplicación y no aplica migraciones a una base compartida.

La plantilla de PR recoge historia, criterios, pruebas y revisión del otro integrante. El reparto observado de las ocho
historias y los nombres de los integrantes (Antonio García Gómez y Ana Bernabeu Castillo) constan en agrupamiento; la Constitution fue ratificada por ambos el 2026-11-21
(véase [agrupamiento](docs/agrupamiento.md)); los permisos GitHub no acreditan esos acuerdos docentes.

## Archivos compartidos y archivos locales

Se versionan código, migraciones, lockfiles, `AGENTS.md`, `.agents/skills`, infraestructura `.specify`,
especificaciones e informes. Se excluyen `.env*` privados, `.specify/feature.json`, dependencias,
cachés, fotos y copias de recuperación bajo `var/`. Las skills son instrucciones compartidas;
no se guardan sesiones ni credenciales de Codex en el repositorio.
