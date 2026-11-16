import { mkdirSync, copyFileSync } from 'node:fs';
mkdirSync('public/vendor', { recursive: true });
for (const [source, target] of [
  ['@hotwired/turbo/dist/turbo.es2017-esm.js', 'turbo.js'],
  ['@hotwired/stimulus/dist/stimulus.js', 'stimulus.js'],
  ['bulma/css/bulma.min.css', 'bulma.css'],
]) copyFileSync(`node_modules/${source}`, `public/vendor/${target}`);
console.log('Turbo, Stimulus y Bulma disponibles localmente.');
