// Guiones 05 (formalización), 06 (acogida), 07 (turnos) y 08 (apadrinamiento).
import { diaMadrid, ddmmyyyy, mesesDespues, madrid } from './grabador.mjs';
import { def, tarjeta, filaAdmin } from './guion-util.mjs';

// ====================================================================================================================
def({
  id: '05-formalizacion', spec: '005 · Formalización de la adopción', titulo: 'Formalizar la entrega: contrato, cuota y seguimientos', rol: 'Coordinadora y adoptante',
  historia: 'Como coordinadora quiero <b>formalizar la entrega mediante el contrato y el abono de la cuota</b> y dejar programados <b>los dos seguimientos</b> para registrar la adopción y mantener la atención posterior al animal.',
  escenarios: [
    { titulo: 'Solicitud aprobada: el formulario de formalización', ca: ['CA-05-01'] },
    { titulo: 'Cuota vacía, cero o sin confirmar el abono: no se formaliza', ca: ['CA-05-07'] },
    { titulo: 'Formalizar con 120,00 €: contrato y animal adoptado', ca: ['CA-05-01', 'CA-05-02'] },
    { titulo: 'Dos seguimientos programados: al mes y a los seis meses', ca: ['CA-05-03'] },
    { titulo: 'Contrato imprimible con las partes, el animal y la cuota', ca: ['CA-05-01'] },
    { titulo: 'El animal adoptado ya no está en la web pública', ca: ['CA-05-04'] },
    { titulo: 'Las demás solicitudes del animal quedan canceladas con motivo', ca: ['CA-05-08'] },
    { titulo: 'La persona adoptante ve su contrato; el de otra persona no', ca: ['CA-05-11'] },
    { titulo: 'Permisos: voluntariado y personas sin cuenta no ven los seguimientos', ca: ['CA-05-11'] },
  ],
}, async (g) => {
  const p = g.page;
  const A = g.d.animals;
  let contrato = null;
  await g.escenario(1, 'Coral abre la solicitud aprobada de Samanta para el perro RESERVA00001', async () => {
    await g.login('coordinadora');
    await g.click(g.nav('Solicitudes'));
    await g.click(filaAdmin(g, 'Samanta').getByRole('link'));
    await g.veTexto('05-1a', 'La solicitud figura «Aprobada» y el animal «Reservado»', 'Aprobada', p.locator('.page-heading .tag'));
    await g.veTexto('05-1b', 'Aparece «Formalizar la entrega»', 'Formalizar la entrega');
    await g.reveal(p.locator('#form-formalize'));
    await g.leer(2500);
  });
  await g.escenario(2, 'Primero con cuota 0 y sin marcar el abono; después con 12,345 €', async () => {
    await g.type(p.locator('#fee'), '0');
    await g.click(p.locator('#btn-formalize'));
    await g.leer(2800);
    await g.veTexto('05-2a', 'Cuota no válida', 'Indica el importe abonado de la cuota', p.locator('#error-fee'));
    await g.veTexto('05-2b', 'Falta confirmar el abono', 'Confirma que se ha abonado la cuota', p.locator('#error-feePaid'));
    await g.check('05-2c', 'No se formaliza: HTTP 422 y el animal sigue reservado', 'HTTP 422', async () => ({ ok: g.ultimoEstado() === 422, observado: `HTTP ${g.ultimoEstado()}` }));
    await g.type(p.locator('#fee'), '12,345');
    await g.click(p.locator('#feePaid'));
    await g.click(p.locator('#btn-formalize'));
    await g.leer(2800);
    await g.veTexto('05-2d', 'Más de dos decimales: se rechaza', 'dos decimales como máximo', p.locator('#error-fee'));
  });
  await g.escenario(3, 'Cuota 120,00 € con el abono confirmado: «Formalizar adopción y generar contrato»', async () => {
    await g.type(p.locator('#fee'), '120,00');
    await g.reveal(p.locator('#feePaid'));
    if (!(await p.locator('#feePaid').isChecked())) await g.click(p.locator('#feePaid'), { asentar: false });
    await g.leer(1200);
    await g.click(p.locator('#btn-formalize'));
    await g.leer(3000);
    contrato = (await g.ruta()).split('/').pop();
    await g.veTexto('05-3a', 'Aviso de adopción formalizada con el número de contrato', `Se ha generado el contrato CTR-${A.reservado.code.slice(4)}`, p.locator('[role=status]'));
    await g.veTexto('05-3b', 'La cuota abonada es 120,00 €', '120,00 €', p.locator('#fee'));
    await g.veTexto('05-3c', 'La adopción consta como «Adopción formalizada»', 'ADOPCIÓN FORMALIZADA');
  });
  await g.escenario(4, 'La planificación de seguimientos de la adopción', async () => {
    await g.reveal(p.locator('#follow-ups'));
    await g.leer(2000);
    await g.check('05-4a', 'Hay exactamente dos seguimientos', '2 filas', async () => {
      const n = await p.locator('#follow-ups tbody tr').count();
      return { ok: n === 2, observado: `${n} filas` };
    });
    await g.veTexto('05-4b', `Seguimiento al mes el ${ddmmyyyy(mesesDespues(1))}`, ddmmyyyy(mesesDespues(1)), p.locator('#follow-ups tbody tr').nth(0));
    await g.veTexto('05-4c', `Seguimiento a los seis meses el ${ddmmyyyy(mesesDespues(6))}`, ddmmyyyy(mesesDespues(6)), p.locator('#follow-ups tbody tr').nth(1));
    await g.nota('El listado general «Seguimientos» los ordena por fecha junto a los de otras adopciones');
    await g.click(g.nav('Seguimientos'));
    await g.check('05-4d', 'Los seguimientos programados salen ordenados por fecha', 'Fechas en orden ascendente', async () => {
      const fechas = await p.locator('#follow-ups tbody tr td:first-child').allInnerTexts();
      const iso = fechas.map((f) => f.trim().split('/').reverse().join('-'));
      return { ok: iso.length >= 4 && iso.every((f, i) => i === 0 || iso[i - 1] <= f), observado: fechas.map((f) => f.trim()).join(' · ') };
    });
    await g.leer(2200);
  });
  await g.escenario(5, '«Ver contrato»: documento imprimible con número único', async () => {
    await g.go(`/adopciones/${contrato}/contrato`);
    await g.leer(1500);
    await g.veTexto('05-5a', 'El contrato recoge el número y la fecha de entrega', `Contrato de adopción CTR-${A.reservado.code.slice(4)}`);
    await g.veTexto('05-5b', 'Nombra a la persona adoptante y su correo', 'samanta@example.org');
    await g.veTexto('05-5c', 'Nombra al animal', A.reservado.code);
    await g.veTexto('05-5d', 'Recoge la cuota abonada', '120,00 €');
    await g.reveal(p.locator('.contract section').nth(2));
    await g.leer(2500);
  });
  await g.escenario(6, 'Una persona anónima busca al perro adoptado', async () => {
    await g.logout();
    await g.go('/animales');
    await g.noVeTexto('05-6a', 'El catálogo ya no lo muestra', A.reservado.code);
    const st = await g.go(`/animales/${A.reservado.id}`);
    await g.leer(1600);
    await g.veEstado('05-6b', 'Su ficha pública: no encontrada', 404, st);
    const st2 = await g.go(`/fotos/${g.d.photos.reservado[0]}`);
    await g.veEstado('05-6c', 'Sus fotografías: no encontradas', 404, st2);
    await g.leer(1500);
  });
  await g.escenario(7, 'Sandra tenía una solicitud en espera para ese perro', async () => {
    await g.login('sandra');
    await g.leer(1500);
    await g.veTexto('05-7a', 'Su solicitud figura «Cancelada»', 'Cancelada', p.locator('tbody'));
    await g.veTexto('05-7b', 'Con el motivo «El animal ha sido adoptado»', 'Motivo: El animal ha sido adoptado', p.locator('tbody'));
    await g.veTexto('05-7c', 'Ya no cuenta como abierta (0 de 2)', '0 de 2 solicitudes abiertas');
    await g.leer(2200);
  });
  await g.escenario(8, 'Samanta ve su contrato; intenta abrir el de Adriana (otro adoptante)', async () => {
    await g.login('samanta');
    await g.leer(1200);
    await g.veTexto('05-8a', 'Ve «¡Adopción formalizada!»', 'Adopción formalizada', p.locator('tbody'));
    await g.click(p.getByRole('link', { name: /Ver contrato/ }));
    await g.veTexto('05-8b', 'Puede abrir su propio contrato', 'samanta@example.org');
    await g.leer(2200);
    const st = await g.go(`/adopciones/${g.d.adoptions.adoptado}/contrato`);
    await g.leer(2200);
    await g.veEstado('05-8c', 'El contrato de otra persona no se muestra (404)', 404, st);
  });
  await g.escenario(9, 'Vera, voluntaria, y una persona sin sesión intentan abrir /admin/seguimientos', async () => {
    await g.login('voluntaria');
    const st = await g.go('/admin/seguimientos');
    await g.leer(2200);
    await g.veEstado('05-9a', 'La voluntaria recibe acceso denegado', 403, st);
    await g.context.clearCookies();
    await g.go('/admin/seguimientos');
    await g.leer(1600);
    await g.veRuta('05-9b', 'Sin sesión se pide acceder', '/acceso');
  });
});

