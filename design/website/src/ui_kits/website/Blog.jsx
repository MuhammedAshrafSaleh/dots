
import React from 'react';

export function KBlog({ app }) {
  const { t, tx, cols, compact } = useK();
  const [cat, setCat] = React.useState('all');
  const [q, setQ] = React.useState('');
  const [page, setPage] = React.useState(1);
  const [feat, ...rest] = K_ARTICLES;
  const list = rest.filter((a) => (cat === 'all' || a.cat === cat) && tx(a.title).toLowerCase().includes(q.toLowerCase()));
  return (
    <>
      <KPageHead crumbs={[{ label: { en: 'Home', ar: 'الرئيسية' }, onClick: () => app.go('home') }, { label: { en: 'Blog', ar: 'المدونة' } }]} eyebrow={{ en: 'Journal', ar: 'المدونة' }}
        title={{ en: 'Ideas, guides & project stories', ar: 'أفكار وأدلة وقصص مشروعات' }} lede={{ en: 'Practical lighting, smart home and electrical know-how from the Dots team.', ar: 'معرفة عملية في الإضاءة والمنزل الذكي والكهرباء من فريق دوتس.' }}>
        <Input icon="search" placeholder={t('Search articles', 'ابحث في المقالات')} value={q} onChange={(e) => setQ(e.target.value)} style={{ width: compact ? '100%' : 320 }} />
      </KPageHead>
      <KSection tight>
        <KReveal><ArticleCard variant="wide" article={feat} onOpen={() => app.go('article', { id: feat.id })} /></KReveal>
        <div style={{ display: 'flex', gap: 8, flexWrap: compact ? 'nowrap' : 'wrap', overflowX: compact ? 'auto' : undefined, margin: compact ? '28px -16px 20px' : '48px 0 28px', paddingInline: compact ? 16 : 0 }}>
          {K_BLOG_CATS.map((c) => <Chip key={c.id} active={c.id === cat} onClick={() => setCat(c.id)} style={{ flex: 'none' }}>{tx(c.label)}</Chip>)}
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: cols('repeat(3, minmax(0,1fr))', '1fr'), gap: compact ? 16 : 24 }}>
          {list.map((a, i) => <KReveal key={a.id + cat} delay={(i % 3) * 60}><ArticleCard article={a} onOpen={() => app.go('article', { id: a.id })} /></KReveal>)}
        </div>
        {!list.length && <p className="t-body" style={{ color: 'var(--fg-2)', textAlign: 'center', padding: 40 }}>{t('No articles match your search.', 'لا توجد مقالات مطابقة.')}</p>}
        <div style={{ marginTop: 40, paddingTop: 16, borderTop: '1px solid var(--border-1)' }}><Pagination page={page} total={9} onChange={setPage} count={78} perPage={9} /></div>
      </KSection>
      <KSection tone="subtle" tight>
        <KReveal style={{ display: 'grid', gridTemplateColumns: cols('1.2fr 1fr', '1fr'), gap: 24, alignItems: 'center' }}>
          <div style={{ display: 'grid', gap: 10 }}><Eyebrow>{t('Newsletter', 'النشرة البريدية')}</Eyebrow><h2 className="t-h3" style={{ margin: 0 }}>{t('One useful lighting idea a month. No spam.', 'فكرة إضاءة مفيدة كل شهر. بدون إزعاج.')}</h2></div>
          <form style={{ display: 'flex', gap: 8 }} onSubmit={(e) => { e.preventDefault(); app.toast('success', t('You’re subscribed', 'تم الاشتراك')); }}>
            <input className="d-input" type="email" placeholder="name@example.com" dir="ltr" aria-label={t('Email', 'البريد')} style={{ height: 52 }} />
            <Button size="lg" type="submit">{t('Subscribe', 'اشترك')}</Button>
          </form>
        </KReveal>
      </KSection>
    </>
  );
}

