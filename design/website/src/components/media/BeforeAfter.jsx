
import React from 'react';
import { useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';
import { ImageFrame } from '../core/ImageFrame.jsx';

/** Drag / keyboard before–after comparison slider. before/after: {src, label?}. */
export function BeforeAfter({ before, after, ratio = '16 / 9', start = 50 }) {
  const { t } = useLocale();
  const [v, setV] = React.useState(start);
  return (
    <div className="d-ba" style={{ aspectRatio: ratio }} dir="ltr">
      <ImageFrame src={before.src} label={before.label} style={{ position: 'absolute', inset: 0 }} />
      <div className="d-ba__after" style={{ clipPath: `inset(0 0 0 ${v}%)` }}><ImageFrame src={after.src} label={after.label} style={{ position: 'absolute', inset: 0 }} /></div>
      <span className="d-ba__label d-ba__label--before">{t('Before', 'قبل')}</span>
      <span className="d-ba__label d-ba__label--after">{t('After', 'بعد')}</span>
      <div className="d-ba__line" style={{ left: v + '%' }}><span className="d-ba__knob"><Icon name="chevrons-left-right" size={20} stroke={2} flip={false} /></span></div>
      <input type="range" min="0" max="100" value={v} onChange={(e) => setV(+e.target.value)} aria-label={t('Compare before and after', 'قارن قبل وبعد')} />
    </div>
  );
}

