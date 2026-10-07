
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';
import { ImageFrame } from '../core/ImageFrame.jsx';
import { Badge } from '../badges/Badge.jsx';

/** Blog article card. article: {title, excerpt, category, date, read, image, author?}. variant "wide" = featured horizontal hero card. */
export function ArticleCard({ article: a, variant, onOpen, className }) {
  const { t, tx } = useLocale();
  const wide = variant === 'wide';
  return (
    <article className={cx('d-card', 'd-acard', wide && 'd-acard--wide', className)}>
      <a href="#" className="d-card__link" aria-label={tx(a.title)} onClick={(e) => { e.preventDefault(); onOpen && onOpen(a); }} />
      <ImageFrame className="d-acard__media" src={a.image} alt={a.title} label={a.imageLabel} />
      {!wide && <span className="d-acard__tag"><Badge tone="outline">{tx(a.category)}</Badge></span>}
      <div className="d-card__body" style={{ gap: 10, padding: wide ? undefined : 20 }}>
        {wide && <div style={{ display: 'flex', gap: 8 }}><Badge tone="accent">{t('Featured', 'مميز')}</Badge><Badge tone="outline">{tx(a.category)}</Badge></div>}
        <h3 className="d-acard__title">{tx(a.title)}</h3>
        <p className="d-acard__ex">{tx(a.excerpt)}</p>
        <div className="d-acard__meta" style={{ marginTop: 'auto', paddingTop: 6 }}>
          {wide && a.author && <><span style={{ color: 'var(--fg-1)', fontWeight: 800 }}>{tx(a.author)}</span><i /></>}
          <span>{tx(a.date)}</span><i /><span style={{ display: 'inline-flex', alignItems: 'center', gap: 4 }}><Icon name="clock" size={13} />{a.read} {t('min read', 'دقائق قراءة')}</span>
        </div>
        {wide && <span className="d-scard__more" style={{ marginTop: 6 }}>{t('Read article', 'اقرأ المقال')}<Icon name="arrow-right" size={16} /></span>}
      </div>
    </article>
  );
}