// ====================================================================================================================
def({
  id: '06-acogida', spec: '006 · Casas de acogida', titulo: 'Gestionar casas de acogida, capacidad y novedades', rol: 'Familia de acogida y coordinadora',
  historia: 'Como familia de acogida quiero <b>indicar mi disponibilidad y capacidad</b> y registrar las novedades de los animales que acojo; la coordinadora <b>registra las acogidas</b> respetando disponibilidad y capacidad.',
  escenarios: [
    { titulo: 'La Familia Nube indica su capacidad (valores fuera de rango se rechazan)', ca: ['CA-06-01', 'CA-06-06'] },
    { titulo: 'Periodos de disponibilidad: fechas no válidas y solapes se rechazan', ca: ['CA-06-01', 'CA-06-06'] },
    { titulo: 'La coordinadora ve las casas con su capacidad, ocupación y periodos', ca: ['CA-06-01'] },
    { titulo: 'Registrar la acogida de un cachorro: fuera de la disponibilidad se rechaza', ca: ['CA-06-02', 'CA-06-03'] },
    { titulo: 'Casa llena: no se admite otra acogida', ca: ['CA-06-03'] },
    { titulo: 'Acoger no cambia la cuarentena ni la visibilidad pública', ca: ['CA-06-04'] },
    { titulo: 'La familia anota novedades; la coordinadora las ve, la web pública no', ca: ['CA-06-05', 'CA-06-06'] },
    { titulo: 'Finalizar la acogida: el animal regresa y queda libre la plaza', ca: ['CA-06-08'] },
    { titulo: 'Permisos: voluntariado, solicitantes y anónimos', ca: ['CA-06-10'] },
  ],
}, async (g) => {
  const p = g.page;
  const A = g.d.animals;
  await g.escenario(1, 'Familia Nube entra en «Mi acogida»: todavía no tiene casa registrada', async () => {
    await g.login('nube');
    await g.veTexto('06-1a', 'Aterriza en «Mi acogida»', 'Todavía no has indicado tus condiciones');
    await g.type(p.locator('#capacity'), '0');
    await g.click(p.getByRole('button', { name: 'Guardar capacidad' }));
    await g.leer(2200);
    await g.veTexto('06-1b', 'Capacidad 0: se rechaza', 'de 1 a 10', p.locator('[role=alert]'));
    await g.type(p.locator('#capacity'), '11');
    await g.click(p.getByRole('button', { name: 'Guardar capacidad' }));
    await g.leer(2200);
    await g.veTexto('06-1c', 'Capacidad 11: se rechaza', 'de 1 a 10', p.locator('[role=alert]'));
    await g.type(p.locator('#capacity'), '1');
    await g.click(p.getByRole('button', { name: 'Guardar capacidad' }));
    await g.leer(2200);
    await g.veTexto('06-1d', 'Capacidad 1: se guarda', 'Capacidad guardada', p.locator('[role=status]'));
    await g.veTexto('06-1e', 'La casa muestra 0 de 1 plazas ocupadas', '0 de 1', p.locator('#occupancy'));
  });
  await g.escenario(2, 'Periodos: hasta antes de desde; después uno válido; después uno que se solapa', async () => {
    await g.fill(p.locator('#startsOn'), diaMadrid(20));
    await g.fill(p.locator('#endsOn'), diaMadrid(10));
    await g.click(p.getByRole('button', { name: 'Añadir periodo' }));
    await g.leer(2400);
    await g.check('06-2a', 'Fin anterior al inicio: se rechaza', 'Mensaje de error del periodo', async () => {
      const t = await g.texto(p.locator('[role=alert]'));
      return { ok: /fin|inicio|fecha/i.test(t), observado: t };
    });
    await g.fill(p.locator('#startsOn'), diaMadrid(0));
    await g.fill(p.locator('#endsOn'), diaMadrid(30));
    await g.click(p.getByRole('button', { name: 'Añadir periodo' }));
    await g.leer(2400);
    await g.veTexto('06-2b', 'Periodo válido: añadido', 'Periodo de disponibilidad añadido', p.locator('[role=status]'));
    await g.veTexto('06-2c', 'La tabla lo muestra', ddmmyyyy(diaMadrid(30)), p.locator('#periods'));
    await g.fill(p.locator('#startsOn'), diaMadrid(10));
    await g.fill(p.locator('#endsOn'), diaMadrid(40));
    await g.click(p.getByRole('button', { name: 'Añadir periodo' }));
    await g.leer(2600);
    await g.veTexto('06-2d', 'Periodo solapado: se rechaza', 'se solapa con otro periodo', p.locator('[role=alert]'));
  });
  await g.escenario(3, 'Coral abre «Acogidas»: Brisa (1 de 2 plazas) y Nube (0 de 1)', async () => {
    await g.login('coordinadora');
    await g.click(g.nav('Acogidas'));
    await g.veTexto('06-3a', 'La Familia Brisa tiene 1 de 2 plazas ocupadas', '1 de 2', p.locator('#homes tbody tr', { hasText: 'Familia Brisa' }));
    await g.veTexto('06-3b', 'La Familia Nube figura con 0 de 1 y su periodo', '0 de 1', p.locator('#homes tbody tr', { hasText: 'Familia Nube' }));
    await g.veTexto('06-3c', 'Acogidas en curso: la gata ya acogida', A.acogido.code, p.locator('#placements'));
    await g.reveal(p.locator('#homes'));
    await g.leer(2600);
  });
  await g.escenario(4, 'Ficha del cachorro: acogerlo en casa de Nube con fin a 45 días (fuera de disponibilidad) y a 20 días', async () => {
    await g.click(g.nav('Refugio'));
    await g.click(p.locator('a.animal-admin', { hasText: A.cachorro.code }));
    await g.reveal(p.locator('#form-foster-place'));
    const opcion = await p.locator('#foster-home option', { hasText: 'Familia Nube' }).getAttribute('value');
    await g.select(p.locator('#foster-home'), opcion);
    await g.fill(p.locator('#foster-planned-end'), diaMadrid(45));
    await g.click(p.getByRole('button', { name: 'Registrar acogida' }));
    await g.leer(3000);
    await g.veTexto('06-4a', 'Se rechaza: la casa no está disponible todos los días', 'no está disponible todos los días', p.locator('#foster-panel'));
    await g.reveal(p.locator('#form-foster-place'));
    await g.fill(p.locator('#foster-planned-end'), diaMadrid(20));
    await g.click(p.getByRole('button', { name: 'Registrar acogida' }));
    await g.leer(3000);
    await g.veTexto('06-4b', 'Aviso de acogida registrada', 'Acogida registrada en la casa de Familia Nube', p.locator('[role=status]'));
    await g.veTexto('06-4c', 'La ficha muestra la familia, el inicio y el fin previsto', ddmmyyyy(diaMadrid(20)), p.locator('#foster-active'));
    await g.reveal(p.locator('#foster-panel'));
    await g.leer(2200);
  });
  await g.escenario(5, 'Ficha del perro adulto: la casa de Nube ya atiende su máximo de un animal', async () => {
    await g.click(g.nav('Refugio'));
    await g.click(p.locator('a.animal-admin', { hasText: A.adulto.code }));
    await g.reveal(p.locator('#form-foster-place'));
    const opcion = await p.locator('#foster-home option', { hasText: 'Familia Nube' }).getAttribute('value');
    await g.select(p.locator('#foster-home'), opcion);
    await g.fill(p.locator('#foster-planned-end'), diaMadrid(10));
    await g.click(p.getByRole('button', { name: 'Registrar acogida' }));
    await g.leer(3000);
    await g.veTexto('06-5a', 'Se rechaza: la casa ya atiende su máximo', 'ya atiende su máximo de 1 animal', p.locator('#foster-panel'));
    await g.veCantidad('06-5b', 'El animal sigue sin acogida activa', p.locator('#foster-active'), 0);
  });
  await g.escenario(6, 'El animal en cuarentena entra en acogida con la Familia Brisa y sigue fuera del catálogo', async () => {
    await g.click(g.nav('Refugio'));
    await g.click(p.locator('a.animal-admin', { hasText: A.cuarentena.code }));
    await g.reveal(p.locator('#form-foster-place'));
    const opcion = await p.locator('#foster-home option', { hasText: 'Familia Brisa' }).getAttribute('value');
    await g.select(p.locator('#foster-home'), opcion);
    await g.fill(p.locator('#foster-planned-end'), diaMadrid(8));
    await g.click(p.getByRole('button', { name: 'Registrar acogida' }));
    await g.leer(2600);
    await g.veTexto('06-6a', 'Sigue «En cuarentena»', 'En cuarentena', p.locator('.page-heading .tag'));
    await g.veTexto('06-6b', 'Y consta «En casa de acogida»', 'En casa de acogida', p.locator('#foster-panel'));
    await g.logout();
    await g.go('/animales');
    await g.noVeTexto('06-6c', 'El catálogo público no lo muestra', A.cuarentena.code);
    await g.veTexto('06-6d', 'El cachorro ya acogido sí figura con «En casa de acogida»', 'En casa de acogida', tarjeta(g, A.cachorro.code));
    await g.reveal(tarjeta(g, A.cachorro.code));
    await g.leer(2400);
  });
  await g.escenario(7, 'Familia Brisa anota una novedad (primero vacía)', async () => {
    await g.login('brisa');
    await g.veTexto('06-7a', 'Ve los dos animales que acoge', A.acogido.code, p.locator('#animals-title').locator('xpath=..'));
    const formNota = p.locator('form[id^="form-note-"]', { hasText: A.acogido.code });
    await g.reveal(formNota);
    await g.sinValidacionNavegador(`form[id^="form-note-"]`);
    await g.click(formNota.getByRole('button', { name: 'Guardar novedad' }));
    await g.leer(2400);
    await g.veTexto('06-7b', 'Novedad vacía: se rechaza', 'Escribe la novedad', p.locator('[role=alert]'));
    await g.type(formNota.locator('textarea'), 'Duerme toda la noche y ya pasea sin miedo; ha dejado de esconderse con el timbre.', 12);
    await g.click(formNota.getByRole('button', { name: 'Guardar novedad' }));
    await g.leer(2800);
    await g.veTexto('06-7c', 'Novedad guardada', `Novedad anotada en la ficha de ${A.acogido.code}`, p.locator('[role=status]'));
    await g.veTexto('06-7d', 'Aparece en su página con la fecha', 'Duerme toda la noche', p.locator('.foster-animal', { hasText: A.acogido.code }));
    await g.nota('La coordinadora la ve en la ficha interna del animal');
    await g.login('coordinadora');
    await g.click(g.nav('Refugio'));
    await g.click(p.locator('a.animal-admin', { hasText: A.acogido.code }));
    await g.reveal(p.locator('#foster-notes'));
    await g.veTexto('06-7e', 'La ficha interna muestra la novedad y su autora', 'Familia Brisa', p.locator('#foster-notes'));
    await g.leer(2000);
    await g.nota('Y la ficha pública del animal no la muestra');
    await g.go(`/animales/${A.acogido.id}`);
    await g.noVeTexto('06-7f', 'La web pública no incluye la novedad', 'Duerme toda la noche');
    await g.leer(1500);
  });
  await g.escenario(8, 'Coral finaliza la acogida de la gata ACOGIDA00001', async () => {
    await g.go(`/admin/animales/${A.acogido.id}`);
    await g.reveal(p.locator('#form-foster-end'));
    await g.click(p.getByRole('button', { name: 'Finalizar acogida' }));
    await g.leer(2800);
    await g.veTexto('06-8a', 'Aviso de acogida finalizada', 'Acogida finalizada: el animal regresa al refugio', p.locator('[role=status]'));
    await g.veCantidad('06-8b', 'Ya no tiene acogida activa', p.locator('#foster-active'), 0);
    await g.veTexto('06-8c', 'Consta en «Acogidas anteriores» con el motivo «Regreso al refugio»', 'Regreso al refugio', p.locator('#foster-past'));
    await g.veTexto('06-8d', 'Las novedades anteriores siguen en el historial', 'Duerme toda la noche', p.locator('#foster-notes'));
    await g.reveal(p.locator('#foster-past'));
    await g.leer(2400);
    await g.nota('La familia deja de ver al animal');
    await g.login('brisa');
    await g.noVeTexto('06-8e', 'La Familia Brisa ya no acoge a la gata', A.acogido.code, p.locator('#animals-title').locator('xpath=..'));
    await g.leer(1800);
  });
  await g.escenario(9, 'Voluntaria, solicitante y persona sin sesión prueban las pantallas de acogida', async () => {
    await g.login('voluntaria');
    let st = await g.go('/acogida');
    await g.leer(1600);
    await g.veEstado('06-9a', 'Voluntaria en /acogida: acceso denegado', 403, st);
    await g.login('sara');
    st = await g.go('/admin/acogidas');
    await g.leer(1600);
    await g.veEstado('06-9b', 'Solicitante en /admin/acogidas: acceso denegado', 403, st);
    await g.context.clearCookies();
    await g.go('/acogida');
    await g.leer(1600);
    await g.veRuta('06-9c', 'Sin sesión en /acogida: se pide acceder', '/acceso');
  });
});

