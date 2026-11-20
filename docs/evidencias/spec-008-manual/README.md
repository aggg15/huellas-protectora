# Verificación manual de HU-08 (T818)

Fecha: 2026-11-19, 18:13:10–18:13:26 UTC (19:13 Europe/Madrid). Una ejecución anterior (18:04 UTC, 17/17) se repitió
porque después se renombró la migración (sin cambios de SQL); se conservan solo las capturas de la ejecución final.
Versión del código: rama `spec-008`, cambios de implementación del commit que añade esta carpeta (base 5e379e6).
Responsable: `abcb5-ua`, con asistencia de Claude Code. Revisión de `aggg15`: pendiente.

## Cómo se ha hecho

Mismo método que HU-05 a HU-07 ([spec-007-manual](../spec-007-manual/README.md)): **Google Chrome 157 real sin
interfaz**, manejado por Chrome DevTools Protocol con [`recorrido.mjs`](recorrido.mjs) (Node 24, sin dependencias),
en Windows 11. El script escribe con eventos de teclado reales (caracteres, Tab, Enter), cambia el tamaño de la
ventana, guarda capturas y anota lo esperado y lo observado. Las capturas se revisaron una a una después.

- Servidor: contenedor `php:8.3-cli` (PHP 8.3.37) con `php -d variables_order=EGPCS -S 0.0.0.0:8098 -t public
  public/router.php`, publicado en 127.0.0.1:8098, `APP_ENV=dev` y `DATABASE_URL` hacia una base propia,
  `huellas_manual_spec008_test` (PostgreSQL 16 en contenedor), migrada hasta Version20261119190000. No se usó la base
  normal ni la de las pruebas automáticas. Los recursos de `public/vendor` (Bulma, Turbo, Stimulus) se copiaron de
  `npm run build`: en un primer intento faltaban y las páginas salían sin estilos, por lo que se repitió.
- Precondiciones ([`datos-ficticios.php`](datos-ficticios.php)): coordinadora; madrina Lucía (cuenta pública);
  voluntario; tres animales disponibles (el primero en casa de acogida). Contraseñas aleatorias fuera del repositorio.
  Todo ficticio.
- La adopción se formaliza a mitad del recorrido con `datos-ficticios.php … adoptar <animal>`, que crea una solicitud
  aprobada de otra cuenta ficticia y llama a `AdoptionService::formalize` (HU-05), como haría la coordinadora.
- Resultado detallado, con cada respuesta HTTP: [`resultado-2026-11-19.json`](resultado-2026-11-19.json).

## Recorrido 1 · satisfactorio (madrina en móvil → coordinadora → adopción → elección)

| Paso | Esperado | Observado | Resultado |
|---|---|---|---|
| Madrina, 390 px: Tab desde el inicio en la ficha pública | Se alcanza «Apadrinar a HUE-…» | Foco tras 8 pulsaciones; Enter abre el formulario | OK |
| Formulario en móvil | Sin desbordar | scrollWidth 390, borde derecho 350 | OK |
| Escribir «9,99» y Enter (CA-08-02) | 422, error del importe, valor conservado, `aria-invalid` | Correcto ([captura 01](01-movil-importe-no-valido.png)) | OK |
| Escribir «15,50» y Enter (CA-08-01) | «Ya apadrinas a … con 15,50 € al mes»; tarjeta «Activo» | Correcto ([captura 02](02-movil-apadrinamiento-activo.png)); sin desbordar | OK |
| Coordinadora, 1280 px: «Apadrinamientos» (D40) | Animal con Lucía y 15,50 € | Correcto | OK |
| Escribir la novedad (con `<b>`), Tab, Enter (CA-08-03) | Foco en «Publicar novedad»; éxito; en la lista | Correcto ([captura 03](03-escritorio-coordinadora-novedad.png)) | OK |
| Madrina: «Mis apadrinamientos» | La novedad, con fecha y escapada | Correcto | OK |
| Formalizar la adopción con HU-05 (CA-08-04) | «Pendiente de tu decisión», fecha de adopción, aportación en pausa | Correcto ([captura 04](04-movil-decision-pendiente.png)); sin desbordar | OK |
| Opciones de continuidad | Los otros dos animales disponibles y «Terminar mi colaboración» | Correcto | OK |
| Elegir otro animal, Tab, Enter (CA-08-05) | Foco en «Apadrinar al animal elegido»; nuevo «Activo» con 15,50 €; anterior «Terminado · Cambio a otro animal (HUE-…)», con su novedad | Correcto ([captura 05](05-movil-eleccion-registrada.png)) | OK |

## Recorrido 2 · errores de permisos y estado

| Paso | Esperado | Observado | Resultado |
|---|---|---|---|
| Voluntario: ficha pública | Sin recuadro «Apadrinar» (D34) | Sin recuadro | OK |
| Voluntario: `/animales/{id}/apadrinar` (CA-08-10) | 403 | 403 | OK |
| Voluntario: `/admin/apadrinamientos` (CA-08-10) | 403 | 403 | OK |
| Anónimo: `/mis-apadrinamientos` (CA-08-10) | Página de acceso | `/acceso` | OK |
| Madrina: apadrinar el animal ya adoptado (CA-08-07) | 404 | 404 ([captura 06](06-escritorio-animal-adoptado-404.png)) | OK |

Resultado: **17/17 comprobaciones correctas**. Revisión visual de las seis capturas: textos completos, sin solapes ni
desbordes en 390 px y etiquetas de estado legibles. El foco por teclado se comprobó con el elemento activo (tabla
anterior), no en las capturas. Sin observaciones.
