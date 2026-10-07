
import React from 'react';

const K_STEPS = [{ en: 'Design', ar: 'التصميم' }, { en: 'First fix', ar: 'التمديدات' }, { en: 'Installation', ar: 'التركيب' }, { en: 'Commissioning', ar: 'التشغيل' }, { en: 'Handover', ar: 'التسليم' }];

function KTypeFilter({ value, onChange, extra }) {
  const { tx, compact } = useLocale();
  return (
    <div style={{ display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap', justifyContent: 'space-between', marginBottom: compact ? 20 : 32 }}>
      <div style={{ display: 'flex', gap: 8, flexWrap: compact ? 'nowrap' : 'wrap', overflowX: compact ? 'auto' : undefined, marginInline: compact ? -16 : 0, paddingInline: compact ? 16 : 0 }}>
        {K_PROJECT_TYPES.map((c) => <Chip key={c.id} active={c.id === value} onClick={() => onChange(c.id)} style={{ flex: 'none' }}>{tx(c.label)}</Chip>)}
      </div>
      {extra}
    </div>
  );
}

export function KProjectsCurrent({ app }) {
  const { t, tx, cols, compact } = useK();
  const [type, setType] = React.useState('all');
  const list = K_PROJECTS.filter((p) => type === 'all' || p.t === type);
  const lead = K_PROJECTS[0];
  return (
    <>
      <KMediaHero image={kPh(20, 2000)} minH={560} eyebrow={{ en: 'Current projects', ar: 'مشروعات جارية' }}
        title={{ en: 'On site right now', ar: 'في المواقع الآن' }}
        lede={{ en: 'Live progress from the homes, offices and landscapes our teams are delivering this season.', ar: 'تقدم مباشر من المنازل والمكاتب والمساحات الخارجية التي تنفذها فرقنا هذا الموسم.' }}
        actions={<Button size="lg" variant="whatsapp" onClick={() => app.quote()}>{t('Start your project', 'ابدأ مشروعك')}</Button>}>
        <nav style={{ font: '600 13px var(--font-body)', color: 'rgba(255,255,255,.85)', display: 'flex', gap: 6, alignItems: 'center' }}>{t('Home', 'الرئيسية')}<Icon name="chevron-right" size={14} />{t('Projects', 'المشروعات')}</nav>
      </KMediaHero>
      <KSection tight>
        <KTypeFilter value={type} onChange={setType} extra={<span style={{ font: '700 13px var(--font-body)', color: 'var(--fg-2)', display: 'inline-flex', gap: 8, alignItems: 'center' }}><span className="d-dot" style={{ animation: 'd-pulse-node 2.4s infinite' }} />{list.length} {t('active sites', 'مواقع نشطة')}</span>} />
        {type === 'all' && (
          <KReveal style={{ marginBottom: compact ? 16 : 24 }}>
            <article className="d-card" style={{ display: 'grid', gridTemplateColumns: cols('1.4fr 1fr', '1fr') }}>
              <a href="#" className="d-card__link" aria-label="" onClick={(e) => { e.preventDefault(); app.go('project', { id: lead.id }); }} />
              <ImageFrame src={lead.image} style={{ minHeight: compact ? 240 : 440 }} />
              <div style={{ padding: compact ? 20 : 40, display: 'grid', gap: 16, alignContent: 'center' }}>
                <div style={{ display: 'flex', gap: 6 }}><Badge tone="accent" dot>{t('In progress', 'قيد التنفيذ')}</Badge><Badge tone="outline">{tx(lead.type)}</Badge></div>
                <h2 className="t-h2" style={{ margin: 0, fontSize: compact ? 'var(--fs-h2-m)' : undefined }}>{tx(lead.title)}</h2>
                <span className="d-prcard__loc"><Icon name="map-pin" size={14} />{tx(lead.location)}</span>
                <Progress value={lead.progress} label={lead.phase} steps={compact ? undefined : K_STEPS} current={lead.stepIdx} />
                <span className="d-scard__more">{t('Follow this project', 'تابع المشروع')}<Icon name="arrow-right" size={16} /></span>
              </div>
            </article>
          </KReveal>
        )}
        <div style={{ display: 'grid', gridTemplateColumns: cols('repeat(3, minmax(0,1fr))', '1fr'), gap: compact ? 16 : 24 }}>
          {(type === 'all' ? list.slice(1) : list).map((p, i) => <KReveal key={p.id} delay={(i % 3) * 60}><ProjectCard project={p} onOpen={() => app.go('project', { id: p.id })} /></KReveal>)}
        </div>
      </KSection>
      <KQuoteBand app={app} />
    </>
  );
}

export function KProjectsPrevious({ app }) {
  const { t, tx, cols, compact } = useK();
  const [type, setType] = React.useState('all');
  const [year, setYear] = React.useState('all');
  const list = K_DONE.filter((p) => (type === 'all' || p.t === type) && (year === 'all' || String(p.year) === year));
  const n = compact ? 1 : 3;
  const colsArr = Array.from({ length: n }, () => []);
  list.forEach((p, i) => colsArr[i % n].push(p));
  return (
    <>
      <KPageHead crumbs={[{ label: { en: 'Home', ar: 'الرئيسية' }, onClick: () => app.go('home') }, { label: { en: 'Projects', ar: 'المشروعات' } }, { label: { en: 'Previous', ar: 'السابقة' } }]} eyebrow={{ en: 'Portfolio', ar: 'أعمالنا' }}
        title={{ en: 'Previous projects', ar: 'مشروعات سابقة' }} lede={{ en: '2,400+ completed homes, offices, hotels and landscapes — a selection of case studies.', ar: 'أكثر من 2,400 منزل ومكتب وفندق ومساحة خارجية منجزة — مختارات من دراسات الحالة.' }} />
      <KSection tight>
        <KTypeFilter value={type} onChange={setType} extra={<Select size="sm" aria-label={t('Year', 'السنة')} value={year} onChange={(e) => setYear(e.target.value)} style={{ width: compact ? '100%' : 180 }} options={[{ value: 'all', label: t('All years', 'كل السنوات') }, '2026', '2025', '2024', '2023']} />} />
        <div style={{ display: 'grid', gridTemplateColumns: `repeat(${n}, minmax(0,1fr))`, gap: compact ? 16 : 20, alignItems: 'start' }}>
          {colsArr.map((c, ci) => (
            <div key={ci} style={{ display: 'grid', gap: compact ? 16 : 20 }}>
              {c.map((p, i) => <KReveal key={p.id + type + year} delay={ci * 60}><ProjectCard variant="overlay" project={p} style={{ minHeight: compact ? 340 : p.h }} onOpen={() => app.go('project', { id: p.id })} /></KReveal>)}
            </div>
          ))}
        </div>
        {!list.length && <p className="t-body" style={{ color: 'var(--fg-2)', textAlign: 'center', padding: 40 }}>{t('No projects for this filter yet.', 'لا توجد مشروعات لهذا الفلتر.')}</p>}
        <div style={{ marginTop: 40, paddingTop: 16, borderTop: '1px solid var(--border-1)' }}><Pagination page={1} total={12} count={96} perPage={8} /></div>
      </KSection>
      <KQuoteBand app={app} />
    </>
  );
}

export function KProjectDetail({ app, route }) {
  const { t, tx, cols, compact } = useK();
  const p = [...K_PROJECTS, ...K_DONE].find((x) => x.id === route.id) || K_PROJECTS[0];
  const live = p.status === 'progress';
  const imgs = p.gallery.map((g, i) => ({ src: kPh(g, 1600), caption: i === 0 ? p.title : undefined }));
  const products = [K_PRODUCTS[0], K_PRODUCTS[1], K_PRODUCTS[2], K_PRODUCTS[4]];
  const related = (live ? K_PROJECTS : K_DONE).filter((x) => x.id !== p.id).slice(0, 3);
  const facts = [
    ['map-pin', t('Location', 'الموقع'), tx(p.location)],
    ['building-2', t('Project type', 'نوع المشروع'), tx(p.type)],
    ...(live ? [['calendar-days', t('Start date', 'تاريخ البدء'), tx(p.start)], ['flag', t('Expected completion', 'التسليم المتوقع'), tx(p.eta)]] : [['calendar-check', t('Completed', 'تاريخ الإنجاز'), String(p.year)], ['user', t('Client', 'العميل'), tx(p.client) || t('Private client', 'عميل خاص')]]),
  ];
  return (
    <>
      <div className="d-container" style={{ paddingTop: compact ? 20 : 28, display: 'grid', gap: compact ? 16 : 22 }}>
        <Breadcrumbs items={[{ label: { en: 'Home', ar: 'الرئيسية' }, onClick: () => app.go('home') }, { label: live ? { en: 'Current projects', ar: 'مشروعات جارية' } : { en: 'Previous projects', ar: 'مشروعات سابقة' }, onClick: () => app.go(live ? 'projects-current' : 'projects-previous') }, { label: p.title }]} />
        <KReveal style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', gap: 20, flexWrap: 'wrap' }}>
          <div style={{ display: 'grid', gap: 14, maxWidth: 760 }}>
            <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>{live ? <Badge tone="accent" dot>{t('In progress', 'قيد التنفيذ')}</Badge> : <Badge tone="success" dot>{t('Completed', 'مكتمل')} {p.year}</Badge>}<Badge tone="outline">{tx(p.type)}</Badge></div>
            <h1 className="t-h1" style={{ margin: 0, fontSize: compact ? 'var(--fs-h1-m)' : undefined, lineHeight: compact ? 'var(--lh-h1-m)' : undefined }}>{tx(p.title)}</h1>
          </div>
          <ShareBar onShare={(l) => app.toast('success', t('Sharing to', 'مشاركة عبر') + ' ' + l)} />
        </KReveal>
      </div>
      <div className="d-container" style={{ paddingBlock: compact ? 16 : 28 }}><Gallery images={imgs} ratio={compact ? '4 / 3' : '21 / 9'} /></div>
      <div className="d-container" style={{ display: 'grid', gridTemplateColumns: cols('minmax(0,1fr) 380px', '1fr'), gap: compact ? 28 : 56, paddingBottom: compact ? 40 : 72, alignItems: 'start' }}>
        <div style={{ display: 'grid', gap: compact ? 28 : 40 }}>
          {live ? (
            <KReveal className="d-prose" style={{ maxWidth: 'none' }}>
              <h2 style={{ marginTop: 0 }}>{t('About the project', 'عن المشروع')}</h2>
              <p>{t('A 1,100 m² contemporary villa with double-height living, a lower-ground cinema and landscaped gardens. Dots was appointed for lighting design, the full electrical package and a KNX smart home, working alongside the architect from concept.', 'فيلا معاصرة بمساحة 1,100 م² بمعيشة مزدوجة الارتفاع وسينما في البدروم وحدائق. تم تكليف دوتس بتصميم الإضاءة والأعمال الكهربائية الكاملة ومنزل ذكي KNX بالتعاون مع المعماري منذ المفهوم.')}</p>
              <h3>{t('Scope of work', 'نطاق العمل')}</h3>
              <ul>
                <li>{t('Lighting design with DIALux calculations for 42 spaces', 'تصميم إضاءة بحسابات DIALux لـ 42 مساحة')}</li>
                <li>{t('Main & sub-distribution boards, 186 circuits', 'لوحات توزيع رئيسية وفرعية، 186 دائرة')}</li>
                <li>{t('KNX lighting, curtains, HVAC and security integration', 'تكامل KNX للإضاءة والستائر والتكييف والأمان')}</li>
                <li>{t('Landscape, facade and pool lighting', 'إضاءة الحدائق والواجهات وحمام السباحة')}</li>
              </ul>
            </KReveal>
          ) : (
            <>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: 16 }}>
                {[['circle-alert', t('Challenge', 'التحدي'), t('Replace 1,400 ageing fixtures across 220 rooms without closing the resort in peak season.', 'استبدال 1,400 وحدة قديمة في 220 غرفة دون إغلاق المنتجع في موسم الذروة.')], ['lightbulb', t('Solution', 'الحل'), t('Night-shift phasing, pre-wired retrofit kits and a DALI scene system tuned per zone.', 'تنفيذ على مراحل ليلية ووحدات تحويل مجهزة مسبقاً ونظام مشاهد DALI لكل منطقة.')], ['trending-down', t('Result', 'النتيجة'), t('38% lower lighting energy, zero room closures, guest scores up 0.4 points.', 'انخفاض استهلاك الإضاءة 38% ودون إغلاق أي غرفة وارتفاع تقييم النزلاء 0.4 نقطة.')]].map(([ic, h, d], i) => (
                  <KReveal key={h} delay={i * 80} style={{ border: '1px solid var(--border-1)', borderRadius: 'var(--radius-lg)', padding: 22, display: 'grid', gap: 10, alignContent: 'start', background: i === 2 ? 'var(--yellow-50)' : 'var(--white)' }}>
                    <span style={{ width: 40, height: 40, borderRadius: '50%', background: i === 2 ? 'var(--accent)' : 'var(--ink-50)', display: 'grid', placeItems: 'center' }}><Icon name={ic} size={20} /></span>
                    <h3 className="t-h4" style={{ margin: 0 }}>{h}</h3>
                    <p className="t-body-sm" style={{ margin: 0, color: 'var(--fg-2)' }}>{d}</p>
                  </KReveal>
                ))}
              </div>
              <KReveal style={{ display: 'grid', gap: 14 }}>
                <h2 className="t-h3" style={{ margin: 0 }}>{t('Before & after', 'قبل وبعد')}</h2>
                <BeforeAfter before={{ src: kPh(p.before ?? p.gallery[1], 1600) }} after={{ src: kPh(p.after ?? p.gallery[0], 1600) }} ratio={compact ? '4 / 3' : '16 / 9'} />
                <span className="t-caption" style={{ color: 'var(--fg-3)' }}>{t('Drag to compare.', 'اسحب للمقارنة.')}</span>
              </KReveal>
            </>
          )}
          <div style={{ display: 'grid', gap: 16 }}>
            <h2 className="t-h3" style={{ margin: 0 }}>{t('Products & services used', 'المنتجات والخدمات المستخدمة')}</h2>
            <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>{[t('Lighting design', 'تصميم الإضاءة'), t('Smart home installation', 'تركيب المنزل الذكي'), t('Electrical contracting', 'مقاولات كهربائية')].map((s) => <Chip key={s} onClick={() => app.go('service')}><Icon name="check" size={14} />{s}</Chip>)}</div>
            <KProductGrid app={app} products={products} cols={compact ? 2 : 4} />
          </div>
        </div>
        <aside style={{ display: 'grid', gap: 16, position: compact ? 'static' : 'sticky', top: 24 }}>
          <div style={{ border: '1px solid var(--border-1)', borderRadius: 'var(--radius-lg)', padding: 22, display: 'grid', gap: 18, background: 'var(--white)' }}>
            {live && <Progress value={p.progress} label={p.phase} steps={K_STEPS} current={p.stepIdx} />}
            {!live && <Progress value={100} label={t('Delivered', 'تم التسليم')} />}
            <div style={{ display: 'grid', gap: 14 }}>
              {facts.map(([ic, k, v]) => (
                <div key={k} style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
                  <span style={{ width: 36, height: 36, borderRadius: '50%', background: 'var(--ink-50)', display: 'grid', placeItems: 'center', flex: 'none' }}><Icon name={ic} size={18} /></span>
                  <div><div style={{ font: '600 12px var(--font-body)', color: 'var(--fg-3)' }}>{k}</div><div style={{ font: '800 14px/1.3 var(--font-body)' }}>{v}</div></div>
                </div>
              ))}
            </div>
            <div style={{ font: '600 13px/1.5 var(--font-body)', color: 'var(--fg-2)', paddingTop: 14, borderTop: '1px solid var(--border-1)' }}>{tx(p.scope)}</div>
          </div>
          <Button variant="whatsapp" size="lg" block onClick={() => app.quote()}>{t('Quote a similar project', 'عرض سعر لمشروع مشابه')}</Button>
        </aside>
      </div>
      <KSection tone="subtle">
        <KHead eyebrow={{ en: 'More work', ar: 'المزيد' }} title={live ? { en: 'Other sites in progress', ar: 'مواقع أخرى قيد التنفيذ' } : { en: 'Related projects', ar: 'مشروعات ذات صلة' }} action={<Button variant="outline" iconEnd="arrow-right" onClick={() => app.go(live ? 'projects-current' : 'projects-previous')}>{t('All projects', 'كل المشروعات')}</Button>} />
        <div style={{ display: 'grid', gridTemplateColumns: cols('repeat(3, minmax(0,1fr))', '1fr'), gap: compact ? 16 : 24 }}>
          {related.map((r, i) => <KReveal key={r.id} delay={i * 60}><ProjectCard project={r} onOpen={() => app.go('project', { id: r.id })} /></KReveal>)}
        </div>
      </KSection>
    </>
  );
}

