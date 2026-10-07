
import React from 'react';

export function KBrands({ app }) {
  const { t, cols, compact } = useK();
  const [q, setQ] = React.useState('');
  const [letter, setLetter] = React.useState('All');
  const letters = ['All', 'A', 'B', 'D', 'F', 'L', 'O', 'P', 'S'];
  const list = K_BRANDS.filter((b) => (letter === 'All' || b.name[0] === letter) && b.name.toLowerCase().includes(q.toLowerCase()));
  return (
    <>
      <KPageHead crumbs={[{ label: { en: 'Home', ar: 'الرئيسية' }, onClick: () => app.go('home') }, { label: { en: 'Brands', ar: 'العلامات التجارية' } }]} eyebrow={{ en: 'Authorised partner', ar: 'شريك معتمد' }}
        title={{ en: 'Brands', ar: 'العلامات التجارية' }} lede={{ en: 'Genuine products with official Egyptian warranty from 60+ manufacturers.', ar: 'منتجات أصلية بضمان رسمي في مصر من أكثر من 60 مصنع.' }}>
        <Input icon="search" placeholder={t('Find a brand', 'ابحث عن علامة')} value={q} onChange={(e) => setQ(e.target.value)} style={{ width: compact ? '100%' : 320 }} />
      </KPageHead>
      <KSection tight>
        <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', marginBottom: 28 }} dir="ltr">
          {letters.map((l) => <Chip key={l} active={l === letter} onClick={() => setLetter(l)}>{l === 'All' ? t('All', 'الكل') : l}</Chip>)}
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: cols('1.2fr 1fr', '1fr'), border: '1px solid var(--border-1)', borderRadius: 'var(--radius-xl)', overflow: 'hidden', marginBottom: 32 }}>
          <ImageFrame style={{ minHeight: compact ? 220 : 320 }} label={t('BRAND FEATURE · Delta Light installation in hotel lobby', 'علامة مميزة · مشروع ديلتا لايت')} />
          <div style={{ padding: compact ? 24 : 40, display: 'grid', gap: 14, alignContent: 'center' }}>
            <Badge tone="accent">{t('Featured brand', 'علامة مميزة')}</Badge>
            <h2 className="t-h3" style={{ margin: 0 }}>Delta Light</h2>
            <p className="t-body" style={{ margin: 0, color: 'var(--fg-2)' }}>{t('Belgian architectural lighting — trimless downlights, profiles and outdoor. Full range in the New Cairo showroom.', 'إضاءة معمارية بلجيكية — سبوتات بدون إطار وبروفايلات وإضاءة خارجية. المجموعة كاملة في معرض القاهرة الجديدة.')}</p>
            <Button variant="dark" iconEnd="arrow-right" style={{ justifySelf: 'start' }} onClick={() => app.go('brand', { id: 'delta' })}>{t('Explore Delta Light', 'استكشف ديلتا لايت')}</Button>
          </div>
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: cols('repeat(4, minmax(0,1fr))', 'repeat(2, minmax(0,1fr))'), gap: compact ? 12 : 20 }}>
          {list.map((b, i) => <KReveal key={b.id} delay={(i % 4) * 60}><BrandCard brand={b} onOpen={() => app.go('brand', { id: b.id })} /></KReveal>)}
        </div>
      </KSection>
      <KQuoteBand app={app} />
    </>
  );
}

