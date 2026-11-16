import '/vendor/turbo.js';
import { Application, Controller } from '/vendor/stimulus.js';
const application = Application.start();
application.register('photos', class extends Controller {
  static targets = ['preview'];
  urls = [];
  preview(event) {
    this.urls.forEach(url => URL.revokeObjectURL(url));
    this.urls = [];
    this.previewTarget.replaceChildren();
    for (const file of [...event.target.files].slice(0, 6)) {
      const image = document.createElement('img');
      image.alt = 'Vista previa de la fotografía seleccionada';
      image.src = URL.createObjectURL(file);
      this.urls.push(image.src);
      this.previewTarget.append(image);
    }
  }
  disconnect() { this.urls.forEach(url => URL.revokeObjectURL(url)); }
});
