
import React from 'react';
import { cx } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';

/** Native select styled as a Dots input. options: string[] or {value,label}[]. */
export function Select({ label, help, error, options = [], size, id, className, style, placeholder, ...rest }) {
  const auto = React.useId();
  const fid = id || auto;
  return (
    <div className={cx('d-field', className)} style={style}>
      {label && <label className="d-label" htmlFor={fid}>{label}</label>}
      <div className="d-control">
        <select id={fid} className={cx('d-input', 'd-select', size === 'sm' && 'd-input--sm')} aria-invalid={error ? 'true' : undefined} {...rest}>
          {placeholder && <option value="">{placeholder}</option>}
          {options.map((o) => { const v = typeof o === 'string' ? { value: o, label: o } : o; return <option key={v.value} value={v.value} disabled={v.disabled}>{v.label}</option>; })}
        </select>
        <span className="d-control__trail"><Icon name="chevron-down" size={18} /></span>
      </div>
      {error ? <span className="d-help d-help--error">{error}</span> : help && <span className="d-help">{help}</span>}
    </div>
  );
}

