// Front-end behaviour for the plugin's pages. Bundled by scripts/build-assets.mjs
// to assets/js/public.js, which is the only script includes/assets.php loads.
// Each behaviour lives in its own module and is imported here.

import { initHeader } from './header.js';
import { initIntro } from './intro.js';
import { initCarousels } from './carousel.js';
import { initRsvp } from './rsvp.js';

document.documentElement.classList.add('brodfest-js');

initHeader();
initIntro();
initCarousels();
initRsvp();
