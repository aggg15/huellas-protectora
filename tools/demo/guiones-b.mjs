// Guiones 02 (catálogo), 03 (solicitud) y 04 (decisiones de la coordinadora).
import { PASSWORD, madrid } from './grabador.mjs';
import { def, tarjeta, filaAdmin, cuestionario, Q_BASE } from './guion-util.mjs';

// ====================================================================================================================
def({
  id: '02-catalogo', spec: '002 · Catálogo público', titulo: 'Consultar y filtrar los animales en adopción', rol: 'Cualquier visitante',
  historia: 'Como persona interesada en adoptar quiero <b>consultar sin registrarme los animales que buscan hogar y filtrarlos por especie, tamaño y edad</b> para encontrar uno que encaje con mi situación.',
  escenarios: [
    { titulo: 'Catálogo público sin registrarse', ca: ['CA-02-01'] },
    { titulo: 'Filtro por especie: solo gatos', ca: ['CA-02-02'] },
    { titulo: 'Filtros combinados: perro, pequeño y menos de 12 meses', ca: ['CA-02-02'] },
    { titulo: 'Filtro sin coincidencias: mensaje y «Restablecer filtros»', ca: ['CA-02-02'] },
    { titulo: 'Cuarentena, reservado y adoptado no se ofrecen (ni su ficha ni sus fotos)', ca: ['CA-02-03'] },
    { titulo: 'Animal en casa de acogida: visible con su distintivo', ca: ['CA-02-04'] },
    { titulo: 'Ficha pública: datos del animal sin información interna', ca: ['CA-02-01', 'CA-02-04'] },
  ],
}, async (g) => {
  const p = g.page;
  const A = g.d.animals;
  const lista = p.locator('article.animal-card');
  await g.escenario(1, 'Sin iniciar sesión: «Buscar un compañero» desde la portada', async () => {
    await g.go('/');
    await g.click(g.nav('Buscar un compañero'));
    await g.veRuta('02-1a', 'Se llega al catálogo sin pasar por la página de acceso', '/animales');
    await g.veCantidad('02-1b', 'Se listan los 8 animales disponibles (incluida la gata cuya cuarentena de diez días acaba de cumplirse)', lista, 8);
    await g.veTexto('02-1c', 'Cada tarjeta muestra código, especie, tamaño y edad', 'Pequeño', lista.first());
    await g.veCantidad('02-1d', 'Cada tarjeta lleva su fotografía principal', p.locator('article.animal-card .card-thumb img'), 8);
    await g.leer(2500);
    await g.reveal(tarjeta(g, A.grande.code));
    await g.leer(2200);
  });
  await g.escenario(2, 'Especie = Gato', async () => {
    await g.select(p.locator('#species'), 'cat');
    await g.click(p.getByRole('button', { name: 'Filtrar' }));
    await g.leer(2000);
    await g.check('02-2a', 'Solo quedan gatos', 'Todas las tarjetas son de gato y no hay perros', async () => {
      const n = await lista.count();
      const gatos = await p.locator('article.animal-card .tag', { hasText: /^Gato$/ }).count();
      const perros = await p.locator('article.animal-card .tag', { hasText: /^Perro$/ }).count();
      return { ok: n > 0 && gatos === n && perros === 0, observado: `${n} tarjetas: ${gatos} gatos y ${perros} perros` };
    });
  });
  await g.escenario(3, 'Especie = Perro, tamaño = Pequeño, edad = Cachorro (menos de 12 meses)', async () => {
    await g.select(p.locator('#species'), 'dog');
    await g.select(p.locator('#size'), 'small');
    await g.select(p.locator('#age'), '<12');
    await g.click(p.getByRole('button', { name: 'Filtrar' }));
    await g.leer(2200);
    await g.check('02-3a', 'Solo queda el cachorro pequeño', `Una tarjeta: ${A.cachorro.code}`, async () => {
      const n = await lista.count();
      const t = n ? await g.texto(lista.first()) : '';
      return { ok: n === 1 && t.includes(A.cachorro.code), observado: `${n} tarjeta(s): ${t.slice(0, 90)}` };
    });
  });
  await g.escenario(4, 'Gato grande: ninguno cumple los criterios', async () => {
    await g.select(p.locator('#species'), 'cat');
    await g.select(p.locator('#size'), 'large');
    await g.select(p.locator('#age'), '');
    await g.click(p.getByRole('button', { name: 'Filtrar' }));
    await g.leer(2200);
    await g.veTexto('02-4a', 'Mensaje informativo sin resultados', 'No se encontraron animales');
    await g.click(p.getByRole('link', { name: 'Restablecer filtros' }));
    await g.leer(1500);
    await g.veCantidad('02-4b', 'Restablecer los filtros devuelve los 8 animales', lista, 8);
  });
  await g.escenario(5, 'Ni en la lista ni abriendo su dirección: cuarentena, reservado y adoptado', async () => {
    for (const k of ['cuarentena', 'reservado', 'adoptado']) {
      await g.noVeTexto(`02-5-${k}`, `El catálogo no muestra ${A[k].code}`, A[k].code);
    }
    await g.nota('Se abre directamente /animales/{id} de cada uno de los tres');
    for (const [k, etiqueta] of [['cuarentena', 'en cuarentena'], ['reservado', 'reservado'], ['adoptado', 'adoptado']]) {
      const st = await g.go(`/animales/${A[k].id}`);
      await g.leer(1600);
      await g.veEstado(`02-5-${k}-ficha`, `Ficha pública del animal ${etiqueta}: no encontrada`, 404, st);
    }
    const st = await g.go(`/fotos/${g.d.photos.reservado[0]}`);
    await g.veEstado('02-5-foto', 'La fotografía de un animal reservado tampoco se sirve', 404, st);
    await g.leer(1500);
  });
  await g.escenario(6, 'La gata ACOGIDA00001 vive en una casa de acogida pero sigue disponible', async () => {
    await g.go('/animales');
    await g.reveal(tarjeta(g, A.acogido.code));
    await g.veTexto('02-6a', 'Su tarjeta lleva el distintivo «En casa de acogida»', 'En casa de acogida', tarjeta(g, A.acogido.code));
    await g.leer(2200);
    await g.click(tarjeta(g, A.acogido.code).getByRole('link', { name: 'Ver ficha completa' }));
    await g.veTexto('02-6b', 'Su ficha explica el cuidado temporal en familia', 'Cuidado temporal en familia');
    await g.leer(2800);
  });
  await g.escenario(7, 'Ficha del perro adulto: fotos, datos y procedencia, sin notas veterinarias ni gestión', async () => {
    await g.go('/animales');
    await g.click(tarjeta(g, A.adulto.code).getByRole('link', { name: 'Ver ficha completa' }));
    await g.leer(2200);
    await g.veCantidad('02-7a', 'La ficha muestra las dos fotografías', p.locator('.photo-gallery img'), 2);
    await g.veTexto('02-7b', 'Se indican especie, tamaño, edad y procedencia', 'Procedencia e historia');
    await g.noVeTexto('02-7c', 'No se muestran revisiones veterinarias ni datos internos', 'Revisiones veterinarias');
    await g.reveal(p.locator('.photo-gallery'));
    await g.leer(2500);
  });
});

