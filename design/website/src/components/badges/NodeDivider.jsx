
import React from 'react';
import { cx } from '../core/LocaleProvider.jsx';

/** Hairline divider that starts with two yellow circuit nodes. Use once per section, max. */
export function NodeDivider({ className, style }) {
  return <hr className={cx('d-node-rule', className)} style={style} />;
}

