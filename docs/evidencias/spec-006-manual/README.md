# Verificación manual de HU-06 (T623)

Fecha: 2026-11-19, 14:16–14:19 UTC (15:16–15:19 Europe/Madrid). Versión del código: rama `spec-006`, cambios de
implementación del commit que añade esta carpeta (base 87a78a0). Responsable: `abcb5-ua`, con asistencia de
Claude Code. Revisión de `aggg15`: pendiente.

## Cómo se ha hecho

Mismo método que HU-05 ([spec-005-manual](../spec-005-manual/README.md)): **Google Chrome real sin interfaz**,
manejado por Chrome DevTools Protocol con [`recorrido.mjs`](recorrido.mjs) (Node 24, sin dependencias), en Windows.
El script rellena formularios, pulsa teclas reales (Tab, Enter), cambia el tamaño de la ventana, guarda capturas y
anota lo esperado y lo observado. Las capturas se revisaron una a una después (véanse las observaciones).

- Servidor: contenedor `huellas-php-ci:latest` con `php -d variables_order=EGPCS -S 0.0.0.0:8096 -t public
  public/router.php`, `APP_ENV=dev` y `DATABASE_URL` hacia una base propia, `huellas_manual_spec006_test`,
  migrada hasta Version20261119120000. No se usó la base normal ni la de las pruebas automáticas.
- Precondiciones ([`datos-ficticios.php`](datos-ficticios.php)): coordinadora; familia Encina (`ROLE_FOSTER`, sin
  condiciones); familia Álamo (`ROLE_FOSTER`, capacidad 1, disponible 60 días y ya con el animal C acogido);
  animales A (perro) y B (gato) disponibles, con foto. Contraseñas aleatorias fuera del repositorio. Todo ficticio.
- Un primer intento se detuvo en el paso 5 por tiempo de espera: el servidor de desarrollo tarda 5–10 s por
  petición sobre el volumen montado y la espera era de 15 s. Se borró la base, se repitieron los datos y se
  ejecutó con 60 s de espera. No se cambió código de la aplicación entre ambos intentos.
- Resultado detallado, con cada respuesta HTTP: [`resultado-2026-11-19.json`](resultado-2026-11-19.json).

## Recorrido 1 · satisfactorio (familia → coordinadora → web pública → novedad), escritorio 1280 px y móvil 390 px

| Paso | Esperado | Observado | Resultado |
|---|---|---|---|
| La familia Encina accede | Llega a «Mi acogida» (`/acogida`) | `/acogida` | OK |
| Estado inicial | «Todavía no has indicado tus condiciones» | Visible | OK |
| Capacidad 2 y periodo de hoy a +30 días | «0 de 2 plazas ocupadas» y el periodo en la tabla | Correcto ([captura 01](01-escritorio-familia-condiciones.png)) | OK |
| Coordinadora: `/admin/acogidas` | Familia Encina, 0 de 2, con su periodo | Correcto | OK |
| Registrar la acogida de A hasta +20 días | Mensaje de éxito; familia, inicio hoy y fin previsto | Correcto ([captura 02](02-escritorio-ficha-animal-en-acogida.png)) | OK |
| Visita anónima a `/animales` | A visible con «En casa de acogida» | Correcto ([captura 03](03-escritorio-catalogo-publico.png)) | OK |
| Familia en móvil: `/acogida` | Sin desplazamiento horizontal | El indicador dice que no lo hay; véase la observación 1 | OK con observación |
| Tab desde el inicio | Se alcanza el campo de la novedad | Foco en la novedad tras 6 pulsaciones | OK |
| Escribir la novedad, Tab, Enter | Foco en «Guardar novedad»; se guarda y se muestra | Correcto ([captura 04](04-movil-familia-novedad.png)) | OK |
| Coordinadora: ficha interna de A | Novedad con su autora | Correcto | OK |
| Ficha pública de A | Sin la novedad (D24) | Ausente | OK |

## Recorrido 2 · errores de estado y de datos, móvil 390 px

| Paso | Esperado | Observado | Resultado |
|---|---|---|---|
| Registrar B en la casa de Álamo (completa), enviando con Enter | 409, «ya atiende su máximo de 1 animal» | 409 y ese mensaje ([captura 05](05-movil-error-casa-completa.png)) | OK |
| Estado tras el error | B sin acogida; se conserva la fecha | Formulario visible; fecha conservada | OK |
| Ficha con el error en móvil | Sin desplazamiento horizontal | Sin desplazamiento | OK |
| Familia Álamo indica capacidad 0 | 422, campo marcado `aria-invalid` | 422, `aria-invalid="true"` ([captura 06](06-movil-familia-error-capacidad.png)) | OK |
| Recargar | Sigue «1 de 1 plaza ocupada» | Correcto | OK |
| Persona anónima en `/acogida` | Redirección al acceso | Redirección | OK |

Total: 17 comprobaciones, 17 correctas.

## Observaciones de la revisión de las capturas

1. **«Mi acogida» en móvil (HU-06).** A 390 px, las tarjetas de la página de la familia sobresalen unos 6 px
   por la derecha: el borde derecho queda cortado (capturas 04 y 06). La causa está en el formulario de alta de
   periodos: dos campos `date` en columnas `is-mobile`, con un ancho mínimo que ensancha la columna de la
   rejilla. La comprobación `scrollWidth <= innerWidth` no lo detecta porque, con la emulación móvil, el
   viewport de diseño se ensancha con el contenido. → Hallazgo C1 de [converge](../../../specs/spec-006/converge.md).
2. En la captura 03, la tarjeta de B aparece sin fotografía. Las imágenes del catálogo (HU-02) usan
   `loading="lazy"` y la captura de página completa se toma antes de cargarlas. No se ha comprobado más allá y
   no afecta a HU-06; la foto de A sí aparece.

## Comprobación de T626 · «Mi acogida» en móvil (2026-11-19)

Script [`comprobacion-t626.mjs`](comprobacion-t626.mjs), con el mismo servidor y la misma base. Accede como la
familia Encina y mide, a 390 px (móvil) y 1280 px, el borde derecho de cada tarjeta y el ancho del contenido.
No usa `scrollWidth <= innerWidth`, que la emulación móvil no detecta.

| Momento | 390 px | 1280 px | Resultado |
|---|---|---|---|
| Antes de T626 ([json](resultado-t626-antes.json), [captura 07](07-movil-t626-antes.png)) | borde derecho 396 px; contenido e `innerWidth` 396 px | 1213 px | FALLO a 390 px |
| Después de T626 ([json](resultado-t626-despues.json), [captura 08](08-movil-t626-despues.png)) | borde derecho 374 px; contenido 390 px | 1213 px | OK |

Cambio: el alta de periodos usa `columns` sin `is-mobile`, de modo que en móvil los dos campos de fecha se apilan.
