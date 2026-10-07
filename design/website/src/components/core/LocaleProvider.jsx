
import React from 'react';

const DotsLocaleCtx = React.createContext({ lang: 'en', dir: 'ltr', compact: false });

export function cx(...a) { return a.filter(Boolean).join(' '); }

export function formatPrice(n, lang = 'en') {
  const s = Number(n).toLocaleString('en-US');
  return lang === 'ar' ? s + ' ج.م' : 'EGP ' + s;
}

export function LocaleProvider({ lang = 'en', compact = false, children, style, className }) {
  const value = React.useMemo(() => ({ lang, dir: lang === 'ar' ? 'rtl' : 'ltr', compact }), [lang, compact]);
  return (
    <DotsLocaleCtx.Provider value={value}>
      <div dir={value.dir} lang={lang} className={cx('d-root', compact && 'd-compact', className)} style={style}>{children}</div>
    </DotsLocaleCtx.Provider>
  );
}

export function useLocale() {
  const c = React.useContext(DotsLocaleCtx);
  const tx = (v) => (v && typeof v === 'object' && !React.isValidElement(v) ? (v[c.lang] ?? v.en) : v);
  return { ...c, t: (en, ar) => (c.lang === 'ar' && ar != null ? ar : en), tx, price: (n) => formatPrice(n, c.lang) };
}

