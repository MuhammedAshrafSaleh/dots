
import React from 'react';

export function KCart({ app }) {
  const { t, cols, compact } = useK();
  const items = app.cart;
  return (
    <>
      <KPageHead crumbs={[{ label: { en: 'Home', ar: 'الرئيسية' }, onClick: () => app.go('home') }, { label: { en: 'Cart', ar: 'السلة' } }]} title={{ en: 'Your cart', ar: 'سلتك' }} />
      <div className="d-container" style={{ paddingBlock: compact ? 20 : 40, display: 'grid', gridTemplateColumns: cols('minmax(0,1fr) 380px', '1fr'), gap: compact ? 20 : 40, alignItems: 'start' }}>
        <div style={{ display: 'grid', gap: 16 }}>
          <div style={{ border: '1px solid var(--border-1)', borderRadius: 'var(--radius-lg)', padding: compact ? '0 14px' : '0 24px' }}>
            {items.length ? items.map((it) => <CartLine key={it.id} item={it} onQty={app.qty} onRemove={app.remove} />) : <div style={{ padding: 48, textAlign: 'center', color: 'var(--fg-2)' }}>{t('Your cart is empty.', 'سلتك فارغة.')}</div>}
          </div>
          <div style={{ display: 'flex', gap: 12, alignItems: 'center', padding: 16, borderRadius: 'var(--radius-lg)', background: 'var(--wa-100)', flexWrap: 'wrap' }}>
            <Icon name="message-circle" size={22} style={{ color: 'var(--wa-600)' }} />
            <span style={{ flex: 1, minWidth: 200, font: '600 14px/1.4 var(--font-body)' }}>{t('Buying for a project? Send this cart to our team for trade pricing.', 'تشتري لمشروع؟ أرسل السلة لفريقنا لأسعار المقاولين.')}</span>
            <Button variant="whatsapp" onClick={() => app.quote()}>{t('Quote this cart', 'عرض سعر للسلة')}</Button>
          </div>
          <Button variant="ghost" icon="arrow-left" style={{ justifySelf: 'start' }} onClick={() => app.go('products')}>{t('Continue shopping', 'متابعة التسوق')}</Button>
        </div>
        <OrderSummary items={items} showLines={false} cta={t('Proceed to checkout', 'المتابعة للدفع')} onCta={() => app.go('checkout')} />
      </div>
      <KSection tone="subtle" tight>
        <KHead eyebrow={{ en: 'Don’t forget', ar: 'لا تنسى' }} title={{ en: 'Drivers, dimmers & accessories', ar: 'محولات وديمرات وإكسسوارات' }} />
        <KProductGrid app={app} products={[K_PRODUCTS[2], K_PRODUCTS[7], K_PRODUCTS[6], K_PRODUCTS[10]]} />
      </KSection>
    </>
  );
}

export function KCheckout({ app }) {
  const { t, tx, cols, compact } = useK();
  const [del, setDel] = React.useState('standard');
  const fee = { standard: 150, express: 300, pickup: 0, install: 450 }[del];
  return (
    <div style={{ background: 'var(--bg-subtle)' }}>
      <div className="d-container" style={{ paddingBlock: compact ? 16 : 28 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap', padding: '9px 14px', marginBottom: compact ? 14 : 20, border: '1px solid var(--border-1)', borderRadius: 'var(--radius-md)', background: 'var(--white)', font: '600 14px/1.4 var(--font-body)', color: 'var(--fg-2)' }}>
          <Icon name="user" size={16} />
          {app.user ? (
            <>{t('Logged in as', 'مسجل الدخول باسم')} <b style={{ color: 'var(--fg-1)' }}>{tx(app.user.name)}</b><span style={{ marginInlineStart: 'auto' }} /><button type="button" onClick={() => app.go('logout')} style={{ border: 0, background: 'none', padding: 0, font: '700 13px var(--font-body)', color: 'var(--fg-2)', textDecoration: 'underline', textUnderlineOffset: 3, cursor: 'pointer' }}>{t('Not you? Log out', 'لست أنت؟ تسجيل الخروج')}</button></>
          ) : (
            <>{t('Have an account?', 'عندك حساب؟')} <button type="button" onClick={() => app.go('login', { next: 'checkout' })} style={{ border: 0, background: 'none', padding: 0, font: '800 14px var(--font-body)', color: 'var(--fg-1)', textDecoration: 'underline', textDecorationColor: 'var(--accent)', textDecorationThickness: 2, textUnderlineOffset: 3, cursor: 'pointer' }}>{t('Log in', 'سجّل الدخول')}</button></>
          )}
        </div>
        <Breadcrumbs items={[{ label: { en: 'Cart', ar: 'السلة' }, onClick: () => app.go('cart') }, { label: { en: 'Checkout', ar: 'الدفع' } }]} />
        <h1 className="t-h2" style={{ margin: '14px 0 24px' }}>{t('Checkout', 'إتمام الشراء')}</h1>
        <div style={{ display: 'grid', gridTemplateColumns: cols('minmax(0,1fr) 400px', '1fr'), gap: compact ? 16 : 32, alignItems: 'start' }}>
          <CheckoutForm key={app.user ? 'u' : 'g'} delivery={del} onDelivery={setDel} contact={app.user || undefined} addresses={app.user ? kaCheckoutAddrs(app.addresses) : undefined} />
          <div style={{ position: compact ? 'static' : 'sticky', top: 24 }}>
            <OrderSummary items={app.cart} delivery={fee} onCta={() => app.go('confirm')} />
          </div>
        </div>
      </div>
    </div>
  );
}

