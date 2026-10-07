
import React from 'react';
import { cx } from '../core/LocaleProvider.jsx';

/** tone: accent | dark | neutral | outline | success | warning | error | info | sale */
export function Badge({ tone = 'neutral', dot, children, className, ...rest }) {
  return (
    <span className={cx('d-badge', `d-badge--${tone}`, className)} {...rest}>
      {dot && <span className="d-badge__dot" />}{children}
    </span>
  );
}

