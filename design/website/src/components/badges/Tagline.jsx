
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';

const DOTS_TAGLINE = { en: ['Lighting', 'Smart Home', 'Electrical'], ar: ['إضاءة', 'منزل ذكي', 'كهرباء'] };

/** Brand tagline: uppercase, wide-tracked words separated by yellow dots. */
export function Tagline({ items, className, style }) {
  const { lang } = useLocale();
  const list = items || DOTS_TAGLINE[lang] || DOTS_TAGLINE.en;
  return (
    <span className={cx('d-tagline', className)} style={style}>
      {list.map((w, i) => (
        <React.Fragment key={i}>{i > 0 && <span className="d-tagline__dot" aria-hidden="true" />}<span>{w}</span></React.Fragment>
      ))}
    </span>
  );
}

