
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';

/** − / qty / + stepper. Controlled via value + onChange. */
export function QuantityStepper({ value = 1, onChange, min = 1, max = 999, size, disabled, className }) {
  const { t } = useLocale();
  const set = (n) => onChange && onChange(Math.max(min, Math.min(max, n)));
  const isz = size === 'sm' ? 14 : 16;
  return (
    <div className={cx('d-stepper', size === 'sm' && 'd-stepper--sm', disabled && 'd-stepper--disabled', className)}>
      <button type="button" onClick={() => set(value - 1)} disabled={disabled || value <= min} aria-label={t('Decrease quantity', 'تقليل الكمية')}><Icon name="minus" size={isz} stroke={2} /></button>
      <input type="number" inputMode="numeric" value={value} disabled={disabled} aria-label={t('Quantity', 'الكمية')}
        onChange={(e) => set(parseInt(e.target.value || String(min), 10))} />
      <button type="button" onClick={() => set(value + 1)} disabled={disabled || value >= max} aria-label={t('Increase quantity', 'زيادة الكمية')}><Icon name="plus" size={isz} stroke={2} /></button>
    </div>
  );
}

