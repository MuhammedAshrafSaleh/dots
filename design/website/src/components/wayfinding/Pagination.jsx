
import React from 'react';
import { useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';

function dotsPageWindow(page, total) {
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
  const s = new Set([1, total, page - 1, page, page + 1]);
  const arr = [...s].filter((n) => n >= 1 && n <= total).sort((a, b) => a - b);
  const out = [];
  arr.forEach((n, i) => { if (i && n - arr[i - 1] > 1) out.push('…' + n); out.push(n); });
  return out;
}

/** Numbered pagination with prev/next; current page is yellow. Shows "Showing x–y of N" when `perPage` & `count` given. */
export function Pagination({ page = 1, total = 1, onChange, count, perPage }) {
  const { t, compact } = useLocale();
  const go = (n) => onChange && onChange(Math.max(1, Math.min(total, n)));
  return (
    <nav aria-label={t('Pagination', 'الصفحات')} style={{ display: 'flex', alignItems: 'center', gap: 16, flexWrap: 'wrap', justifyContent: 'space-between' }}>
      {count != null && perPage && !compact && (
        <span style={{ font: '600 13px var(--font-body)', color: 'var(--fg-2)' }}>
          {t('Showing', 'عرض')} {(page - 1) * perPage + 1}–{Math.min(page * perPage, count)} {t('of', 'من')} {count.toLocaleString('en-US')}
        </span>
      )}
      <div className="d-pages">
        <button type="button" className="d-page" onClick={() => go(page - 1)} disabled={page <= 1} aria-label={t('Previous page', 'السابق')}><Icon name="chevron-left" size={16} />{!compact && t('Prev', 'السابق')}</button>
        {(compact ? [page] : dotsPageWindow(page, total)).map((n) => typeof n === 'string'
          ? <span key={n} className="d-page d-page--gap">…</span>
          : <button key={n} type="button" className="d-page" aria-current={n === page ? 'page' : undefined} onClick={() => go(n)}>{n}</button>)}
        {compact && <span className="d-page d-page--gap" style={{ minWidth: 'auto' }}>{t('of', 'من')} {total}</span>}
        <button type="button" className="d-page" onClick={() => go(page + 1)} disabled={page >= total} aria-label={t('Next page', 'التالي')}>{!compact && t('Next', 'التالي')}<Icon name="chevron-right" size={16} /></button>
      </div>
    </nav>
  );
}

