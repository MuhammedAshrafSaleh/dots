
import React from 'react';
import { cx, useLocale } from './LocaleProvider.jsx';

/**
 * Image container. `src` renders a covered photo; `video` renders a muted looping background video (src = poster).
 * Without media — or if it fails to load — shows a labelled placeholder stating the shot required.
 */
export function ImageFrame({ src, video, alt = '', label, ratio, contain, radius, className, style, children }) {
  const { tx } = useLocale();
  const [failed, setFailed] = React.useState(false);
  const ok = src && !failed;
  return (
    <div className={cx('d-img', contain && 'd-img--contain', className)} style={{ aspectRatio: ratio, borderRadius: radius, ...style }}>
      {video && !failed ? (
        <video src={video} poster={src} autoPlay muted loop playsInline aria-hidden="true" onError={() => {}} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
      ) : ok ? <img src={src} alt={tx(alt)} loading="lazy" onError={() => setFailed(true)} /> : (
        <div className="d-img__ph" aria-hidden="true">{label && <span className="d-img__label">{tx(label)}</span>}</div>
      )}
      {children}
    </div>
  );
}

