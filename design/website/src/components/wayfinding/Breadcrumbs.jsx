
import React from 'react';
import { useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';

/** items: {label, onClick?}[] — last item is the current page. Chevrons mirror in RTL. */
export function Breadcrumbs({ items = [] }) {
  const { tx, t } = useLocale();
  return (
    <nav aria-label={t('Breadcrumb', 'مسار التنقل')}>
      <ol className="d-crumbs">
        {items.map((it, i) => {
          const last = i === items.length - 1;
          return (
            <li key={i} style={{ display: 'inline-flex', alignItems: 'center', gap: 6 }}>
              {i > 0 && <Icon name="chevron-right" size={14} />}
              {last ? <span aria-current="page">{tx(it.label)}</span> : <a href="#" onClick={(e) => { e.preventDefault(); it.onClick && it.onClick(); }}>{tx(it.label)}</a>}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}