// ====================================================================================================================
def({
  id: '07-turnos', spec: '007 · Turnos de voluntariado', titulo: 'Turnos de paseo y limpieza', rol: 'Voluntariado y coordinadora',
  historia: 'Como voluntaria quiero <b>consultar los turnos de paseo y limpieza e inscribirme en los que tienen plazas</b> y detectar con antelación los turnos que necesitan más personas, destacados cuando faltan 48 horas.',
  escenarios: [
    { titulo: 'Listado de turnos: fecha, mínimo, máximo e inscritas; ficha con las personas', ca: ['CA-07-01'] },
    { titulo: 'Turno por debajo del mínimo a menos de 48 horas: destacado y aviso', ca: ['CA-07-04'] },
    { titulo: 'Apuntarse: la inscripción se registra y el turno alcanza el mínimo', ca: ['CA-07-02', 'CA-07-05'] },
    { titulo: 'Turno que coincide con otro: no se puede', ca: ['CA-07-09'] },
    { titulo: 'Turno completo: sin botón para apuntarse', ca: ['CA-07-03'] },
    { titulo: 'Turno en curso: sin inscripciones ni bajas', ca: ['CA-07-08'] },
    { titulo: 'Darse de baja: la plaza queda libre y el turno vuelve a destacarse', ca: ['CA-07-08'] },
    { titulo: 'La coordinadora crea turnos: datos no válidos y turno válido', ca: ['CA-07-06', 'CA-07-07'] },
    { titulo: 'Modificar un turno con inscripciones: solo mínimo y máximo', ca: ['CA-07-06'] },
    { titulo: 'Permisos: solicitantes, veterinaria, voluntariado y anónimos', ca: ['CA-07-11'] },
  ],
}, async (g) => {
  const p = g.page;
  const S = g.d.shifts;
  const fila = (id) => p.locator(`#shift-${id}`);
  await g.escenario(1, 'Vera, voluntaria, abre «Turnos»', async () => {
    await g.login('voluntaria');
    await g.click(g.nav('Turnos'));
    await g.leer(2000);
    await g.veTexto('07-1a', 'La tabla indica tipo, mínimo, máximo e inscritas del paseo de mañana', 'Paseo', fila(S.paseoManana));
    await g.check('07-1b', 'El paseo de mañana (mínimo 2, máximo 5) tiene 1 inscrita y 4 plazas libres', '1 (4 plazas libres)', async () => {
      const t = await g.texto(fila(S.paseoManana));
      return { ok: /1 \(4 plazas libres\)/.test(t), observado: t };
    });
    await g.click(fila(S.paseoManana).getByRole('link', { name: 'Paseo' }));
    await g.veTexto('07-1c', 'La ficha del turno nombra a la persona inscrita', 'Víctor Voluntario', p.locator('#signups'));
    await g.veTexto('07-1d', 'La ficha indica mínimo y máximo', 'Máximo admitido', p.locator('#shift-facts'));
    await g.leer(2200);
  });
  await g.escenario(2, 'El paseo de mañana (mínimo 2, 1 inscrita) empieza dentro de 48 horas', async () => {
    await g.click(g.nav('Turnos'));
    await g.veTexto('07-2a', 'Aparece destacado: «Faltan voluntarios: 1 para el mínimo»', 'Faltan voluntarios: 1 para el mínimo', fila(S.paseoManana));
    await g.veTexto('07-2b', 'Un aviso al principio cuenta los turnos de las próximas 48 horas que no cubren su mínimo', '2 turnos de las próximas 48 horas no cubren su mínimo', p.locator('#undercovered-notice'));
    await g.check('07-2c', 'El paseo de dentro de 5 días (mínimo 2, 1 inscrita) no se destaca todavía', 'Sin «Faltan voluntarios» en ese turno', async () => {
      const t = await g.texto(fila(S.paseoSemana));
      return { ok: !t.includes('Faltan voluntarios'), observado: t };
    });
    await g.reveal(fila(S.paseoManana));
    await g.leer(2600);
  });
  await g.escenario(3, 'Vera pulsa «Apuntarme» en el paseo de mañana', async () => {
    await g.click(fila(S.paseoManana).getByRole('button', { name: /Apuntarme/ }));
    await g.leer(2800);
    await g.veTexto('07-3a', 'Aviso de inscripción', 'Te has apuntado al turno de paseo', p.locator('[role=status]'));
    await g.veTexto('07-3b', 'El turno marca «Te has apuntado»', 'Te has apuntado', fila(S.paseoManana));
    await g.veTexto('07-3c', 'Ahora hay 2 inscritas y 3 plazas libres', '2 (3 plazas libres)', fila(S.paseoManana));
    await g.veTexto('07-3d', 'Alcanza el mínimo: «Mínimo cubierto», ya no se destaca', 'Mínimo cubierto', fila(S.paseoManana));
    await g.veTexto('07-3e', 'El aviso baja a 1 turno sin cubrir', '1 turno de las próximas 48 horas no cubre su mínimo', p.locator('#undercovered-notice'));
    await g.leer(2200);
  });
  await g.escenario(4, 'La limpieza de mañana a las 11:00 coincide con el paseo de las 10:00 a las 12:00 de Vera', async () => {
    await g.reveal(fila(S.limpiezaCoincide));
    await g.veTexto('07-4a', 'Se indica «Coincide con otro de tus turnos»', 'Coincide con otro de tus turnos', fila(S.limpiezaCoincide));
    await g.check('07-4b', 'No hay botón para apuntarse', 'Sin «Apuntarme» en ese turno', async () => {
      const n = await fila(S.limpiezaCoincide).getByRole('button', { name: /Apuntarme/ }).count();
      return { ok: n === 0, observado: `${n} botones «Apuntarme»` };
    });
    await g.leer(2600);
  });
  await g.escenario(5, 'La limpieza de mañana por la tarde tiene máximo 2 y ya está cubierta', async () => {
    await g.reveal(fila(S.limpiezaCompleta));
    await g.veTexto('07-5a', 'Figura «Completo»', 'Completo', fila(S.limpiezaCompleta));
    await g.check('07-5b', 'No ofrece apuntarse', 'Sin botones de inscripción', async () => {
      const n = await fila(S.limpiezaCompleta).getByRole('button').count();
      return { ok: n === 0, observado: `${n} botones en la fila` };
    });
    await g.leer(2600);
  });
  await g.escenario(6, 'El paseo que empezó hace unos minutos figura «En curso»', async () => {
    const hay = S.enCurso !== undefined;
    await g.check('07-6a', 'El turno empezado figura «En curso» y no admite acciones', 'En curso, sin botones', async () => {
      if (!hay) return { ok: false, observado: 'No hay turno en curso (la carga se hizo entre las 22:00 y la 01:00)' };
      const t = await g.texto(fila(S.enCurso));
      const n = await fila(S.enCurso).getByRole('button').count();
      return { ok: t.includes('En curso') && n === 0, observado: `${t} · ${n} botones` };
    });
    if (hay) { await g.reveal(fila(S.enCurso)); await g.leer(2600); }
  });
  await g.escenario(7, 'Vera se da de baja del paseo de mañana', async () => {
    await g.reveal(fila(S.paseoManana));
    await g.click(fila(S.paseoManana).getByRole('button', { name: /Darme de baja/ }));
    await g.leer(2800);
    await g.veTexto('07-7a', 'Aviso de baja', 'Te has dado de baja del turno de paseo', p.locator('[role=status]'));
    await g.veTexto('07-7b', 'La plaza queda libre: 1 inscrita y 4 plazas libres', '1 (4 plazas libres)', fila(S.paseoManana));
    await g.veTexto('07-7c', 'Vuelve a destacarse por faltar una persona', 'Faltan voluntarios: 1 para el mínimo', fila(S.paseoManana));
    await g.leer(2200);
  });
  await g.escenario(8, 'Coral crea un turno con máximo menor que el mínimo, fecha pasada y fin anterior al inicio; luego uno válido', async () => {
    await g.login('coordinadora');
    await g.click(g.nav('Turnos'));
    await g.click(p.getByRole('link', { name: 'Nuevo turno' }));
    await g.select(p.locator('#kind'), 'cleaning');
    await g.fill(p.locator('#date'), diaMadrid(-2));
    await g.fill(p.locator('#startTime'), '12:00');
    await g.fill(p.locator('#endTime'), '10:00');
    await g.type(p.locator('#minVolunteers'), '5');
    await g.type(p.locator('#maxVolunteers'), '2');
    await g.click(p.getByRole('button', { name: 'Crear turno' }));
    await g.leer(3200);
    await g.veTexto('07-8a', 'Se rechaza: máximo menor que el mínimo', 'no menor que el mínimo', p.locator('[role=alert]'));
    await g.veTexto('07-8b', 'Se rechaza: fin no posterior al inicio', 'fin', p.locator('[role=alert]'));
    await g.check('07-8c', 'No se guarda nada: HTTP 422 y se conservan los valores escritos', 'HTTP 422 y mínimo 5', async () => {
      const v = await p.locator('#minVolunteers').inputValue();
      return { ok: g.ultimoEstado() === 422 && v === '5', observado: `HTTP ${g.ultimoEstado()}, mínimo conservado: ${v}` };
    });
    await g.fill(p.locator('#date'), diaMadrid(4));
    await g.fill(p.locator('#startTime'), '16:00');
    await g.fill(p.locator('#endTime'), '18:00');
    await g.type(p.locator('#minVolunteers'), '2');
    await g.type(p.locator('#maxVolunteers'), '6');
    await g.click(p.getByRole('button', { name: 'Crear turno' }));
    await g.leer(3000);
    await g.veTexto('07-8d', 'Turno creado', 'Turno creado: ', p.locator('[role=status]'));
    await g.veTexto('07-8e', 'Aparece en el listado', ddmmyyyy(diaMadrid(4)), p.locator('#shifts'));
    await g.leer(1800);
  });
  await g.escenario(9, 'El paseo de dentro de cinco días ya tiene una persona inscrita: cambiar la fecha no se admite, cambiar el mínimo y el máximo sí', async () => {
    await g.click(fila(S.paseoSemana).getByRole('link', { name: 'Paseo' }));
    await g.reveal(p.locator('#form-shift-edit'));
    await g.fill(p.locator('#date'), diaMadrid(6));
    await g.click(p.getByRole('button', { name: 'Guardar cambios' }));
    await g.leer(3000);
    await g.veTexto('07-9a', 'Con inscripciones no se cambian tipo, fecha ni horas', 'ya tiene inscripciones', p.locator('[role=alert]'));
    await g.fill(p.locator('#date'), diaMadrid(5));
    await g.type(p.locator('#minVolunteers'), '3');
    await g.type(p.locator('#maxVolunteers'), '5');
    await g.click(p.getByRole('button', { name: 'Guardar cambios' }));
    await g.leer(3000);
    await g.veTexto('07-9b', 'Aviso de turno modificado', 'Turno modificado', p.locator('[role=status]'));
    await g.veTexto('07-9c', 'El máximo nuevo (5) consta en la ficha', '5 personas', p.locator('#shift-facts'));
    await g.leer(1800);
  });
  await g.escenario(10, 'Sara (solicitante), Vicente (veterinario), Vera (a «Nuevo turno») y una persona sin sesión', async () => {
    await g.login('sara');
    let st = await g.go('/admin/turnos');
    await g.leer(1500);
    await g.veEstado('07-10a', 'Solicitante en /admin/turnos: acceso denegado', 403, st);
    await g.login('veterinario');
    st = await g.go('/admin/turnos');
    await g.leer(1500);
    await g.veEstado('07-10b', 'Veterinario (no es voluntario) en /admin/turnos: acceso denegado', 403, st);
    await g.login('voluntaria');
    st = await g.go('/admin/turnos/nuevo');
    await g.leer(1500);
    await g.veEstado('07-10c', 'Voluntaria en /admin/turnos/nuevo: solo la coordinadora crea turnos', 403, st);
    await g.context.clearCookies();
    await g.go('/admin/turnos');
    await g.leer(1500);
    await g.veRuta('07-10d', 'Sin sesión se pide acceder', '/acceso');
  });
});