export function KBrandDetail({ app, route }) {
  const { t, tx, cols, compact } = useK();
  const b = K_BRANDS.find((x) => x.id === route.id) || K_BRANDS[0];
  const [cat, setCat] = React.useState('all');
  return (
    <>
      <div style={{ background: 'var(--bg-subtle)', borderBottom: '1px solid var(--border-1)' }}>
        <div className="d-container" style={{ paddingBlock: compact ? 20 : 28, display: 'grid', gap: 24 }}>
          <Breadcrumbs items={[{ label: { en: 'Home', ar: 'الرئيسية' }, onClick: () => app.go('home') }, { label: { en: 'Brands', ar: 'العلامات التجارية' }, onClick: () => app.go('brands') }, { label: b.name }]} />
          <div style={{ display: 'grid', gridTemplateColumns: cols('220px 1fr 1.1fr', '1fr'), gap: compact ? 20 : 40, alignItems: 'center' }}>
            <div style={{ aspectRatio: compact ? '16/7' : '1/1', background: 'var(--white)', border: '1px solid var(--border-1)', borderRadius: 'var(--radius-lg)', display: 'grid', placeItems: 'center' }}><span className="d-bcard__word" style={{ fontSize: 30 }}>{b.name}</span></div>
            <div style={{ display: 'grid', gap: 12 }}>
              <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}><Badge tone="dark">{t('Authorised distributor', 'موزع معتمد')}</Badge><Badge tone="neutral">{tx(b.origin)}</Badge></div>
              <h1 className="t-h1" style={{ margin: 0, fontSize: compact ? 'var(--fs-h1-m)' : undefined }}>{b.name}</h1>
              <p className="t-body" style={{ margin: 0, color: 'var(--fg-2)' }}>{t('Full catalogue with official warranty, local stock of top lines and project pricing through Dots.', 'الكتالوج الكامل بضمان رسمي، مخزون محلي للخطوط الأساسية وأسعار مشروعات من خلال دوتس.')}</p>
              <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap' }}><Button icon="download" variant="outline">{t('2026 catalogue (PDF)', 'كتالوج 2026')}</Button><Button variant="whatsapp" onClick={() => app.quote()}>{t('Project pricing', 'أسعار المشروعات')}</Button></div>
            </div>
            {!compact && <ImageFrame ratio="16 / 10" radius="var(--radius-xl)" label={t('BRAND · signature product in a real interior', 'العلامة · منتج مميز في مساحة حقيقية')} />}
          </div>
        </div>
      </div>
      <KSection tight>
        <Tabs value={cat} onChange={setCat} style={{ marginBottom: 24 }} tabs={[{ id: 'all', label: { en: 'All', ar: 'الكل' }, count: b.count }, { id: 'in', label: { en: 'Indoor', ar: 'داخلية' }, count: 612 }, { id: 'out', label: { en: 'Outdoor', ar: 'خارجية' }, count: 244 }, { id: 'smart', label: { en: 'Smart', ar: 'ذكي' }, count: 138 }, { id: 'lamps', label: { en: 'Lamps', ar: 'لمبات' }, count: 418 }]} />
        <KProductGrid app={app} products={K_PRODUCTS.slice(0, 8)} />
        <div style={{ display: 'flex', justifyContent: 'center', marginTop: 32 }}><Button variant="outline" iconEnd="arrow-right" onClick={() => app.go('products')}>{t('View all', 'عرض كل')} {b.count.toLocaleString('en-US')} {t('products', 'منتج')}</Button></div>
      </KSection>
      <KSection tone="subtle">
        <KHead eyebrow={{ en: 'Projects', ar: 'مشروعات' }} title={{ en: 'Specified by Dots', ar: 'من تنفيذ دوتس' }} />
        <div style={{ display: 'grid', gridTemplateColumns: cols('2fr 1fr 1fr', '1fr'), gap: 16 }}>
          <ImageFrame ratio={compact ? '4/3' : undefined} style={{ minHeight: compact ? 0 : 340 }} radius="var(--radius-lg)" label={t('PROJECT · restaurant, Zamalek', 'مشروع · مطعم بالزمالك')} />
          <ImageFrame ratio={compact ? '4/3' : undefined} radius="var(--radius-lg)" label={t('PROJECT · villa facade', 'مشروع · واجهة فيلا')} />
          <ImageFrame ratio={compact ? '4/3' : undefined} radius="var(--radius-lg)" label={t('PROJECT · office, New Capital', 'مشروع · مكتب بالعاصمة الإدارية')} />
        </div>
      </KSection>
    </>
  );
}

