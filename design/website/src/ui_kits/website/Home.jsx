
import React from 'react';

function KHero({ app }) {
  const { t, compact, dir } = useLocale();
  const [run, setRun] = React.useState(0);
  const [paused, setPaused] = React.useState(false);
  const words = t('Light, done right — from first sketch to final switch.', 'إضاءة بإتقان — من أول فكرة لآخر مفتاح.').split(' ');
  const d = (s) => ({ '--hi-d': s + 'ms' });
  return (
    <section key={run} className="d-hi" style={{ position: 'relative', minHeight: compact ? 600 : 720, display: 'flex', alignItems: 'flex-end', overflow: 'hidden', background: 'var(--ink-900)' }}>
      <div className="d-hi__media">
        <ImageFrame src={kPh(0, 2000)} video={paused ? undefined : ((window.__resources || {}).heroVid || 'https://assets.mixkit.co/videos/4196/4196-720.mp4')} label={t('HERO VIDEO', 'فيديو رئيسي')} style={{ position: 'absolute', inset: 0 }} />
        <div style={{ position: 'absolute', inset: 0, background: `linear-gradient(${dir === 'rtl' ? 270 : 90}deg, rgba(24,24,22,.8) 0%, rgba(24,24,22,.5) 42%, rgba(24,24,22,.05) 78%)` }} />
        <div style={{ position: 'absolute', inset: 0, background: 'linear-gradient(0deg, rgba(24,24,22,.45), transparent 45%)' }} />
        <div className="d-hi__glow" />
      </div>
      <div className="d-hi__bulb" aria-hidden="true"><span /></div>
      <div className="d-container" style={{ position: 'relative', width: '100%', paddingBlock: compact ? 40 : 96 }}>
        <div style={{ maxWidth: 760, display: 'grid', gap: compact ? 18 : 24, color: 'var(--white)' }}>
          <span className="d-hi__in" style={{ ...d(1700), display: 'inline-flex', alignItems: 'center', gap: 10, font: '800 12px/1 var(--font-body)', letterSpacing: 'var(--tracking-overline)', textTransform: 'var(--text-transform-overline)' }}><span className="d-dot" />{t('Lighting · Smart Home · Electrical', 'إضاءة · منزل ذكي · كهرباء')}</span>
          <h1 className="t-display d-hi__title" style={{ margin: 0, fontSize: compact ? 'var(--fs-display-m)' : 'clamp(48px, 6.4vw, 92px)', lineHeight: compact ? 'var(--lh-display-m)' : 1.02, textWrap: 'balance' }}>
            {words.map((w, i) => <span key={i} className="d-hi__w"><span style={d(1850 + i * 70)}>{w}</span>{i < words.length - 1 ? ' ' : ''}</span>)}
          </h1>
          <p className="t-body-lg d-hi__in" style={{ ...d(1850 + words.length * 70 + 150), margin: 0, color: 'rgba(255,255,255,.9)', maxWidth: 560 }}>{t('10,000+ products from 60+ brands, with lighting design, installation and after-sales support across Egypt.', 'أكثر من 10,000 منتج من 60+ علامة تجارية، مع تصميم الإضاءة والتركيب وخدمة ما بعد البيع في كل مصر.')}</p>
          <div className="d-hi__in" style={{ ...d(1850 + words.length * 70 + 300), display: 'flex', gap: 12, flexWrap: 'wrap' }}>
            <Button size="lg" iconEnd="arrow-right" onClick={() => app.go('products')}>{t('Shop lighting', 'تسوق الإضاءة')}</Button>
            <Button size="lg" style={{ background: 'rgba(255,255,255,.12)', color: '#fff', borderColor: 'rgba(255,255,255,.6)' }} onClick={() => app.go('service')}>{t('Book lighting design', 'احجز تصميم إضاءة')}</Button>
          </div>
        </div>
        <div className="d-hi__in" style={{ ...d(2600), position: 'absolute', insetInlineEnd: compact ? 16 : 'var(--page-margin)', bottom: compact ? 40 : 96, display: 'flex', gap: 8 }}>
          <IconButton icon="rotate-ccw" label={t('Replay intro', 'إعادة المقدمة')} onClick={() => setRun(run + 1)} style={{ background: 'rgba(255,255,255,.14)', color: '#fff' }} />
          <IconButton icon={paused ? 'play' : 'pause'} label={paused ? t('Play video', 'تشغيل الفيديو') : t('Pause video', 'إيقاف الفيديو')} onClick={() => setPaused(!paused)} style={{ background: 'rgba(255,255,255,.14)', color: '#fff' }} />
        </div>
      </div>
    </section>
  );
}

