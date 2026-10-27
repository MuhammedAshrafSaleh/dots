// Public-site motion (port of design/website/src/ui_kits/website/Motion.jsx):
// first-visit preloader, page-transition curtain, custom cursor, scroll reveal.
// All are progressive enhancement and fully disabled under prefers-reduced-motion.

const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// ---- Responsive compact flag -------------------------------------------------
// Mirrors the design's `compact` mode: toggles `.d-compact` on <html> so the
// compact header + mobile component styles apply. The initial value is set by a
// synchronous inline script in the layout <head> to avoid a flash.
function initCompact() {
    const mq = window.matchMedia('(max-width: 860px)');
    const apply = () => document.documentElement.classList.toggle('d-compact', mq.matches);
    mq.addEventListener('change', apply);
    apply();
}

// ---- Scroll reveal -----------------------------------------------------------
function initReveal() {
    const items = document.querySelectorAll('.d-rv');
    if (reduceMotion() || !('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-on'));
        return;
    }
    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-on');
                io.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px' });
    items.forEach((el) => io.observe(el));
}

// ---- Custom cursor -----------------------------------------------------------
function initCursor() {
    if (reduceMotion() || !window.matchMedia('(pointer: fine)').matches) return;
    const ring = document.querySelector('[data-motion="cursor-ring"]');
    const dot = document.querySelector('[data-motion="cursor-dot"]');
    const label = document.querySelector('[data-cursor-label]');
    if (!ring || !dot) return;

    document.documentElement.classList.add('d-has-cursor');
    let x = -100, y = -100, rx = -100, ry = -100, raf;
    const ar = () => document.documentElement.getAttribute('lang') === 'ar-EG';

    const move = (e) => {
        x = e.clientX; y = e.clientY;
        dot.style.transform = `translate(${x}px, ${y}px)`;
        const t = e.target.closest ? e.target : null;
        const card = t && t.closest('.d-card, .d-gallery__main');
        const hot = t && t.closest('a, button, [role="button"], select, label, .d-chip');
        const txt = t && t.closest('input, textarea');
        ring.dataset.mode = txt ? 'text' : card ? 'card' : hot ? 'hot' : '';
        if (label) label.textContent = card ? (ar() ? 'عرض' : 'View') : '';
    };
    const down = () => ring.classList.add('is-down');
    const up = () => ring.classList.remove('is-down');
    const leave = () => { ring.style.opacity = 0; dot.style.opacity = 0; };
    const enter = () => { ring.style.opacity = 1; dot.style.opacity = 1; };
    const loop = () => { rx += (x - rx) * 0.18; ry += (y - ry) * 0.18; ring.style.transform = `translate(${rx}px, ${ry}px)`; raf = requestAnimationFrame(loop); };

    window.addEventListener('mousemove', move);
    window.addEventListener('mousedown', down);
    window.addEventListener('mouseup', up);
    document.addEventListener('mouseleave', leave);
    document.addEventListener('mouseenter', enter);
    raf = requestAnimationFrame(loop);
}

// ---- First-visit preloader ---------------------------------------------------
function initPreloader() {
    const pre = document.querySelector('[data-motion="preloader"]');
    if (!pre) return;
    // The inline head script adds `.d-preload` only on the first visit of a
    // session and when motion is allowed. Otherwise the preloader stays hidden.
    if (!document.documentElement.classList.contains('d-preload')) {
        pre.remove();
        return;
    }
    try { sessionStorage.setItem('dots-pre', '1'); } catch (e) { /* ignore */ }

    const countEl = pre.querySelector('[data-pre-count]');
    const bar = pre.querySelector('.d-pre__line > span');
    let t0, raf;
    const step = (ts) => {
        t0 = t0 || ts;
        const p = Math.min(1, (ts - t0) / 1700);
        const n = Math.round(100 * (1 - Math.pow(1 - p, 2.2)));
        if (countEl) countEl.textContent = String(n).padStart(3, '0');
        if (bar) bar.style.width = n + '%';
        if (p < 1) { raf = requestAnimationFrame(step); return; }
        setTimeout(() => pre.classList.add('is-exit'), 250);
        setTimeout(() => pre.remove(), 1650);
    };
    raf = requestAnimationFrame(step);
    // Safety: never leave the preloader covering the page.
    setTimeout(() => { if (document.body.contains(pre)) { cancelAnimationFrame(raf); pre.remove(); } }, 4000);
}

// ---- Page-transition curtain -------------------------------------------------
function initCurtain() {
    if (reduceMotion()) return;
    const curtain = document.querySelector('[data-motion="curtain"]');
    if (!curtain) return;
    const labelEl = curtain.querySelector('[data-curtain-label]');

    document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const a = e.target.closest && e.target.closest('a[href]');
        if (!a) return;
        if (a.target === '_blank' || a.hasAttribute('download') || a.dataset.noCurtain !== undefined) return;
        const url = new URL(a.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        if (url.pathname === window.location.pathname && url.search === window.location.search) return; // same page / hash
        if (url.href.startsWith('mailto:') || url.href.startsWith('tel:')) return;

        e.preventDefault();
        if (labelEl) labelEl.textContent = (a.textContent || 'Dots').trim().slice(0, 40);
        curtain.hidden = false;
        curtain.classList.remove('is-out');
        curtain.classList.add('is-in');
        setTimeout(() => { window.location.href = url.href; }, 480);
    });
}

// ---- Page-in: drop the animation once it finishes --------------------------
// `.d-pagein` animates transform with fill-mode `both`; it ends visually at
// `transform: none` but the retained computed value is `matrix(1,0,0,1,0,0)`,
// which is non-none. A non-none transform makes <main> a containing block for
// `position: fixed` descendants, so the mobile filter drawer anchors to <main>
// instead of the viewport. Removing the animation (CSS animations outrank inline
// styles, so we can't just set transform) drops the retained fill value and lets
// <main> revert to transform:none.
function initPageIn() {
    const el = document.querySelector('.d-pagein');
    if (!el) return;
    const clear = (e) => {
        if (e && e.animationName && e.animationName !== 'd-page-in') return;
        el.style.animation = 'none';
    };
    el.addEventListener('animationend', clear, { once: true });
    // Fallback if the animation was skipped/already finished (reduced motion,
    // bfcache restore) so the transform can never linger.
    setTimeout(clear, 1200);
}

export function initMotion() {
    initCompact();
    const run = () => { initReveal(); initCursor(); initPreloader(); initCurtain(); initPageIn(); };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
}
