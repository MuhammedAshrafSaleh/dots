
import React from 'react';

/** Full-bleed photo/video hero shared by About and Projects. */
export function KMediaHero({ image, video, eyebrow, title, lede, actions, minH = 640, children }) {
  const { tx, compact, dir, t } = useLocale();
  const [paused, setPaused] = React.useState(false);
  const ref = React.useRef(null);
  return (
    <section ref={ref} style={{ position: 'relative', minHeight: compact ? 560 : minH, display: 'flex', alignItems: 'flex-end', overflow: 'hidden', background: 'var(--ink-900)' }}>
      <ImageFrame src={image} video={paused ? undefined : video} style={{ position: 'absolute', inset: 0 }} />
      <div style={{ position: 'absolute', inset: 0, background: `linear-gradient(${dir === 'rtl' ? 270 : 90}deg, rgba(24,24,22,.8) 0%, rgba(24,24,22,.5) 45%, rgba(24,24,22,.08) 80%)` }} />
      <div style={{ position: 'absolute', inset: 0, background: 'linear-gradient(0deg, rgba(24,24,22,.45), transparent 45%)' }} />
      <div className="d-container" style={{ position: 'relative', width: '100%', paddingBlock: compact ? 40 : 80 }}>
        <KReveal style={{ maxWidth: 760, display: 'grid', gap: compact ? 16 : 22, color: 'var(--white)' }}>
          {children}
          {eyebrow && <span style={{ display: 'inline-flex', alignItems: 'center', gap: 10, font: '800 12px/1 var(--font-body)', letterSpacing: 'var(--tracking-overline)', textTransform: 'var(--text-transform-overline)' }}><span className="d-dot" />{tx(eyebrow)}</span>}
          <h1 className="t-display" style={{ margin: 0, fontSize: compact ? 'var(--fs-display-m)' : undefined, lineHeight: compact ? 'var(--lh-display-m)' : undefined, textWrap: 'balance' }}>{tx(title)}</h1>
          {lede && <p className="t-body-lg" style={{ margin: 0, color: 'rgba(255,255,255,.9)', maxWidth: 580 }}>{tx(lede)}</p>}
          {actions && <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>{actions}</div>}
        </KReveal>
        {video && <IconButton icon={paused ? 'play' : 'pause'} label={paused ? t('Play video', 'تشغيل الفيديو') : t('Pause video', 'إيقاف الفيديو')} onClick={() => setPaused(!paused)} style={{ position: 'absolute', insetInlineEnd: compact ? 16 : 'var(--page-margin)', bottom: compact ? 40 : 80, background: 'rgba(255,255,255,.14)', color: '#fff' }} />}
      </div>
    </section>
  );
}

export const K_HERO_GHOST = { background: 'rgba(255,255,255,.12)', color: '#fff', borderColor: 'rgba(255,255,255,.6)' };