export function KHome({ app }) {
  const { t, tx, compact, cols } = useK();
  const [tab, setTab] = React.useState('new');
  const sets = { new: [0, 1, 3, 7], best: [2, 4, 9, 11], smart: [2, 8, 10, 6] };
  return (
    <>
      <KHero app={app} />
      <div style={{ borderBottom: '1px solid var(--border-1)' }}>
        <div className="d-container" style={{ display: 'grid', gridTemplateColumns: cols('repeat(4,1fr)', '1fr 1fr'), gap: compact ? 14 : 24, paddingBlock: compact ? 18 : 22 }}>
          {[['truck', t('Delivery to all governorates', 'توصيل لكل المحافظات')], ['wrench', t('Certified installation', 'تركيب معتمد')], ['shield-check', t('Official brand warranty', 'ضمان رسمي')], ['message-circle', t('Engineers on WhatsApp', 'مهندسين على واتساب')]].map(([i, l]) => (
            <div key={i} style={{ display: 'flex', alignItems: 'center', gap: 12, font: '700 14px/1.3 var(--font-body)' }}><span style={{ width: 40, height: 40, borderRadius: '50%', background: 'var(--yellow-100)', display: 'grid', placeItems: 'center', flex: 'none' }}><Icon name={i} size={20} /></span>{l}</div>
          ))}
        </div>
      </div>
      <KSection>
        <KHead eyebrow={{ en: 'Shop by category', ar: 'تسوق حسب القسم' }} title={{ en: 'Everything that lights, switches and connects', ar: 'كل ما يضيء ويتحكم ويوصل' }} action={<Button variant="outline" iconEnd="arrow-right" onClick={() => app.go('products')}>{t('All categories', 'كل الأقسام')}</Button>} />
        <div style={{ display: 'grid', gridTemplateColumns: cols('repeat(3, minmax(0,1fr))', 'repeat(2, minmax(0,1fr))'), gap: compact ? 12 : 20 }}>
          {K_CATEGORIES.map((c, i) => <KReveal key={c.id} delay={(i % 3) * 60}><CategoryCard category={c} onOpen={() => app.go('category', { id: c.id })} /></KReveal>)}
        </div>
      </KSection>
      <KSection tone="subtle">
        <KHead eyebrow={{ en: 'Featured', ar: 'مختارات' }} title={{ en: 'In the showroom this month', ar: 'في المعرض هذا الشهر' }} action={<Tabs variant="pill" value={tab} onChange={setTab} tabs={[{ id: 'new', label: { en: 'New arrivals', ar: 'وصل حديثاً' } }, { id: 'best', label: { en: 'Best sellers', ar: 'الأكثر مبيعاً' } }, { id: 'smart', label: { en: 'Smart home', ar: 'منزل ذكي' } }]} />} />
        <KProductGrid key={tab} app={app} products={sets[tab].map((i) => K_PRODUCTS[i])} />
      </KSection>
      <KSection>
        <KHead eyebrow={{ en: 'Services', ar: 'الخدمات' }} title={{ en: 'We don’t just supply it. We design, install and maintain it.', ar: 'لا نكتفي بالتوريد. نصمم ونركب ونصون.' }} action={<Button variant="dark" iconEnd="arrow-right" onClick={() => app.go('services')}>{t('All services', 'كل الخدمات')}</Button>} />
        <div style={{ display: 'grid', gridTemplateColumns: cols('repeat(4, minmax(0,1fr))', '1fr'), gap: 20 }}>
          {K_SERVICES.map((s, i) => <KReveal key={s.id} delay={i * 60}><ServiceCard service={s} onOpen={() => app.go('service', { id: s.id })} /></KReveal>)}
        </div>
      </KSection>
      <KSection tone="subtle" tight>
        <div style={{ display: 'grid', gridTemplateColumns: cols('1fr 1.15fr', '1fr'), border: '1px solid var(--border-1)', borderRadius: 'var(--radius-xl)', overflow: 'hidden', background: 'var(--white)' }}>
          <KReveal style={{ padding: compact ? 24 : 56, display: 'grid', gap: 18, alignContent: 'center' }}>
            <Eyebrow>{t('Smart home', 'المنزل الذكي')}</Eyebrow>
            <h2 className="t-h2" style={{ margin: 0 }}>{t('Your whole home, one tap away.', 'بيتك كله بلمسة واحدة.')}</h2>
            <p className="t-body-lg" style={{ margin: 0, color: 'var(--fg-2)' }}>{t('Scenes for lighting, curtains and AC — installed, programmed and supported by Dots engineers.', 'مشاهد للإضاءة والستائر والتكييف — تركيب وبرمجة ودعم من مهندسي دوتس.')}</p>
            <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap' }}><Button variant="dark" onClick={() => app.go('service', { id: 'smart' })}>{t('Book a home demo', 'احجز عرض منزلي')}</Button><Button variant="ghost" iconEnd="arrow-right" onClick={() => app.go('category', { id: 'smart' })}>{t('Shop smart devices', 'تسوق الأجهزة الذكية')}</Button></div>
          </KReveal>
          <ImageFrame src={kPh(2, 1400)} style={{ minHeight: compact ? 260 : 440 }} label={t('PHOTO · evening living room, scene "Relax" active, phone app in hand', 'صورة · غرفة معيشة مع مشهد إضاءة')} />
        </div>
      </KSection>
      <KSection>
        <KHead eyebrow={{ en: 'Brands', ar: 'العلامات التجارية' }} title={{ en: 'Authorised partner for 60+ manufacturers', ar: 'شريك معتمد لأكثر من 60 مصنع' }} action={<Button variant="outline" iconEnd="arrow-right" onClick={() => app.go('brands')}>{t('All brands', 'كل العلامات')}</Button>} />
        <div style={{ display: 'grid', gridTemplateColumns: cols('repeat(4, minmax(0,1fr))', 'repeat(2, minmax(0,1fr))'), gap: compact ? 12 : 20 }}>
          {K_BRANDS.map((b, i) => <KReveal key={b.id} delay={(i % 4) * 60}><BrandCard brand={b} onOpen={() => app.go('brand', { id: b.id })} /></KReveal>)}
        </div>
      </KSection>
      <KQuoteBand app={app} />
    </>
  );
}

