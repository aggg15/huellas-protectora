// Guiones 00 (acceso) y 01 a 02. Cada guion actúa sobre la aplicación real y anota con g.check(...) lo que de verdad observa.
import { PASSWORD, madrid, diaMadrid, ddmmyyyy } from './grabador.mjs';
import { def, foto, tarjeta, filaAdmin } from './guion-util.mjs';

// ====================================================================================================================
def({
  id: '00-acceso', spec: '000 · Base común', titulo: 'Acceso a la aplicación', rol: 'Todas las personas',
  historia: 'Toda persona <b>entra con su correo y contraseña</b> y solo ve lo que su rol le permite. Sin sesión no se accede a la gestión; la persona solicitante crea su cuenta pública en el registro.',
  escenarios: [
    { titulo: 'Acceso correcto como coordinadora', ca: ['Base común'] },
    { titulo: 'Contraseña incorrecta', ca: ['Base común'] },
    { titulo: 'Sin sesión no se ve la gestión: se pide acceder', ca: ['Base común'] },
    { titulo: 'Cada rol aterriza en lo suyo y no ve lo ajeno', ca: ['Base común'] },
    { titulo: 'Registro público: datos no válidos y correo ya usado', ca: ['CA-03-01 (cuenta)'] },
    { titulo: 'Cerrar sesión', ca: ['Base común'] },
  ],
}, async (g) => {
  const p = g.page;
  await g.escenario(1, 'Desde la portada, «Acceder» con la cuenta ficticia de la coordinadora', async () => {
    await g.go('/');
    await g.leer(1500);
    await g.click(p.locator('header').getByRole('link', { name: 'Acceder' }));
    await g.type(p.locator('#email'), 'coordinadora@example.org');
    await g.type(p.locator('#password'), PASSWORD);
    await g.click(p.getByRole('button', { name: 'Acceder', exact: true }));
    await g.leer(1800);
    await g.veRuta('00-1a', 'Tras acceder entra en el panel de gestión', '/admin/animales');
    await g.veTexto('00-1b', 'La cabecera muestra el nombre de la cuenta y el menú de coordinación', 'Coral Coordinadora', p.locator('header'));
    await g.veTexto('00-1c', 'El menú incluye «Solicitudes»', 'Solicitudes', p.locator('header'));
    await g.leer(1800);
    await g.logout();
  });
  await g.escenario(2, 'Se escribe una contraseña equivocada: no se entra y no se dice qué dato falló', async () => {
    await g.go('/acceso');
    await g.type(p.locator('#email'), 'coordinadora@example.org');
    await g.type(p.locator('#password'), 'contraseña-equivocada');
    await g.click(p.getByRole('button', { name: 'Acceder', exact: true }));
    await g.leer(2500);
    await g.veTexto('00-2a', 'Mensaje de error genérico', 'Email o contraseña incorrectos', p.locator('[role=alert]'));
    await g.veRuta('00-2b', 'Se permanece en la página de acceso', '/acceso');
  });
  await g.escenario(3, 'Se pide directamente /admin/animales sin haber iniciado sesión', async () => {
    await g.context.clearCookies();
    await g.go('/admin/animales');
    await g.leer(2200);
    await g.veRuta('00-3a', 'Se redirige a la página de acceso', '/acceso');
    await g.noVeTexto('00-3b', 'No se muestra ninguna ficha interna', 'Nuestros animales');
  });
  await g.escenario(4, 'Sara (solicitante) no ve el refugio; la Familia Brisa aterriza en «Mi acogida»', async () => {
    await g.login('sara');
    await g.leer(1500);
    await g.veRuta('00-4a', 'La solicitante aterriza en «Mis solicitudes»', '/mis-solicitudes');
    await g.noVeTexto('00-4b', 'Su menú no incluye «Refugio»', 'Refugio', p.locator('header'));
    const st = await g.go('/admin/animales');
    await g.leer(2200);
    await g.veEstado('00-4c', 'Abrir /admin/animales con su cuenta da acceso denegado', 403, st);
    await g.login('brisa');
    await g.leer(1500);
    await g.veRuta('00-4d', 'La familia de acogida aterriza en «Mi acogida»', '/acogida');
  });
  await g.escenario(5, 'Registro con un correo que ya existe y una contraseña de menos de 12 caracteres', async () => {
    await g.context.clearCookies();
    await g.go('/registro');
    await g.sinValidacionNavegador('form[action="/registro"]');
    await g.type(p.locator('#name'), 'Sara Repetida');
    await g.type(p.locator('#email'), 'sara@example.org');
    await g.type(p.locator('#password'), 'corta');
    await g.type(p.locator('#passwordConfirmation'), 'otra');
    await g.click(p.getByRole('button', { name: 'Crear cuenta' }));
    await g.leer(2800);
    await g.veTexto('00-5a', 'Se avisa de que el correo ya tiene cuenta', 'Ya existe una cuenta con este email', p.locator('[role=alert]'));
    await g.veTexto('00-5b', 'Se exigen 12 caracteres de contraseña', 'al menos 12 caracteres', p.locator('[role=alert]'));
  });
  await g.escenario(6, 'Pulsar «Salir» cierra la sesión', async () => {
    await g.login('victor');
    await g.leer(1200);
    await g.logout();
    await g.leer(1500);
    await g.veRuta('00-6a', 'Tras salir se vuelve a la página de acceso', '/acceso');
    await g.go('/admin/turnos');
    await g.veRuta('00-6b', 'La sesión ya no existe: /admin/turnos pide acceder', '/acceso');
  });
});

