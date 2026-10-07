
import React from 'react';
import { useLocale } from '../core/LocaleProvider.jsx';
import { ImageFrame } from '../core/ImageFrame.jsx';
import { IconButton } from '../buttons/IconButton.jsx';

/** Image gallery: large stage with prev/next, counter, caption, and a thumbnail rail. images: {src, caption?, label?}[] */
export function Gallery({ images = [], ratio = '16 / 10', thumbs = true }) {
  const { t, tx } = useLocale();
  const [i, setI] = React.useState(0);
  const cur = images[i] || {};
  const go = (d) => setI((x) => (x + d + images.length) % images.length);
  return (
    <div className="d-gallery">
      <div className="d-gallery__main">
        <ImageFrame key={i} ratio={ratio} src={cur.src} alt={cur.caption} label={cur.label} style={{ animation: 'd-fade-in 320ms var(--ease-out)' }} />
        {images.length > 1 && <>
          <IconButton className="d-gallery__nav d-gallery__nav--prev" variant="outline" icon="chevron-left" label={t('Previous image', 'الصورة السابقة')} onClick={() => go(-1)} style={{ borderRadius: '50%', borderColor: 'transparent' }} />
          <IconButton className="d-gallery__nav d-gallery__nav--next" variant="outline" icon="chevron-right" label={t('Next image', 'الصورة التالية')} onClick={() => go(1)} style={{ borderRadius: '50%', borderColor: 'transparent' }} />
        </>}
        {cur.caption && <span className="d-gallery__cap">{tx(cur.caption)}</span>}
        <span className="d-gallery__count">{i + 1} / {images.length}</span>
      </div>
      {thumbs && images.length > 1 && (
        <div className="d-gallery__thumbs">
          {images.map((im, k) => (
            <button key={k} type="button" className="d-gallery__thumb" aria-current={k === i} aria-label={tx(im.caption) || String(k + 1)} onClick={() => setI(k)}>
              <ImageFrame ratio="4 / 3" src={im.src} />
            </button>
          ))}
        </div>
      )}
    </div>
  );
}