export function KAbout({ app }) {
  const { t, tx, cols, compact } = useK();
  const pillars = [
    { id: 'l', img: 33, icon: 'lightbulb', title: t('Lighting', 'الإضاءة'), text: t('Decorative, architectural and outdoor lighting from 60+ brands — specified, supplied and aimed on site.', 'إضاءة ديكورية ومعمارية وخارجية من أكثر من 60 علامة — تصميم وتوريد وضبط في الموقع.'), go: 'category' },
    { id: 's', img: 17, icon: 'wifi', title: t('Smart Home', 'المنزل الذكي'), text: t('Scenes, curtains, climate and security on one app, designed around how your family lives.', 'مشاهد وستائر وتكييف وأمان في تطبيق واحد، مصممة حول أسلوب حياة أسرتك.'), go: 'service' },
    { id: 'e', img: 29, icon: 'plug-zap', title: t('Electrical', 'الكهرباء'), text: t('Wiring devices, distribution and certified contracting for homes, offices and hospitality.', 'مفاتيح وبرايز ولوحات توزيع ومقاولات معتمدة للمنازل والمكاتب والفنادق.'), go: 'services' },
  ];
  const why = [
    ['shield-check', t('Genuine, warrantied', 'أصلي ومضمون'), t('Authorised partner — every product with official Egyptian warranty.', 'شريك معتمد — كل منتج بضمان رسمي في مصر.')],
    ['pencil-ruler', t('Designed, not guessed', 'تصميم لا تخمين'), t('In-house lighting designers with DIALux calculations for every project.', 'مصممو إضاءة داخليون بحسابات DIALux لكل مشروع.')],
    ['hard-hat', t('Certified installers', 'فنيون معتمدون'), t('Our own technicians — no subcontracting, 12-month workmanship warranty.', 'فنيونا الخاصون — بدون مقاولين من الباطن، وضمان 12 شهر على التركيب.')],
    ['package', t('Stock that ships', 'مخزون جاهز'), t('10,000+ SKUs, 4,000 m² warehouse, next-day in Greater Cairo.', 'أكثر من 10,000 منتج ومخزن 4,000 م² وتوصيل اليوم التالي بالقاهرة الكبرى.')],
    ['file-text', t('Technical depth', 'عمق تقني'), t('Datasheets, IES files and BOQ pricing within 48 hours for consultants.', 'داتا شيت وملفات IES وتسعير مقايسات خلال 48 ساعة للاستشاريين.')],
    ['headset', t('Support after handover', 'دعم بعد التسليم'), t('Maintenance contracts and a WhatsApp line answered by engineers.', 'عقود صيانة وخط واتساب يرد عليه مهندسون.')],
  ];
  return (
    <>
      <KMediaHero image={kPh(4, 2000)} video="" eyebrow={{ en: 'About Dots', ar: 'عن دوتس' }}
        title={{ en: 'We make spaces work better in light.', ar: 'نجعل المساحات أجمل وأذكى بالضوء.' }}
        lede={{ en: 'Since 2008, Dots has supplied, designed and installed lighting, smart home and electrical systems for homes and landmark projects across Egypt.', ar: 'منذ 2008 توفر دوتس وتصمم وتركب أنظمة الإضاءة والمنزل الذكي والكهرباء للمنازل والمشروعات الكبرى في مصر.' }}
        actions={<><Button size="lg" iconEnd="arrow-right" onClick={() => app.go('projects-previous')}>{t('See our work', 'شاهد أعمالنا')}</Button><Button size="lg" icon="play" style={K_HERO_GHOST}>{t('Watch our story', 'شاهد قصتنا')}</Button></>} />
      <KSection>
        <div style={{ display: 'grid', gridTemplateColumns: cols('1fr 1fr', '1fr'), gap: compact ? 28 : 64, alignItems: 'center' }}>
          <KReveal style={{ display: 'grid', gap: 18 }}>
            <Eyebrow>{t('Our story', 'قصتنا')}</Eyebrow>
            <h2 className="t-h2" style={{ margin: 0 }}>{t('From a Heliopolis lighting counter to a full-service engineering house.', 'من محل إضاءة في مصر الجديدة إلى بيت هندسي متكامل الخدمات.')}</h2>
            <p className="t-body-lg" style={{ margin: 0, color: 'var(--fg-2)' }}>{t('We started by selling quality fixtures to architects who were tired of guesswork. Customers kept asking us to install them, then to design the whole scheme, then to automate it. Today one Dots team takes a project from first sketch to final switch — and stays for the years after.', 'بدأنا ببيع وحدات إضاءة عالية الجودة لمعماريين سئموا التخمين. طلب العملاء منا التركيب، ثم تصميم المنظومة كاملة، ثم أتمتتها. اليوم يأخذ فريق دوتس واحد المشروع من أول فكرة لآخر مفتاح — ويبقى معك في السنوات التالية.')}</p>
          </KReveal>
          <div style={{ display: 'grid', gridTemplateColumns: '1.2fr 1fr', gap: 12 }}>
            <KReveal><ImageFrame ratio="3 / 4" radius="var(--radius-lg)" src={kPh(12, 900)} alt={t('Dots showroom', 'معرض دوتس')} /></KReveal>
            <div style={{ display: 'grid', gap: 12, alignContent: 'end' }}>
              <KReveal delay={80}><ImageFrame ratio="1 / 1" radius="var(--radius-lg)" src={kPh(19, 700)} /></KReveal>
              <KReveal delay={160}><ImageFrame ratio="4 / 3" radius="var(--radius-lg)" src={kPh(18, 700)} /></KReveal>
            </div>
          </div>
        </div>
      </KSection>
      <KSection tight style={{ paddingTop: 0 }}>
        <StatStrip stats={[{ value: 18, suffix: '+', label: { en: 'Years of experience', ar: 'سنة خبرة' } }, { value: 2400, suffix: '+', label: { en: 'Projects delivered', ar: 'مشروع منجز' } }, { value: 60, suffix: '+', label: { en: 'Brands carried', ar: 'علامة تجارية' } }, { value: 10000, suffix: '+', label: { en: 'Products in catalogue', ar: 'منتج في الكتالوج' } }]} />
      </KSection>
      <KSection tone="subtle">
        <div style={{ display: 'grid', gridTemplateColumns: cols('1fr 1fr', '1fr'), gap: 20 }}>
          {[['target', t('Mission', 'مهمتنا'), t('To make well-designed light and reliable electrical systems simple to buy, install and live with.', 'أن نجعل الإضاءة المصممة جيداً والأنظمة الكهربائية الموثوقة سهلة الشراء والتركيب والاستخدام.'), 26], ['eye', t('Vision', 'رؤيتنا'), t('To be the first call for every home, architect and developer in Egypt who cares how a space feels after dark.', 'أن نكون الاختيار الأول لكل منزل ومعماري ومطور في مصر يهتم بشعور المساحة بعد الغروب.'), 38]].map(([ic, h, p, img], i) => (
            <KReveal key={h} delay={i * 80} style={{ background: 'var(--white)', border: '1px solid var(--border-1)', borderRadius: 'var(--radius-xl)', overflow: 'hidden', display: 'grid', gridTemplateColumns: '1fr' }}>
              <ImageFrame src={kPh(img, 1000)} ratio="16 / 9" />
              <div style={{ padding: compact ? 22 : 32, display: 'grid', gap: 14, alignContent: 'center' }}>
                <span style={{ width: 48, height: 48, borderRadius: '50%', background: 'var(--yellow-100)', display: 'grid', placeItems: 'center' }}><Icon name={ic} size={24} /></span>
                <h3 className="t-h3" style={{ margin: 0 }}>{h}</h3>
                <p className="t-body" style={{ margin: 0, color: 'var(--fg-2)' }}>{p}</p>
              </div>
            </KReveal>
          ))}
        </div>
      </KSection>
      <KSection>
        <KHead eyebrow={{ en: 'What we do', ar: 'ماذا نقدم' }} title={{ en: 'Three disciplines, one accountable team', ar: 'ثلاثة تخصصات وفريق واحد مسؤول' }} />
        <div style={{ display: 'grid', gridTemplateColumns: cols('repeat(3, minmax(0,1fr))', '1fr'), gap: compact ? 16 : 24 }}>
          {pillars.map((p, i) => (
            <KReveal key={p.id} delay={i * 80}>
              <article className="d-card d-prcard d-prcard--overlay" style={{ minHeight: compact ? 380 : 520 }}>
                <a href="#" className="d-card__link" aria-label={p.title} onClick={(e) => { e.preventDefault(); app.go(p.go); }} />
                <ImageFrame className="d-prcard__media" src={kPh(p.img, 1000)} />
                <div className="d-card__body" style={{ padding: 28, gap: 12 }}>
                  <span style={{ width: 48, height: 48, borderRadius: '50%', background: 'var(--accent)', color: 'var(--ink-900)', display: 'grid', placeItems: 'center' }}><Icon name={p.icon} size={24} /></span>
                  <h3 className="t-h3" style={{ margin: 0 }}>{p.title}</h3>
                  <p style={{ margin: 0, font: '500 15px/1.55 var(--font-body)', color: 'rgba(255,255,255,.9)' }}>{p.text}</p>
                  <span className="d-scard__more" style={{ color: '#fff' }}>{t('Explore', 'اكتشف')}<Icon name="arrow-right" size={16} /></span>
                </div>
              </article>
            </KReveal>
          ))}
        </div>
      </KSection>
      <KSection tone="subtle">
        <KHead eyebrow={{ en: 'Why Dots', ar: 'لماذا دوتس' }} title={{ en: 'Why clients choose us — and come back', ar: 'لماذا يختارنا العملاء — ويعودون' }} />
        <div style={{ display: 'grid', gridTemplateColumns: cols('repeat(3, minmax(0,1fr))', '1fr'), gap: 0, borderTop: '1px solid var(--border-2)' }}>
          {why.map(([ic, h, p], i) => (
            <KReveal key={h} delay={(i % 3) * 60} style={{ padding: compact ? '22px 0' : '32px 28px 32px 0', paddingInlineStart: !compact && i % 3 ? 28 : 0, borderBottom: '1px solid var(--border-2)', borderInlineStart: !compact && i % 3 ? '1px solid var(--border-2)' : 0, display: 'grid', gap: 12, alignContent: 'start' }}>
              <span style={{ position: 'relative', width: 48, height: 48, borderRadius: 12, border: '1px solid var(--border-2)', background: 'var(--white)', display: 'grid', placeItems: 'center' }}><Icon name={ic} size={24} /><span style={{ position: 'absolute', top: -4, insetInlineEnd: -4, width: 10, height: 10, borderRadius: '50%', background: 'var(--accent)', border: '2px solid var(--ink-50)' }} /></span>
              <h3 className="t-h5" style={{ margin: 0 }}>{h}</h3>
              <p className="t-body-sm" style={{ margin: 0, color: 'var(--fg-2)' }}>{p}</p>
            </KReveal>
          ))}
        </div>
      </KSection>
      <KSection>
        <KHead eyebrow={{ en: 'Partners', ar: 'شركاؤنا' }} title={{ en: 'Brands and manufacturers we represent', ar: 'العلامات والمصانع التي نمثلها' }} action={<Button variant="outline" iconEnd="arrow-right" onClick={() => app.go('brands')}>{t('All 60+ brands', 'كل العلامات')}</Button>} />
        <KReveal><LogoStrip brands={[...K_BRANDS, { id: 'sie', name: 'Siemens' }, { id: 'ledv', name: 'Ledvance' }, { id: 'hager', name: 'Hager' }, { id: 'knx', name: 'Gira' }]} onOpen={(b) => app.go('brand', { id: b.id })} /></KReveal>
      </KSection>
      <section style={{ position: 'relative', overflow: 'hidden' }}>
        <ImageFrame src={kPh(37, 2000)} style={{ position: 'absolute', inset: 0 }} />
        <div style={{ position: 'absolute', inset: 0, background: 'rgba(24,24,22,.72)' }} />
        <div className="d-container" style={{ position: 'relative', paddingBlock: compact ? 56 : 104, display: 'grid', gap: 20, justifyItems: 'center', textAlign: 'center', color: '#fff' }}>
          <KReveal style={{ display: 'grid', gap: 18, justifyItems: 'center', maxWidth: 720 }}>
            <Tagline style={{ color: '#fff' }} />
            <h2 className="t-h1" style={{ margin: 0, fontSize: compact ? 'var(--fs-h1-m)' : undefined, lineHeight: compact ? 'var(--lh-h1-m)' : undefined }}>{t('Planning a project? Let’s price it together.', 'تخطط لمشروع؟ دعنا نسعّره معاً.')}</h2>
            <p className="t-body-lg" style={{ margin: 0, color: 'rgba(255,255,255,.9)' }}>{t('Send drawings or a BOQ — an engineer replies within 2 working hours.', 'أرسل الرسومات أو المقايسة — ويرد مهندس خلال ساعتين عمل.')}</p>
            <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap', justifyContent: 'center' }}><Button size="lg" variant="whatsapp" onClick={() => app.quote()}>{t('Request a quote', 'اطلب عرض سعر')}</Button><Button size="lg" style={K_HERO_GHOST} onClick={() => app.go('services')}>{t('Our services', 'خدماتنا')}</Button></div>
          </KReveal>
        </div>
      </section>
    </>
  );
}

