const { chromium } = require('playwright');
const fs = require('node:fs');
const assert = require('node:assert/strict');
const path = require('node:path');
const base = process.env.HUELLAS_TEST_URL || 'http://127.0.0.1:8087';
const fixture = JSON.parse(fs.readFileSync(path.join(__dirname, '../var/browser-data.json'), 'utf8'));
const output = path.join(__dirname, '../docs/evidencias/browser');
fs.mkdirSync(output, { recursive: true });
const results = [];
async function check(name, action) {
  await action(); results.push({ name, result: 'correcto' }); console.log(`OK ${name}`);
}
function madridNow() {
  return new Intl.DateTimeFormat('sv-SE', { timeZone: 'Europe/Madrid', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }).format(new Date()).replace(' ', 'T');
}
async function login(page, email) {
  await page.goto(`${base}/acceso`);
  await page.locator('#email').fill(email);
  await page.locator('#password').fill('Ficticia-Prueba-2026');
  await page.getByRole('button', { name: 'Acceder', exact: true }).click();
  await page.waitForURL(url => !url.pathname.startsWith('/acceso'));
}
(async () => {
  const browser = await chromium.launch({ headless: true });
  const contexts = [];
  const errors = [];
  const pageFor = async () => {
    const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    contexts.push(context); const page = await context.newPage();
    page.on('pageerror', error => errors.push(error.message));
    page.on('dialog', dialog => dialog.accept());
    return page;
  };
  let page;
  try {
    page = await pageFor();
    await check('CA-01-01/02: alta real con foto, Stimulus y cuarentena', async () => {
      await login(page, 'voluntario@example.test');
      await page.goto(`${base}/admin/animales/nuevo`);
      await page.locator('#species').selectOption('dog'); await page.locator('#size').selectOption('small');
      await page.locator('#ageMonths').fill('4'); await page.locator('#receivedAt').fill(madridNow());
      await page.locator('#origin').fill('Llegada ficticia del recorrido de navegador.');
      await page.locator('#photos').setInputFiles(fixture.photo);
      await page.locator('.photo-preview img').waitFor();
      await page.getByRole('button', { name: 'Guardar ficha', exact: true }).click();
      await page.waitForURL(/\/admin\/animales\/[0-9a-f-]+$/);
      fixture.createdId = new URL(page.url()).pathname.split('/').pop(); fixture.createdCode = await page.locator('h1').innerText();
      assert.match(fixture.createdCode, /^HUE-/); assert.match(await page.locator('main').innerText(), /En cuarentena/);
      await page.screenshot({ path: `${output}/01-ficha-cuarentena.png`, fullPage: true });
    });
    const vet = await pageFor();
    await check('CA-01-04: revisión veterinaria por su rol', async () => {
      await login(vet, 'veterinario@example.test'); await vet.goto(`${base}/admin/animales/${fixture.createdId}`);
      await vet.locator('#reviewedAt').fill(madridNow()); await vet.locator('#notes').fill('Revisión ficticia registrada desde navegador.');
      await vet.getByRole('button', { name: 'Guardar revisión', exact: true }).click();
      await vet.getByText('Revisión ficticia registrada desde navegador.', { exact: true }).waitFor();
    });
    const publicPage = await pageFor();
    await check('CA-02-01/03: catálogo anónimo y exclusión de cuarentena', async () => {
      await publicPage.goto(base); assert.equal(await publicPage.locator('.animal-card').count(), 3);
      assert.ok(!(await publicPage.locator('main').innerText()).includes(fixture.createdCode));
      await publicPage.locator('footer').scrollIntoViewIfNeeded();
      await publicPage.locator('img').evaluateAll(images => Promise.all(images.map(image => image.decode())));
      await publicPage.evaluate(() => window.scrollTo(0, 0));
      await publicPage.screenshot({ path: `${output}/02-catalogo-escritorio.png`, fullPage: true });
    });
    await check('CA-02-02/04: filtros AND y acogida visible', async () => {
      await publicPage.locator('#filter-species').selectOption('cat'); await publicPage.locator('#filter-age').selectOption('adult');
      await publicPage.getByRole('button', { name: 'Filtrar', exact: true }).click();
      await publicPage.waitForURL(/species=cat/); assert.equal(await publicPage.locator('.animal-card').count(), 1);
      assert.match(await publicPage.locator('.animal-card').innerText(), /En acogida/);
    });
    await check('Interfaz móvil sin desbordamiento horizontal', async () => {
      await publicPage.setViewportSize({ width: 390, height: 844 }); await publicPage.goto(base);
      assert.ok(await publicPage.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
      await publicPage.screenshot({ path: `${output}/03-catalogo-movil.png`, fullPage: true });
    });
    const applicant = await pageFor();
    await login(applicant, 'solicitante@example.test');
    await check('CA-03-01/02: cuestionario completo con contador Stimulus', async () => {
      for (const animal of fixture.animals.slice(0, 2)) {
        await applicant.goto(`${base}/animales/${animal.id}/solicitud`);
        await applicant.locator('#housing').fill('Vivienda ficticia con un espacio seguro.');
        await applicant.locator('#experience').fill('No tengo experiencia previa, me prepararé para sus cuidados.');
        await applicant.locator('#availability').fill('Dispongo de mañanas y tardes.');
        await applicant.getByText(/3 de 3 respuestas completadas/).waitFor();
        await applicant.getByRole('button', { name: 'Enviar solicitud', exact: true }).click();
        await applicant.waitForURL('**/mis-solicitudes');
      }
      assert.equal(await applicant.locator('main article').count(), 2);
    });
    await check('CA-03-03: tercera solicitud rechazada desde navegador', async () => {
      await applicant.goto(`${base}/animales/${fixture.animals[2].id}/solicitud`);
      for (const id of ['housing', 'experience', 'availability']) await applicant.locator(`#${id}`).fill('Respuesta ficticia completa.');
      await applicant.getByRole('button', { name: 'Enviar solicitud', exact: true }).click();
      await applicant.getByRole('alert').waitFor(); assert.match(await applicant.getByRole('alert').innerText(), /dos solicitudes abiertas/);
      await applicant.screenshot({ path: `${output}/04-limite-solicitudes.png`, fullPage: true });
    });
    const coordinator = await pageFor();
    await login(coordinator, 'coordinadora@example.test');
    await check('CA-03-04/CA-04-01: coordinación lee y registra entrevista', async () => {
      await coordinator.goto(`${base}/admin/solicitudes`);
      await coordinator.locator('main a.box').filter({ hasText: fixture.animals[0].code }).click();
      await coordinator.getByRole('heading', { name: 'Cuestionario de adopción', exact: true }).waitFor();
      assert.match(await coordinator.locator('main').innerText(), /Vivienda ficticia/);
      await coordinator.locator('#interviewAt').fill(madridNow()); await coordinator.locator('#interviewNotes').fill('Entrevista ficticia realizada en el refugio.');
      await coordinator.getByRole('button', { name: 'Guardar entrevista', exact: true }).click();
      await coordinator.getByText('Entrevista ficticia realizada en el refugio.', { exact: true }).first().waitFor();
    });
    await check('CA-04-03: aprobación con confirmación y reserva', async () => {
      await coordinator.getByRole('button', { name: 'Aprobar y reservar', exact: true }).click();
      await coordinator.locator('.page-heading .tag').filter({ hasText: 'Aprobada' }).waitFor();
      await coordinator.screenshot({ path: `${output}/05-aprobacion.png`, fullPage: true });
      await publicPage.goto(base); assert.equal(await publicPage.locator('.animal-card').count(), 2);
      const response = await publicPage.goto(`${base}/animales/${fixture.animals[0].id}`); assert.equal(response.status(), 404);
    });
    await check('CA-04-02: rechazo motivado y consulta del solicitante', async () => {
      await coordinator.goto(`${base}/admin/solicitudes`);
      await coordinator.locator('main a.box').filter({ hasText: fixture.animals[1].code }).click();
      await coordinator.getByRole('heading', { name: 'Cuestionario de adopción', exact: true }).waitFor();
      await coordinator.locator('#reason').fill('Disponibilidad ficticia incompatible con este animal.');
      await coordinator.getByRole('button', { name: 'Rechazar solicitud', exact: true }).click();
      await coordinator.locator('.page-heading .tag').filter({ hasText: 'Rechazada' }).waitFor();
      await applicant.goto(`${base}/mis-solicitudes`); assert.match(await applicant.locator('main').innerText(), /Disponibilidad ficticia incompatible/);
    });
    await check('Ningún error JavaScript en Turbo/Stimulus', async () => { assert.deepEqual(errors, []); });
    fs.writeFileSync(`${output}/resultados.json`, JSON.stringify({ base, results, javascriptErrors: errors }, null, 2));
  } catch (error) {
    if (page) await page.screenshot({ path: `${output}/fallo.png`, fullPage: true }).catch(() => {});
    fs.writeFileSync(`${output}/resultados.json`, JSON.stringify({ results, error: error.stack, javascriptErrors: errors }, null, 2));
    throw error;
  } finally { for (const context of contexts) await context.close(); await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
