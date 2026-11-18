# Decisiones funcionales y alcance de Huellas

Fecha: 2026-11-05. El usuario ha aceptado como base catálogo público, cuentas con email/contraseña,
roles internos y diez periodos de 24 horas de cuarentena. Las precisiones restantes son decisiones
de implementación documentadas, no frases atribuidas al enunciado ni acuerdos inventados del compañero.

Después de consultar al profesor, el usuario limita esta publicación a **spec-001**. Las decisiones
de HU-02 a HU-04 se conservan como planificación futura, sin publicar su implementación. Se retiran
sus ramas remotas y se cierran sus PR; el trabajo posterior se conserva únicamente en local.

| ID | Decisión | Motivo / historias |
|---|---|---|
| D01 | Cuarentena de 240 horas desde recepción UTC; promoción automática al consultar o por comando. | Evitar ambigüedad de medianoches y cambios horarios; HU-01/02. |
| D02 | Revisión: veterinario, fecha real y notas. No bloquea la liberación al día diez si falta el registro. | El enunciado ordena disponibilidad pasada la cuarentena, sin definir otra condición; HU-01. |
| D03 | Código HUE generado; especie perro/gato; edad aproximada en meses, tamaño pequeño/mediano/grande. | Permite identidad y filtros sin usar datos personales; HU-01/02. |
| D04 | Una a seis fotos JPEG/PNG/WebP, máximo 5 MiB cada una; ficheros protegidos y comprobados por contenido. | Verificar las fotografías requeridas sin publicar la cuarentena; HU-01. |
| D05 | Filtros combinados con AND; edad en intervalos: menos de 12 meses, 12 a 83 y desde 84. | Concreción verificable del catálogo; HU-02. |
| D06 | Cuenta con email único normalizado y contraseña; el catálogo sigue abierto. Identifica una cuenta, no acredita identidad legal de una persona. | Aplicar el máximo sin confiar en un email libre en cada formulario; HU-03. |
| D07 | Pendiente, en espera y aprobada cuentan como abiertas; rechazada y cancelada no. Duplicados abiertos del mismo animal no se admiten. | Respeta el máximo de dos sin favorecer acumulación por reservas; HU-03/04. |
| D08 | Cuestionario obligatorio: vivienda, experiencia previa (incluida ninguna) y disponibilidad diaria; nombre en la cuenta. | Tres temas de la narrativa, sin introducir un perfilado automático; HU-03. |
| D09 | Entrevista con fecha/hora y notas; la coordinadora debe haber registrado una entrevista no futura para aprobar. Puede rechazar antes, con motivo. | Representar revisión y entrevista antes de aprobación; HU-04. |
| D10 | No se reciben nuevas solicitudes para un animal reservado. Al aprobar, las otras pendientes pasan a espera; las rechazadas no se reabren. | El catálogo ofrece solo animales realmente disponibles; HU-03/04. |
| D11 | Si falla una reserva, la coordinadora puede liberarla con motivo: candidata cancelada, esperas a pendientes, animal disponible. | Dar sentido a la espera descrita sin implementar la formalización de HU-05; HU-04. |

Actualización del 2026-11-17: el usuario encarga expresamente desarrollar y publicar **spec-003**
(HU-03) a continuación de HU-02, ya integrada en `main` mediante la PR #5. Para aplicar D06 se añade un alta pública mínima
(`/registro`: nombre, email y contraseña) que solo crea cuentas `ROLE_USER`. La concreción de los
campos del cuestionario de D08 está en specs/spec-003/clarify.md. No cambia ninguna decisión anterior.

La autenticación es soporte común, no una novena historia. El alcance no incluye verificación de
email, recuperación de contraseña, servicios externos ni administración pública de roles.
Las cuentas de personal se crean por comando; las credenciales locales se excluyen de Git.

Actualización del 2026-11-18: HU-02 a HU-04 están integradas en `main` (PR #5, #6 y #8). Las
concreciones de HU-02 y HU-04 están en specs/spec-002/clarify.md y specs/spec-004/clarify.md y
aplican D03, D05 y D07 a D11 sin cambiarlas. El usuario (`aggg15`) asume HU-05; sus decisiones
se registran a partir de D12. Las tablas anteriores se conservan como historial.

## Reparto y material vigente

El nuevo enunciado v1 mezcla cuatro integrantes/dos historias con dos integrantes/cuatro historias.
Se conserva la guía específica de dos personas. El reparto observado y los encargos vigentes están en
agrupamiento.md; los nombres académicos siguen pendientes y la revisión cruzada consta en cada PR.
La nueva fuente es Practica-SDD-enunciado_v1_IR.pdf y sustituye al archivo anterior retirado por el
usuario. El informe de octubre y el PDF de historias son antecedentes fechados, no evidencia de la
implementación actual. La revisión actual deja de usar Práctica 1 como fuente.

El enunciado v1 requiere specs/ y docs/agrupamiento.md y docs/memoria.md, además de dos ZIP finales
(proyecto y vídeo, máximo conjunto 100 MB). Esta iteración prepara la aplicación y documentación
de las historias integradas; no declara terminadas HU-05 a HU-08, la memoria ni la demo final.
