
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';

/** Manufacturer card. brand: {name, origin, count, logo?}. Without a logo file the name is set as a neutral wordmark. */
export function BrandCard({ brand: b, onOpen, className }) {
  const { t, tx } = useLocale();
  return (
    <article className={cx('d-card', className)}>
      <a href="#" className="d-card__link" aria-label={b.name} onClick={(e) => { e.preventDefault(); onOpen && onOpen(b); }} />
      <div className="d-bcard__logo">{b.logo ? <img src={b.logo} alt={b.name} style={{ maxHeight: 56, width: 'auto' }} /> : <span className="d-bcard__word">{b.name}</span>}</div>
      <div className="d-card__body" style={{ gap: 4 }}>
        <div className="d-bcard__row">
          <div>
            <h3 className="d-bcard__name">{b.name}</h3>
            <div className="d-bcard__origin" style={{ marginTop: 4 }}>{tx(b.origin)} · {b.count.toLocaleString('en-US')} {t('products', 'منتج')}</div>
          </div>
          <span className="d-bcard__arrow"><Icon name="arrow-right" size={16} /></span>
        </div>
      </div>
    </article>
  );
}

