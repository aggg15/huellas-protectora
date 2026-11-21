# Tareas spec-001

- [x] T101: concretar decisiones y criterios de HU-01.
- [x] T102: implementar dominio, persistencia y permisos según plan.md.
- [x] T103: implementar interfaz Twig/Bulma y comportamiento Hotwire.
- [x] T104: validar los criterios de aceptación con PostgreSQL y pruebas HTTP.
- [x] T105: registrar analyze, converge, resultados y commit.

Responsable académico y revisor: pendientes de identificación; desarrollo asistido por Codex a solicitud del usuario. No se atribuye revisión humana realizada.

Resultado T104: 7 pruebas / 32 aserciones correctas. Véase ../../docs/evidencias/spec-001-phpunit.txt. La revisión humana queda pendiente.

- [x] T106: limitar la publicación a spec-001 por la corrección de alcance del usuario tras consultar
  al profesor; actualizar README, plan y responsabilidades, y repetir las comprobaciones sobre
  una base de pruebas nueva sin modificar los datos anteriores.

Resultado T106: 7 pruebas / 32 aserciones, validadores de contenedor y Twig correctos y esquema
Doctrine sincronizado. Véase ../../docs/evidencias/spec-001-alcance-corregido.txt.

- [x] T107: inicializar Spec Kit 1.0.11 para Codex conservando la Constitution; preparar el contexto
  de spec-001, su plan compatible, instrucciones de equipo, configuración local reproducible y
  validación automática en PR. Acceso de abcb5-ua y revisión obligatoria en main verificados.

La preparación técnica y sus comprobaciones se registran en
../../docs/evidencias/preparacion-equipo-2026-11-16.txt y ../../docs/evidencias/equipo-2026-11-16.json.
Esta tarea no integra la PR #1 ni acredita aprobación del compañero.

## Phase 2: Revisión previa a la entrega (2026-11-21)

Origen: revisión de código previa a la entrega, hallazgos C1-05, C1-07, C1-14, C1-17 y C1-19.
Responsable de la ejecución: `aggg15` (asistido por Claude Code); revisión: pendiente en la PR de `revision-entrega`.

- [x] T108 Sustituir la prueba vacía de fotos privadas por `testPrivatePhotosAreOnlyServedForAvailableAnimalsOrStaff`, con PNG reales en el almacén de pruebas (anónimo y `ROLE_USER` reciben 404 en cuarentena y reservado, 200 en disponible; el personal recibe 200) (C1-05)
- [x] T109 Ampliar `testVeterinaryReviewAndRoleValidation` (veterinario, fecha y notas persistidos; fecha futura, anterior a la recepción, notas vacías, 5001 caracteres y CSRF inválido; límite de 5000 válido) y añadir `testVetCannotCreateAnimals` (C1-07)
- [x] T110 Fijar `date_default_timezone_set('UTC')` en `Kernel::__construct` y `tests/bootstrap.php`, con una prueba de zona UTC y de visualización en Europe/Madrid (C1-14)
- [x] T111 Cambiar el mensaje del comando `app:release-quarantine` a «Animales liberados: N» (C1-17)
- [x] T112 Usar `var/uploads_test` como almacén en `when@test` y vaciarlo en `WebTestBase::tearDown` sin tocar `var/uploads` (C1-19)
- [x] T113 Añadir `testReleaseDueAnimalsSqlBoundaryAt240Hours` para el límite de 240 h de la consulta SQL (C1-20a)
- [x] T114 Documentar en el README los límites de subida y sus efectos (C1-16, solo documentación)
