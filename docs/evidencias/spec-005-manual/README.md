# Verificación manual de HU-05 (T525)

Fecha: 2026-11-19, 00:08–00:09 (Europe/Madrid). Versión del código: commit ca68017 de la rama `spec-005`.
Responsable: `aggg15`, con asistencia de Claude Code. Revisión de `abcb5-ua`: pendiente.

## Cómo se ha hecho

En la sesión no había ninguna herramienta de navegador disponible para el asistente. Por eso el recorrido
se ejecutó en **Google Chrome real sin interfaz**, manejado por Chrome DevTools Protocol con
[`recorrido.mjs`](recorrido.mjs) (Node 25, sin dependencias). El script rellena formularios, pulsa
teclas reales (Tab, Espacio, Enter), cambia el tamaño de la ventana, emula la impresión, guarda capturas
y anota lo esperado y lo observado. Las capturas se revisaron una a una después (véanse las observaciones).

- Servidor: `php -d variables_order=EGPCS -S 127.0.0.1:8095 -t public public/router.php` con `APP_ENV=dev`
  y `DATABASE_URL` hacia una base propia, `huellas_manual_spec005_test`, migrada hasta Version20261118230000.
  Hace falta `variables_order=EGPCS`: con el valor por defecto de PHP (`GPCS`), Symfony no ve la variable
  y usa `.env.local`. Un primer intento sin esa opción leyó la base normal `huellas`, sin escribir nada
  (0 animales y 0 cuentas `example.test` antes y después). Se detuvo y se repitió con la base propia.
- Precondiciones ([`datos-ficticios.php`](datos-ficticios.php)): cuenta de coordinadora y otra familia
  (contraseñas aleatorias fuera del repositorio); animal A (perro, disponible, en acogida, con foto);
  animal B (gato, reservado por una solicitud aprobada con entrevista). Todos los datos son ficticios.
- Resultado detallado, con cada respuesta HTTP: [`resultado-2026-11-19.json`](resultado-2026-11-19.json).

## Recorrido 1 · satisfactorio (HU-02 → HU-03 → HU-04 → HU-05), escritorio 1280 px y móvil 390 px

| Paso | Esperado | Observado | Resultado |
|---|---|---|---|
| Visita anónima a `/animales` | Aparece el animal A | Aparece HUE-08345B16744D | OK |
| «Acceder para adoptar» → alta en `/registro` | Vuelve al formulario de solicitud | `/animales/{A}/solicitud` | OK |
| Enviar cuestionario completo | Pendiente de valoración | Pendiente de valoración | OK |
| Coordinadora: entrevista (hace 1 h) y aprobación | Aparece «Formalizar la entrega»; animal reservado | Formulario visible; Reservado ([captura 01](01-escritorio-formalizar-solicitud-aprobada.png)) | OK |
| Formalizar con «120,00» y abono confirmado | Éxito, contrato CTR-…, 120,00 € | CTR-08345B16744D, 120,00 € | OK |
| Planificación | Dos seguimientos programados, 1 y 6 meses | 19/12/2026 y 19/05/2027 ([captura 02](02-escritorio-adopcion-y-seguimientos.png)) | OK |
| `/admin/seguimientos` | 2 filas por fecha | 2 filas | OK |
| Contrato (coordinadora) e impresión | Botón Imprimir; al imprimir se ocultan cabecera y botones | Correcto ([captura 03](03-impresion-contrato.png)) | OK |
| Adoptante en móvil: «Mis solicitudes» | Estado «Adopción formalizada» y enlace al contrato | Presentes en el HTML; véase la observación 1 ([captura 04](04-movil-mis-solicitudes.png)) | OK con observación |
| Adoptante abre su contrato en móvil | Datos del animal y del adoptante, sin desplazamiento horizontal | Correcto ([captura 05](05-movil-contrato.png)) | OK |
| Adoptante pide `/admin/seguimientos` | 403 | 403 | OK |
| Visita anónima tras la adopción | Animal ausente del catálogo; ficha 404 | Ausente; 404 | OK |

## Recorrido 2 · error de estado y teclado (HU-04 → HU-05), móvil 390 px

