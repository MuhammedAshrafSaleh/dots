
import React from 'react';
import { cx } from '../core/LocaleProvider.jsx';

/** Radio — yellow fill with an ink centre node when selected. */
export function Radio({ label, name, value, checked, onChange, disabled, className, ...rest }) {
  return (
    <label className={cx('d-check', disabled && 'd-check--disabled', className)}>
      <input type="radio" name={name} value={value} checked={!!checked} disabled={disabled} onChange={() => onChange && onChange(value)} {...rest} />
      <span>{label}</span>
    </label>
  );
}

