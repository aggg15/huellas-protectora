# Demostración en vídeo de Huellas

Herramientas para grabar, con Playwright y sobre la aplicación real, un vídeo por historia (HU-01 a HU-08), el acceso y dos
recorridos de verificación integrada (apartado 5.4 del enunciado). No modifica `src/`, `templates/`, `config/` ni `migrations/`.

## Contenido

| Fichero | Para qué sirve |
|---|---|
| `seed.php` | Carga datos ficticios y reproducibles: 17 cuentas `@example.org` (una por rol y varias solicitantes y padrinos), 11 animales en distintos estados (cuarentena de 2 días, cuarentena de más de 10 días, disponibles, en acogida, reservado y adoptado) con fotografías PNG dibujadas con GD, solicitudes, una casa de acogida, apadrinamientos y turnos. Solo funciona con `APP_ENV=dev` y una base cuyo nombre contenga `demo`. |
| `lib.php` | Dibujo de las fotografías ficticias (ilustraciones de perro y gato; no hay fotos reales). |
| `ctl.php` | Servidor de control interno (`/reset`, `/photo.png`) que usa la grabación para volver a cargar los datos antes de cada vídeo. |
| `record.mjs`, `grabador.mjs` | Grabación: vídeo 1280x720, rótulo con el título del escenario, cursor visible y registro del instante de cada escenario. |
| `guiones-a.mjs` … `guiones-d.mjs` | Guiones: 00 acceso, 01 a 08 (una por historia), 09 recorrido satisfactorio y 10 recorrido con errores. Cada comprobación (`g.check`) guarda lo esperado y lo observado. |
| `postproceso.mjs` | mp4 H.264 (ffmpeg en Docker), pósters, `demo-completa.mp4`, `datos.js` e `index.html`. |
| `informe.mjs` | Genera `docs/evidencias/verificacion-integrada.md` con los resultados reales de los recorridos 09 y 10. |
| `run.sh` | Orquesta todo con Docker y borra los contenedores al terminar. |
| `site/index.html` | Página de acceso a los vídeos (misma estructura que la del ejemplo del profesor). |

## Cuentas de demostración

Todas usan la contraseña `Huellas-Demo-2026` y el correo `<clave>@example.org`: `voluntaria`, `victor`, `valeria` (voluntariado),
`veterinario`, `coordinadora`, `brisa`, `nube` (familias de acogida), `sara`, `samuel`, `silvia`, `sergio`, `sonia`, `samanta`,
`sandra`, `adriana` (solicitantes y adoptante), `paula`, `pablo` (padrinos). Los animales tienen códigos fijos, p. ej.
`HUE-DISPON000001`.

## Ejecutarlo a mano (sin vídeo)

```sh
# Con la aplicación configurada (README principal) y una base propia, p. ej. huellas_demo:
createdb huellas_demo            # o la base que prefieras, con «demo» en el nombre
DATABASE_URL="postgresql://…/huellas_demo?serverVersion=16" APP_ENV=dev php bin/console doctrine:migrations:migrate --no-interaction
DATABASE_URL="postgresql://…/huellas_demo?serverVersion=16" APP_ENV=dev php tools/demo/seed.php --reset
```

`--json` imprime identificadores y contraseña; `--release-in=N` añade un animal cuya cuarentena termina dentro de N segundos.

## Grabar todo con Docker

```sh
bash tools/demo/run.sh          # todos los vídeos; resultado en .demo-work/demo/ (index.html, datos.js, video/)
bash tools/demo/run.sh 04,05    # solo algunos
```

Usa los contenedores `huellas-pg-demo`, `huellas-app-demo` y `huellas-pw-demo` y la red `huellas-net-demo` (se eliminan al terminar),
la imagen `huellas-ci-php`, `postgres:16-alpine`, `mcr.microsoft.com/playwright:v1.48.0-jammy` y `jrottenberg/ffmpeg:6.1-ubuntu`.
Playwright se instala en `.demo-work/pw` (fuera de Git; añade `.demo-work/` a tu `.git/info/exclude`). La aplicación se copia a
`/app` dentro del contenedor (un montaje directo desde Windows era muy lento) y se sirve con
`php -S 0.0.0.0:8000 -t public public/router.php`. Ritmo: `SLOW_MO` (ms entre acciones, 450), `READ_FACTOR` (multiplica las pausas
de lectura) y `TYPE_DELAY` (ms por tecla).

## Notas

- El vídeo 01 espera a que se cumplan las 240 horas de un animal creado con `--release-in=170`: el cambio a «Disponible» ocurre de verdad.
- En los formularios con validación del navegador, los escenarios de «datos no válidos» desactivan esa validación (`noValidate`) para
  mostrar la del servidor; el rótulo lo indica.
- CA no demostrables en vídeo (concurrencia, fin de mes, cambio de hora): quedan cubiertos por las pruebas automáticas (`tests/`).
