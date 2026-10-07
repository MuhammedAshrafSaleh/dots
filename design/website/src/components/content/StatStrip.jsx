
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';

/** Key-numbers strip. stats: {value: number, suffix?: string, label}[]. Counts up once when scrolled into view. */
export function StatStrip({ stats = [], className }) {
  const { tx } = useLocale();
  const ref = React.useRef(null);
  const [k, setK] = React.useState(0);
  React.useEffect(() => {
    const el = ref.current; if (!el) return;
    let raf, t0, started = false;
    const run = (ts) => { t0 = t0 || ts; const p = Math.min(1, (ts - t0) / 1200); setK(1 - Math.pow(1 - p, 3)); if (p < 1) raf = requestAnimationFrame(run); };
    const check = () => { if (started) return; const r = el.getBoundingClientRect(); if (r.top < window.innerHeight && r.bottom > 0) { started = true; raf = requestAnimationFrame(run); setTimeout(() => setK(1), 1600); } };
    const iv = setInterval(check, 300); window.addEventListener('scroll', check, true); check();
    return () => { clearInterval(iv); window.removeEventListener('scroll', check, true); cancelAnimationFrame(raf); };
  }, []);
  return (
    <div ref={ref} className={cx('d-stats', className)}>
      {stats.map((s, i) => (
        <div key={i} className="d-stat">
          <span className="d-stat__n">{Math.round(s.value * k).toLocaleString('en-US')}{s.suffix && <sup>{s.suffix}</sup>}</span>
          <span className="d-stat__l">{tx(s.label)}</span>
        </div>
      ))}
    </div>
  );
}

