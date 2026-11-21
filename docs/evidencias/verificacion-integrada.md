# Verificación integrada (enunciado, apartado 5.4)

Recorridos completos que conectan historias de los dos integrantes (`aggg15`: HU-01, HU-03, HU-05, HU-07; `abcb5-ua`: HU-02, HU-04, HU-06, HU-08). Cada recorrido se ha ejecutado de verdad con Playwright 1.48 (Chromium) contra la aplicación en marcha y está grabado en vídeo; las aserciones de la columna «Resultado observado» son las del propio script `tools/demo/guiones-d.mjs`.

- **Versión del código** (`git rev-parse HEAD`): `7f69a58175f52a8f1f671e2d203dcf5d18767780` (rama `revision-entrega`).
- **Código realmente grabado**: la grabación se hizo el 2026-11-21 sobre una copia del árbol en el commit `114b74d` (el último que tocaba `src/`, `templates/`, `config/` y `migrations/` en ese momento). Desde entonces `HEAD` solo añade `7f69a58` (`ShiftService`: un turno empezado se rechaza con 409 antes de validar los datos), que no interviene en R1 ni en R2, y el commit `542f822` (documentación).
- Sin cambios sin confirmar en `src/`, `templates/`, `config/` ni `migrations/` al grabar.
- **Entorno**: contenedores Docker (`huellas-ci-php` con PHP 8.3, `postgres:16-alpine`, `mcr.microsoft.com/playwright:v1.48.0-jammy`), `APP_ENV=dev`, base propia `huellas_demo`. Cómo repetirlo: `tools/demo/README.md`.

## R1 · Recorrido satisfactorio

Vídeo: `video/09-flujo-satisfactorio.mp4` (duración 180 s). Historias cruzadas: HU-01 (aggg15) → HU-02 (abcb5-ua) → HU-08 (abcb5-ua) → HU-03 (aggg15) → HU-04 (abcb5-ua) → HU-05 (aggg15) → HU-08 (abcb5-ua).

### Precondiciones

- Base `huellas_demo` recién cargada con `tools/demo/seed.php --reset` (PostgreSQL 16, `APP_ENV=dev`, servidor integrado de PHP 8.3).
- Cuentas ficticias `@example.org` con la contraseña de demostración: voluntaria, veterinario, coordinadora, madrina Paula y animales de demostración (entre ellos `HUE-DISPON000001`, perro cachorro disponible).
- La cuenta de la solicitante (Rosa Registrada) no existe: se crea durante el recorrido desde la ficha pública (HU-03).

### Datos de prueba

- Animal nuevo: gata, pequeña, 14 meses, recepción registrada 11 días antes (registro tardío, de modo que la cuarentena de diez días ya está cumplida), una fotografía generada.
- Revisión veterinaria: «Revisión: vacunas puestas, sin hallazgos».
- Apadrinamiento: 12,00 € al mes. Solicitud: piso de 70 m², experiencia con perros, 2 a 4 horas diarias. Entrevista el mismo día. Cuota de adopción: 90,00 €.

### Pasos

1. HU-01 · Alta del animal por la voluntaria y revisión veterinaria
2. HU-02 · El animal aparece en el catálogo público y se filtra
3. HU-08 · Una madrina apadrina al animal con 12,00 € al mes
4. HU-03 · Una persona crea su cuenta y envía la solicitud
5. HU-04 · La coordinadora entrevista y aprueba: el animal queda reservado
6. HU-05 · Formalización: contrato, cuota y seguimientos
7. HU-03/05 · La adoptante ve su adopción y su contrato
8. HU-08 · La madrina recibe la decisión pendiente y elige otro animal

### Resultado esperado

El animal se da de alta y queda disponible, se ve y se filtra en el catálogo público, se apadrina, recibe una solicitud, se aprueba (reservado, fuera del catálogo), se formaliza (contrato, dos seguimientos, adoptado, ficha pública 404), la adoptante ve su contrato y la madrina recibe la decisión pendiente y elige otro animal con la misma aportación.

### Resultado observado

**22 de 22 comprobaciones correctas.**

