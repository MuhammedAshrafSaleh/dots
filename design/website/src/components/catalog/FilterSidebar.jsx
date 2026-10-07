
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';
import { Checkbox } from '../forms/Checkbox.jsx';
import { Input } from '../forms/Input.jsx';

function DotsFSec({ title, open: o = true, children }) {
  const [open, setOpen] = React.useState(o);
  return (
    <div className={cx('d-fsec', open && 'd-fsec--open')}>
      <button type="button" className="d-fsec__btn" aria-expanded={open} onClick={() => setOpen(!open)}>{title}<Icon name="chevron-down" size={18} /></button>
      {open && <div className="d-fsec__body">{children}</div>}
    </div>
  );
}

/**
 * Catalog filter sidebar: category tree, manufacturer checklist (searchable), variant facets, price range, availability.
 * Controlled: value = {category, brands[], temps[], watts[], finishes[], inStock}; onChange(next).
 * facets = {categories:[{id,name,count,children?}], brands:[{name,count}], temps:[{value,label,chip,count}], watts:[{value,count}], finishes:[{value,label,color}]}
 */
export function FilterSidebar({ facets, value, onChange, onClear, className, style }) {
  const { t, tx } = useLocale();
  const [bq, setBq] = React.useState('');
  const [allBrands, setAllBrands] = React.useState(false);
  const v = value || {};
  const toggle = (key, x) => { const cur = v[key] || []; onChange && onChange({ ...v, [key]: cur.includes(x) ? cur.filter((y) => y !== x) : [...cur, x] }); };
  const brands = (facets.brands || []).filter((b) => b.name.toLowerCase().includes(bq.toLowerCase()));
  const tree = (list) => (
    <ul className="d-tree">{list.map((c) => (
      <li key={c.id}>
        <button type="button" aria-current={v.category === c.id} onClick={() => onChange && onChange({ ...v, category: c.id })}>
          <span>{tx(c.name)}</span><span>{c.count}</span>
        </button>
        {c.children && tree(c.children)}
      </li>))}
    </ul>
  );
  return (
    <aside className={cx('d-filters', className)} style={style} aria-label={t('Filters', 'الفلاتر')}>
      <div className="d-filters__head">
        <h3><Icon name="sliders-horizontal" size={18} />{t('Filters', 'الفلاتر')}</h3>
        {onClear && <button type="button" className="d-fsec__more" onClick={onClear}>{t('Clear all', 'مسح الكل')}</button>}
      </div>
      {facets.categories && <DotsFSec title={t('Category', 'القسم')}>{tree(facets.categories)}</DotsFSec>}
      {facets.brands && (
        <DotsFSec title={t('Manufacturer', 'الشركة المصنعة')}>
          <Input size="sm" icon="search" placeholder={t('Search brands', 'ابحث عن علامة')} value={bq} onChange={(e) => setBq(e.target.value)} />
          {(allBrands ? brands : brands.slice(0, 5)).map((b) => (
            <Checkbox key={b.name} row label={b.name} count={b.count} checked={(v.brands || []).includes(b.name)} onChange={() => toggle('brands', b.name)} />
          ))}
          {brands.length > 5 && <button type="button" className="d-fsec__more" onClick={() => setAllBrands(!allBrands)}>{allBrands ? t('Show less', 'عرض أقل') : `${t('Show all', 'عرض الكل')} (${brands.length})`}</button>}
        </DotsFSec>
      )}
      {facets.temps && (
        <DotsFSec title={t('Colour temperature', 'درجة حرارة اللون')}>
          {facets.temps.map((o) => (
            <label key={o.value} className="d-check d-check--row">
              <input type="checkbox" checked={(v.temps || []).includes(o.value)} onChange={() => toggle('temps', o.value)} />
              <span className="d-opt__chip" style={{ background: o.chip }} />
              <span>{o.value} <span style={{ color: 'var(--fg-3)', fontWeight: 500 }}>· {tx(o.label)}</span></span>
              <span className="d-check__count">{o.count}</span>
            </label>
          ))}
        </DotsFSec>
      )}
      {facets.watts && (
        <DotsFSec title={t('Wattage', 'القدرة')}>
          <div className="d-variant__opts">{facets.watts.map((w) => (
            <button key={w.value} type="button" className="d-opt" style={{ minWidth: 0, height: 34, paddingInline: 10, fontSize: 13 }} aria-pressed={(v.watts || []).includes(w.value)} onClick={() => toggle('watts', w.value)}>{w.value}</button>
          ))}</div>
        </DotsFSec>
      )}
      {facets.finishes && (
        <DotsFSec title={t('Finish', 'التشطيب')}>
          <div className="d-variant__opts" style={{ gap: 12, paddingInline: 3 }}>{facets.finishes.map((f) => (
            <button key={f.value} type="button" className="d-swatch" style={{ background: f.color, width: 30, height: 30 }} aria-pressed={(v.finishes || []).includes(f.value)} aria-label={tx(f.label)} title={tx(f.label)} onClick={() => toggle('finishes', f.value)} />
          ))}</div>
        </DotsFSec>
      )}
      <DotsFSec title={t('Price (EGP)', 'السعر (ج.م)')}>
        <div className="d-range__track"><div className="d-range__fill" style={{ insetInlineStart: '8%', insetInlineEnd: '30%' }} /><span className="d-range__knob" style={{ left: '8%' }} /><span className="d-range__knob" style={{ left: '70%' }} /></div>
        <div className="d-range"><input className="d-input d-input--sm" defaultValue="250" aria-label={t('Min', 'من')} /><span style={{ color: 'var(--fg-3)' }}>–</span><input className="d-input d-input--sm" defaultValue="12,000" aria-label={t('Max', 'إلى')} /></div>
      </DotsFSec>
      <DotsFSec title={t('Availability', 'التوفر')}>
        <Checkbox label={t('In stock only', 'المتوفر فقط')} checked={!!v.inStock} onChange={(c) => onChange && onChange({ ...v, inStock: c })} />
        <Checkbox label={t('Ships in 24h (Cairo & Giza)', 'شحن خلال 24 ساعة (القاهرة والجيزة)')} />
      </DotsFSec>
    </aside>
  );
}

