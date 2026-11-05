# Registro de revisión y refinamiento

## Método y punto de partida

Se releyeron exclusivamente los documentos actuales de Práctica Grupal. El punto inicial es
c065f7d y los hashes de las fichas iniciales están en `../evidencias/estado-inicial.json`.
Se formalizaron specs, decisiones y plan antes de escribir la aplicación. Las fases se aplican
por Codex; no se atribuyen resultados a comandos `/speckit.*` no ejecutados ni a otros asistentes.

El usuario aceptó cuentas por email, roles internos y cuarentena automática de 240 horas.
Las otras concreciones se justifican en `../decisiones.md`. Analyze contrasta los documentos;
la verificación y el contraste del código sirven de base al cierre de converge de cada historia.

## Hallazgos reales

| ID | Tipo / ámbito | Evidencia inicial | Corrección y verificación | Estado |
|---|---|---|---|---|
| H01 | Documental | El enunciado retirado seguía referenciado y no existía specs/ | Se preserva la sustitución de PDF hecha por el usuario, se actualizan fuentes, Constitution y estructura specs/. El informe de octubre queda como antecedente. | Corregido. |
| H02 | Funcional | Historias con decisiones abiertas de identidad, fechas y estados | Se documentan D01-D11 antes de implementar, con la base aceptada por el usuario. Criterios y servicios mantienen esas reglas. | Resuelto para HU-01 a HU-04. |
| H03 | Herramienta de prueba | El proceso independiente reutilizaba una caché sin ReviewService | Se habilita reconstrucción de caché en el worker de pruebas. [Resultado anterior](../evidencias/spec-004-inicial.txt); prueba de dos aprobaciones correcta en el resultado final. | Corregido. |
| H04 | Acceso / soporte común | El formulario con email no normalizado fallaba pese a existir la cuenta | UserRepository normaliza el identificador; el proveedor delega en el repositorio sin property fija. Se añade prueba de login real, sin sustituirlo por loginUser. | Corregido, 31 pruebas finales. |
| H05 | Ejecución HTTP | PHP -S no propagaba APP_ENV al contexto de Symfony con la configuración local | public/index.php respeta APP_ENV/APP_DEBUG del proceso antes de Runtime. Se usa entorno e2e con sesiones nativas, separado del mock de PHPUnit. El recorrido completo de navegador pasa. | Corregido. |
| H06 | Coherencia técnica | Doctrine proponía recrear índices parciales equivalentes | Se normaliza el predicado del mapeo a la expresión de PostgreSQL. schema:validate confirma sincronización sin modificar las reglas de unicidad. | Corregido. |
| H07 | Prueba E2E | Una aserción leía el listado anterior antes de terminar la navegación Turbo | Se espera la cabecera del cuestionario. No se cambia el comportamiento de la aplicación para satisfacer una espera incorrecta. | Corregido. |
| H08 | Presentación / configuración | En móvil se recortaba el texto de filtros; PHP estándar limitaba ficheros por debajo del límite acordado | Filtros a una columna en móvil y límites de subida explícitos de PHP. Capturas revisadas y configuración Docker comprobada. | Corregido. |

No se inventan incidencias para cumplir una cuota. Los problemas de las herramientas de prueba
se distinguen de los defectos funcionales. Las correcciones se registran en las tareas y la
matriz de trazabilidad; no se rebajan criterios para presentar resultados correctos.

## Cierre y pendientes

Converge final contrasta los 18 criterios de las cuatro historias con servicios, controladores,
restricciones y pruebas. No se conocen brechas accionables en ese alcance tras las comprobaciones
documentadas. Siguen pendientes la revisión cruzada humana, el reparto nominal y la integración
del código en main; HU-05 a HU-08, la memoria de todo el sistema y la demo final no se declaran
completas por esta iteración.