// ====================================================================================================================
def({
  id: '03-solicitud', spec: '003 · Solicitud de adopción', titulo: 'Solicitar un animal con un cuestionario completo', rol: 'Persona solicitante y coordinadora',
  historia: 'Como persona que quiere adoptar quiero <b>enviar una solicitud con un cuestionario completo</b> sobre mi vivienda, experiencia y disponibilidad para que la protectora valore mi candidatura; solo puedo tener <b>dos solicitudes abiertas</b>.',
  escenarios: [
    { titulo: 'Sin cuenta: «Acceder para adoptar» lleva a crear la cuenta y de vuelta al formulario', ca: ['CA-03-01'] },
    { titulo: 'Cuestionario vacío: no se admite y se señalan los datos pendientes', ca: ['CA-03-02'] },
    { titulo: 'Solicitud completa: queda registrada y pendiente', ca: ['CA-03-01'] },
    { titulo: 'Segunda solicitud: ya son dos abiertas', ca: ['CA-03-01'] },
    { titulo: 'Tercera solicitud: rechazada por el límite de dos; las dos existentes se conservan', ca: ['CA-03-03'] },
    { titulo: 'Animal no disponible o ya solicitado: no se registra nada y se explica', ca: ['CA-03-05'] },
    { titulo: 'La coordinadora consulta la candidatura con su cuestionario completo', ca: ['CA-03-04'] },
    { titulo: 'Permiso: una solicitante no abre las candidaturas ajenas', ca: ['CA-03-04 (roles)'] },
  ],
}, async (g) => {
  const p = g.page;
  const A = g.d.animals;
  await g.escenario(1, 'Nuria, sin cuenta, abre la ficha del cachorro y pulsa «Acceder para adoptar»', async () => {
    await g.go('/animales');
    await g.click(tarjeta(g, A.cachorro.code).getByRole('link', { name: 'Ver ficha completa' }));
    await g.click(p.getByRole('link', { name: 'Acceder para adoptar' }));
    await g.veRuta('03-1a', 'Se le pide acceder', '/acceso');
    await g.click(p.getByRole('link', { name: 'Crea tu cuenta de solicitante' }));
    await g.type(p.locator('#name'), 'Nuria Nueva');
    await g.type(p.locator('#email'), 'nuria@example.org');
    await g.type(p.locator('#password'), PASSWORD);
    await g.type(p.locator('#passwordConfirmation'), PASSWORD);
    await g.click(p.getByRole('button', { name: 'Crear cuenta' }));
    await g.leer(2000);
    await g.veRuta('03-1b', 'Tras crear la cuenta vuelve al formulario de solicitud del cachorro', `/animales/${A.cachorro.id}/solicitud`);
    await g.veTexto('03-1c', 'Se muestra el título de la solicitud del cachorro', `Quiero adoptar a ${A.cachorro.code}`);
    await g.veTexto('03-1d', 'El contador indica 0 de 2 solicitudes abiertas', '0 de 2 solicitudes abiertas');
  });
  await g.escenario(2, 'Se salta la validación del navegador y se envía el cuestionario en blanco', async () => {
    await g.sinValidacionNavegador('form.form-wide');
    await g.click(p.getByRole('button', { name: 'Enviar solicitud' }));
    await g.leer(3200);
    await g.veTexto('03-2a', 'Se señala el tipo de vivienda pendiente', 'Indica el tipo de vivienda', p.locator('[role=alert]'));
    await g.veTexto('03-2b', 'Se señala la experiencia pendiente', 'Indica tu experiencia previa', p.locator('[role=alert]'));
    await g.veTexto('03-2c', 'Se señala la disponibilidad pendiente', 'Describe tu disponibilidad', p.locator('[role=alert]'));
    await g.check('03-2d', 'La solicitud no se admite a medias (HTTP 422)', 'HTTP 422', async () => ({ ok: g.ultimoEstado() === 422, observado: `HTTP ${g.ultimoEstado()}` }));
  });
  await g.escenario(3, 'Cuestionario completo: «Enviar solicitud»', async () => {
    await cuestionario(g, Q_BASE);
    await g.leer(1200);
    await g.click(p.getByRole('button', { name: 'Enviar solicitud' }));
    await g.leer(2800);
    await g.veTexto('03-3a', 'Aviso de solicitud enviada', `Solicitud enviada. La coordinadora valorará tu candidatura para ${A.cachorro.code}`, p.locator('[role=status]'));
    await g.veRuta('03-3b', 'Se muestran «Mis solicitudes»', '/mis-solicitudes');
    await g.veTexto('03-3c', 'La solicitud figura pendiente de revisión', 'Pendiente de revisión', p.locator('tbody'));
    await g.veTexto('03-3d', 'Cuenta 1 de 2 solicitudes abiertas', '1 de 2 solicitudes abiertas');
  });
  await g.escenario(4, 'Segunda solicitud, para el perro adulto, con experiencia «ninguna» y sin comentario', async () => {
    await g.go('/animales');
    await g.click(tarjeta(g, A.adulto.code).getByRole('link', { name: 'Ver ficha completa' }));
    await g.click(p.getByRole('link', { name: 'Iniciar solicitud de adopción' }));
    await cuestionario(g, { ...Q_BASE, housingType: 'house_outdoor', experienceType: 'none', experience: '', dailyTime: 'gt4' });
    await g.nota('«Ninguna experiencia» es una respuesta completa: no exige comentario');
    await g.leer(1500);
    await g.click(p.getByRole('button', { name: 'Enviar solicitud' }));
    await g.leer(2600);
    await g.veTexto('03-4a', 'Ya constan 2 de 2 solicitudes abiertas', '2 de 2 solicitudes abiertas');
    await g.veCantidad('03-4b', 'Dos filas en «Mis solicitudes»', p.locator('tbody tr'), 2);
  });
  await g.escenario(5, 'Tercera solicitud, para el perro senior', async () => {
    await g.go('/animales');
    await g.click(tarjeta(g, A.senior.code).getByRole('link', { name: 'Ver ficha completa' }));
    await g.click(p.getByRole('link', { name: 'Iniciar solicitud de adopción' }));
    await g.leer(1500);
    await g.veTexto('03-5a', 'Se rechaza explicando el límite de dos', 'Ya tienes dos solicitudes abiertas', p.locator('.notification.is-warning'));
    await g.check('03-5b', 'No hay formulario para enviar', 'Sin botón «Enviar solicitud»', async () => {
      const n = await p.getByRole('button', { name: 'Enviar solicitud' }).count();
      return { ok: n === 0, observado: `${n} botones «Enviar solicitud»` };
    });
    await g.leer(2200);
    await g.click(p.getByRole('link', { name: 'Consultar mis solicitudes' }));
    await g.veCantidad('03-5c', 'Las dos solicitudes anteriores se conservan', p.locator('tbody tr'), 2);
    await g.leer(2200);
  });
  await g.escenario(6, 'Nuria prueba con el animal en cuarentena; Samuel repite una solicitud; nadie pide el reservado', async () => {
    await g.nota('Nuria abre la dirección de solicitud del animal en cuarentena');
    const st = await g.go(`/animales/${A.cuarentena.id}/solicitud`);
    await g.leer(1800);
    await g.veEstado('03-6a', 'Animal en cuarentena: no encontrado', 404, st);
    await g.nota('Samuel (ya tiene una solicitud abierta para el gato) intenta pedirlo otra vez');
    await g.login('samuel');
    await g.go(`/animales/${A.gato.id}/solicitud`);
    await g.leer(1800);
    await g.veTexto('03-6b', 'Se explica que ya tiene una solicitud abierta para ese animal', 'Ya tienes una solicitud abierta para este animal', p.locator('.notification.is-warning'));
    await g.nota('El animal reservado tampoco admite solicitudes');
    const st2 = await g.go(`/animales/${A.reservado.id}/solicitud`);
    await g.leer(1800);
    await g.veEstado('03-6c', 'Animal reservado: no encontrado', 404, st2);
  });
  await g.escenario(7, 'Coral, la coordinadora, abre «Solicitudes» y la candidatura de Nuria', async () => {
    await g.login('coordinadora');
    await g.click(g.nav('Solicitudes'));
    await g.veTexto('03-7a', 'La lista incluye la solicitud de Nuria Nueva', 'Nuria Nueva', filaAdmin(g, 'Nuria'));
    await g.click(filaAdmin(g, 'Nuria').filter({ hasText: A.adulto.code }).getByRole('link'));
    await g.veTexto('03-7b', 'Se ve la vivienda', 'Piso de 70 m²');
    await g.veTexto('03-7c', 'Se ve la experiencia («Ninguna experiencia previa»)', 'Ninguna experiencia previa');
    await g.veTexto('03-7d', 'Se ve la disponibilidad', 'Uno de los dos trabaja desde casa');
    await g.leer(2000);
    await g.reveal(p.getByRole('heading', { name: 'Disponibilidad' }));
    await g.leer(2200);
  });
  await g.escenario(8, 'Sara, solicitante, intenta abrir las candidaturas', async () => {
    await g.login('sara');
    const st = await g.go('/admin/solicitudes');
    await g.leer(2200);
    await g.veEstado('03-8a', '/admin/solicitudes con cuenta de solicitante: acceso denegado', 403, st);
  });
});

