# Implement · spec-004

ReviewService, ReviewController y vistas de coordinación implementan entrevista, rechazo motivado,
aprobación y reserva transaccionales, espera de alternativas y liberación de reservas fallidas.
Se conserva autora, fecha y versión; la reserva tiene índice parcial único y bloqueo del animal.

La aceptación incluye versiones obsoletas, entrevista ausente/futura, permisos, CSRF y dos procesos
que aprueban el mismo animal. El refinamiento común incorpora acceso real, configuración HTTP,
normalización del esquema y despliegue Docker. Ver ../../docs/verificacion.md.
