
import React from 'react';
import { cx } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';

/** Large selectable radio tile for delivery / payment methods. */
export function RadioCard({ title, desc, meta, icon, name, value, checked, onChange, className }) {
  return (
    <label className={cx('d-radio-card', checked && 'd-radio-card--on', className)}>
      <input type="radio" name={name} value={value} checked={!!checked} onChange={() => onChange && onChange(value)} style={{ position: 'absolute', opacity: 0, pointerEvents: 'none' }} />
      <span className="d-check"><input type="radio" readOnly checked={!!checked} tabIndex={-1} aria-hidden="true" /></span>
      <span style={{ flex: 1, minWidth: 0, display: 'grid', gap: 3 }}>
        <span style={{ font: '800 14px/1.3 var(--font-body)', display: 'flex', alignItems: 'center', gap: 8 }}>{icon && <Icon name={icon} size={18} />}{title}</span>
        {desc && <span style={{ font: '500 13px/1.45 var(--font-body)', color: 'var(--fg-2)' }}>{desc}</span>}
      </span>
      {meta && <span style={{ font: '800 14px/1.3 var(--font-body)', whiteSpace: 'nowrap' }}>{meta}</span>}
    </label>
  );
}

