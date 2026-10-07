
import React from 'react';

export function KProducts({ app, route }) {
  const { t, tx, compact, cols } = useK();
  const [f, setF] = React.useState({ category: 'pend', brands: ['Philips', 'Flos'], temps: ['3000K'], watts: [], finishes: [] });
  const [page, setPage] = React.useState(1);
  const [view, setView] = React.useState('grid');
  const [showF, setShowF] = React.useState(false);
  const chips = [
    ...f.brands.map((b) => ({ id: 'b' + b, key: 'brands', v: b, facet: { en: 'Brand', ar: 'العلامة' }, label: b })),
    ...f.temps.map((b) => ({ id: 't' + b, key: 'temps', v: b, facet: { en: 'CCT', ar: 'درجة اللون' }, label: b })),
    ...f.watts.map((b) => ({ id: 'w' + b, key: 'watts', v: b, facet: { en: 'Wattage', ar: 'القدرة' }, label: b })),
    ...(f.inStock ? [{ id: 'st', key: 'inStock', facet: { en: 'Availability', ar: 'التوفر' }, label: { en: 'In stock', ar: 'متوفر' } }] : []),
  ];
  const rm = (c) => setF((x) => (c.key === 'inStock' ? { ...x, inStock: false } : { ...x, [c.key]: x[c.key].filter((y) => y !== c.v) }));
  const clear = () => setF({ category: f.category, brands: [], temps: [], watts: [], finishes: [] });
  const list = K_PRODUCTS.slice(0, compact ? 8 : 12);
  const sidebar = <FilterSidebar facets={K_FACETS} value={f} onChange={setF} onClear={clear} />;
  return (
    <>
      <KPageHead crumbs={[{ label: { en: 'Home', ar: 'الرئيسية' }, onClick: () => app.go('home') }, { label: { en: 'Indoor Lighting', ar: 'إضاءة داخلية' }, onClick: () => app.go('category') }, { label: { en: 'Pendants', ar: 'معلقات' } }]}
        title={{ en: 'Pendants', ar: 'معلقات' }} count={1148} />
      <div className="d-container" style={{ paddingBlock: compact ? 16 : 32, display: 'grid', gridTemplateColumns: cols('280px minmax(0,1fr)', '1fr'), gap: compact ? 16 : 40, alignItems: 'start' }}>
        {!compact && sidebar}
        <div style={{ display: 'grid', gap: 20 }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 12, flexWrap: 'wrap' }}>
            {compact && <Button variant="outline" icon="sliders-horizontal" onClick={() => setShowF(!showF)}>{t('Filters', 'الفلاتر')} ({chips.length})</Button>}
            <Select size="sm" style={{ width: compact ? 'auto' : 220, flex: compact ? 1 : undefined }} aria-label={t('Sort', 'ترتيب')} options={[t('Sort: Recommended', 'ترتيب: المقترح'), t('Price: low to high', 'السعر: من الأقل'), t('Price: high to low', 'السعر: من الأعلى'), t('Newest', 'الأحدث')]} />
            {!compact && <Tabs variant="pill" value={view} onChange={setView} style={{ marginInlineStart: 'auto' }} tabs={[{ id: 'grid', label: { en: 'Grid', ar: 'شبكة' } }, { id: 'list', label: { en: 'List', ar: 'قائمة' } }]} />}
          </div>
          {compact && showF && <div style={{ border: '1px solid var(--border-1)', borderRadius: 'var(--radius-lg)', padding: 16 }}>{sidebar}</div>}
          <ActiveFilters chips={chips} count={214} onRemove={rm} onClear={clear} />
          <KProductGrid app={app} products={list} cols={compact ? 2 : 3} />
          <div style={{ paddingTop: 12, borderTop: '1px solid var(--border-1)' }}><Pagination page={page} total={18} onChange={setPage} count={214} perPage={12} /></div>
        </div>
      </div>
      <KQuoteBand app={app} />
    </>
  );
}

