// Utilidades compartidas por los guiones de la demo.
import { CTL } from './grabador.mjs';

export const foto = async (kind, n) => ({
  name: `${kind}-${n}.png`, mimeType: 'image/png',
  buffer: Buffer.from(await (await fetch(`${CTL}/photo.png?kind=${kind}&n=${n}`)).arrayBuffer()),
});
export const tarjeta = (g, code) => g.page.locator('article.animal-card', { hasText: code });
export const filaAdmin = (g, texto) => g.page.locator('tbody tr', { hasText: texto });
export const cuestionario = async (g, q) => {
  const p = g.page;
  await g.select(p.locator('#housingType'), q.housingType);
  await g.type(p.locator('#housing'), q.housing, 8);
  await g.select(p.locator('#experienceType'), q.experienceType);
  if (q.experience) await g.type(p.locator('#experience'), q.experience, 8);
  await g.select(p.locator('#dailyTime'), q.dailyTime);
  await g.type(p.locator('#availability'), q.availability, 8);
};
export const Q_BASE = {
  housingType: 'flat', housing: 'Piso de 70 m² con balcón, dos adultos y sin otros animales; el contrato de alquiler permite mascotas.',
  experienceType: 'dogs', experience: 'Hemos convivido con un perro durante diez años.', dailyTime: '2to4',
  availability: 'Uno de los dos trabaja desde casa; paseos por la mañana y por la tarde. En vacaciones lo cuida mi hermana.',
};
/** Abre en el panel de la coordinadora la solicitud de una persona. */
export const abrirSolicitud = async (g, persona, animalCode) => {
  await g.click(g.nav('Solicitudes'));
  await g.click(filaAdmin(g, animalCode ? `${persona}` : persona).filter({ hasText: animalCode ?? '' }).getByRole('link'));
};

export const VIDEOS = [];
export const def = (d, fn) => VIDEOS.push({ ...d, fn });
