#!/usr/bin/env bash
# Prepara el entorno Docker de la demo, graba los vídeos, genera los mp4 y limpia los contenedores.
#   bash tools/demo/run.sh [ids de vídeo separados por comas, p. ej. 04,05]   (sin argumento: todos)
# Variables opcionales: WORK (directorio de trabajo, ./.demo-work), SLOW_MO (450), READ_FACTOR (1), TYPE_DELAY (28), CRF (30), KEEP=1 (no borrar contenedores).
# Requisitos: Docker, Node en el equipo y las imágenes huellas-ci-php (PHP 8.3 con pdo_pgsql, gd e intl), postgres:16-alpine,
# mcr.microsoft.com/playwright:v1.48.0-jammy y jrottenberg/ffmpeg:6.1-ubuntu. Todo se nombra con el sufijo -demo.
set -euo pipefail
export MSYS_NO_PATHCONV=1
REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
WORK="${WORK:-$REPO/.demo-work}"
NET=huellas-net-demo; PG=huellas-pg-demo; APP=huellas-app-demo
# En Windows (Git Bash) Docker necesita rutas de Windows para los montajes.
winpath() { if command -v cygpath >/dev/null 2>&1; then cygpath -w "$1"; else echo "$1"; fi; }
mkdir -p "$WORK/pw" "$WORK/out"
cleanup() { [ "${KEEP:-0}" = 1 ] || { docker rm -f "$APP" "$PG" huellas-pw-demo >/dev/null 2>&1 || true; docker network rm "$NET" >/dev/null 2>&1 || true; }; }
trap cleanup EXIT

docker network create "$NET" >/dev/null 2>&1 || true
docker rm -f "$APP" "$PG" >/dev/null 2>&1 || true
docker run -d --name "$PG" --network "$NET" -e POSTGRES_USER=huellas -e POSTGRES_PASSWORD=demo-secret -e POSTGRES_DB=huellas_demo postgres:16-alpine >/dev/null
# La aplicación se copia a /app dentro del contenedor (un montaje directo desde Windows hace cada petición de PHP 100 veces más lenta)
# y se sirve con opcache. APP_ENV=dev, base huellas_demo; seed.php se niega a trabajar con bases sin «demo» en el nombre.
docker run -d --name "$APP" --network "$NET" -v "$(winpath "$REPO"):/repo:ro" \
  -e APP_ENV=dev -e APP_DEBUG=0 -e APP_SECRET=demo-secret-not-for-production \
  -e 'DATABASE_URL=postgresql://huellas:demo-secret@huellas-pg-demo:5432/huellas_demo?serverVersion=16&charset=utf8' \
  -e PHP_CLI_SERVER_WORKERS=4 huellas-ci-php sh -c '
set -e
mkdir -p /app/var && cd /repo && cp -r bin config src templates public vendor migrations tools composer.json /app/
cd /app
php -d opcache.enable_cli=1 -d opcache.validate_timestamps=0 -d upload_max_filesize=5M -d post_max_size=32M -d max_file_uploads=20 -S 0.0.0.0:8000 -t public public/router.php &
exec php -d opcache.enable_cli=1 -S 0.0.0.0:8001 tools/demo/ctl.php' >/dev/null
echo "Esperando a la aplicación..."
until docker exec "$APP" php -r 'exit(@file_get_contents("http://127.0.0.1:8001/health")==="ok"?0:1);' 2>/dev/null; do sleep 2; done
until docker exec "$PG" pg_isready -U huellas -d huellas_demo >/dev/null 2>&1; do sleep 1; done
docker exec "$APP" php bin/console doctrine:migrations:migrate --no-interaction >/dev/null
docker exec "$APP" php tools/demo/seed.php --reset

# Playwright se instala fuera del repositorio.
if [ ! -d "$WORK/pw/node_modules/playwright" ]; then
  docker run --rm -v "$(winpath "$WORK/pw"):/pw" -w /pw mcr.microsoft.com/playwright:v1.48.0-jammy sh -c 'npm init -y >/dev/null && npm i playwright@1.48.0'
fi

docker run --rm --name huellas-pw-demo --network "$NET" --ipc=host \
  -v "$(winpath "$REPO/tools/demo"):/demo:ro" -v "$(winpath "$WORK/pw"):/pw:ro" -v "$(winpath "$WORK/out"):/out" -w /demo \
  -e PLAYWRIGHT_MODULE=/pw/node_modules/playwright/index.mjs -e BASE_URL=http://huellas-app-demo:8000 -e CTL_URL=http://huellas-app-demo:8001 -e OUT_DIR=/out \
  -e SLOW_MO="${SLOW_MO:-450}" -e READ_FACTOR="${READ_FACTOR:-1}" -e TYPE_DELAY="${TYPE_DELAY:-28}" \
  mcr.microsoft.com/playwright:v1.48.0-jammy node record.mjs "${1:-}"

node "$REPO/tools/demo/postproceso.mjs" "$WORK/out" "$WORK/demo"
node "$REPO/tools/demo/informe.mjs" "$WORK/out" "$WORK/verificacion-integrada.md" || true
echo "Listo: $WORK/demo/index.html"
