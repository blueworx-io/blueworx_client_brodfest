// Front-end behaviour for the plugin's pages. Bundled by scripts/build-assets.mjs
// to assets/js/public.js, which is the only script includes/assets.php loads.
// Each behaviour lives in its own module and is imported here.

import { initHeader } from './header.js';
import { initIntro } from './intro.js';
import { initCarousels } from './carousel.js';
import { initCalendar } from './calendar.js';
import { initRsvp } from './rsvp.js';
import { initRsvpPanel } from './rsvp-panel.js';
import { initAnimations } from './animations.js';

document.documentElement.classList.add('brodfest-js');

// Focus rings show while the keyboard is in use, never after a tap or click.
document.addEventListener('keydown', () => document.documentElement.setAttribute('data-bf-keys', ''), true);
document.addEventListener('pointerdown', () => document.documentElement.removeAttribute('data-bf-keys'), true);

initHeader();
initIntro();
initCarousels();
initCalendar();
initRsvp();
initRsvpPanel();
initAnimations();