| Paso | Id | Comprobación | Esperado | Observado | Resultado |
|---|---|---|---|---|---|
| 1 | R1-01 | HU-01: la ficha se crea con código propio | Código HUE-… y aviso «Ficha creada» | HUE-55363F6D83DF · Ficha creada. Su disponibilidad respeta los diez días de cuarentena. | OK |
| 1 | R1-02 | HU-01: con la cuarentena ya cumplida el animal queda «Disponible» | Aparece «Disponible» | Disponible… | OK |
| 1 | R1-03 | HU-01: la revisión queda en la ficha | Aparece «Vicente Veterinario» | …6 21:14 Revisión: vacunas puestas, sin hallazgos. Apta para adopción. Vicente Veterinario… | OK |
| 2 | R1-04 | HU-02: el animal nuevo aparece en el catálogo sin iniciar sesión | Aparece «HUE-55363F6D83DF» | …ven (< 12 meses) Adulto (1 a 6 años) Senior (7+ años) Filtrar Limpiar HUE-55363F6D83DF Gato Pequeño 1 año y 2 m Gata encontrada en un almacén ficticio; registrada con retraso t… | OK |
| 2 | R1-05 | HU-02: el filtro gato + pequeño lo incluye y no hay perros | Tarjeta del animal nuevo y 0 perros | 1 tarjeta del animal nuevo, 0 perros | OK |
| 3 | R1-06 | HU-08: apadrinamiento activo con 12,00 € | Aparece «Ya apadrinas a HUE-55363F6D83DF con 12,00 € al mes» | Ya apadrinas a HUE-55363F6D83DF con 12,00 € al mes. ¡Gracias!… | OK |
| 4 | R1-07 | HU-03: solicitud enviada | Aparece «Solicitud enviada. La coordinadora valorará tu candidatura para HUE-55363F6D83DF» | Solicitud enviada. La coordinadora valorará tu candidatura para HUE-55363F6D83DF.… | OK |
| 4 | R1-08 | HU-03: figura pendiente y cuenta 1 de 2 | Aparece «1 de 2 solicitudes abiertas» | …a para HUE-55363F6D83DF. MI CUENTA Mis solicitudes de adopción Tienes 1 de 2 solicitudes abiertas. Ver animales en adopción Animal Fecha de envío Estado Detalle / Seguimiento HUE-55363F6D… | OK |
| 5 | R1-09 | HU-03/04: la coordinadora ve el cuestionario completo | Aparece «Piso de 70 m²» | …a las 21:15. Pendiente de valoración Vivienda Tipo Piso o apartamento Piso de 70 m² con balcón, dos adultos y sin otros animales; el contrato de alquiler permite mascotas. E… | OK |
| 5 | R1-10 | HU-04: candidatura aprobada | Aparece «Candidatura aprobada. El animal ha quedado reservado» | Candidatura aprobada. El animal ha quedado reservado y las demás solicitudes han pasado a espera.… | OK |
| 5 | R1-11 | HU-04: el animal figura «Reservado» | Aparece «Reservado» | Reservado… | OK |
| 5 | R1-12 | HU-02/04: el animal reservado desaparece del catálogo | No aparece «HUE-55363F6D83DF» | No aparece «HUE-55363F6D83DF» en la página | OK |
| 6 | R1-13 | HU-05: contrato generado | Aparece «Se ha generado el contrato CTR-55363F6D83DF» | Adopción formalizada. Se ha generado el contrato CTR-55363F6D83DF y se han programado los dos seguimientos.… | OK |
| 6 | R1-14 | HU-05: cuota abonada 90,00 € | Aparece «90,00 €» | 90,00 €… | OK |
| 6 | R1-15 | HU-05: seguimiento al mes el 21/12/2026 | Aparece «21/12/2026» | Seguimiento Fecha prevista Estado Seguimiento al mes 21/12/2026 Programado Seguimiento a los seis meses 21/05/2027 Programado… | OK |
| 6 | R1-16 | HU-05: seguimiento a los seis meses el 21/05/2027 | Aparece «21/05/2027» | …Seguimiento al mes 21/12/2026 Programado Seguimiento a los seis meses 21/05/2027 Programado… | OK |
| 6 | R1-17 | HU-02/05: la ficha pública del animal adoptado ya no existe | HTTP 404 | HTTP 404 | OK |
| 7 | R1-18 | HU-05: Rosa ve «Adopción formalizada» | Aparece «Adopción formalizada» | HUE-55363F6D83DF 21/11/2026 21:15 Adopción formalizada ¡Adopción formalizada! Entrega del 21/11/2026. Ver contrato CTR-55363F6D83DF… | OK |
| 7 | R1-19 | HU-05: puede abrir su contrato | Aparece «rosa@example.org» | …ón de adopciones Persona adoptante Rosa Registrada Email de la cuenta rosa@example.org Animal adoptado Código HUE-55363F6D83DF Especie Gato Edad aproximada en la entrega 1 año … | OK |
| 8 | R1-20 | HU-08: «Pendiente de tu decisión» con la aportación en pausa | Aparece «Pendiente de tu decisión» | HUE-55363F6D83DF Pendiente de tu decisión Aportación mensual 12,00 € Desde 21/11/2026 ¡HUE-55363F6D83DF ha encontrado familia! Fue … | OK |
| 8 | R1-21 | HU-08: nuevo apadrinamiento con la misma aportación | Aparece «Ahora apadrinas a HUE-DISPON000001 con 12,00 € al mes» | Ahora apadrinas a HUE-DISPON000001 con 12,00 € al mes.… | OK |
| 8 | R1-22 | HU-08: el anterior termina con «Cambio a otro animal» | Aparece «Cambio a otro animal» | …do Aportación mensual 12,00 € Desde 21/11/2026 Terminado 21/11/2026 · Cambio a otro animal (HUE-DISPON000001) Novedades Todavía no hay novedades de este periodo.… | OK |

