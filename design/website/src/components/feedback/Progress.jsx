
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';

/** Progress bar with pulsing node head; optional milestone steps. value 0–100; tone "light" for use on photos. */
export function Progress({ value = 0, label, steps, current, tone, className }) {
  const { tx } = useLocale();
  const done = value >= 100;
  return (
    <div className={cx('d-progress', done && 'd-progress--done', tone === 'light' && 'd-progress--light', className)} role="progressbar" aria-valuenow={value} aria-valuemin={0} aria-valuemax={100} aria-label={tx(label)}>
      {label && <div className="d-progress__head"><span>{tx(label)}</span><b>{value}%</b></div>}
      <div className="d-progress__track"><div className="d-progress__fill" style={{ width: Math.min(100, value) + '%' }} /></div>
      {steps && (
        <div className="d-progress__steps">
          {steps.map((s, i) => <span key={i} className={cx('d-progress__step', i < current && 'd-progress__step--done', i === current && 'd-progress__step--now')}>{tx(s)}</span>)}
        </div>
      )}
    </div>
  );
}

