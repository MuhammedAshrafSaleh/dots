
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';

/**
 * Text-option variant picker (size, wattage, colour temperature, beam angle).
 * options: {value, label, sub?, chip? (css color), disabled?}. `tall` stacks label + sub (e.g. 3000K / Warm white).
 */
export function VariantPicker({ label, value, options = [], onChange, tall, className }) {
  const { tx } = useLocale();
  const cur = options.find((o) => o.value === value);
  return (
    <div className={cx('d-variant', className)} role="group" aria-label={tx(label)}>
      <div className="d-variant__head">{tx(label)}{cur && <span>{tx(cur.label)}{cur.sub ? ' · ' + tx(cur.sub) : ''}</span>}</div>
      <div className="d-variant__opts">
        {options.map((o) => (
          <button key={o.value} type="button" className={cx('d-opt', tall && 'd-opt--tall')} aria-pressed={o.value === value}
            disabled={o.disabled} onClick={() => onChange && onChange(o.value)}>
            {o.chip && !tall && <span className="d-opt__chip" style={{ background: o.chip }} />}
            <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6 }}>{o.chip && tall && <span className="d-opt__chip" style={{ background: o.chip, width: 12, height: 12 }} />}{tx(o.label)}</span>
            {tall && o.sub && <span className="d-opt__sub">{tx(o.sub)}</span>}
          </button>
        ))}
      </div>
    </div>
  );
}

