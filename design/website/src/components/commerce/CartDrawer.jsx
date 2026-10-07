
import React from 'react';
import { useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';
import { Button } from '../buttons/Button.jsx';
import { IconButton } from '../buttons/IconButton.jsx';
import { CartLine } from './CartLine.jsx';

/** Slide-in cart (from the end edge; mirrors in RTL). `inline` renders it in-flow for docs/previews. */
export function CartDrawer({ open = true, inline, items = [], onClose, onQty, onRemove, onCheckout, onViewCart, onQuote, freeShipAt = 5000 }) {
  const { t, price } = useLocale();
  if (!open) return null;
  const sub = items.reduce((s, i) => s + i.price * i.qty, 0);
  const left = Math.max(0, freeShipAt - sub);
  const n = items.reduce((s, i) => s + i.qty, 0);
  const panel = (
    <aside className={'d-drawer' + (inline ? ' d-drawer--inline' : '')} role="dialog" aria-label={t('Cart', 'السلة')}>
      <div className="d-drawer__head">
        <Icon name="shopping-bag" size={22} />
        <h3>{t('Your cart', 'سلتك')} <span style={{ color: 'var(--fg-3)', fontFamily: 'var(--font-body)', fontSize: 15, fontWeight: 600 }}>({n})</span></h3>
        <IconButton icon="x" label={t('Close', 'إغلاق')} onClick={onClose} style={{ marginInlineStart: 'auto' }} />
      </div>
      <div className="d-ship">
        {left > 0 ? <>{t('Add', 'أضف')} <b>{price(left)}</b> {t('more for free delivery in Cairo & Giza', 'للحصول على توصيل مجاني للقاهرة والجيزة')}</> : <b>{t('You’ve unlocked free delivery', 'حصلت على توصيل مجاني')}</b>}
        <div className="d-ship__bar"><div className="d-ship__fill" style={{ width: Math.min(100, (sub / freeShipAt) * 100) + '%' }} /></div>
      </div>
      <div className="d-drawer__body">
        {items.length ? items.map((it) => <CartLine key={it.id} item={it} onQty={onQty} onRemove={onRemove} />) : (
          <div style={{ padding: '48px 0', textAlign: 'center', color: 'var(--fg-2)', font: '600 15px var(--font-body)' }}>{t('Your cart is empty.', 'سلتك فارغة.')}</div>
        )}
      </div>
      <div className="d-drawer__foot">
        <div className="d-totals">
          <div style={{ color: 'var(--fg-1)', font: '800 16px var(--font-body)' }}><span>{t('Subtotal', 'الإجمالي الفرعي')}</span><b style={{ fontSize: 20, fontWeight: 800 }}>{price(sub)}</b></div>
          <span style={{ font: '500 12px var(--font-body)', color: 'var(--fg-3)' }}>{t('Includes 14% VAT. Delivery calculated at checkout.', 'شامل ضريبة 14%. يُحسب التوصيل عند الدفع.')}</span>
        </div>
        <Button size="lg" block iconEnd="arrow-right" onClick={onCheckout} disabled={!items.length}>{t('Checkout', 'إتمام الشراء')}</Button>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 }}>
          <Button variant="outline" onClick={onViewCart}>{t('View cart', 'عرض السلة')}</Button>
          <Button variant="quote-outline" onClick={onQuote}>{t('Quote this cart', 'عرض سعر للسلة')}</Button>
        </div>
      </div>
    </aside>
  );
  if (inline) return panel;
  return <><div className="d-overlay" onClick={onClose} />{panel}</>;
}

