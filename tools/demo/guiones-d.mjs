// Guiones 09 y 10: verificación integrada (§5.4 del enunciado). Recorridos que cruzan historias de aggg15 (HU-01/03/05/07)
// y de abcb5-ua (HU-02/04/06/08). Las comprobaciones de g.check son las aserciones que recoge docs/evidencias/verificacion-integrada.md.
import { PASSWORD, madrid, diaMadrid, ddmmyyyy, mesesDespues } from './grabador.mjs';
import { def, foto, tarjeta, filaAdmin, cuestionario, Q_BASE } from './guion-util.mjs';

// ====================================================================================================================
def({
  id: '09-flujo-satisfactorio', spec: 'Verificación integrada · R1', titulo: 'Recorrido satisfactorio: del alta del animal al padrino', rol: 'Seis cuentas, historias de ambos integrantes',
  historia: 'Un animal <b>entra en el refugio (HU-01)</b>, aparece en el <b>catálogo (HU-02)</b>, una persona lo <b>solicita (HU-03)</b>, la coordinadora <b>aprueba (HU-04)</b> y <b>formaliza (HU-05)</b>, y la madrina que lo apadrinaba <b>decide cómo continuar (HU-08)</b>.',
  escenarios: [
    { titulo: 'HU-01 · Alta del animal por la voluntaria y revisión veterinaria', ca: ['CA-01-01', 'CA-01-04', 'CA-01-05'] },
    { titulo: 'HU-02 · El animal aparece en el catálogo público y se filtra', ca: ['CA-02-01', 'CA-02-02'] },
    { titulo: 'HU-08 · Una madrina apadrina al animal con 12,00 € al mes', ca: ['CA-08-01'] },
    { titulo: 'HU-03 · Una persona crea su cuenta y envía la solicitud', ca: ['CA-03-01'] },
    { titulo: 'HU-04 · La coordinadora entrevista y aprueba: el animal queda reservado', ca: ['CA-04-01', 'CA-04-03'] },
    { titulo: 'HU-05 · Formalización: contrato, cuota y seguimientos', ca: ['CA-05-01', 'CA-05-02', 'CA-05-03', 'CA-05-04'] },
    { titulo: 'HU-03/05 · La adoptante ve su adopción y su contrato', ca: ['CA-05-11'] },
    { titulo: 'HU-08 · La madrina recibe la decisión pendiente y elige otro animal', ca: ['CA-08-04', 'CA-08-05'] },
  ],
}, async (g) => {
  const p = g.page;
  const A = g.d.animals;
  const nuevo = { id: null, code: null };
  await g.escenario(1, 'Vera da de alta una gata recibida hace 11 días (registro tardío) y Vicente la revisa', async () => {
    await g.login('voluntaria');
    await g.click(g.nav('Refugio'));
    await g.click(p.getByRole('link', { name: 'Registrar llegada' }));
    await g.select(p.locator('#species'), 'cat');
    await g.select(p.locator('#size'), 'small');
    await g.type(p.locator('#ageMonths'), '14');
    await g.fill(p.locator('#receivedAt'), madrid(-11 * 1440).local);
    await g.type(p.locator('#origin'), 'Gata encontrada en un almacén ficticio; registrada con retraso tras pasar diez días en un hogar temporal.', 8);
    await p.locator('#photos').setInputFiles([await foto('cat', 1)]);
    await g.leer(1200);
    await g.click(p.getByRole('button', { name: 'Guardar ficha' }));
    await g.leer(2200);
    nuevo.id = (await g.ruta()).split('/').pop();
    nuevo.code = await g.texto(p.locator('h1.title'));
    await g.check('R1-01', 'HU-01: la ficha se crea con código propio', 'Código HUE-… y aviso «Ficha creada»', async () => {
      const aviso = await g.texto(p.locator('[role=status]'));
      return { ok: /^HUE-[0-9A-Z]{12}$/.test(nuevo.code) && aviso.includes('Ficha creada'), observado: `${nuevo.code} · ${aviso}` };
    });
    await g.veTexto('R1-02', 'HU-01: con la cuarentena ya cumplida el animal queda «Disponible»', 'Disponible', p.locator('.page-heading .tag'));
    await g.login('veterinario');
    await g.go(`/admin/animales/${nuevo.id}`);
    await g.reveal(p.getByRole('heading', { name: 'Registrar revisión' }));
    await g.type(p.locator('#notes'), 'Revisión: vacunas puestas, sin hallazgos. Apta para adopción.', 10);
    await g.click(p.getByRole('button', { name: 'Guardar revisión' }));
    await g.leer(2200);
    await g.veTexto('R1-03', 'HU-01: la revisión queda en la ficha', 'Vicente Veterinario', p.locator('.review').first());
  });
  await g.escenario(2, 'Una persona sin cuenta abre el catálogo y filtra gatos pequeños', async () => {
    await g.context.clearCookies();
    await g.go('/animales');
    await g.veTexto('R1-04', 'HU-02: el animal nuevo aparece en el catálogo sin iniciar sesión', nuevo.code);
    await g.select(p.locator('#species'), 'cat');
    await g.select(p.locator('#size'), 'small');
    await g.click(p.getByRole('button', { name: 'Filtrar' }));
    await g.leer(1800);
    await g.check('R1-05', 'HU-02: el filtro gato + pequeño lo incluye y no hay perros', 'Tarjeta del animal nuevo y 0 perros', async () => {
      const dentro = await tarjeta(g, nuevo.code).count();
      const perros = await p.locator('article.animal-card .tag', { hasText: /^Perro$/ }).count();
      return { ok: dentro === 1 && perros === 0, observado: `${dentro} tarjeta del animal nuevo, ${perros} perros` };
    });
    await g.reveal(tarjeta(g, nuevo.code));
    await g.leer(1800);
  });
  await g.escenario(3, 'Paula, madrina, abre la ficha pública y apadrina con 12,00 €', async () => {
    await g.login('paula');
    await g.go(`/animales/${nuevo.id}/apadrinar`);
    await g.type(p.locator('#amount'), '12,00');
    await g.click(p.getByRole('button', { name: `Apadrinar a ${nuevo.code}` }));
    await g.leer(2400);
    await g.veTexto('R1-06', 'HU-08: apadrinamiento activo con 12,00 €', `Ya apadrinas a ${nuevo.code} con 12,00 € al mes`, p.locator('[role=status]'));
  });
  await g.escenario(4, 'Rosa crea su cuenta desde la ficha y envía la solicitud', async () => {
    await g.context.clearCookies();
    await g.go(`/animales/${nuevo.id}`);
    await g.click(p.getByRole('link', { name: 'Acceder para adoptar' }));
    await g.click(p.getByRole('link', { name: 'Crea tu cuenta de solicitante' }));
    await g.type(p.locator('#name'), 'Rosa Registrada');
    await g.type(p.locator('#email'), 'rosa@example.org');
    await g.type(p.locator('#password'), PASSWORD);
    await g.type(p.locator('#passwordConfirmation'), PASSWORD);
    await g.click(p.getByRole('button', { name: 'Crear cuenta' }));
    await g.leer(1500);
    await cuestionario(g, Q_BASE);
    await g.click(p.getByRole('button', { name: 'Enviar solicitud' }));
    await g.leer(2400);
    await g.veTexto('R1-07', 'HU-03: solicitud enviada', `Solicitud enviada. La coordinadora valorará tu candidatura para ${nuevo.code}`, p.locator('[role=status]'));
    await g.veTexto('R1-08', 'HU-03: figura pendiente y cuenta 1 de 2', '1 de 2 solicitudes abiertas');
  });
  await g.escenario(5, 'Coral registra la entrevista y aprueba la candidatura de Rosa', async () => {
    await g.login('coordinadora');
    await g.click(g.nav('Solicitudes'));
    await g.click(filaAdmin(g, 'Rosa').getByRole('link'));
    await g.veTexto('R1-09', 'HU-03/04: la coordinadora ve el cuestionario completo', 'Piso de 70 m²');
    await g.click(p.getByText('Registrar entrevista', { exact: true }));
    await g.fill(p.locator('#interviewAt'), madrid().local);
    await g.type(p.locator('#interviewNotes'), 'Entrevista favorable: familia informada y con experiencia.', 8);
    await g.click(p.getByRole('button', { name: 'Guardar entrevista' }));
    await g.leer(1800);
    await g.click(p.locator('#btn-approve'));
    await g.leer(2600);
    await g.veTexto('R1-10', 'HU-04: candidatura aprobada', 'Candidatura aprobada. El animal ha quedado reservado', p.locator('[role=status]'));
    await g.veTexto('R1-11', 'HU-04: el animal figura «Reservado»', 'Reservado', p.locator('aside .tag').first());
    await g.go('/animales');
    await g.noVeTexto('R1-12', 'HU-02/04: el animal reservado desaparece del catálogo', nuevo.code);
    await g.leer(1500);
  });
  await g.escenario(6, 'Coral formaliza la entrega con una cuota de 90,00 €', async () => {
    await g.click(g.nav('Solicitudes'));
    await g.click(filaAdmin(g, 'Rosa').getByRole('link'));
    await g.type(p.locator('#fee'), '90,00');
    await g.click(p.locator('#feePaid'), { asentar: false });
    await g.click(p.locator('#btn-formalize'));
    await g.leer(2800);
    await g.veTexto('R1-13', 'HU-05: contrato generado', `Se ha generado el contrato CTR-${nuevo.code.slice(4)}`, p.locator('[role=status]'));
    await g.veTexto('R1-14', 'HU-05: cuota abonada 90,00 €', '90,00 €', p.locator('#fee'));
    await g.veTexto('R1-15', `HU-05: seguimiento al mes el ${ddmmyyyy(mesesDespues(1))}`, ddmmyyyy(mesesDespues(1)), p.locator('#follow-ups'));
    await g.veTexto('R1-16', `HU-05: seguimiento a los seis meses el ${ddmmyyyy(mesesDespues(6))}`, ddmmyyyy(mesesDespues(6)), p.locator('#follow-ups'));
    await g.reveal(p.locator('#follow-ups'));
    await g.leer(2000);
    const st = await g.go(`/animales/${nuevo.id}`);
    await g.veEstado('R1-17', 'HU-02/05: la ficha pública del animal adoptado ya no existe', 404, st);
    await g.leer(1500);
  });
  await g.escenario(7, 'Rosa entra en «Mis solicitudes»: adopción formalizada y contrato', async () => {
    await g.login('rosa');
    await g.veTexto('R1-18', 'HU-05: Rosa ve «Adopción formalizada»', 'Adopción formalizada', p.locator('tbody'));
    await g.click(p.getByRole('link', { name: /Ver contrato/ }));
    await g.veTexto('R1-19', 'HU-05: puede abrir su contrato', 'rosa@example.org');
    await g.leer(2200);
  });
  await g.escenario(8, 'Paula ve su apadrinamiento pendiente de decisión y elige al cachorro', async () => {
    await g.login('paula');
    await g.click(g.nav('Mis apadrinamientos'));
    const t = p.locator('.sponsorship-card', { hasText: nuevo.code });
    await g.veTexto('R1-20', 'HU-08: «Pendiente de tu decisión» con la aportación en pausa', 'Pendiente de tu decisión', t);
    await g.reveal(t);
    await g.leer(2400);
    await g.select(t.locator('select'), A.cachorro.id);
    await g.click(t.getByRole('button', { name: 'Apadrinar al animal elegido' }));
    await g.leer(2800);
    await g.veTexto('R1-21', 'HU-08: nuevo apadrinamiento con la misma aportación', `Ahora apadrinas a ${A.cachorro.code} con 12,00 € al mes`, p.locator('[role=status]'));
    await g.veTexto('R1-22', 'HU-08: el anterior termina con «Cambio a otro animal»', 'Cambio a otro animal', p.locator('.sponsorship-card', { hasText: nuevo.code }));
    await g.leer(2200);
  });
});