| Paso | Esperado | Observado | Resultado |
|---|---|---|---|
| Ficha de la solicitud aprobada de B | Sin desplazamiento horizontal | Sin desplazamiento | OK |
| Tab desde el inicio de la página | Se alcanza el campo de cuota | Foco en `#fee` tras 10 pulsaciones | OK |
| Escribir «120,00», Tab, Tab | Orden cuota → casilla → botón | `feePaid`, `btn-formalize` | OK |
| Enter sin marcar la casilla | 422, error junto a la casilla, importe conservado | 422, «Confirma que se ha abonado la cuota de adopción.», «120,00» ([captura 06](06-movil-error-sin-abono.png)) | OK |
| Estado tras el error | Animal reservado, solicitud aprobada | Reservado; formulario disponible | OK |
| Espacio sobre la casilla y Enter en el botón | Formalización completada | Página de la adopción | OK |

Total: 20 comprobaciones, 20 correctas.

## Observaciones de la revisión de las capturas

1. **«Mis solicitudes» en móvil (HU-03/04, afecta a CA-05-08).** A 390 px la tabla supera el ancho y se
   desplaza dentro de su contenedor: el estado aparece cortado («Adopción fo…») y la columna con el
   motivo o el enlace al contrato queda fuera de la vista hasta deslizar la tabla. La página no tiene
   desplazamiento horizontal, así que la comprobación automática no lo detecta. → Hallazgo C1 de
   [converge](../../../specs/spec-005/converge.md).
2. **«Liberar reserva (D11)» (HU-04).** El botón desborda por la derecha la tarjeta de decisión en
   escritorio y en móvil (capturas 01 y 06). El marcado es el de cdb0416, anterior a HU-05; se comunica
   a `abcb5-ua` sin añadir tarea en HU-05.

## Repetir

```sh
# Base propia terminada en _test, migrada; después:
DATABASE_URL=… APP_ENV=dev php docs/evidencias/spec-005-manual/datos-ficticios.php "$PWD" > /tmp/seed.json
DATABASE_URL=… APP_ENV=dev php -d variables_order=EGPCS -S 127.0.0.1:8095 -t public public/router.php &
node docs/evidencias/spec-005-manual/recorrido.mjs /tmp/salida /tmp/seed.json http://127.0.0.1:8095
```

## Comprobación de T528 · «Mis solicitudes» apilada en móvil (2026-11-19)

Código: rama `spec-005` con T528–T530 sin publicar todavía (posterior a 191e98c). Mismo servidor y base que antes.
[`datos-ficticios-t528.php`](datos-ficticios-t528.php) añade a «Otra Familia Ficticia» una solicitud
cancelada con el motivo «El animal ha sido adoptado». Así la misma cuenta tiene una fila formalizada
(animal B, del recorrido 2) y una fila con motivo. Script: [`recorrido-t528.mjs`](recorrido-t528.mjs);
resultado: [`resultado-t528-2026-11-19.json`](resultado-t528-2026-11-19.json).

| Paso | Esperado | Observado | Resultado |
|---|---|---|---|
| 390 px: desplazamiento horizontal de la página y del contenedor | Ninguno | Ninguno | OK |
| 390 px: estado y detalle de cada fila dentro de la ventana y sin recortes | 2 filas visibles | 2/2 | OK |
| 390 px: fila formalizada | Enlace al contrato visible | Visible | OK |
| 390 px: fila cancelada | «Motivo: El animal ha sido adoptado» visible | Visible | OK |
| 390 px: etiqueta de cada dato | Animal, Fecha de envío, Estado, Detalle | Las cuatro | OK |
| 1280 px | Tabla con cabecera, como antes | Tabla | OK |

Capturas: [07 móvil](07-movil-mis-solicitudes-apiladas.png) (comparar con la [04](04-movil-mis-solicitudes.png))
y [08 escritorio](08-escritorio-mis-solicitudes.png). En la primera captura, la etiqueta de estado ocupaba
todo el ancho de la celda. Se alineó a la izquierda (`justify-self:start`), se repitió el script (6/6) y se
revisó la captura definitiva. La observación 1 queda resuelta. La observación 2 sigue pendiente de HU-04.
