# Implement · spec-008

Fecha: 2026-11-19. Aplicado por Claude Code a petición de `abcb5-ua`. No se ha ejecutado la skill
`$speckit-implement`. Tareas: T805–T819 de [tasks.md](tasks.md), con [analyze](analyze.md).

## Componentes

- **Entidades.** `Sponsorship` (animal, cuenta, importe en céntimos, inicio, fin, motivo y apadrinamiento anterior;
  `END_REASON_LABELS`, `isPendingDecision`, `amountLabel`; único parcial `uniq_sponsorship_active` declarado en el mapeo)
  y `SponsorUpdate` (animal, autora, fecha y texto; índice `idx_sponsor_update_animal_written`). `User`, `Animal` y
  `Adoption` no cambian; no hay roles nuevos.
- **Migración** `Version20261119190000`: dos tablas, claves ajenas, único parcial, índices y CHECK de importe
  (1000–999999 céntimos), fin y motivo, fechas y longitud del texto. No modifica migraciones aplicadas. `down()` borra
  las dos tablas. Se generó con `doctrine:migrations:diff` sobre una base `_test` recién migrada (el diff solo contenía
  las tablas de HU-08) y se completó con los CHECK.
- **Servicio.** `SponsorshipService`: `amountCents`, `sponsor`, `finish`, `moveTo`, `addUpdate` y las consultas `mine`,
  `state`, `updatesFor`, `availableFor` y `overview`. Validación con `ValidationError` (422) y reglas con
  `SponsorshipRefused` (409), decididas con lecturas DBAL después de `FOR UPDATE` (cuenta → apadrinamiento → animal;
  solo el animal al publicar una novedad). Usa `AdoptionService::findByAnimal` (D18) para la fecha de la pausa.
- **Controladores.** `SponsorshipController` (`ROLE_USER`, 403 para `ROLE_STAFF` y `ROLE_FOSTER`, 404 para lo ajeno) y
  `SponsorshipAdminController` (`ROLE_COORDINATOR`). Los rechazos vuelven a mostrar la página con el motivo (422/409).
- **Vistas.** `sponsorship/new`, `sponsorship/mine`, `sponsorship/admin_index`; recuadro en `public/show.html.twig`;
  enlaces «Mis apadrinamientos» y «Apadrinamientos» en `base.html.twig`; estilos `.sponsorship-*` y `.sponsor-updates`.
- **Pruebas.** `tests/Spec008Test.php` (13 pruebas) y `tests/Support/sponsorship-race.php`. El CI las detecta por su
  nombre sin cambios en `ci.yml`, que ya incluía la rama `spec-008`.

## Decisiones tomadas durante la implementación

- **Rol comprobado también en el servicio.** El controlador responde 403 a las cuentas de trabajo, pero `sponsor` las
  rechaza además (`WORK_ROLES`): la prueba de permisos llamaba al servicio directamente y sin esa comprobación habría
  creado apadrinamientos de cuentas de personal. Se compara con los roles asignados porque la coordinadora y el resto
  del personal los tienen directamente (el comando `app:create-user` asigna uno).
- **Cuarentena vencida.** El POST de apadrinamiento llama antes a `AnimalService::getPublicAnimal`, que promociona las
  cuarentenas vencidas (D01), como el GET; si el animal no es público responde 409 con el formulario, no 404.
- **Estado «pendiente» derivado.** No hay columna ni cambio en HU-05: un apadrinamiento activo con el animal adoptado está
  pendiente. La formalización no bloquea la cuenta, así que no hay ciclos con el orden de HU-08.
- **Cambio de animal por SQL y entidad nueva.** El fin del anterior se escribe por SQL y se refresca la entidad (lección de
  HU-06 y HU-07); el nuevo se persiste con `previous`, que es único.
- **EntityManager cerrado tras un rechazo.** `wrapInTransaction` cierra el EntityManager al lanzar `SponsorshipRefused`.
  En HTTP no afecta (cada petición tiene el suyo y la página de error solo consulta). En las pruebas de servicio, los
  rechazos se dejan al final o se obtiene un EntityManager nuevo.
- **Nombre de la migración.** El diff la nombró `Version20261119174701` (hora del contenedor), anterior a la de HU-07; la
  comprobación down/up de T819 lo detectó (`prev` deshacía HU-07) y se renombró a `Version20261119190000` sin cambiar su SQL.

## Resultado

`vendor/bin/phpunit --testdox`: 112 pruebas y 1301 aserciones correctas (Spec008 13/13; Spec001–Spec007 sin cambios en
sus pruebas). Esquema sincronizado, migraciones up/down/up y linters correctos. Las pruebas de concurrencia fallan con
las dos mutaciones registradas. Detalle: [evidencia](../../docs/evidencias/spec-008-phpunit.txt).
Verificación manual (T818): 17/17, sin observaciones ([evidencia](../../docs/evidencias/spec-008-manual/README.md)).

## Tareas de converge (2026-11-19)

- **T822.** `testOnlyPublicAccountsSponsorAndOnlyCoordinatorPublishes` comprueba también el 404 al cambiar un
  apadrinamiento ajeno.
- **T823.** Nueva prueba `testEmptyStatesAreExplained`: estado vacío de «Mis apadrinamientos» (con enlace al catálogo) y de
  la página de la coordinadora (un apadrinamiento terminado deja de figurar).