export function KProductDetail({ app, route }) {
  const { t, tx, price, compact, cols } = useK();
  const p = K_PRODUCTS.find((x) => x.id === route.id) || K_PRODUCTS[0];
  const [img, setImg] = React.useState(0);
  const [cct, setCct] = React.useState('3000');
  const [w, setW] = React.useState('24');
  const [fin, setFin] = React.useState('black');
  const [size, setSize] = React.useState('450');
  const [qty, setQty] = React.useState(1);
  const [tab, setTab] = React.useState('specs');
  const shots = [t('Cut-out · front 3/4', 'المنتج · زاوية أمامية'), t('Lifestyle · over dining table, lit', 'صورة استخدام · فوق طاولة الطعام'), t('Detail · diffuser & canopy', 'تفاصيل · الناشر والقاعدة'), t('Dimension drawing', 'رسم الأبعاد'), t('Polar curve (from IES)', 'منحنى التوزيع الضوئي')];
  const files = [{ name: { en: 'Product datasheet', ar: 'الداتا شيت' }, ext: 'pdf', size: '1.2 MB', kind: { en: 'Datasheet', ar: 'داتا شيت' } }, { name: { en: 'Photometric file', ar: 'ملف القياسات الضوئية' }, ext: 'ies', size: '24 KB', kind: { en: 'IES · LM-63', ar: 'IES' } }, { name: { en: 'Photometric file', ar: 'ملف القياسات الضوئية' }, ext: 'ldt', size: '22 KB', kind: { en: 'EULUMDAT', ar: 'EULUMDAT' } }, { name: { en: 'Installation guide', ar: 'دليل التركيب' }, ext: 'pdf', size: '640 KB', kind: { en: 'Manual', ar: 'دليل' } }];
  const specs = [
    { title: { en: 'Optical', ar: 'بصري' }, rows: [[{ en: 'Luminous flux', ar: 'الفيض الضوئي' }, '2,450 lm'], [{ en: 'Colour temperature', ar: 'درجة حرارة اللون' }, cct + 'K'], [{ en: 'Colour rendering', ar: 'مؤشر تجسيد اللون' }, 'CRI ≥ 90'], [{ en: 'Beam angle', ar: 'زاوية الإضاءة' }, '110°'], [{ en: 'UGR', ar: 'UGR' }, '< 19']] },
    { title: { en: 'Electrical', ar: 'كهربائي' }, rows: [[{ en: 'Power', ar: 'القدرة' }, w + ' W'], [{ en: 'Input voltage', ar: 'الجهد' }, '220–240 V AC, 50/60 Hz'], [{ en: 'Dimming', ar: 'التعتيم' }, 'TRIAC / 1–10V'], [{ en: 'Lifetime', ar: 'العمر الافتراضي' }, 'L80 · 50,000 h']] },
    { title: { en: 'Physical', ar: 'فيزيائي' }, rows: [[{ en: 'Dimensions', ar: 'الأبعاد' }, 'Ø' + size + ' × H 210 mm'], [{ en: 'Cable length', ar: 'طول السلك' }, '1,500 mm, adjustable'], [{ en: 'IP rating', ar: 'درجة الحماية' }, 'IP20'], [{ en: 'Warranty', ar: 'الضمان' }, { en: '3 years', ar: '3 سنوات' }]] },
  ];
  const unit = 4850 + (w === '36' ? 900 : 0) + (size === '600' ? 1400 : 0);
  return (
    <>
      <div className="d-container" style={{ paddingBlock: compact ? '16px 8px' : '24px 8px' }}>
        <Breadcrumbs items={[{ label: { en: 'Home', ar: 'الرئيسية' }, onClick: () => app.go('home') }, { label: { en: 'Indoor Lighting', ar: 'إضاءة داخلية' }, onClick: () => app.go('category') }, { label: { en: 'Pendants', ar: 'معلقات' }, onClick: () => app.go('products') }, { label: p.name }]} />
      </div>
      <div className="d-container" style={{ display: 'grid', gridTemplateColumns: cols('minmax(0,1.1fr) minmax(0,1fr)', '1fr'), gap: compact ? 20 : 56, paddingBottom: compact ? 32 : 64, alignItems: 'start' }}>
        <div style={{ display: 'grid', gridTemplateColumns: compact ? '1fr' : '84px 1fr', gap: 14 }}>
          {!compact && <div style={{ display: 'grid', gap: 10, alignContent: 'start' }}>{shots.map((s, i) => (
            <button key={i} type="button" onClick={() => setImg(i)} aria-label={s} style={{ padding: 0, border: i === img ? '1.5px solid var(--ink-900)' : '1px solid var(--border-1)', borderRadius: 10, overflow: 'hidden', cursor: 'pointer', background: 'none', position: 'relative' }}>
              <ImageFrame ratio="1 / 1" />{i === img && <span style={{ position: 'absolute', top: 4, insetInlineEnd: 4, width: 8, height: 8, borderRadius: '50%', background: 'var(--accent)' }} />}
            </button>))}</div>}
          <div style={{ position: 'relative' }}>
            <ImageFrame key={img} ratio="1 / 1" radius="var(--radius-xl)" label={shots[img]} style={{ animation: 'd-fade-in 240ms var(--ease-out)' }} />
            <div style={{ position: 'absolute', top: 16, insetInlineStart: 16, display: 'flex', gap: 6 }}>{(p.badges || []).map((b, i) => <Badge key={i} tone={b.tone}>{tx(b.label)}</Badge>)}</div>
            {compact && <div style={{ display: 'flex', gap: 6, justifyContent: 'center', marginTop: 12 }}>{shots.map((_, i) => <button key={i} type="button" onClick={() => setImg(i)} aria-label={String(i + 1)} style={{ width: i === img ? 20 : 8, height: 8, borderRadius: 4, border: 0, padding: 0, background: i === img ? 'var(--ink-900)' : 'var(--ink-200)', transition: 'width 240ms var(--ease-out)' }} />)}</div>}
          </div>
        </div>
        <div style={{ display: 'grid', gap: 22 }}>
          <div style={{ display: 'grid', gap: 10 }}>
            <a href="#" onClick={(e) => { e.preventDefault(); app.go('brand', { id: 'philips' }); }} className="d-pcard__brand" style={{ textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: 4 }}>{p.brand}<Icon name="arrow-right" size={12} /></a>
            <h1 className="t-h2" style={{ margin: 0, fontSize: compact ? 'var(--fs-h2-m)' : undefined, lineHeight: compact ? 'var(--lh-h2-m)' : undefined }}>{tx(p.name)}</h1>
            <div style={{ display: 'flex', gap: 14, alignItems: 'center', flexWrap: 'wrap' }}><span className="d-pcard__sku">SKU {p.sku}</span><Badge tone="success" dot>{t('In stock · 38 units', 'متوفر · 38 قطعة')}</Badge><Badge tone="outline">{t('Dimmable', 'قابل للتعتيم')}</Badge></div>
          </div>
          <div style={{ display: 'grid', gap: 4, paddingBlock: 16, borderBlock: '1px solid var(--border-1)' }}>
            <div style={{ display: 'flex', alignItems: 'baseline', gap: 10 }}><span style={{ font: '800 30px/1 var(--font-body)' }}>{price(unit)}</span>{p.was && <span className="d-pcard__was" style={{ fontSize: 16 }}>{price(p.was)}</span>}</div>
            <span style={{ font: '500 13px var(--font-body)', color: 'var(--fg-3)' }}>{t('Incl. 14% VAT · project pricing for 10+ units', 'شامل الضريبة · أسعار مشروعات لـ 10 قطع فأكثر')}</span>
          </div>
          <VariantPicker tall label={{ en: 'Colour temperature', ar: 'درجة حرارة اللون' }} value={cct} onChange={setCct} options={K_TEMPS} />
          <div style={{ display: 'grid', gridTemplateColumns: cols('1fr 1fr', '1fr'), gap: 22 }}>
            <VariantPicker label={{ en: 'Wattage', ar: 'القدرة' }} value={w} onChange={setW} options={[{ value: '18', label: '18W' }, { value: '24', label: '24W' }, { value: '36', label: '36W' }]} />
            <VariantPicker label={{ en: 'Size', ar: 'المقاس' }} value={size} onChange={setSize} options={[{ value: '300', label: 'Ø300' }, { value: '450', label: 'Ø450' }, { value: '600', label: 'Ø600' }]} />
          </div>
          <SwatchPicker label={{ en: 'Finish', ar: 'التشطيب' }} value={fin} onChange={setFin} options={K_FINISHES} />
          <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap' }}>
            <QuantityStepper value={qty} onChange={setQty} className="" />
            <Button size="lg" icon="shopping-bag" style={{ flex: '1 1 180px' }} onClick={() => app.add(p, qty)}>{t('Add to cart', 'أضف للسلة')}</Button>
            <Button size="lg" variant="whatsapp" style={{ flex: compact ? '1 1 0' : '1 1 auto' }} onClick={() => app.quote(p)}>{t('Request a quote', 'اطلب عرض سعر')}</Button>
            <KFavButton app={app} product={p} />
          </div>
          <div className="d-trust" style={{ fontSize: 13 }}>
            <span><Icon name="truck" size={18} />{t('Next-day delivery in Cairo & Giza', 'توصيل اليوم التالي للقاهرة والجيزة')}</span>
            <span><Icon name="wrench" size={18} />{t('Add installation at checkout', 'أضف التركيب عند الدفع')}</span>
            <span><Icon name="shield-check" size={18} />{t('3-year warranty', 'ضمان 3 سنوات')}</span>
          </div>
          <div style={{ display: 'grid', gap: 10 }}><span className="d-variant__head">{t('Downloads', 'التحميلات')}</span><FileDownloads files={files.slice(0, 3)} /></div>
        </div>
      </div>
      <KSection tone="subtle" tight>
        <Tabs value={tab} onChange={setTab} style={{ marginBottom: 28 }} tabs={[{ id: 'overview', label: { en: 'Overview', ar: 'نظرة عامة' } }, { id: 'specs', label: { en: 'Specifications', ar: 'المواصفات' } }, { id: 'downloads', label: { en: 'Downloads', ar: 'التحميلات' }, count: 4 }, { id: 'delivery', label: { en: 'Delivery & returns', ar: 'التوصيل والاسترجاع' } }]} />
        <div style={{ display: 'grid', gridTemplateColumns: cols('1.4fr 1fr', '1fr'), gap: 40, alignItems: 'start' }}>
          <div style={{ background: 'var(--white)', borderRadius: 'var(--radius-lg)', padding: compact ? 12 : 20, border: '1px solid var(--border-1)' }}>
            {tab === 'specs' && <SpecTable groups={specs} />}
            {tab === 'downloads' && <FileDownloads files={files} />}
            {tab === 'overview' && <p className="t-body" style={{ margin: 0, color: 'var(--fg-2)' }}>{t('A soft opal dome with a high-CRI LED module that dims smoothly to 1%. Designed for dining tables and kitchen islands; hang in groups of three at 700 mm spacing.', 'قبة أوبال ناعمة بوحدة ليد عالية التجسيد اللوني تعتم بسلاسة حتى 1%. مصممة لطاولات الطعام وجزر المطبخ.')}</p>}
            {tab === 'delivery' && <Accordion items={[{ id: 'a', title: { en: 'Delivery', ar: 'التوصيل' }, content: { en: 'Next day in Cairo & Giza; 2–4 working days elsewhere.', ar: 'اليوم التالي للقاهرة والجيزة؛ 2–4 أيام عمل لباقي المحافظات.' } }, { id: 'b', title: { en: 'Returns', ar: 'الاسترجاع' }, content: { en: '14 days, unused and in original packaging.', ar: '14 يوم، غير مستخدم وبالعبوة الأصلية.' } }]} defaultOpen={['a']} />}
          </div>
          <ImageFrame ratio="1 / 1" radius="var(--radius-lg)" label={t('Polar diagram + dimension drawing (from datasheet)', 'منحنى التوزيع ورسم الأبعاد')} style={{ background: 'var(--white)' }} />
        </div>
      </KSection>
      <KSection>
        <KHead eyebrow={{ en: 'Complete the look', ar: 'أكمل التصميم' }} title={{ en: 'Often specified together', ar: 'غالباً ما تُطلب معاً' }} />
        <KProductGrid app={app} products={[K_PRODUCTS[9], K_PRODUCTS[2], K_PRODUCTS[11], K_PRODUCTS[5]]} />
      </KSection>
    </>
  );
}

