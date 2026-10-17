// Public site JS entry.
//
// Livewire 4 ships and starts Alpine itself (auto-injected assets), and the
// site layout always mounts a Livewire component (the cart drawer), so Alpine
// is present on every public page. We must NOT import/start a second Alpine
// here — a duplicate instance has no `$wire` magic, so it throws
// "$wire is not defined" on `x-show="$wire.open"` and makes Livewire morphs
// (e.g. the cart drawer stepper) flaky. Rely on Livewire's global Alpine.
import { initMotion } from './site/motion.js';

// Preloader, page curtain, custom cursor, scroll reveal + responsive compact
// flag. All disabled under prefers-reduced-motion / when JS is off.
initMotion();