export function KArticle({ app, route }) {
  const { t, tx, cols, compact } = useK();
  const a = K_ARTICLES.find((x) => x.id === route.id) || K_ARTICLES[0];
  const related = K_ARTICLES.filter((x) => x.id !== a.id).slice(0, 3);
  const share = (l) => app.toast('success', l === 'Copy link' || l === 'نسخ الرابط' ? t('Link copied', 'تم نسخ الرابط') : t('Sharing to', 'مشاركة عبر') + ' ' + l);
  return (
    <>
      <div className="d-container" style={{ paddingTop: compact ? 20 : 32, display: 'grid', gap: compact ? 18 : 24, maxWidth: 1000 }}>
        <Breadcrumbs items={[{ label: { en: 'Home', ar: 'الرئيسية' }, onClick: () => app.go('home') }, { label: { en: 'Blog', ar: 'المدونة' }, onClick: () => app.go('blog') }, { label: a.category }]} />
        <KReveal style={{ display: 'grid', gap: 18 }}>
          <div style={{ display: 'flex', gap: 8 }}><Badge tone="accent">{tx(a.category)}</Badge></div>
          <h1 className="t-h1" style={{ margin: 0, fontSize: compact ? 'var(--fs-h1-m)' : undefined, lineHeight: compact ? 'var(--lh-h1-m)' : undefined, textWrap: 'balance' }}>{tx(a.title)}</h1>
          <p className="t-body-lg" style={{ margin: 0, color: 'var(--fg-2)' }}>{tx(a.excerpt)}</p>
          <div style={{ display: 'flex', alignItems: 'center', gap: 14, flexWrap: 'wrap', justifyContent: 'space-between', paddingBlock: 14, borderBlock: '1px solid var(--border-1)' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
              <ImageFrame src={kPh(12, 200)} style={{ width: 44, height: 44, borderRadius: '50%' }} />
              <div><div style={{ font: '800 14px/1.3 var(--font-body)' }}>{tx(a.author) || t('Dots editorial team', 'فريق تحرير دوتس')}</div><div className="d-acard__meta" style={{ marginTop: 4 }}><span>{tx(a.date)}</span><i /><span>{a.read} {t('min read', 'دقائق قراءة')}</span></div></div>
            </div>
            <ShareBar onShare={share} />
          </div>
        </KReveal>
      </div>
      <div className="d-container" style={{ paddingBlock: compact ? 20 : 36, maxWidth: 1240 }}>
        <ImageFrame ratio={compact ? '4 / 3' : '21 / 9'} radius="var(--radius-xl)" src={a.image} alt={a.title} />
      </div>
      <div className="d-container" style={{ display: 'grid', gridTemplateColumns: cols('minmax(0,1fr) 260px', '1fr'), gap: 56, maxWidth: 1060, paddingBottom: compact ? 40 : 80 }}>
        <article className="d-prose">
          <p>{t('Most rooms are lit by a single ceiling point — which is why they feel flat. Designers think in layers instead: a soft base of ambient light, focused task light where you work, and accent light that gives the room depth.', 'معظم الغرف تُضاء بنقطة واحدة في السقف — ولهذا تبدو مسطحة. المصممون يفكرون في طبقات: إضاءة عامة ناعمة، وإضاءة وظيفية حيث تعمل، وإضاءة جمالية تعطي الغرفة عمقاً.')}</p>
          <h2>{t('1. Ambient: the base layer', '1. الإضاءة العامة: الطبقة الأساسية')}</h2>
          <p>{t('Aim for 100–150 lux across a living room. Recessed downlights on a dimmer, or indirect cove lighting, give an even wash without glare.', 'استهدف 100–150 لوكس في غرفة المعيشة. السبوتات الغاطسة مع ديمر أو الإضاءة المخفية تعطي توزيعاً متساوياً بدون انبهار.')}</p>
          <ul>
            <li>{t('Choose 2700–3000K for living spaces', 'اختر 2700–3000K لمساحات المعيشة')}</li>
            <li>{t('Keep UGR below 19 near seating', 'حافظ على UGR أقل من 19 قرب الجلسات')}</li>
            <li>{t('Put every circuit on a dimmer or scene', 'ضع كل دائرة على ديمر أو مشهد')}</li>
          </ul>
          <figure>
            <ImageFrame ratio="16 / 9" radius="var(--radius-lg)" src={kPh(0)} alt={t('Living room with layered lighting', 'غرفة معيشة بإضاءة متعددة')} />
            <figcaption>{t('Ambient downlights at 30%, floor lamp for task, and picture lights for accent — Mivida apartment by Dots.', 'سبوتات عامة على 30%، أباجورة للإضاءة الوظيفية، وإضاءة لوحات — شقة ميفيدا من دوتس.')}</figcaption>
          </figure>
          <h2>{t('2. Task: light where you work', '2. الإضاءة الوظيفية: الضوء حيث تعمل')}</h2>
          <p>{t('Reading chairs, desks and kitchen counters need 300–500 lux. A floor lamp or adjustable spot beats turning every light up.', 'كراسي القراءة والمكاتب وأسطح المطبخ تحتاج 300–500 لوكس. الأباجورة أو السبوت المتحرك أفضل من رفع كل الإضاءة.')}</p>
          <blockquote>{t('“Light the task, not the room. Your eyes — and your electricity bill — will thank you.”', '«أضئ المهمة، لا الغرفة. عيناك — وفاتورة الكهرباء — ستشكرانك.»')}<cite>— {tx(a.author) || 'Dots'}</cite></blockquote>
          <h2>{t('3. Accent: depth and drama', '3. الإضاءة الجمالية: العمق والدراما')}</h2>
          <ol>
            <li>{t('Wash one feature wall with a narrow-beam spot', 'أضئ حائطاً مميزاً بسبوت ضيق الزاوية')}</li>
            <li>{t('Add LED strip under floating shelves', 'أضف شريط ليد تحت الأرفف المعلقة')}</li>
            <li>{t('Use a 3:1 contrast between accent and ambient', 'استخدم تبايناً 3:1 بين الجمالية والعامة')}</li>
          </ol>
          <div className="d-callout"><b>{t('Need a layout?', 'تحتاج توزيعاً؟')}</b> {t('Our designers will send a free lighting plan for any Dots order over EGP 60,000.', 'مصممونا يرسلون خطة إضاءة مجانية لأي طلب من دوتس يتجاوز 60,000 ج.م.')}</div>
          <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>{['2700K', 'Downlights', 'Dimming', 'Living room'].map((x) => <Chip key={x}>{x}</Chip>)}</div>
        </article>
        <aside style={{ display: compact ? 'none' : 'grid', gap: 20, alignContent: 'start', position: 'sticky', top: 24 }}>
          <div style={{ border: '1px solid var(--border-1)', borderRadius: 'var(--radius-lg)', overflow: 'hidden' }}>
            <ImageFrame ratio="4 / 3" src={kPh(33, 600)} />
            <div style={{ padding: 16, display: 'grid', gap: 10 }}>
              <b style={{ font: '600 17px/1.3 var(--font-display)' }}>{t('Shop the products in this article', 'تسوق منتجات هذا المقال')}</b>
              <Button size="sm" iconEnd="arrow-right" onClick={() => app.go('products')}>{t('View 12 products', 'عرض 12 منتج')}</Button>
            </div>
          </div>
          <Button variant="whatsapp" block onClick={() => app.quote()}>{t('Ask a lighting designer', 'اسأل مصمم إضاءة')}</Button>
        </aside>
      </div>
      <KSection tone="subtle">
        <KHead eyebrow={{ en: 'Keep reading', ar: 'تابع القراءة' }} title={{ en: 'Related articles', ar: 'مقالات ذات صلة' }} action={<Button variant="outline" iconEnd="arrow-right" onClick={() => app.go('blog')}>{t('All articles', 'كل المقالات')}</Button>} />
        <div style={{ display: 'grid', gridTemplateColumns: cols('repeat(3, minmax(0,1fr))', '1fr'), gap: compact ? 16 : 24 }}>
          {related.map((r, i) => <KReveal key={r.id} delay={i * 60}><ArticleCard article={r} onOpen={() => app.go('article', { id: r.id })} /></KReveal>)}
        </div>
      </KSection>
    </>
  );
}

