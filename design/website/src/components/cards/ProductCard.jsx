
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';
import { ImageFrame } from '../core/ImageFrame.jsx';
import { Badge } from '../badges/Badge.jsx';
import { Button } from '../buttons/Button.jsx';
import { IconButton } from '../buttons/IconButton.jsx';

/** Small anchored prompt shown when a logged-out visitor taps the wishlist heart. Position via className/style (default: under the card's heart). */
export function WishlistPrompt({ onClose, onLogin, onRegister, style, className }) {
  const { t } = useLocale();
  React.useEffect(() => { const h = setTimeout(() => onClose && onClose(), 6000); return () => clearTimeout(h); }, []);
  return (
    <div className={cx('d-favtip', className)} role="dialog" aria-label={t('Log in to save', 'سجّل الدخول للحفظ')} style={style}>
      <div className="d-favtip__head"><Icon name="heart" size={16} /><b>{t('Save it for later', 'احفظه لوقت لاحق')}</b>
        <button type="button" className="d-favtip__x" aria-label={t('Close', 'إغلاق')} onClick={onClose}><Icon name="x" size={16} /></button></div>
      <span>{t('Log in to keep a wishlist on all your devices.', 'سجّل الدخول لتحتفظ بالمفضلة على كل أجهزتك.')}</span>
      <div className="d-favtip__acts"><Button size="sm" onClick={onLogin}>{t('Log in', 'تسجيل الدخول')}</Button><button type="button" className="d-favtip__link" onClick={onRegister}>{t('Create account', 'إنشاء حساب')}</button></div>
    </div>
  );
}

/**
 * Catalog product card. Add to Cart (yellow) always sits beside Request a Quote (WhatsApp green).
 * product: {name, brand, sku, price?, was?, image?, cover? (photo fills frame), imageLabel?, badges?: [{tone,label}], stock: 'in'|'low'|'order', finishes?: css[], variants?: string}
 * No price → "Price on request" and the quote button becomes the primary action.
 * Wishlist: pass saved + onSave(p); return false from onSave (logged out) to show the WishlistPrompt with onLogin / onRegister.
 */
export function ProductCard({ product: p, onOpen, onAdd, onQuote, saved, onSave, onLogin, onRegister, className }) {
  const { t, tx, price, compact } = useLocale();
  const [favLocal, setFavLocal] = React.useState(false);
  const [tip, setTip] = React.useState(false);
  const fav = saved ?? favLocal;
  const toggleFav = () => { if (onSave) { if (onSave(p) === false) setTip(true); } else setFavLocal(!favLocal); };
  const stock = { in: [t('In stock', 'متوفر'), 'var(--success-600)'], low: [t('Only a few left', 'كمية محدودة'), 'var(--warning-600)'], order: [t('On order · 2–3 wks', 'بالطلب · 2–3 أسابيع'), 'var(--ink-600)'] }[p.stock || 'in'];
  const hasPrice = p.price != null;
  return (
    <article className={cx('d-card', 'd-pcard', compact && 'd-pcard--compact', className)}>
      <a href="#" className="d-card__link" aria-label={tx(p.name)} onClick={(e) => { e.preventDefault(); onOpen && onOpen(p); }} />
      <ImageFrame className="d-pcard__media" contain={!!p.image && !p.cover} src={p.image} label={p.imageLabel || { en: 'Product cut-out, white bg', ar: 'صورة المنتج على خلفية بيضاء' }} />
      {p.badges && <div className="d-pcard__badges">{p.badges.map((b, i) => <Badge key={i} tone={b.tone}>{tx(b.label)}</Badge>)}</div>}
      <button type="button" className="d-pcard__fav" aria-pressed={fav} aria-label={fav ? t('Remove from wishlist', 'إزالة من المفضلة') : t('Save to wishlist', 'أضف للمفضلة')} onClick={toggleFav}>
        <Icon name="heart" size={18} style={fav ? { fill: 'var(--accent)', stroke: 'var(--ink-900)' } : undefined} />
      </button>
      {tip && <WishlistPrompt onClose={() => setTip(false)} onLogin={onLogin} onRegister={onRegister} />}
      <div className="d-card__body">
        <span className="d-pcard__brand">{tx(p.brand)}</span>
        <h3 className="d-pcard__title">{tx(p.name)}</h3>
        <span className="d-pcard__sku">{p.sku}</span>
        {(p.finishes || p.variants) && (
          <div className="d-pcard__meta">
            {p.finishes && <span className="d-pcard__dots">{p.finishes.map((c, i) => <i key={i} style={{ background: c }} />)}</span>}
            {p.variants && <span>{tx(p.variants)}</span>}
          </div>
        )}
        <div className="d-pcard__price">
          {hasPrice ? <><span className="d-pcard__now">{price(p.price)}</span>{p.was && <span className="d-pcard__was">{price(p.was)}</span>}</> : <span className="d-pcard__por">{t('Price on request', 'السعر عند الطلب')}</span>}
        </div>
        <span className="d-pcard__stock" style={{ color: stock[1] }}><i />{stock[0]}</span>
      </div>
      <div className="d-pcard__actions">
        {hasPrice ? (
          <>
            <Button size="sm" icon="shopping-bag" onClick={() => onAdd && onAdd(p)}>{compact ? t('Add', 'أضف') : t('Add to cart', 'أضف للسلة')}</Button>
            <IconButton variant="whatsapp" size="sm" icon="message-circle" label={t('Request a quote on WhatsApp', 'اطلب عرض سعر عبر واتساب')} onClick={() => onQuote && onQuote(p)} />
          </>
        ) : (
          <Button size="sm" variant="whatsapp" block onClick={() => onQuote && onQuote(p)}>{t('Request a quote', 'اطلب عرض سعر')}</Button>
        )}
      </div>
    </article>
  );
}

