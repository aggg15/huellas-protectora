# Verificación de HU-01 a HU-04

Fecha: 2026-11-05. Entorno: PHP 8.3.30, Symfony 7.4.23, Doctrine ORM, PostgreSQL 16 y Chromium
mediante Playwright 1.62.1. Todos los datos usados son ficticios.

## Hitos secuenciales

| Hito | Resultado observado antes de continuar | Evidencia |
|---|---|---|
| spec-001 | 7 pruebas, 32 aserciones correctas | [Resultado inicial](evidencias/spec-001-phpunit.txt) |
| spec-002 | 12 pruebas, 70 aserciones, incluida regresión | [Resultado](evidencias/spec-002-phpunit.txt) |
| spec-003 | 21 pruebas, 118 aserciones, incluida concurrencia | [Resultado](evidencias/spec-003-phpunit.txt) |
| spec-004 | 29 pruebas, 155 aserciones, incluida aprobación simultánea | [Resultado](evidencias/spec-004-phpunit.txt) |
| Refinamiento final | 31 pruebas, 169 aserciones; añade acceso real y límites de fotos/campos malformados | [Texto](evidencias/phpunit-final.txt), [JUnit](evidencias/phpunit-final.xml) |
| Navegador integrado | 11 comprobaciones correctas y ningún error JavaScript | [JSON](evidencias/browser/resultados.json) |

Los 403 y 404 esperados se registran en stderr por Symfony; PHPUnit comprueba esas respuestas
como resultados correctos. No se confunden con fallos de las suites.

## Escenarios y resultados

| Historia | Entrada y pasos | Esperado y observado |
|---|---|---|
| HU-01 | Voluntario crea ficha con foto PNG real y recepción actual | UUID/código único, datos persistidos y cuarentena. Foto visible al personal; anónimo no puede acceder a la ficha ni a la foto. |
| HU-01 | Comparar 239 h 59 min 59 s y 240 h desde la recepción | No disponible antes y disponible al límite; una reserva nunca se libera por cuarentena. |
| HU-01 | Veterinario registra fecha y notas; voluntario intenta la misma acción | Revisión vinculada al veterinario; la petición del voluntario se rechaza con 403. |
| HU-01 | Especie/edad inválida, fecha futura o fichero PHP renombrado a JPG | Formulario con error, sin crear ficha ni fotografía parcial. |
| HU-02 | Consultar sin cuenta; combinar especie, tamaño y rangos de edad | Solo coincidencias disponibles; límites en 12 y 84 meses; estado vacío cuando no hay resultados. |
| HU-02 | Abrir por ID un animal en cuarentena/reservado/adoptado | 404; no se ofrece en catálogo. Animal acogido disponible conserva su señal de acogida. |
| HU-03 | Registrar cuenta e introducir tres respuestas completas | Hash de contraseña, rol público, solicitud ligada a cuenta y animal. Email normalizado también al acceder. |
| HU-03 | Respuesta vacía, duplicado del mismo animal, tercera solicitud o CSRF ausente | 422/409/403 según el caso; se conservan las solicitudes válidas y el formulario recuperable. |
| HU-03 | Tres procesos independientes envían solicitudes de una misma cuenta | Exactamente dos éxitos y un rechazo; nunca tres abiertas. Esperas y aprobadas cuentan; rechazadas no. |
| HU-03 | Coordinadora lee cuestionario; voluntario intenta leerlo | Cuestionario completo para la coordinadora y 403 para el voluntario. Cada solicitante solo consulta los suyos. |
| HU-04 | Registrar entrevista; aprobar con entrevista no futura | Reserva del animal y solicitud aprobada; las otras activas esperan y las rechazadas siguen rechazadas. |
| HU-04 | Rechazar sin motivo, aprobar sin entrevista o usar versión antigua | 422 o 409; ningún cambio parcial ni sobrescritura. El rechazo válido conserva motivo, autora y fecha. |
| HU-04 | Dos procesos aprueban candidaturas del mismo animal | Un éxito y un conflicto; una aprobada, una en espera y un animal reservado. Índice único de PostgreSQL como segunda garantía. |
| HU-04 | Liberar una reserva fallida con motivo | Candidatura cancelada, esperas pendientes y animal nuevamente disponible. |

## Navegador y capturas

Se usa una base `_test` separada, con sesiones HTTP nativas en el entorno `e2e`. Los fixtures son
ilustraciones y cuentas ficticias, no datos reales. El recorrido enlaza las cuatro historias:
alta y revisión → catálogo → solicitud → entrevista → reserva/rechazo. Incluye el error de la
tercera solicitud, permisos en HTTP y desaparición del animal reservado del catálogo.

- [Ficha en cuarentena](evidencias/browser/01-ficha-cuarentena.png).
- [Catálogo de escritorio](evidencias/browser/02-catalogo-escritorio.png).
- [Catálogo móvil](evidencias/browser/03-catalogo-movil.png), sin desbordamiento horizontal.
- [Límite de solicitudes](evidencias/browser/04-limite-solicitudes.png).
- [Aprobación y reserva](evidencias/browser/05-aprobacion.png).

También se verifican carga local de Turbo/Stimulus, previsualización de fotos, contador del
cuestionario, confirmación de decisiones y ausencia de errores JavaScript.

## Comprobaciones técnicas

Composer valida el manifiesto y fija dependencias en lock; los YAML, las vistas Twig y el
contenedor de servicios pasan sus validadores. `doctrine:schema:validate` confirma que mapeo y
esquema están sincronizados. Los índices parciales usan la expresión normalizada de PostgreSQL
para evitar diferencias falsas del comparador de Doctrine.

Se ha construido la imagen web y arrancado con PostgreSQL en Docker. El puerto 8080 estaba
ocupado por un servicio ajeno y se eligió 8086, sin detenerlo. La construcción directa con
`docker build` evita el fallo del generador de Compose al normalizar tildes de la ruta.
Los límites PHP de fotos se fijan explícitamente en `docker/php.ini`.
La [comprobación Docker](evidencias/docker-final.txt) registra ambos servicios saludables,
el proceso web sin privilegios, el entorno `prod`, el catálogo HTTP 200 y la página 404 en español.

## Alcance de las evidencias

La matriz recoge 18 criterios del alcance actual comprobados y 20 de HU-05 a HU-08 pendientes.
No se afirma revisión del compañero, pruebas de carga, identidad legal de solicitantes,
ejecución de comandos slash no instalados, integración en main ni despliegue público de la app.
La aplicación Docker escucha solo en la interfaz local. Los documentos iniciales y el PDF de
octubre se conservan como antecedentes y no sustituyen los resultados actuales en specs/.
