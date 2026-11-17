# Implement · spec-003

Fecha: 2026-11-17. Aplicado por Claude Code a petición del usuario; no se ha ejecutado la skill
`$speckit-implement`.

## Componentes

- **Entidad** `App\Entity\AdoptionApplication`: animal, solicitante, seis respuestas, estado y fecha
  de envío UTC. Constantes de estados abiertos y etiquetas en español.
- **Migración** `Version20261117090718`: tabla, claves ajenas, índices y CHECK de estados comunes y
  respuestas obligatorias. No modifica migraciones existentes.
- **Servicios**: `ApplicationService` (validación, recuento, motivo de rechazo y presentación
  transaccional con `SELECT … FOR UPDATE` de `app_user` y `animal`); `AccountService` (alta sin roles);
  excepción `ApplicationRefused` para los rechazos por estado.
- **Controladores**: `ApplicationController` (`/animales/{id}/solicitud`, `/mis-solicitudes`),
  `ApplicationReviewController` (`/admin/solicitudes`, `/admin/solicitudes/{id}`, `ROLE_COORDINATOR`),
  `AccountController` (`/registro`) y `SecurityController::dashboard` (`/panel`).
- **Vistas**: formulario por secciones con errores por campo y `aria-invalid`; «Mis solicitudes»;
  listado y detalle para la coordinadora; alta de cuenta; enlaces en cabecera, acceso y ficha pública.
- **Stimulus** `questionnaire`: la explicación de experiencia deja de ser obligatoria con «Ninguna».
- **CI**: paso «Criterios de aceptación de HU-03» y contexto Spec Kit de spec-003 si existen.

## Resultado

`vendor/bin/phpunit --filter Spec003 --testdox`: 11 pruebas, 127 aserciones correctas.
Regresión `--filter Spec001`: 7 pruebas, 32 aserciones correctas. Validadores de contenedor, Twig,
YAML y esquema correctos. Detalle y prueba de humo: ../../docs/evidencias/spec-003-phpunit.txt.
Las respuestas 403/404/409/422 esperadas aparecen en el log como errores HTTP comprobados.
