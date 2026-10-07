
import React from 'react';
import { cx } from '../core/LocaleProvider.jsx';

/** Checkbox with optional result count (filters). `row` stretches to full width so count aligns to the end edge. */
export function Checkbox({ label, count, checked, onChange, disabled, row, className, ...rest }) {
  return (
    <label className={cx('d-check', row && 'd-check--row', disabled && 'd-check--disabled', className)}>
      <input type="checkbox" checked={!!checked} disabled={disabled} onChange={(e) => onChange && onChange(e.target.checked)} {...rest} />
      <span>{label}</span>
      {count != null && <span className="d-check__count">{count}</span>}
    </label>
  );
}