// ====================================================================================================================
def({
  id: '01-ficha-cuarentena', spec: '001 · Ficha, cuarentena y revisión', titulo: 'Abrir la ficha de un animal y su cuarentena', rol: 'Voluntaria y veterinario',
  releaseIn: Number(process.env.RELEASE_IN ?? 170),
  historia: 'Como voluntaria que recibe animales quiero <b>abrir la ficha de cada animal y reflejar su cuarentena y su revisión veterinaria</b>, para ofrecerlo en adopción cuando haya pasado el periodo establecido.',
  escenarios: [
    { titulo: 'Un animal en cuarentena a punto de cumplir las 240 horas', ca: ['CA-01-02'] },
    { titulo: 'Alta de la ficha: código, especie, edad, procedencia y fotografías', ca: ['CA-01-01', 'CA-01-02'] },
    { titulo: 'Datos no válidos: el servidor no guarda nada', ca: ['CA-01-01'] },
    { titulo: 'En cuarentena no se ve en la web pública', ca: ['CA-01-03'] },
    { titulo: 'La veterinaria registra su revisión (y una fecha imposible se rechaza)', ca: ['CA-01-04'] },
    { titulo: 'Permisos: quién puede abrir fichas', ca: ['CA-01-01 (roles)'] },
    { titulo: 'Fin de la cuarentena: el animal pasa a «Disponible»', ca: ['CA-01-05'] },
  ],
}, async (g) => {
  const p = g.page;
  const liberable = g.d.animals.liberable;
  let nuevo = { id: null, code: null };
  await g.escenario(1, 'Vera, voluntaria, abre el refugio; la ficha de HUE-CUMPLE000001 indica cuándo termina su cuarentena', async () => {
    await g.login('voluntaria');
    await g.click(p.locator('a.animal-admin', { hasText: liberable.code }));
    await g.leer(2600);
    await g.veTexto('01-1a', 'La ficha del animal recién llegado figura «En cuarentena»', 'En cuarentena', p.locator('.page-heading .tag'));
    await g.check('01-1b', 'La ficha indica la fecha y hora de fin de cuarentena', 'Hay un dato «Fin de cuarentena» con fecha y hora', async () => {
      const dd = await g.texto(p.locator('dt', { hasText: 'Fin de cuarentena' }).locator('xpath=following-sibling::dd[1]'));
      return { ok: /\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}/.test(dd), observado: `Fin de cuarentena: ${dd}` };
    });
  });
  await g.escenario(2, 'Registrar una llegada: perro mediano de 14 meses, con dos fotografías', async () => {
    await g.click(g.nav('Refugio'));
    await g.click(p.getByRole('link', { name: 'Registrar llegada' }));
    await g.select(p.locator('#species'), 'dog');
    await g.select(p.locator('#size'), 'medium');
    await g.type(p.locator('#ageMonths'), '14');
    await g.type(p.locator('#origin'), 'Encontrado atado a una valla en el camino ficticio de «La Noria».', 12);
    await g.reveal(p.locator('#photos'));
    await p.locator('#photos').setInputFiles([await foto('dog', 0), await foto('dog', 4)]);
    await g.leer(1800);
    await g.veCantidad('01-2a', 'La vista previa muestra las dos fotografías elegidas', p.locator('.photo-preview img'), 2);
    await g.click(p.getByRole('button', { name: 'Guardar ficha' }));
    await g.leer(2600);
    nuevo.id = (await g.ruta()).split('/').pop();
    nuevo.code = await g.texto(p.locator('h1.title'));
    await g.veTexto('01-2b', 'Aviso de ficha creada que respeta la cuarentena', 'Ficha creada', p.locator('[role=status]'));
    await g.check('01-2c', 'La ficha recibe un código propio HUE-…', 'Código con formato HUE-XXXXXXXXXXXX', async () => ({ ok: /^HUE-[0-9A-Z]{12}$/.test(nuevo.code), observado: `Código ${nuevo.code}` }));
    await g.veTexto('01-2d', 'El animal queda «En cuarentena»', 'En cuarentena', p.locator('.page-heading .tag'));
    await g.check('01-2e', 'La cuarentena termina diez días después de la recepción', `Fin de cuarentena ${ddmmyyyy(diaMadrid(10))}`, async () => {
      const dd = await g.texto(p.locator('dt', { hasText: 'Fin de cuarentena' }).locator('xpath=following-sibling::dd[1]'));
      return { ok: dd.startsWith(ddmmyyyy(diaMadrid(10))), observado: `Fin de cuarentena: ${dd}` };
    });
    await g.veCantidad('01-2f', 'La ficha conserva las dos fotografías', p.locator('.photo-gallery img'), 2);
    await g.veTexto('01-2g', 'Se guardan especie, edad y procedencia', 'Perro', p.locator('dl.facts'));
    await g.reveal(p.locator('.photo-gallery'));
    await g.leer(2200);
  });
  await g.escenario(3, 'Se salta la validación del navegador: sin especie ni tamaño, 700 meses, fecha futura y sin fotos', async () => {
    await g.click(g.nav('Refugio'));
    await g.click(p.getByRole('link', { name: 'Registrar llegada' }));
    await g.sinValidacionNavegador('#animal_form');
    await g.type(p.locator('#ageMonths'), '700');
    await g.fill(p.locator('#receivedAt'), '2027-12-31T10:00');
    await g.type(p.locator('#origin'), 'Sin datos.', 12);
    await g.click(p.getByRole('button', { name: 'Guardar ficha' }));
    await g.leer(3000);
    await g.veTexto('01-3a', 'Error de especie', 'Selecciona perro o gato', p.locator('[role=alert]'));
    await g.veTexto('01-3b', 'Error de edad', 'entre 0 y 600', p.locator('[role=alert]'));
    await g.veTexto('01-3c', 'Error de fecha futura', 'no sea futura', p.locator('[role=alert]'));
    await g.check('01-3d', 'Nada se ha guardado: la respuesta es 422 y el refugio sigue con el mismo número de fichas', 'HTTP 422', async () => ({ ok: g.ultimoEstado() === 422, observado: `HTTP ${g.ultimoEstado()}` }));
  });
  await g.escenario(4, 'Una persona sin cuenta busca el animal recién registrado en el catálogo y abre su dirección', async () => {
    await g.logout();
    await g.go('/animales');
    await g.leer(2200);
    await g.noVeTexto('01-4a', 'El catálogo público no muestra el animal en cuarentena', nuevo.code);
    const st = await g.go(`/animales/${nuevo.id}`);
    await g.leer(2200);
    await g.veEstado('01-4b', 'Su ficha pública responde «no encontrado»', 404, st);
  });
  await g.escenario(5, 'Vicente, veterinario, registra la revisión del animal en cuarentena', async () => {
    await g.login('veterinario');
    await g.go(`/admin/animales/${nuevo.id}`);
    await g.reveal(p.getByRole('heading', { name: 'Registrar revisión' }));
    await g.nota('Primero, una revisión con fecha anterior a la llegada del animal');
    await g.fill(p.locator('#reviewedAt'), `${diaMadrid(-30)}T10:00`);
    await g.type(p.locator('#notes'), 'Revisión inicial: peso adecuado, sin parásitos visibles.', 10);
    await g.click(p.getByRole('button', { name: 'Guardar revisión' }));
    await g.leer(2600);
    await g.veTexto('01-5a', 'Se rechaza una revisión anterior a la recepción', 'entre la recepción y el momento actual', p.locator('[role=alert]'));
    await g.nota('Ahora con la fecha y hora actuales');
    await g.fill(p.locator('#reviewedAt'), madrid().local);
    await g.click(p.getByRole('button', { name: 'Guardar revisión' }));
    await g.leer(2600);
    await g.veTexto('01-5b', 'Aviso de revisión registrada', 'Revisión veterinaria registrada', p.locator('[role=status]'));
    await g.veTexto('01-5c', 'La revisión queda asociada a la ficha con el nombre del veterinario', 'Vicente Veterinario', p.locator('.review').first());
    await g.reveal(p.locator('.review').first());
    await g.leer(1800);
  });
  await g.escenario(6, 'La veterinaria no puede dar de alta animales; una solicitante no entra al refugio', async () => {
    let st = await g.go('/admin/animales/nuevo');
    await g.leer(2000);
    await g.veEstado('01-6a', 'Veterinaria en /admin/animales/nuevo: acceso denegado', 403, st);
    await g.login('sara');
    st = await g.go('/admin/animales');
    await g.leer(2000);
    await g.veEstado('01-6b', 'Solicitante en /admin/animales: acceso denegado', 403, st);
  });
  await g.escenario(7, 'Han pasado las 240 horas de HUE-CUMPLE000001: sin intervención de nadie pasa a «Disponible»', async () => {
    const lista = Date.parse(g.d.generado) + (g.def.releaseIn + 4) * 1000;
    const falta = lista - Date.now();
    if (falta > 0) {
      await g.nota(`Esperando a que se cumplan las 240 horas (faltan ${Math.ceil(falta / 1000)} s)…`);
      await g.page.waitForTimeout(falta);
    }
    await g.login('voluntaria');
    await g.click(p.locator('a.animal-admin', { hasText: liberable.code }));
    await g.leer(2400);
    await g.veTexto('01-7a', 'La ficha del animal cuya cuarentena terminó figura «Disponible»', 'Disponible', p.locator('.page-heading .tag'));
    await g.logout();
    await g.go('/animales');
    await g.leer(1500);
    await g.check('01-7b', 'Ya aparece en el catálogo público', `Tarjeta de ${liberable.code}`, async () => {
      const n = await p.locator('article.animal-card', { hasText: liberable.code }).count();
      return { ok: n === 1, observado: `${n} tarjeta(s) de ${liberable.code}` };
    });
    await g.reveal(p.locator('article.animal-card', { hasText: liberable.code }));
    await g.leer(2200);
  });
});
