
import React from 'react';
import { cx } from '../core/LocaleProvider.jsx';

/** Section eyebrow with the circuit-node lead (● ─). Use above section titles. */
export function Eyebrow({ children, className, style }) {
  return <span className={cx('d-eyebrow', className)} style={style}>{children}</span>;
}

