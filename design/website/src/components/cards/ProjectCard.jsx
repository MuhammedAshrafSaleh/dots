
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';
import { ImageFrame } from '../core/ImageFrame.jsx';
import { Badge } from '../badges/Badge.jsx';
import { Progress } from '../feedback/Progress.jsx';

/**
 * Project card. project: {title, location, type, status: 'progress'|'done', progress?, year?, image, client?}.
 * Current projects show an "In progress" badge + progress bar; completed show year. variant "overlay" = text on photo (masonry).
 */
export function ProjectCard({ project: p, variant, onOpen, style, className }) {
  const { t, tx } = useLocale();
  const live = p.status === 'progress';
  const overlay = variant === 'overlay';
  return (
    <article className={cx('d-card', 'd-prcard', overlay && 'd-prcard--overlay', className)} style={style}>
      <a href="#" className="d-card__link" aria-label={tx(p.title)} onClick={(e) => { e.preventDefault(); onOpen && onOpen(p); }} />
      <ImageFrame className="d-prcard__media" src={p.image} alt={p.title} label={p.imageLabel} />
      <div className="d-prcard__badges">
        {live ? <Badge tone="accent" dot>{t('In progress', 'قيد التنفيذ')}</Badge> : <Badge tone="dark">{t('Completed', 'مكتمل')} {p.year}</Badge>}
        <Badge tone="outline">{tx(p.type)}</Badge>
      </div>
      <div className="d-card__body" style={{ gap: 10, padding: 20 }}>
        <span className="d-prcard__loc"><Icon name="map-pin" size={14} />{tx(p.location)}</span>
        <h3 className="d-prcard__title">{tx(p.title)}</h3>
        {live && !overlay && <Progress value={p.progress} label={tx(p.phase) || t('Progress', 'نسبة الإنجاز')} />}
        <div className="d-prcard__foot">
          <span>{live ? t('Handover', 'التسليم') + ' ' + tx(p.eta) : tx(p.client) || tx(p.scope)}</span>
          <span style={{ whiteSpace: 'nowrap', flex: 'none', display: 'inline-flex', alignItems: 'center', gap: 6, color: overlay ? '#fff' : 'var(--fg-1)', fontWeight: 800 }}>{t('View project', 'عرض المشروع')}<Icon name="arrow-right" size={14} /></span>
        </div>
      </div>
    </article>
  );
}

