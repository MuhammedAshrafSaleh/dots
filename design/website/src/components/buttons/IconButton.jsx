
import React from 'react';
import { cx } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';

/** Square icon-only button. Always pass `label` (becomes aria-label). Optional `count` shows a yellow badge (cart). */
export function IconButton({ icon, label, variant = 'ghost', size = 'md', count, className, ...rest }) {
  const isz = size === 'sm' ? 18 : size === 'lg' ? 22 : 20;
  return (
    <button type="button" aria-label={label} title={label}
      className={cx('d-btn', `d-btn--${variant}`, 'd-btn--icon', size !== 'md' && `d-btn--${size}`, count != null && 'd-btn--has-count', className)} {...rest}>
      <Icon name={icon} size={isz} />
      {count != null && count > 0 && <span className="d-btn__count">{count}</span>}
    </button>
  );
}

