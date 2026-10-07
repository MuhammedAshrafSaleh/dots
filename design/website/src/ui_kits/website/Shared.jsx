
import React from 'react';

export function useK() {
  const L = useLocale();
  return { ...L, cols: (d, m) => (L.compact ? m : d) };
}

/** Scroll reveal, once: rises + fades, images inside un-zoom. Elements already in view animate right after page entry. */
export function KReveal({ children, delay = 0, style, className }) {
  const ref = React.useRef(null);
  const [on, setOn] = React.useState(false);
  React.useEffect(() => {
    const el = ref.current; if (!el) return;
    let done = false;
    const cleanup = () => { window.removeEventListener('scroll', check, true); window.removeEventListener('resize', check); clearInterval(iv); };
    function check() { if (done) return; const r = el.getBoundingClientRect(); if (r.top < window.innerHeight * 0.92 && r.bottom > 0) { done = true; setOn(true); cleanup(); } }
    window.addEventListener('scroll', check, true); window.addEventListener('resize', check);
    const iv = setInterval(check, 250);
    const t = setTimeout(check, 60);
    return () => { cleanup(); clearTimeout(t); };
  }, []);
  return (
    <div ref={ref} className={cx('d-rv', on && 'is-on', className)} style={{ '--rv-d': delay + 'ms', ...style }}>{children}</div>
  );
}

export function KSection({ children, tone, tight, style }) {
  const { compact } = useLocale();
  const y = tight ? (compact ? 32 : 48) : compact ? 56 : 96;
  return (
    <section style={{ background: tone === 'subtle' ? 'var(--bg-subtle)' : 'var(--bg-page)', paddingBlock: y, ...style }}>
      <div className="d-container">{children}</div>
    </section>
  );
}

export function KHead({ eyebrow, title, lede, action, center }) {
  const { tx, compact } = useLocale();
  return (
    <KReveal style={{ display: 'flex', alignItems: compact ? 'flex-start' : 'flex-end', flexDirection: compact ? 'column' : 'row', justifyContent: 'space-between', gap: 16, marginBottom: compact ? 24 : 36, textAlign: center ? 'center' : undefined }}>
      <div style={{ display: 'grid', gap: 12, maxWidth: 720, marginInline: center ? 'auto' : undefined, justifyItems: center ? 'center' : undefined }}>
        {eyebrow && <Eyebrow>{tx(eyebrow)}</Eyebrow>}
        <h2 className="t-h2" style={{ margin: 0, fontSize: compact ? 'var(--fs-h2-m)' : undefined, lineHeight: compact ? 'var(--lh-h2-m)' : undefined, textWrap: 'balance' }}>{tx(title)}</h2>
        {lede && <p className="t-body-lg" style={{ margin: 0, color: 'var(--fg-2)', textWrap: 'pretty' }}>{tx(lede)}</p>}
      </div>
      {action}
    </KReveal>
  );
}

export function KPageHead({ crumbs, eyebrow, title, lede, children, count }) {
  const { tx, t, compact } = useLocale();
  return (
    <div style={{ background: 'var(--bg-subtle)', borderBottom: '1px solid var(--border-1)' }}>
      <div className="d-container" style={{ paddingBlock: compact ? '20px 28px' : '28px 44px', display: 'grid', gap: compact ? 14 : 18 }}>
        {crumbs && <Breadcrumbs items={crumbs} />}
        <div style={{ display: 'flex', alignItems: 'flex-end', justifyContent: 'space-between', gap: 24, flexWrap: 'wrap' }}>
          <div style={{ display: 'grid', gap: 12, maxWidth: 760 }}>
            {eyebrow && <Eyebrow>{tx(eyebrow)}</Eyebrow>}
            <h1 className="t-h1" style={{ margin: 0, fontSize: compact ? 'var(--fs-h1-m)' : undefined, lineHeight: compact ? 'var(--lh-h1-m)' : undefined }}>
              {tx(title)}{count != null && <span style={{ font: '600 16px var(--font-body)', color: 'var(--fg-3)', marginInlineStart: 12, letterSpacing: 0 }}>{count.toLocaleString('en-US')} {t('products', 'منتج')}</span>}
            </h1>
            {lede && <p className="t-body-lg" style={{ margin: 0, color: 'var(--fg-2)', textWrap: 'pretty' }}>{tx(lede)}</p>}
          </div>
          {children}
        </div>
      </div>
    </div>
  );
}

export function KQuoteBand({ app }) {
  const { t, compact } = useLocale();
  return (
    <KSection tone="subtle" tight>
      <KReveal style={{ border: '1px solid var(--border-1)', background: 'var(--white)', borderRadius: 'var(--radius-xl)', padding: compact ? 24 : 40, display: 'grid', gridTemplateColumns: compact ? '1fr' : '1.3fr 1fr', gap: compact ? 20 : 40, alignItems: 'center' }}>
        <div style={{ display: 'grid', gap: 12 }}>
          <Eyebrow>{t('Projects & trade', 'المشروعات والمقاولين')}</Eyebrow>
          <h2 className="t-h3" style={{ margin: 0 }}>{t('Have a BOQ or drawings? Get a priced quote within 48 hours.', 'عندك مقايسة أو رسومات؟ احصل على عرض سعر خلال 48 ساعة.')}</h2>
          <ul className="d-node-list" style={{ font: '600 14px/1.4 var(--font-body)', color: 'var(--fg-2)', gridTemplateColumns: compact ? '1fr' : '1fr 1fr' }}>
            <li>{t('Project pricing on 10,000+ items', 'أسعار مشروعات على أكثر من 10,000 منتج')}</li>
            <li>{t('Alternatives & value engineering', 'بدائل وهندسة قيمة')}</li>
            <li>{t('Datasheets & IES files included', 'مع الداتا شيت وملفات IES')}</li>
            <li>{t('Site delivery across Egypt', 'توصيل للموقع في كل مصر')}</li>
          </ul>
        </div>
        <div style={{ display: 'grid', gap: 10 }}>
          <Button size="lg" variant="whatsapp" block onClick={() => app.quote()}>{t('Request a quote on WhatsApp', 'اطلب عرض سعر على واتساب')}</Button>
          <Button size="lg" variant="outline" block icon="upload" onClick={() => app.toast('info', t('Upload your BOQ', 'ارفع المقايسة'), t('PDF, XLSX or DWG up to 25 MB', 'PDF أو XLSX أو DWG حتى 25 ميجا'))}>{t('Upload BOQ / drawings', 'ارفع المقايسة / الرسومات')}</Button>
          <span style={{ font: '500 12px var(--font-body)', color: 'var(--fg-3)', textAlign: 'center' }}>{t('Sun–Thu 9:00–18:00 · reply within 2 working hours', 'الأحد–الخميس 9–6 · الرد خلال ساعتين عمل')}</span>
        </div>
      </KReveal>
    </KSection>
  );
}

export function KProductGrid({ products, app, cols }) {
  const { compact } = useLocale();
  const n = cols || (compact ? 2 : 4);
  return (
    <div style={{ display: 'grid', gridTemplateColumns: `repeat(${n}, minmax(0, 1fr))`, gap: compact ? 12 : 20 }}>
      {products.map((p, i) => (
        <KReveal key={p.id} delay={(i % n) * 60}><ProductCard product={p} onOpen={() => app.go('product', { id: p.id })} onAdd={() => app.add(p)} onQuote={() => app.quote(p)} saved={app.wish.includes(p.id)} onSave={app.toggleWish} onLogin={() => app.go('login')} onRegister={() => app.go('register')} /></KReveal>
      ))}
    </div>
  );
}