Ejecutado el 2026-11-21T20:16:53.998Z.

## R2 · Recorrido con errores, estados y permisos

Vídeo: `video/10-flujo-con-error.mp4` (duración 167 s). Historias cruzadas: HU-03 (aggg15) → HU-04 (abcb5-ua) → HU-02 (abcb5-ua) → HU-05 (aggg15) → HU-08 (abcb5-ua).

### Precondiciones

- Base `huellas_demo` recién cargada con `tools/demo/seed.php --reset`.
- Samuel Solicitante ya tiene dos solicitudes abiertas (gato y perro grande). Tres candidaturas pendientes (Silvia, Sergio y Sonia) para la gata `HUE-DISPON000004`.
- Cuentas de voluntaria, solicitante, coordinadora, madrina y solicitantes con la contraseña de demostración.

### Datos de prueba

- Solicitud sobre el cachorro (tercera de Samuel) y sobre el gato (repetida).
- Aprobación de Silvia sin entrevista y con ella; aprobación de Sergio (en espera) sobre el animal ya reservado.
- Formalización de Silvia con cuota 75,50 €, primero sin confirmar el abono y después confirmándolo.

### Pasos

1. HU-03 · Tercera solicitud y solicitud repetida: bloqueadas por el límite
2. HU-04 · Aprobar sin entrevista y aprobar con entrevista: el animal queda reservado
3. HU-04 · Aprobar a otra persona sobre el animal ya reservado: rechazado
4. HU-02/03 · El animal reservado no se ofrece ni admite solicitudes
5. HU-04/05 · Permisos: voluntariado, solicitante y anónimo ante la gestión
6. HU-05 · Formalizar sin confirmar el abono se rechaza; con abono se completa
7. HU-05 · Otra persona no puede abrir el contrato
8. HU-08 · El animal adoptado no se apadrina; el personal tampoco

### Resultado esperado

La tercera solicitud y la repetida se rechazan y se conservan las dos existentes; aprobar sin entrevista y aprobar sobre un animal ya reservado se rechazan (422 y 409); el animal reservado no está en el catálogo y su ficha y su solicitud dan 404; el voluntariado, las solicitantes y las personas sin sesión no acceden a la gestión (403 o acceso); formalizar sin confirmar el abono se rechaza; otra cuenta no abre el contrato (404); un animal adoptado no se apadrina (404) y el personal tampoco (403).

### Resultado observado

**23 de 23 comprobaciones correctas.**

