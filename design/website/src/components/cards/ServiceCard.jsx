
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';
import { ImageFrame } from '../core/ImageFrame.jsx';

/** Service card. service: {num, title, desc, image?, imageLabel?}. */
export function ServiceCard({ service: s, onOpen, className }) {
  const { t, tx } = useLocale();
  return (
    <article className={cx('d-card', className)}>
      <a href="#" className="d-card__link" aria-label={tx(s.title)} onClick={(e) => { e.preventDefault(); onOpen && onOpen(s); }} />
      <ImageFrame className="d-scard__media" src={s.image} label={s.imageLabel || { en: 'Service photo: team on site', ar: 'صورة الخدمة' }} />
      <div className="d-scard__body">
        <span className="d-scard__num">{s.num}</span>
        <h3 className="d-scard__title">{tx(s.title)}</h3>
        <p className="d-scard__desc">{tx(s.desc)}</p>
        <span className="d-scard__more">{t('Learn more', 'اعرف المزيد')}<Icon name="arrow-right" size={16} /></span>
      </div>
    </article>
  );
}

