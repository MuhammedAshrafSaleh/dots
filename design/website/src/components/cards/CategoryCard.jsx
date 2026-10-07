
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';
import { ImageFrame } from '../core/ImageFrame.jsx';

/** Category entry. variant "card" = lifestyle photo 4:3; "tile" = product cut-out on warm gray, square. */
export function CategoryCard({ category: c, variant = 'card', onOpen, className }) {
  const { t, tx } = useLocale();
  return (
    <article className={cx('d-card', variant === 'tile' && 'd-ccard--tile', className)}>
      <a href="#" className="d-card__link" aria-label={tx(c.name)} onClick={(e) => { e.preventDefault(); onOpen && onOpen(c); }} />
      <ImageFrame className="d-ccard__media" src={c.image} contain={variant === 'tile' && !!c.image} label={c.imageLabel || { en: 'Category photo', ar: 'صورة القسم' }} />
      <div className="d-ccard__body">
        <div style={{ flex: 1, minWidth: 0 }}>
          <h3 className="d-ccard__name">{tx(c.name)}</h3>
          {c.count != null && <div className="d-ccard__count">{c.count.toLocaleString('en-US')} {t('products', 'منتج')}</div>}
        </div>
        <span className="d-bcard__arrow"><Icon name="arrow-right" size={16} /></span>
      </div>
    </article>
  );
}