export function KCategory({ app, route }) {
  const { t, tx, cols, compact, dir } = useK();
  const c = K_CATEGORIES.find((x) => x.id === route.id) || K_CATEGORIES[0];
  return (
    <>
      <section style={{ position: 'relative', minHeight: compact ? 360 : 440, display: 'flex', alignItems: 'flex-end' }}>
        <ImageFrame label={c.imageLabel} style={{ position: 'absolute', inset: 0 }} />
        <div style={{ position: 'absolute', inset: 0, background: `linear-gradient(${dir === 'rtl' ? 270 : 90}deg, rgba(24,24,22,.75), rgba(24,24,22,.1) 75%)` }} />
        <div className="d-container" style={{ position: 'relative', width: '100%', paddingBlock: compact ? 28 : 48, color: '#fff', display: 'grid', gap: 14 }}>
          <nav style={{ font: '600 13px var(--font-body)', color: 'rgba(255,255,255,.85)', display: 'flex', gap: 6, alignItems: 'center' }}>{t('Home', 'الرئيسية')}<Icon name="chevron-right" size={14} />{t('Indoor Lighting', 'إضاءة داخلية')}</nav>
          <h1 className="t-h1" style={{ margin: 0, fontSize: compact ? 'var(--fs-h1-m)' : undefined, maxWidth: 700 }}>{tx(c.name)}</h1>
          <p className="t-body-lg" style={{ margin: 0, maxWidth: 560, color: 'rgba(255,255,255,.88)' }}>{t('Statement pieces and task light for dining, kitchens and double-height spaces.', 'قطع مميزة وإضاءة وظيفية لغرف الطعام والمطابخ والمساحات المرتفعة.')}</p>
          <div style={{ display: 'flex', gap: 10 }}><Button iconEnd="arrow-right" onClick={() => app.go('products')}>{t('Shop all', 'تسوق الكل')} {c.count.toLocaleString('en-US')}</Button></div>
        </div>
      </section>
      <KSection tight>
        <div style={{ display: 'grid', gridTemplateColumns: cols('repeat(5, minmax(0,1fr))', 'repeat(2, minmax(0,1fr))'), gap: compact ? 12 : 16 }}>
          {K_SUBCATS.map((s, i) => <KReveal key={s.id} delay={i * 50}><CategoryCard variant="tile" category={s} onOpen={() => app.go('products')} /></KReveal>)}
        </div>
      </KSection>
      <KSection tone="subtle">
        <KHead eyebrow={{ en: 'Top picks', ar: 'الأفضل' }} title={{ en: 'Most specified this season', ar: 'الأكثر طلباً هذا الموسم' }} action={<Button variant="outline" iconEnd="arrow-right" onClick={() => app.go('products')}>{t('View all', 'عرض الكل')}</Button>} />
        <KProductGrid app={app} products={[K_PRODUCTS[0], K_PRODUCTS[9], K_PRODUCTS[5], K_PRODUCTS[3]]} />
      </KSection>
      <KSection>
        <div style={{ display: 'grid', gridTemplateColumns: cols('1fr 1.4fr', '1fr'), gap: 40 }}>
          <div style={{ display: 'grid', gap: 20, alignContent: 'start' }}>
            <KHead eyebrow={{ en: 'Buying guide', ar: 'دليل الشراء' }} title={{ en: 'Choosing the right pendant', ar: 'كيف تختار المعلقة المناسبة' }} />
            <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>{K_BRANDS.slice(0, 5).map((b) => <Chip key={b.id} onClick={() => app.go('brand', { id: b.id })}>{b.name}</Chip>)}</div>
          </div>
          <Accordion defaultOpen={['g1']} items={[{ id: 'g1', title: { en: 'What size for my dining table?', ar: 'ما المقاس المناسب لطاولة الطعام؟' }, content: { en: 'Choose a diameter roughly half to two-thirds of the table width, hung 750–850 mm above the tabletop.', ar: 'اختر قطراً يعادل نصف إلى ثلثي عرض الطاولة، على ارتفاع 750–850 مم فوق سطحها.' } }, { id: 'g2', title: { en: 'Warm or neutral white?', ar: 'أبيض دافئ أم محايد؟' }, content: { en: '2700–3000K for living and dining; 4000K for kitchens and work areas.', ar: '2700–3000K للمعيشة والطعام؛ 4000K للمطابخ ومناطق العمل.' } }, { id: 'g3', title: { en: 'Will it work with my dimmer?', ar: 'هل تعمل مع الديمر عندي؟' }, content: { en: 'Check the Dimming row in the specs; TRIAC works with most wall dimmers.', ar: 'راجع خانة التعتيم في المواصفات؛ نوع TRIAC يعمل مع معظم الديمرات.' } }]} />
        </div>
      </KSection>
      <KQuoteBand app={app} />
    </>
  );
}

