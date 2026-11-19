# Verificación manual de HU-07 (T718)

Fecha: 2026-11-19, 16:27:44–16:28:01 UTC (17:27–17:28 Europe/Madrid); repetida tras T721 a las 16:32:42–16:32:54 UTC.
Versión del código: rama `spec-007`, cambios de implementación del commit que añade esta carpeta (base edc7941).
Responsable: `aggg15`, con asistencia de Claude Code. Revisión de `abcb5-ua`: pendiente.

## Cómo se ha hecho

Mismo método que HU-05 y HU-06 ([spec-006-manual](../spec-006-manual/README.md)): **Google Chrome 148 real sin
interfaz**, manejado por Chrome DevTools Protocol con [`recorrido.mjs`](recorrido.mjs) (Node 25, sin dependencias),
en macOS. El script rellena formularios, pulsa teclas reales (Tab, Enter), cambia el tamaño de la ventana, guarda
capturas y anota lo esperado y lo observado. Las capturas se revisaron una a una después (véase la observación).

- Servidor: PHP 8.3.30 local con `php -d variables_order=EGPCS -S 127.0.0.1:8097 -t public public/router.php`,
  `APP_ENV=dev` y `DATABASE_URL` hacia una base propia, `huellas_manual_spec007_test`, migrada hasta
  Version20261119180000. No se usó la base normal ni la de las pruebas automáticas.
- Precondiciones ([`datos-ficticios.php`](datos-ficticios.php)): coordinadora; voluntarias Ana y Bruno; veterinaria;
  una limpieza dentro de 3 días con máximo 2 y una persona (Carla); un paseo dentro de 4 días de 10:00 a 12:00 con
  Bruno y otro paseo coincidente de 11:00 a 13:00. Contraseñas aleatorias fuera del repositorio. Todo ficticio.
- El turno del recorrido 1 lo crea la coordinadora: 20/11/2026 de 21:00 a 23:00, unas 27,5 horas después.
- Para el error de turno completo, el script ejecuta `datos-ficticios.php … ocupar <turno>` con la página de Ana
  abierta: otra voluntaria ocupa la última plaza antes de que Ana pulse Enter.
- Resultado detallado, con cada respuesta HTTP: [`resultado-2026-11-19.json`](resultado-2026-11-19.json).

## Recorrido 1 · satisfactorio (coordinadora → voluntaria en móvil → voluntario → ficha)

| Paso | Esperado | Observado | Resultado |
|---|---|---|---|
| Coordinadora, escritorio 1280 px: Tab desde el inicio | Se alcanza «Nuevo turno» | Foco tras 10 pulsaciones; Enter abre el formulario | OK |
| Crear paseo 20/11 21:00–23:00, mínimo 2, máximo 3 | Éxito; fila destacada «Faltan voluntarios: 2 para el mínimo» | Correcto ([captura 01](01-escritorio-coordinadora-turno-destacado.png)) | OK |
| Aviso de cabecera | «1 turno de las próximas 48 horas no cubre su mínimo» | Visible | OK |
| Ana en móvil (390 px): listado | Sin desbordar; turno destacado con «Apuntarme» | scrollWidth 390, borde derecho 374; destacado | OK con observación |
| Tab hasta «Apuntarme» del turno nuevo y Enter | 1 inscrita, «Te has apuntado», sigue destacado con 1 para el mínimo | 7 pulsaciones; correcto ([captura 02](02-movil-voluntaria-apuntada.png)) | OK |
| Bruno, escritorio: se apunta | Deja de destacarse, «Mínimo cubierto», sin aviso | Correcto ([captura 03](03-escritorio-voluntario-minimo-cubierto.png)) | OK |
| Bruno ve el paseo de 11:00 a 13:00 | «Coincide con otro de tus turnos», sin botón | Correcto | OK |
| Coordinadora: ficha del turno | 2 / 3 personas, 2 inscritas, 1 plaza; Ana y Bruno | Correcto ([captura 04](04-escritorio-ficha-turno.png)) | OK |

## Recorrido 2 · errores y permisos, móvil 390 px

| Paso | Esperado | Observado | Resultado |
|---|---|---|---|
| Ana ve la limpieza con 1 plaza libre | «Apuntarme» visible | Visible | OK |
| Otra persona ocupa la plaza; Ana pulsa Enter en «Apuntarme» | 409, «ya tiene cubierto su máximo de 2 personas», «Completo» sin botón | Correcto, sin desbordar ([captura 05](05-movil-rechazo-turno-completo.png)) | OK |
| Coordinadora baja el máximo a 1 con dos inscritas | 409, «el máximo no puede ser menor»; el turno no cambia | Correcto | OK |
| Alta con fin 11:00 < inicio 12:00 y máximo 2 < mínimo 3 | 422, `endTime` y `maxVolunteers` con `aria-invalid`, valores conservados | Correcto, sin desbordar ([captura 06](06-movil-alta-con-errores.png)) | OK |
| Veterinaria en `/admin/turnos` | 403 | 403 | OK |
| Persona anónima en `/admin/turnos` | Redirección a `/acceso` | Redirección | OK |

Resultado: 19/19 comprobaciones correctas.

## Observación de la revisión de capturas

1. En las capturas 02 y 05 (móvil), dentro de las filas apiladas, «(N plazas libres)» y la etiqueta «Te has apuntado»
   aparecen debajo de la etiqueta del campo («Inscritas», «Estado»), en la columna de etiquetas, en lugar de junto al
   valor. Es legible, pero desordenado. Las medidas de anchura no lo detectan porque nada sobresale. Pasa a converge
   como C1 (specs/spec-007/converge.md).

## Comprobación de T721 · filas apiladas en móvil (2026-11-19)

[`comprobacion-t721.mjs`](comprobacion-t721.mjs) mide, a 390 px y con la sesión de Ana, la posición de cada elemento
de las celdas del listado respecto a su celda (la columna de etiquetas mide 112 px).

| Medida | Antes (código de T711) | Después (T721) |
|---|---|---|
| Elementos en la columna de etiquetas | 5 («(N plazas libres)» ×4, «Te has apuntado») | 0 |
| scrollWidth / ventana | 390 / 390 | 390 / 390 |
| Captura | [07](07-movil-t721-antes.png) · [datos](07-movil-t721-antes.json) | [08](08-movil-t721-despues.png) · [datos](08-movil-t721-despues.json) |

Después se repitió el recorrido completo en una base recién preparada: 19/19 correctas
([`resultado-tras-t721.json`](resultado-tras-t721.json)); la vista de escritorio no cambia.