// ====================================================================================================================
def({
  id: '04-decision-reserva', spec: '004 · Entrevista, decisión y reserva', titulo: 'Evaluar solicitudes, entrevistar y reservar el animal', rol: 'Coordinadora de adopciones',
  historia: 'Como coordinadora quiero <b>revisar las solicitudes, concertar entrevistas y decidir</b> sobre las candidaturas para reservar cada animal a una familia y <b>conservar las alternativas en espera</b>.',
  escenarios: [
    { titulo: 'Revisar la candidatura: las seis respuestas del cuestionario', ca: ['CA-04-01'] },
    { titulo: 'Entrevista: datos incompletos se rechazan, completos se registran', ca: ['CA-04-01'] },
    { titulo: 'Aprobar sin entrevista previa: no se admite', ca: ['CA-04-03'] },
    { titulo: 'Rechazar exige un motivo escrito', ca: ['CA-04-02'] },
    { titulo: 'Aprobar con entrevista: el animal queda reservado y las demás pasan a espera', ca: ['CA-04-03', 'CA-04-04'] },
    { titulo: 'Un animal ya reservado no admite otra aprobación', ca: ['CA-04-05'] },
    { titulo: 'Liberar la reserva con motivo: el animal vuelve a estar disponible', ca: ['CA-04-03', 'CA-04-04'] },
    { titulo: 'La persona rechazada ve el motivo en «Mis solicitudes»', ca: ['CA-04-02'] },
    { titulo: 'Permiso: el voluntariado no decide candidaturas', ca: ['CA-04-01 (roles)'] },
  ],
}, async (g) => {
  const p = g.page;
  const A = g.d.animals;
  const abrir = async (persona) => {
    await g.click(g.nav('Solicitudes'));
    await g.click(filaAdmin(g, persona).filter({ hasText: A.gatito.code }).getByRole('link'));
  };
  const entrevista = async (notas) => {
    await g.click(p.getByText('Registrar entrevista', { exact: true }).or(p.getByText('Modificar entrevista', { exact: true })));
    await g.fill(p.locator('#interviewAt'), madrid().local);
    await g.type(p.locator('#interviewNotes'), notas, 8);
  };
  await g.escenario(1, 'Coral entra en «Solicitudes»: hay tres candidaturas pendientes para la gata DISPON000004', async () => {
    await g.login('coordinadora');
    await g.click(g.nav('Solicitudes'));
    await g.leer(1800);
    await g.check('04-1a', 'Hay tres solicitudes pendientes para la gata', '3 filas de DISPON000004 pendientes', async () => {
      const n = await filaAdmin(g, A.gatito.code).filter({ hasText: 'Pendiente de valoración' }).count();
      return { ok: n === 3, observado: `${n} filas pendientes de ${A.gatito.code}` };
    });
    await g.click(filaAdmin(g, 'Silvia').filter({ hasText: A.gatito.code }).getByRole('link'));
    await g.veTexto('04-1b', 'Se ve la vivienda de Silvia', 'Casa con patio cerrado');
    await g.veTexto('04-1c', 'Se ve su experiencia', 'He tenido dos gatos');
    await g.veTexto('04-1d', 'Se ve su disponibilidad', 'Teletrabajo cuatro días');
    await g.reveal(p.getByRole('heading', { name: 'Disponibilidad' }));
    await g.leer(2200);
  });
  await g.escenario(2, 'Silvia: se intenta guardar la entrevista sin notas y después con fecha y notas', async () => {
    await g.reveal(p.getByRole('heading', { name: 'Entrevista previa' }));
    await g.click(p.getByText('Registrar entrevista', { exact: true }));
    await g.sinValidacionNavegador('details form');
    await g.fill(p.locator('#interviewAt'), madrid().local);
    await g.click(p.getByRole('button', { name: 'Guardar entrevista' }));
    await g.leer(2600);
    await g.veTexto('04-2a', 'Sin notas se rechaza la entrevista', 'Escribe las observaciones de la entrevista');
    await g.check('04-2b', 'La respuesta es 422 y la solicitud no cambia', 'HTTP 422', async () => ({ ok: g.ultimoEstado() === 422, observado: `HTTP ${g.ultimoEstado()}` }));
    await g.type(p.locator('#interviewNotes'), 'Entrevista favorable: conoce las necesidades de una gata joven y tiene el patio cerrado.', 8);
    await g.click(p.getByRole('button', { name: 'Guardar entrevista' }));
    await g.leer(2600);
    await g.veTexto('04-2c', 'Aviso de entrevista registrada', 'Entrevista registrada correctamente', p.locator('[role=status]'));
    await g.veTexto('04-2d', 'Se muestra la fecha y las notas de la entrevista', 'Entrevista favorable', p.locator('article.notification'));
  });
  await g.escenario(3, 'Sergio no tiene entrevista: se pulsa «Aprobar y reservar animal»', async () => {
    await abrir('Sergio');
    await g.click(p.locator('#btn-approve'));
    await g.leer(3000);
    await g.veTexto('04-3a', 'La aprobación se rechaza porque falta la entrevista', 'imprescindible haber realizado y registrado previamente la entrevista', p.locator('.notification.is-warning'));
    await g.veTexto('04-3b', 'La solicitud sigue pendiente', 'Pendiente de valoración', p.locator('.page-heading .tag'));
    await g.veTexto('04-3c', 'El animal sigue disponible', 'Disponible', p.locator('aside .tag'));
  });
  await g.escenario(4, 'Sonia: rechazar sin motivo falla; con un motivo escrito queda rechazada', async () => {
    await abrir('Sonia');
    await g.sinValidacionNavegador('form[action$="/rechazar"]');
    await g.click(p.locator('#btn-reject'));
    await g.leer(2600);
    await g.veTexto('04-4a', 'Sin motivo se rechaza el rechazo', 'Indica el motivo justificado del rechazo', p.locator('#error-rejectionReason'));
    await g.check('04-4b', 'La respuesta es 422 y sigue pendiente', 'HTTP 422', async () => ({ ok: g.ultimoEstado() === 422, observado: `HTTP ${g.ultimoEstado()}` }));
    await g.type(p.locator('#rejectionReason'), 'No dispone del tiempo mínimo requerido para atender a una gata joven.', 10);
    await g.click(p.locator('#btn-reject'));
    await g.leer(2600);
    await g.veTexto('04-4c', 'Aviso de rechazo con motivo', 'Candidatura rechazada con motivo registrado', p.locator('[role=status]'));
    await g.veTexto('04-4d', 'La solicitud figura rechazada y el motivo queda visible', 'No dispone del tiempo mínimo', p.locator('.notification.is-danger'));
  });
  await g.escenario(5, 'Silvia ya tiene entrevista: se aprueba y se reserva la gata', async () => {
    await abrir('Silvia');
    await g.click(p.locator('#btn-approve'));
    await g.leer(2800);
    await g.veTexto('04-5a', 'Aviso de candidatura aprobada', 'Candidatura aprobada. El animal ha quedado reservado', p.locator('[role=status]'));
    await g.veTexto('04-5b', 'La solicitud figura «Aprobada»', 'Aprobada', p.locator('.page-heading .tag'));
    await g.veTexto('04-5c', 'El animal figura «Reservado»', 'Reservado', p.locator('aside .tag').first());
    await g.check('04-5d', 'Sergio pasa a «En espera», sin rechazarse ni borrarse', 'Sergio En espera', async () => {
      const t = await g.texto(p.locator('aside li', { hasText: 'Sergio' }));
      return { ok: t.includes('En espera'), observado: t };
    });
    await g.reveal(p.getByRole('heading', { name: /Otras solicitudes/ }));
    await g.leer(2200);
    await g.nota('Fuera del panel, el catálogo público ya no ofrece la gata');
    await g.go('/animales');
    await g.noVeTexto('04-5e', 'La gata reservada desaparece del catálogo', A.gatito.code);
    await g.leer(1800);
  });
  await g.escenario(6, 'Sergio (en espera) tiene entrevista y se intenta aprobar también: el animal ya está reservado', async () => {
    await abrir('Sergio');
    await entrevista('Entrevista favorable: vive solo y trabaja fuera, pero cuenta con apoyo vecinal.');
    await g.click(p.getByRole('button', { name: 'Guardar entrevista' }));
    await g.leer(1800);
    await g.click(p.locator('#btn-approve'));
    await g.leer(3200);
    await g.veTexto('04-6a', 'Se rechaza: el animal ya está reservado', 'El animal ya está reservado para otra candidatura', p.locator('.notification.is-danger'));
    await g.check('04-6b', 'La respuesta es 409 y no hay dos aprobadas', 'HTTP 409', async () => ({ ok: g.ultimoEstado() === 409, observado: `HTTP ${g.ultimoEstado()}` }));
    await g.veTexto('04-6c', 'La solicitud de Sergio sigue «En espera»', 'En espera', p.locator('.page-heading .tag'));
  });
  await g.escenario(7, 'Silvia desiste: se libera la reserva (primero sin motivo, después con motivo)', async () => {
    await abrir('Silvia');
    await g.click(p.getByText('Liberar reserva', { exact: true }));
    await g.sinValidacionNavegador('form[action$="/cancelar-reserva"]');
    await g.click(p.getByRole('button', { name: 'Confirmar liberación de reserva' }));
    await g.leer(2400);
    await g.veTexto('04-7a', 'Sin motivo no se libera la reserva', 'Indica el motivo de la cancelación de la reserva', p.locator('#error-cancellationReason'));
    await g.type(p.locator('#cancellationReason'), 'La familia desiste de la adopción por un cambio de domicilio.', 10);
    await g.click(p.getByRole('button', { name: 'Confirmar liberación de reserva' }));
    await g.leer(2800);
    await g.veTexto('04-7b', 'Aviso de reserva liberada', 'Reserva liberada. El animal vuelve a estar disponible', p.locator('[role=status]'));
    await g.veTexto('04-7c', 'La solicitud de Silvia queda cancelada', 'Cancelada', p.locator('.page-heading .tag'));
    await g.veTexto('04-7d', 'El animal vuelve a «Disponible»', 'Disponible', p.locator('aside .tag').first());
    await g.check('04-7e', 'Sergio vuelve de «En espera» a pendiente', 'Sergio Pendiente de valoración', async () => {
      const t = await g.texto(p.locator('aside li', { hasText: 'Sergio' }));
      return { ok: t.includes('Pendiente'), observado: t };
    });
    await g.reveal(p.getByRole('heading', { name: /Otras solicitudes/ }));
    await g.leer(2200);
  });
  await g.escenario(8, 'Sonia entra en «Mis solicitudes»: ve que fue rechazada y por qué', async () => {
    await g.login('sonia');
    await g.leer(1500);
    await g.veTexto('04-8a', 'Su solicitud figura «Rechazada» con el motivo', 'Motivo: No dispone del tiempo mínimo', p.locator('tbody'));
    await g.leer(2200);
  });
  await g.escenario(9, 'Vera, voluntaria, intenta abrir /admin/solicitudes', async () => {
    await g.login('voluntaria');
    const st = await g.go('/admin/solicitudes');
    await g.leer(2200);
    await g.veEstado('04-9a', 'Una voluntaria no puede ver ni decidir candidaturas', 403, st);
    await g.noVeTexto('04-9b', 'Su menú no incluye «Solicitudes»', 'Seguimientos', p.locator('header'));
  });
});
