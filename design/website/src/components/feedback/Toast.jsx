
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';

/** Toast notification. tone: success | error | warning | info | cart. Place inside a `.d-toasts` container (bottom-end). */
export function Toast({ tone = 'success', title, message, actions = [], onClose, style }) {
  const { t, tx } = useLocale();
  const ico = { success: 'check', error: 'circle-alert', warning: 'triangle-alert', info: 'info', cart: 'shopping-bag' }[tone];
  return (
    <div className={cx('d-toast', `d-toast--${tone}`)} role={tone === 'error' ? 'alert' : 'status'} style={style}>
      <span className="d-toast__ico"><Icon name={ico} size={16} stroke={2} /></span>
      <div style={{ minWidth: 0 }}>
        <div className="d-toast__title">{tx(title)}</div>
        {message && <div className="d-toast__msg">{tx(message)}</div>}
        {actions.length > 0 && <div className="d-toast__act">{actions.map((a, i) => <button key={i} type="button" onClick={a.onClick}>{tx(a.label)}</button>)}</div>}
      </div>
      {onClose && <button type="button" className="d-toast__x" onClick={onClose} aria-label={t('Dismiss', 'إغلاق')}><Icon name="x" size={16} /></button>}
    </div>
  );
}

