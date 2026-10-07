
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';

/** Catalog search: optional scope select (All / Lighting / Smart Home / Electrical / Brands) + field + yellow submit. */
export function SearchBar({ scopes, scope, onScope, value, onChange, onSubmit, placeholder, size, iconOnly, className, style }) {
  const { t } = useLocale();
  const list = scopes === false ? null : scopes || [t('All', 'الكل'), t('Lighting', 'إضاءة'), t('Smart Home', 'منزل ذكي'), t('Electrical', 'كهرباء'), t('Brands', 'العلامات')];
  return (
    <form role="search" className={cx('d-search', size === 'sm' && 'd-search--sm', className)} style={style}
      onSubmit={(e) => { e.preventDefault(); onSubmit && onSubmit(value); }}>
      {list && (
        <div className="d-search__scope">
          <select aria-label={t('Search in', 'البحث في')} value={scope} onChange={(e) => onScope && onScope(e.target.value)}>
            {list.map((s) => <option key={s}>{s}</option>)}
          </select>
          <Icon name="chevron-down" size={14} />
        </div>
      )}
      <label className="d-search__field">
        <Icon name="search" size={18} />
        <input type="search" value={value} onChange={(e) => onChange && onChange(e.target.value)}
          placeholder={placeholder || t('Search 10,000+ products, SKUs, brands…', 'ابحث في أكثر من 10,000 منتج أو كود أو علامة…')}
          aria-label={t('Search', 'بحث')} />
      </label>
      <button type="submit" className="d-search__btn" aria-label={t('Search', 'بحث')}>
        {iconOnly ? <Icon name="arrow-right" size={18} /> : t('Search', 'بحث')}
      </button>
    </form>
  );
}

