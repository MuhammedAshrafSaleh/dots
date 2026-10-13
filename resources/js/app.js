// Public site JS entry.
import Alpine from 'alpinejs';
import { initMotion } from './site/motion.js';

window.Alpine = Alpine;
Alpine.start();

// Preloader, page curtain, custom cursor, scroll reveal + responsive compact
// flag. All disabled under prefers-reduced-motion / when JS is off.
initMotion();