// ====================================================================================================================
def({
  id: '08-apadrinamiento', spec: '008 · Apadrinamiento', titulo: 'Apadrinar a un animal y recibir sus novedades', rol: 'Padrinos y coordinadora',
  historia: 'Como padrino o madrina quiero <b>apoyar a un animal concreto con una aportación mensual desde 10 €</b>, recibir sus novedades y decidir cómo continuar mi colaboración cuando sea adoptado.',
  escenarios: [
    { titulo: 'Aportación inferior a 10 €: no se admite', ca: ['CA-08-02'] },
    { titulo: 'Apadrinar con 15,50 € al mes', ca: ['CA-08-01'] },
    { titulo: 'Apadrinar otra vez el mismo animal: se rechaza', ca: ['CA-08-06'] },
    { titulo: 'La coordinadora publica una novedad para los padrinos', ca: ['CA-08-03'] },
    { titulo: 'La madrina recibe las novedades de sus animales', ca: ['CA-08-03'] },
    { titulo: 'El animal apadrinado es adoptado: apadrinamiento pendiente de decisión', ca: ['CA-08-04'] },
    { titulo: 'La madrina elige otro animal: no se le asigna ninguno por su cuenta', ca: ['CA-08-05'] },
    { titulo: 'Otro padrino termina su colaboración', ca: ['CA-08-05', 'CA-08-06'] },
    { titulo: 'Permisos y estados: personal, anónimos y animal ya adoptado', ca: ['CA-08-07', 'CA-08-10'] },
  ],
}, async (g) => {
  const p = g.page;
  const A = g.d.animals;
  await g.escenario(1, 'Paula abre la ficha del perro adulto y pulsa «Apadrinar»; escribe 9,99 €', async () => {
    await g.login('paula');
    await g.go('/animales');
    await g.click(tarjeta(g, A.adulto.code).getByRole('link', { name: 'Ver ficha completa' }));
    await g.reveal(p.locator('#sponsor-box'));
    await g.click(p.getByRole('link', { name: `Apadrinar a ${A.adulto.code}` }));
    await g.type(p.locator('#amount'), '9,99');
    await g.click(p.getByRole('button', { name: `Apadrinar a ${A.adulto.code}` }));
    await g.leer(2800);
    await g.veTexto('08-1a', 'Se rechaza con el error del importe', 'al menos 10,00 €', p.locator('[role=alert]'));
    await g.check('08-1b', 'HTTP 422 y se conserva el importe escrito', 'HTTP 422 y valor 9,99', async () => {
      const v = await p.locator('#amount').inputValue();
      return { ok: g.ultimoEstado() === 422 && v === '9,99', observado: `HTTP ${g.ultimoEstado()}, importe conservado: ${v}` };
    });
  });
  await g.escenario(2, 'Con 15,50 € al mes', async () => {
    await g.type(p.locator('#amount'), '15,50');
    await g.click(p.getByRole('button', { name: `Apadrinar a ${A.adulto.code}` }));
    await g.leer(3000);
    await g.veTexto('08-2a', 'Aviso con el importe', `Ya apadrinas a ${A.adulto.code} con 15,50 € al mes`, p.locator('[role=status]'));
    const tarjetaAdulto = p.locator('.sponsorship-card', { hasText: A.adulto.code });
    await g.veTexto('08-2b', '«Mis apadrinamientos» lo muestra «Activo»', 'Activo', tarjetaAdulto);
    await g.veTexto('08-2c', 'Con el importe de 15,50 € y la fecha de inicio', '15,50 €', tarjetaAdulto);
    await g.reveal(tarjetaAdulto);
    await g.leer(2200);
  });
  await g.escenario(3, 'Paula intenta apadrinar otra vez al mismo perro', async () => {
    await g.go(`/animales/${A.adulto.id}/apadrinar`);
    await g.type(p.locator('#amount'), '20');
    await g.click(p.getByRole('button', { name: `Apadrinar a ${A.adulto.code}` }));
    await g.leer(2800);
    await g.veTexto('08-3a', 'Se rechaza porque ya lo apadrina', `Ya apadrinas a ${A.adulto.code}`, p.locator('[role=alert]'));
    await g.check('08-3b', 'La respuesta es 409', 'HTTP 409', async () => ({ ok: g.ultimoEstado() === 409, observado: `HTTP ${g.ultimoEstado()}` }));
  });
  await g.escenario(4, 'Coral publica una novedad para los padrinos del perro adulto', async () => {
    await g.login('coordinadora');
    await g.click(g.nav('Apadrinamientos'));
    await g.veTexto('08-4a', 'La sección del perro lista a Paula con 15,50 €', 'Paula Padrina', p.locator(`#sponsored-${A.adulto.id}`));
    const f = p.locator(`#form-update-${A.adulto.id}`);
    await g.type(f.locator('textarea'), 'Esta semana ha conocido a su cuidadora y ya juega con la pelota en el patio. Gracias por vuestro apoyo.', 10);
    await g.click(f.getByRole('button', { name: 'Publicar novedad' }));
    await g.leer(2800);
    await g.veTexto('08-4b', 'Aviso de novedad publicada', `Novedad de ${A.adulto.code} publicada para sus padrinos`, p.locator('[role=status]'));
    await g.veTexto('08-4c', 'Figura en «Novedades publicadas»', 'ya juega con la pelota', p.locator(`#sponsored-${A.adulto.id}`));
    await g.leer(1800);
  });
  await g.escenario(5, 'Paula abre «Mis apadrinamientos» y lee la novedad', async () => {
    await g.login('paula');
    await g.click(g.nav('Mis apadrinamientos'));
    const adulto = p.locator('.sponsorship-card', { hasText: A.adulto.code });
    await g.veTexto('08-5a', 'Ve la novedad nueva del perro adulto', 'ya juega con la pelota', adulto);
    const reservado = p.locator('.sponsorship-card', { hasText: A.reservado.code });
    await g.veTexto('08-5b', 'Y la de su otro apadrinado desde hace dos meses', 'aprendido a pasear con correa', reservado);
    await g.reveal(adulto);
    await g.leer(2200);
    await g.reveal(reservado);
    await g.leer(2200);
  });
  await g.escenario(6, 'Coral formaliza la adopción del perro RESERVA00001, que Paula y Pablo apadrinan', async () => {
    await g.login('coordinadora');
    await g.click(g.nav('Solicitudes'));
    await g.click(filaAdmin(g, 'Samanta').getByRole('link'));
    await g.type(p.locator('#fee'), '120');
    await g.click(p.locator('#feePaid'), { asentar: false });
    await g.click(p.locator('#btn-formalize'));
    await g.leer(2400);
    await g.veTexto('08-6a', 'La adopción queda formalizada', 'Adopción formalizada', p.locator('[role=status]'));
    await g.click(g.nav('Apadrinamientos'));
    await g.veTexto('08-6b', 'La coordinadora ve el apadrinamiento «En pausa» y pendiente de decisión', 'En pausa', p.locator(`#sponsored-${A.reservado.id}`));
    await g.reveal(p.locator(`#sponsored-${A.reservado.id}`));
    await g.leer(2400);
    await g.login('paula');
    await g.click(g.nav('Mis apadrinamientos'));
    const reservado = p.locator('.sponsorship-card', { hasText: A.reservado.code });
    await g.veTexto('08-6c', 'Paula ve «Pendiente de tu decisión»', 'Pendiente de tu decisión', reservado);
    await g.veTexto('08-6d', 'Se indica que la aportación está en pausa', 'en pausa', reservado);
    await g.reveal(reservado);
    await g.leer(3000);
  });
  await g.escenario(7, 'Paula elige al perro grande DISPON000006 con la misma aportación', async () => {
    const reservado = p.locator('.sponsorship-card', { hasText: A.reservado.code });
    await g.veTexto('08-7a', 'Puede elegir entre los animales disponibles que no apadrina', A.grande.code, reservado.locator('select'));
    await g.select(reservado.locator('select'), A.grande.id);
    await g.click(reservado.getByRole('button', { name: 'Apadrinar al animal elegido' }));
    await g.leer(3000);
    await g.veTexto('08-7b', 'Aviso con el nuevo apadrinamiento', `Ahora apadrinas a ${A.grande.code} con 15,50 € al mes`, p.locator('[role=status]'));
    await g.veTexto('08-7c', 'El anterior termina con «Cambio a otro animal»', 'Cambio a otro animal', p.locator('.sponsorship-card', { hasText: A.reservado.code }));
    await g.veTexto('08-7d', 'El nuevo figura «Activo»', 'Activo', p.locator('.sponsorship-card', { hasText: A.grande.code }));
    await g.reveal(p.locator('.sponsorship-card', { hasText: A.grande.code }));
    await g.leer(2200);
  });
  await g.escenario(8, 'Pablo, que apadrinaba al mismo perro, prefiere terminar su colaboración', async () => {
    await g.login('pablo');
    await g.click(g.nav('Mis apadrinamientos'));
    const reservado = p.locator('.sponsorship-card', { hasText: A.reservado.code });
    await g.veTexto('08-8a', 'Pablo también ve «Pendiente de tu decisión»', 'Pendiente de tu decisión', reservado);
    await g.click(reservado.getByRole('button', { name: 'Terminar mi colaboración' }));
    await g.leer(2800);
    await g.veTexto('08-8b', 'Aviso de apadrinamiento terminado', `Has terminado tu apadrinamiento de ${A.reservado.code}`, p.locator('[role=status]'));
    await g.veTexto('08-8c', 'Consta «Terminado · Fin de la colaboración»', 'Fin de la colaboración', p.locator('.sponsorship-card', { hasText: A.reservado.code }));
    await g.reveal(p.locator('.sponsorship-card', { hasText: A.reservado.code }));
    await g.leer(2000);
  });
  await g.escenario(9, 'Vera (voluntaria) no apadrina; el perro ya adoptado no se puede apadrinar; sin sesión se pide acceder', async () => {
    await g.login('voluntaria');
    await g.go('/animales');
    await g.click(tarjeta(g, A.cachorro.code).getByRole('link', { name: 'Ver ficha completa' }));
    await g.noVeTexto('08-9a', 'La ficha pública no ofrece «Apadrinar» al personal', 'Apadrinar a', p.locator('main'));
    let st = await g.go(`/animales/${A.cachorro.id}/apadrinar`);
    await g.leer(1600);
    await g.veEstado('08-9b', 'Voluntaria en el formulario de apadrinamiento: acceso denegado', 403, st);
    await g.login('paula');
    st = await g.go(`/animales/${A.reservado.id}/apadrinar`);
    await g.leer(1600);
    await g.veEstado('08-9c', 'Animal ya adoptado: el formulario no se encuentra (404)', 404, st);
    await g.context.clearCookies();
    await g.go('/mis-apadrinamientos');
    await g.leer(1600);
    await g.veRuta('08-9d', 'Sin sesión «Mis apadrinamientos» pide acceder', '/acceso');
  });
});
