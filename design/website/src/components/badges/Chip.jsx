
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';

/** Filter chip. `active` + `onRemove` = active-filter chip with ✕. `facet` renders a muted "Brand:" prefix. */
export function Chip({ children, facet, active, onRemove, onClick, className, ...rest }) {
  const { t } = useLocale();
  const El = onRemove ? 'span' : 'button';
  return (
    <El className={cx('d-chip', active && 'd-chip--active', className)} onClick={onClick} type={El === 'button' ? 'button' : undefined} {...rest}>
      {facet && <span className="d-chip__key">{facet}:</span>}
      {children}
      {onRemove && (
        <button type="button" className="d-chip__x" onClick={onRemove} aria-label={t('Remove filter', 'إزالة الفلتر')}>
          <Icon name="x" size={14} stroke={2} />
        </button>
      )}
    </El>
  );
}