| Paso | Id | Comprobación | Esperado | Observado | Resultado |
|---|---|---|---|---|---|
| 1 | R2-01 | Precondición: Samuel tiene 2 de 2 solicitudes abiertas | Aparece «2 de 2 solicitudes abiertas» | MI CUENTA Mis solicitudes de adopción Tienes 2 de 2 solicitudes abiertas. Ver animales en adopción Animal Fecha de envío Estado Detalle / Seguimiento HUE-DISPON00… | OK |
| 1 | R2-02 | HU-03: la tercera solicitud se rechaza por el límite | Aparece «Ya tienes dos solicitudes abiertas» | No se puede enviar la solicitud. Ya tienes dos solicitudes abiertas. Solo podrás presentar otra cuando alguna se resuelva o se cancele. Consultar mis solicit… | OK |
| 1 | R2-03 | HU-03: repetir un animal ya solicitado se rechaza | Aparece «Ya tienes una solicitud abierta para este animal» | No se puede enviar la solicitud. Ya tienes una solicitud abierta para este animal. Consultar mis solicitudes… | OK |
| 1 | R2-04 | HU-03: las dos solicitudes existentes se conservan | 2 elementos | 2 elementos | OK |
| 2 | R2-05 | HU-04: aprobar sin entrevista se rechaza (422) | Aparece «imprescindible haber realizado y registrado previamente la entrevista» | Para aprobar la solicitud es imprescindible haber realizado y registrado previamente la entrevista (con fecha no futura).… | OK |
| 2 | R2-06 | HU-04: aprobada y animal reservado | Aparece «Reservado» | Reservado… | OK |
| 2 | R2-07 | HU-04: Sergio pasa a «En espera» | Sergio En espera | Sergio Solicitante En espera | OK |
| 3 | R2-08 | HU-04: se rechaza con un mensaje claro de animal ya reservado | Aparece «El animal ya está reservado para otra candidatura» | El animal ya está reservado para otra candidatura.… | OK |
| 3 | R2-09 | HU-04: la respuesta es 409 | HTTP 409 | HTTP 409 | OK |
| 4 | R2-10 | HU-02: la gata reservada no está en el catálogo | No aparece «HUE-DISPON000004» | No aparece «HUE-DISPON000004» en la página | OK |
| 4 | R2-11 | HU-02: su ficha pública da 404 | HTTP 404 | HTTP 404 | OK |
| 4 | R2-12 | HU-03: su formulario de solicitud da 404 | HTTP 404 | HTTP 404 | OK |
| 5 | R2-13 | Permiso: la voluntaria no abre /admin/solicitudes | HTTP 403 | HTTP 403 | OK |
| 5 | R2-14 | Permiso: la voluntaria no abre /admin/seguimientos | HTTP 403 | HTTP 403 | OK |
| 5 | R2-15 | Permiso: una solicitante no abre el refugio | HTTP 403 | HTTP 403 | OK |
| 5 | R2-16 | Permiso: sin sesión se pide acceder | La ruta contiene «/acceso» | Ruta /acceso | OK |
| 6 | R2-17 | HU-05: sin confirmar el abono no se formaliza | Aparece «Confirma que se ha abonado la cuota» | Confirma que se ha abonado la cuota de adopción.… | OK |
| 6 | R2-18 | HU-05: HTTP 422 y el animal sigue reservado | HTTP 422 | HTTP 422 | OK |
| 6 | R2-19 | HU-05: con el abono confirmado se formaliza | Aparece «Adopción formalizada» | Adopción formalizada. Se ha generado el contrato CTR-DISPON000004 y se han programado los dos seguimientos.… | OK |
| 6 | R2-20 | HU-05: la solicitud en espera de Sergio queda cancelada con motivo | Aparece «Motivo: El animal ha sido adoptado» | HUE-DISPON000004 18/11/2026 21:16 Cancelada Motivo: El animal ha sido adoptado… | OK |
| 7 | R2-21 | HU-05: una cuenta que no es la titular no ve el contrato (404) | HTTP 404 | HTTP 404 | OK |
| 8 | R2-22 | HU-08: un animal adoptado no se apadrina (404) | HTTP 404 | HTTP 404 | OK |
| 8 | R2-23 | HU-08: el personal no apadrina (403) | HTTP 403 | HTTP 403 | OK |

Ejecutado el 2026-11-21T20:19:41.600Z.

