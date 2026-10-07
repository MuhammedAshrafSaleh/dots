
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';

/** Round colour/finish swatches. options: {value, label, color (css background — may be a gradient for metals), disabled?}. */
export function SwatchPicker({ label, value, options = [], onChange, className }) {
  const { tx } = useLocale();
  const cur = options.find((o) => o.value === value);
  return (
    <div className={cx('d-variant', className)} role="group" aria-label={tx(label)}>
      <div className="d-variant__head">{tx(label)}{cur && <span>{tx(cur.label)}</span>}</div>
      <div className="d-variant__opts" style={{ gap: 12, paddingInline: 3 }}>
        {options.map((o) => (
          <button key={o.value} type="button" className="d-swatch" style={{ background: o.color }} aria-pressed={o.value === value}
            aria-label={tx(o.label)} title={tx(o.label)} disabled={o.disabled} onClick={() => onChange && onChange(o.value)} />
        ))}
      </div>
    </div>
  );
}

