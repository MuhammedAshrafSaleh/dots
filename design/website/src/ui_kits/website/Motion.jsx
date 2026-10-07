
import React from 'react';

/** Custom cursor: precise dot + lagging ring. Grows over links/buttons, becomes a "View" disc over cards, squeezes on press. Fine pointers only. */
export function KCursor({ enabled }) {
  const dot = React.useRef(null);
  const ring = React.useRef(null);
  const label = React.useRef(null);
  React.useEffect(() => {
    if (!enabled || !window.matchMedia('(pointer: fine)').matches) return;
    document.documentElement.classList.add('d-has-cursor');
    let x = -100, y = -100, rx = -100, ry = -100, raf;
    const move = (e) => {
      x = e.clientX; y = e.clientY;
      if (dot.current) dot.current.style.transform = `translate(${x}px, ${y}px)`;
      const t = e.target.closest ? e.target : null;
      const card = t && t.closest('.d-card, .d-gallery__main, .d-ba');
      const hot = t && t.closest('a, button, [role="button"], select, label, .d-chip');
      const txt = t && t.closest('input, textarea');
      const r = ring.current; if (!r) return;
      r.dataset.mode = txt ? 'text' : card ? 'card' : hot ? 'hot' : '';
      if (label.current) label.current.textContent = card ? (t.closest('.d-ba') ? '⟷' : (document.documentElement.getAttribute('lang') === 'ar' || t.closest('[dir="rtl"]') ? 'عرض' : 'View')) : '';
    };
    const down = () => ring.current && ring.current.classList.add('is-down');
    const up = () => ring.current && ring.current.classList.remove('is-down');
    const leave = () => { if (ring.current) ring.current.style.opacity = 0; if (dot.current) dot.current.style.opacity = 0; };
    const enter = () => { if (ring.current) ring.current.style.opacity = 1; if (dot.current) dot.current.style.opacity = 1; };
    const loop = () => { rx += (x - rx) * 0.18; ry += (y - ry) * 0.18; if (ring.current) ring.current.style.transform = `translate(${rx}px, ${ry}px)`; raf = requestAnimationFrame(loop); };
    window.addEventListener('mousemove', move); window.addEventListener('mousedown', down); window.addEventListener('mouseup', up);
    document.addEventListener('mouseleave', leave); document.addEventListener('mouseenter', enter);
    raf = requestAnimationFrame(loop);
    return () => { document.documentElement.classList.remove('d-has-cursor'); cancelAnimationFrame(raf); window.removeEventListener('mousemove', move); window.removeEventListener('mousedown', down); window.removeEventListener('mouseup', up); document.removeEventListener('mouseleave', leave); document.removeEventListener('mouseenter', enter); };
  }, [enabled]);
  if (!enabled) return null;
  return (
    <>
      <div ref={ring} className="d-cur__ring" aria-hidden="true"><span className="d-cur__disc"><span ref={label} className="d-cur__label" /></span></div>
      <div ref={dot} className="d-cur__dot" aria-hidden="true"><span /></div>
    </>
  );
}

/** First-load preloader: wordmark, counting percentage and a node filling a line; exits with a two-layer curtain lift. */
export function KPreloader({ logoSrc, onReveal }) {
  const [n, setN] = React.useState(0);
  const [phase, setPhase] = React.useState('load');
  React.useEffect(() => {
    let raf, t0;
    const step = (ts) => { t0 = t0 || ts; const p = Math.min(1, (ts - t0) / 1700); setN(Math.round(100 * (1 - Math.pow(1 - p, 2.2)))); if (p < 1) raf = requestAnimationFrame(step); else { setTimeout(() => { setPhase('exit'); onReveal && onReveal(); }, 250); setTimeout(() => setPhase('done'), 1400); } };
    raf = requestAnimationFrame(step);
    const fb = setTimeout(() => { if (!t0) { setN(100); setPhase('done'); onReveal && onReveal(); } }, 2500);
    return () => { cancelAnimationFrame(raf); clearTimeout(fb); };
  }, []);
  if (phase === 'done') return null;
  return (
    <div className={'d-pre' + (phase === 'exit' ? ' is-exit' : '')} aria-hidden="true">
      <div className="d-pre__accent" />
      <div className="d-pre__panel">
        <div className="d-pre__in">
          <div className="d-pre__brand"><img src={(window.__resources || {}).mark || logoSrc.replace('dots-wordmark', 'dots-mark')} alt="" /><span>Dots</span></div>
          <div className="d-pre__line"><span style={{ width: n + '%' }}><i /></span></div>
          <div className="d-pre__meta"><span>Lighting · Smart Home · Electrical</span><b>{String(n).padStart(3, '0')}</b></div>
        </div>
      </div>
    </div>
  );
}

/** Page-transition curtain: ink panel with yellow leading edge sweeps across while the route swaps. phase: idle | in | out */
export function KCurtain({ phase, label }) {
  if (phase === 'idle') return null;
  return (
    <div className={'d-curtain is-' + phase} aria-hidden="true">
      <div className="d-curtain__y" />
      <div className="d-curtain__k"><span className="d-curtain__label"><span className="d-dot" />{label}</span></div>
    </div>
  );
}