// ====================================================================================================================
def({
  id: '10-flujo-con-error', spec: 'Verificación integrada · R2', titulo: 'Recorrido con errores, estados y permisos', rol: 'Seis cuentas, historias de ambos integrantes',
  historia: 'Un recorrido que <b>choca a propósito con las reglas</b>: el límite de dos solicitudes abiertas (HU-03), la aprobación de un animal ya reservado (HU-04), la formalización incompleta (HU-05), el catálogo (HU-02) y los permisos de HU-05 y HU-08.',
  escenarios: [
    { titulo: 'HU-03 · Tercera solicitud y solicitud repetida: bloqueadas por el límite', ca: ['CA-03-03', 'CA-03-05'] },
    { titulo: 'HU-04 · Aprobar sin entrevista y aprobar con entrevista: el animal queda reservado', ca: ['CA-04-03', 'CA-04-04'] },
    { titulo: 'HU-04 · Aprobar a otra persona sobre el animal ya reservado: rechazado', ca: ['CA-04-05'] },
    { titulo: 'HU-02/03 · El animal reservado no se ofrece ni admite solicitudes', ca: ['CA-02-03', 'CA-03-05'] },
    { titulo: 'HU-04/05 · Permisos: voluntariado, solicitante y anónimo ante la gestión', ca: ['CA-05-11'] },
    { titulo: 'HU-05 · Formalizar sin confirmar el abono se rechaza; con abono se completa', ca: ['CA-05-07', 'CA-05-08'] },
    { titulo: 'HU-05 · Otra persona no puede abrir el contrato', ca: ['CA-05-11'] },
    { titulo: 'HU-08 · El animal adoptado no se apadrina; el personal tampoco', ca: ['CA-08-07', 'CA-08-10'] },
  ],
}, async (g) => {
  const p = g.page;
  const A = g.d.animals;
  const abrir = async (persona) => {
    await g.click(g.nav('Solicitudes'));
    await g.click(filaAdmin(g, persona).filter({ hasText: A.gatito.code }).getByRole('link'));
  };
  let contrato = null;
  await g.escenario(1, 'Samuel ya tiene dos solicitudes abiertas: intenta pedir al cachorro y repetir la del gato', async () => {
    await g.login('samuel');
    await g.click(g.nav('Mis solicitudes'));
    await g.veTexto('R2-01', 'Precondición: Samuel tiene 2 de 2 solicitudes abiertas', '2 de 2 solicitudes abiertas');
    await g.go('/animales');
    await g.click(tarjeta(g, A.cachorro.code).getByRole('link', { name: 'Ver ficha completa' }));
    await g.click(p.getByRole('link', { name: 'Iniciar solicitud de adopción' }));
    await g.leer(1500);
    await g.veTexto('R2-02', 'HU-03: la tercera solicitud se rechaza por el límite', 'Ya tienes dos solicitudes abiertas', p.locator('.notification.is-warning'));
    await g.go(`/animales/${A.gato.id}/solicitud`);
    await g.leer(1500);
    await g.veTexto('R2-03', 'HU-03: repetir un animal ya solicitado se rechaza', 'Ya tienes una solicitud abierta para este animal', p.locator('.notification.is-warning'));
    await g.go('/mis-solicitudes');
    await g.veCantidad('R2-04', 'HU-03: las dos solicitudes existentes se conservan', p.locator('tbody tr'), 2);
    await g.leer(1800);
  });
  await g.escenario(2, 'Coral intenta aprobar a Silvia sin entrevista; la registra y aprueba', async () => {
    await g.login('coordinadora');
    await abrir('Silvia');
    await g.click(p.locator('#btn-approve'));
    await g.leer(2600);
    await g.veTexto('R2-05', 'HU-04: aprobar sin entrevista se rechaza (422)', 'imprescindible haber realizado y registrado previamente la entrevista', p.locator('.notification.is-warning'));
    await g.click(p.getByText('Registrar entrevista', { exact: true }));
    await g.fill(p.locator('#interviewAt'), madrid().local);
    await g.type(p.locator('#interviewNotes'), 'Entrevista favorable.', 8);
    await g.click(p.getByRole('button', { name: 'Guardar entrevista' }));
    await g.leer(1500);
    await g.click(p.locator('#btn-approve'));
    await g.leer(2600);
    await g.veTexto('R2-06', 'HU-04: aprobada y animal reservado', 'Reservado', p.locator('aside .tag').first());
    await g.check('R2-07', 'HU-04: Sergio pasa a «En espera»', 'Sergio En espera', async () => {
      const t = await g.texto(p.locator('aside li', { hasText: 'Sergio' }));
      return { ok: t.includes('En espera'), observado: t };
    });
  });
  await g.escenario(3, 'Sergio (en espera) tiene entrevista; aprobarlo sobre el animal reservado se rechaza', async () => {
    await abrir('Sergio');
    await g.click(p.getByText('Registrar entrevista', { exact: true }));
    await g.fill(p.locator('#interviewAt'), madrid().local);
    await g.type(p.locator('#interviewNotes'), 'Entrevista favorable.', 8);
    await g.click(p.getByRole('button', { name: 'Guardar entrevista' }));
    await g.leer(1500);
    await g.click(p.locator('#btn-approve'));
    await g.leer(3000);
    await g.veTexto('R2-08', 'HU-04: se rechaza con un mensaje claro de animal ya reservado', 'El animal ya está reservado para otra candidatura', p.locator('.notification.is-danger'));
    await g.check('R2-09', 'HU-04: la respuesta es 409', 'HTTP 409', async () => ({ ok: g.ultimoEstado() === 409, observado: `HTTP ${g.ultimoEstado()}` }));
  });
  await g.escenario(4, 'Samuel busca la gata reservada en el catálogo y abre su solicitud', async () => {
    await g.login('samuel');
    await g.go('/animales');
    await g.noVeTexto('R2-10', 'HU-02: la gata reservada no está en el catálogo', A.gatito.code);
    const st = await g.go(`/animales/${A.gatito.id}`);
    await g.leer(1500);
    await g.veEstado('R2-11', 'HU-02: su ficha pública da 404', 404, st);
    const st2 = await g.go(`/animales/${A.gatito.id}/solicitud`);
    await g.leer(1500);
    await g.veEstado('R2-12', 'HU-03: su formulario de solicitud da 404', 404, st2);
  });
  await g.escenario(5, 'Vera (voluntaria), Sara (solicitante) y una persona sin sesión ante la gestión de adopciones', async () => {
    await g.login('voluntaria');
    let st = await g.go('/admin/solicitudes');
    await g.leer(1400);
    await g.veEstado('R2-13', 'Permiso: la voluntaria no abre /admin/solicitudes', 403, st);
    st = await g.go('/admin/seguimientos');
    await g.veEstado('R2-14', 'Permiso: la voluntaria no abre /admin/seguimientos', 403, st);
    await g.login('sara');
    st = await g.go('/admin/animales');
    await g.leer(1400);
    await g.veEstado('R2-15', 'Permiso: una solicitante no abre el refugio', 403, st);
    await g.context.clearCookies();
    await g.go('/admin/solicitudes');
    await g.veRuta('R2-16', 'Permiso: sin sesión se pide acceder', '/acceso');
  });
  await g.escenario(6, 'Coral intenta formalizar a Silvia sin confirmar el abono, y después lo confirma', async () => {
    await g.login('coordinadora');
    await abrir('Silvia');
    await g.type(p.locator('#fee'), '75,50');
    await g.click(p.locator('#btn-formalize'));
    await g.leer(2800);
    await g.veTexto('R2-17', 'HU-05: sin confirmar el abono no se formaliza', 'Confirma que se ha abonado la cuota', p.locator('#error-feePaid'));
    await g.check('R2-18', 'HU-05: HTTP 422 y el animal sigue reservado', 'HTTP 422', async () => ({ ok: g.ultimoEstado() === 422, observado: `HTTP ${g.ultimoEstado()}` }));
    await g.click(p.locator('#feePaid'), { asentar: false });
    await g.click(p.locator('#btn-formalize'));
    await g.leer(2800);
    contrato = (await g.ruta()).split('/').pop();
    await g.veTexto('R2-19', 'HU-05: con el abono confirmado se formaliza', 'Adopción formalizada', p.locator('[role=status]'));
    await g.login('sergio');
    await g.veTexto('R2-20', 'HU-05: la solicitud en espera de Sergio queda cancelada con motivo', 'Motivo: El animal ha sido adoptado', p.locator('tbody'));
    await g.leer(2000);
  });
  await g.escenario(7, 'Sergio intenta abrir el contrato de Silvia', async () => {
    const st = await g.go(`/adopciones/${contrato}/contrato`);
    await g.leer(2200);
    await g.veEstado('R2-21', 'HU-05: una cuenta que no es la titular no ve el contrato (404)', 404, st);
  });
  await g.escenario(8, 'Paula intenta apadrinar a la gata ya adoptada; Vera intenta apadrinar al cachorro', async () => {
    await g.login('paula');
    let st = await g.go(`/animales/${A.gatito.id}/apadrinar`);
    await g.leer(1600);
    await g.veEstado('R2-22', 'HU-08: un animal adoptado no se apadrina (404)', 404, st);
    await g.login('voluntaria');
    st = await g.go(`/animales/${A.cachorro.id}/apadrinar`);
    await g.leer(1600);
    await g.veEstado('R2-23', 'HU-08: el personal no apadrina (403)', 403, st);
  });
});
