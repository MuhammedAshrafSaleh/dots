
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';

/** Underline tabs (default) or pill segmented tabs. tabs: {id, label, count?, disabled?}[] */
export function Tabs({ tabs = [], value, onChange, variant = 'line', className, style }) {
  const { tx } = useLocale();
  return (
    <div role="tablist" className={cx('d-tabs', variant === 'pill' && 'd-tabs--pill', className)} style={style}>
      {tabs.map((tb) => (
        <button key={tb.id} role="tab" type="button" className="d-tab" aria-selected={tb.id === value} disabled={tb.disabled}
          onClick={() => onChange && onChange(tb.id)}>
          {tx(tb.label)}{tb.count != null && <span className="d-tab__n">{tb.count}</span>}
        </button>
      ))}
    </div>
  );
}

