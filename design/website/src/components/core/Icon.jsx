
import React from 'react';
import { cx, useLocale } from './LocaleProvider.jsx';

const DOTS_DIRECTIONAL = new Set(['chevron-right', 'chevron-left', 'arrow-right', 'arrow-left', 'arrow-up-right', 'move-right', 'undo-2', 'redo-2', 'log-out', 'list-filter']);
const DOTS_ALIASES = { home: 'house', 'alert-circle': 'circle-alert', 'check-circle': 'circle-check', 'alert-triangle': 'triangle-alert', whatsapp: 'message-circle' };

function dotsPascal(n) { return n.split('-').map((p) => p.charAt(0).toUpperCase() + p.slice(1)).join(''); }

/** Outlined Lucide icon — 1.5px stroke, round caps/joins. Directional icons mirror in RTL. */
export function Icon({ name, size = 20, stroke = 1.5, flip, className, style, title, ...rest }) {
  const { dir } = useLocale();
  const key = DOTS_ALIASES[name] || name;
  const lib = typeof window !== 'undefined' ? window.lucide : null;
  const node = lib && lib.icons ? (lib.icons[dotsPascal(key)] || lib.icons[dotsPascal(name)]) : null;
  const kids = node ? (node[0] === 'svg' ? node[2] : node) : [['circle', { cx: 12, cy: 12, r: 3 }]];
  const mirror = flip ?? (dir === 'rtl' && DOTS_DIRECTIONAL.has(key));
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={stroke}
      strokeLinecap="round" strokeLinejoin="round" className={cx('d-icon', className)}
      style={{ flex: 'none', transform: mirror ? 'scaleX(-1)' : undefined, ...style }}
      aria-hidden={title ? undefined : 'true'} role={title ? 'img' : undefined} {...rest}>
      {title && <title>{title}</title>}
      {kids.map(([tag, attrs], i) => React.createElement(tag, { key: i, ...attrs }))}
    </svg>
  );
}

