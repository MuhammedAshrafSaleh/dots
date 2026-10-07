
import React from 'react';
import { useLocale } from '../core/LocaleProvider.jsx';
import { Chip } from '../badges/Chip.jsx';

/** Row of removable active-filter chips + "Clear all". chips: {id, facet, label}[] */
export function ActiveFilters({ chips = [], onRemove, onClear, count }) {
  const { t, tx } = useLocale();
  if (!chips.length) return null;
  return (
    <div className="d-active">
      {count != null && <span style={{ font: '800 13px var(--font-body)', marginInlineEnd: 4 }}>{count.toLocaleString('en-US')} {t('results', 'نتيجة')}</span>}
      {chips.map((c) => <Chip key={c.id} active facet={tx(c.facet)} onRemove={() => onRemove && onRemove(c)}>{tx(c.label)}</Chip>)}
      <button type="button" className="d-active__clear" onClick={onClear}>{t('Clear all', 'مسح الكل')}</button>
    </div>
  );
}

