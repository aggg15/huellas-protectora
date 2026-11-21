# Tareas spec-003

- [x] T301: concretar decisiones y criterios de HU-03.
- [x] T302: implementar dominio, persistencia y permisos según plan.md.
- [x] T303: implementar interfaz Twig/Bulma y comportamiento Hotwire.
- [x] T304: validar los criterios de aceptación con PostgreSQL y pruebas HTTP.
- [x] T305: registrar analyze, converge, resultados y commit.

Responsable académico y revisor: pendientes de identificación; desarrollo asistido por Codex a solicitud del usuario. No se atribuye revisión humana realizada. Actualización 2026-11-21: revisión de la PR #6 aprobada por `abcb5-ua` (según `docs/agrupamiento.md`).

## Ejecución del 2026-11-17

Encargo expreso del usuario (`aggg15`) de desarrollar y publicar spec-003; desarrollo asistido por
Claude Code. Desarrollada sobre `spec-002` (96f1929) y rebasada sobre `main` (890d2b3) tras integrar
la PR #5, sin conflictos; las pruebas se repitieron sobre la nueva base. Revisión de `abcb5-ua`: pendiente. Actualización 2026-11-21: integrada en `main` con la PR #6; aprobada por `abcb5-ua`.

- T301 → [clarify](clarify.md) y [spec](spec.md): identidad por cuenta (D06), abiertas (D07),
  seis respuestas del cuestionario (D08), animales disponibles (D10); CA-03-05 y CA-03-06 derivados.
- T302 → `AdoptionApplication`, migración `Version20261117090718` con CHECK, `ApplicationService`
  con `FOR UPDATE` de cuenta y animal, `AccountService`, controladores con `IsGranted` y CSRF.
- T303 → `application/*.html.twig`, `security/register.html.twig`, enlace desde la ficha pública
  de HU-02, navegación por rol y controlador Stimulus `questionnaire`.
- T304 → `tests/Spec003Test.php`: 11 pruebas / 127 aserciones correctas; regresión Spec001 7 / 32.
  Véase ../../docs/evidencias/spec-003-phpunit.txt.
- T305 → [analyze](analyze.md), [implement](implement.md), [converge](converge.md) y CI.

Resultado T304: correcto. La prueba Spec002 de promoción falla ya en `main` (890d2b3); se comunica
al responsable de HU-02 sin modificarla aquí. La comprobación visual móvil/escritorio queda pendiente.
