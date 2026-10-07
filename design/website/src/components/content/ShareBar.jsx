
import React from 'react';
import { useLocale } from '../core/LocaleProvider.jsx';
import { IconButton } from '../buttons/IconButton.jsx';

/** Share row for articles/projects: WhatsApp, Facebook, LinkedIn, X, copy link. */
export function ShareBar({ onShare, vertical }) {
  const { t } = useLocale();
  const items = [['message-circle', 'WhatsApp'], ['facebook', 'Facebook'], ['linkedin', 'LinkedIn'], ['twitter', 'X'], ['link', t('Copy link', 'نسخ الرابط')]];
  return (
    <div className="d-share" style={vertical ? { flexDirection: 'column', alignItems: 'flex-start' } : undefined}>
      <span className="d-share__l">{t('Share', 'مشاركة')}</span>
      {items.map(([ic, l]) => <IconButton key={l} icon={ic} label={l} variant="outline" size="sm" style={{ borderRadius: '50%', borderColor: 'var(--border-2)', borderWidth: 1 }} onClick={() => onShare && onShare(l)} />)}
    </div>
  );
}

