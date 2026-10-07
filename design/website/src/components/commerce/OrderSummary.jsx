
import React from 'react';
import { useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';
import { ImageFrame } from '../core/ImageFrame.jsx';
import { Button } from '../buttons/Button.jsx';

/** Order summary panel: compact lines, promo code, totals (VAT-inclusive), CTA, trust row. */
export function OrderSummary({ items = [], delivery = 150, discount = 0, cta, onCta, showLines = true }) {
  const { t, tx, price } = useLocale();
  const sub = items.reduce((s, i) => s + i.price * i.qty, 0);
  const total = sub + delivery - discount;
  return (
    <aside className="d-summary">
      <h3>{t('Order summary', 'ملخص الطلب')}</h3>
      {showLines && <div>{items.map((it) => (
        <div className="d-line" key={it.id}>
          <div className="d-line__img" style={{ position: 'relative' }}><ImageFrame src={it.image} contain style={{ width: '100%', height: '100%', borderRadius: 6 }} /><span className="d-summary__qty">{it.qty}</span></div>
          <div style={{ minWidth: 0 }}><p className="d-line__name" style={{ fontSize: 13 }}>{tx(it.name)}</p><div className="d-line__var">{tx(it.variant)}</div></div>
          <div className="d-line__price" style={{ fontSize: 14 }}>{price(it.price * it.qty)}</div>
        </div>))}</div>}
      <div className="d-promo">
        <input className="d-input" placeholder={t('Promo code', 'كود الخصم')} aria-label={t('Promo code', 'كود الخصم')} />
        <Button variant="outline">{t('Apply', 'تطبيق')}</Button>
      </div>
      <div className="d-totals">
        <div><span>{t('Subtotal', 'الإجمالي الفرعي')}</span><b>{price(sub)}</b></div>
        <div><span>{t('Delivery', 'التوصيل')}</span><b>{delivery ? price(delivery) : t('Free', 'مجاناً')}</b></div>
        {discount > 0 && <div><span>{t('Discount', 'الخصم')}</span><b style={{ color: 'var(--success-600)' }}>−{price(discount)}</b></div>}
        <div className="d-totals__grand"><span>{t('Total', 'الإجمالي')}</span><b>{price(total)}</b></div>
        <span style={{ font: '500 12px var(--font-body)', color: 'var(--fg-3)' }}>{t('Prices include 14% VAT', 'الأسعار شاملة ضريبة القيمة المضافة 14%')}</span>
      </div>
      {cta !== false && <Button size="lg" block iconEnd="arrow-right" onClick={onCta}>{cta || t('Place order', 'تأكيد الطلب')}</Button>}
      <div className="d-trust">
        <span><Icon name="shield-check" size={16} />{t('Official warranty', 'ضمان رسمي')}</span>
        <span><Icon name="rotate-ccw" size={16} />{t('14-day returns', 'استرجاع 14 يوم')}</span>
        <span><Icon name="lock" size={16} />{t('Secure payment', 'دفع آمن')}</span>
      </div>
    </aside>
  );
}

