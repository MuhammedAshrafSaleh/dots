
import React from 'react';

const K_FAQ = [
  { id: 'f1', title: { en: 'How much does a lighting design cost?', ar: 'كم تكلفة تصميم الإضاءة؟' }, content: { en: 'Residential designs start from EGP 6,000 and are fully credited against a supply order above EGP 60,000.', ar: 'تبدأ التصميمات السكنية من 6,000 ج.م وتُخصم بالكامل عند طلب توريد يتجاوز 60,000 ج.م.' } },
  { id: 'f2', title: { en: 'Which areas do you install in?', ar: 'ما المناطق التي تركبون بها؟' }, content: { en: 'Greater Cairo, Alexandria, the North Coast, Sokhna and Hurghada. Other governorates on request.', ar: 'القاهرة الكبرى والإسكندرية والساحل الشمالي والسخنة والغردقة. باقي المحافظات حسب الطلب.' } },
  { id: 'f3', title: { en: 'Can you work from my contractor’s drawings?', ar: 'هل يمكنكم العمل على رسومات المقاول؟' }, content: { en: 'Yes — send DWG or PDF plans and we return a lighting layout and priced schedule.', ar: 'نعم — أرسل رسومات DWG أو PDF ونرسل لك توزيع الإضاءة وجدول أسعار.' } },
  { id: 'f4', title: { en: 'What warranty do installations carry?', ar: 'ما الضمان على التركيب؟' }, content: { en: '12 months on workmanship, plus the manufacturer’s warranty on every product.', ar: '12 شهر على التركيب، بالإضافة لضمان المصنع على كل منتج.' } },
];

function KProcess() {
  const { t, cols, compact } = useK();
  const steps = [[t('Consult', 'استشارة'), t('Site visit or video call to understand the space and budget.', 'زيارة أو مكالمة فيديو لفهم المساحة والميزانية.')], [t('Design', 'تصميم'), t('Layouts, renders and a fixture schedule you approve.', 'توزيع وتصميمات وجدول وحدات تعتمده.')], [t('Supply', 'توريد'), t('Genuine products, one delivery, one invoice.', 'منتجات أصلية، توصيل واحد، فاتورة واحدة.')], [t('Install', 'تركيب'), t('Certified technicians, testing and handover.', 'فنيين معتمدين واختبار وتسليم.')]];
  return (
    <div style={{ display: 'grid', gridTemplateColumns: cols('repeat(4, 1fr)', '1fr'), gap: compact ? 16 : 0, position: 'relative' }}>
      {steps.map(([h, d], i) => (
        <KReveal key={i} delay={i * 80} style={{ display: 'grid', gap: 12, paddingInlineEnd: 24, position: 'relative' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 0 }}>
            <span className="d-co__num" style={{ width: 40, height: 40, fontSize: 15 }}>{i + 1}</span>
            {!compact && i < 3 && <span style={{ flex: 1, height: 1.5, background: 'var(--ink-200)', marginInlineStart: 12 }} />}
          </div>
          <h3 className="t-h4" style={{ margin: 0 }}>{h}</h3>
          <p className="t-body-sm" style={{ margin: 0, color: 'var(--fg-2)' }}>{d}</p>
        </KReveal>
      ))}
    </div>
  );
}

export function KServices({ app }) {
  const { t, cols } = useK();
  return (
    <>
      <KPageHead crumbs={[{ label: { en: 'Home', ar: 'الرئيسية' }, onClick: () => app.go('home') }, { label: { en: 'Services', ar: 'الخدمات' } }]} eyebrow={{ en: 'Services', ar: 'الخدمات' }}
        title={{ en: 'Design, install, maintain.', ar: 'تصميم، تركيب، صيانة.' }} lede={{ en: 'One team from the first lux calculation to the last switch plate — for homes, offices, hotels and retail.', ar: 'فريق واحد من أول حساب إضاءة لآخر مفتاح — للمنازل والمكاتب والفنادق والمحلات.' }}>
        <Button size="lg" variant="whatsapp" onClick={() => app.quote()}>{t('Talk to an engineer', 'كلم مهندس')}</Button>
      </KPageHead>
      <KSection>
        <div style={{ display: 'grid', gridTemplateColumns: cols('repeat(2, minmax(0,1fr))', '1fr'), gap: 24 }}>
          {K_SERVICES.map((s, i) => <KReveal key={s.id} delay={(i % 2) * 80}><ServiceCard service={s} onOpen={() => app.go('service', { id: s.id })} /></KReveal>)}
        </div>
      </KSection>
      <KSection tone="subtle">
        <KHead eyebrow={{ en: 'How it works', ar: 'كيف نعمل' }} title={{ en: 'Four steps, one accountable team', ar: 'أربع خطوات وفريق واحد مسؤول' }} />
        <KProcess />
      </KSection>
      <KSection>
        <div style={{ display: 'grid', gridTemplateColumns: cols('1fr 1.4fr', '1fr'), gap: 40 }}>
          <KHead eyebrow={{ en: 'FAQ', ar: 'أسئلة شائعة' }} title={{ en: 'Questions we hear every week', ar: 'أسئلة نسمعها كل أسبوع' }} />
          <Accordion items={K_FAQ} defaultOpen={['f1']} />
        </div>
      </KSection>
      <KQuoteBand app={app} />
    </>
  );
}

export function KServiceDetail({ app, route }) {
  const { t, tx, cols, compact } = useK();
  const s = K_SERVICES.find((x) => x.id === route.id) || K_SERVICES[0];
  return (
    <>
      <div style={{ borderBottom: '1px solid var(--border-1)' }}>
        <div className="d-container" style={{ paddingBlock: compact ? 20 : 28 }}>
          <Breadcrumbs items={[{ label: { en: 'Home', ar: 'الرئيسية' }, onClick: () => app.go('home') }, { label: { en: 'Services', ar: 'الخدمات' }, onClick: () => app.go('services') }, { label: s.title }]} />
          <div style={{ display: 'grid', gridTemplateColumns: cols('1fr 1.1fr', '1fr'), gap: compact ? 24 : 56, alignItems: 'center', paddingTop: compact ? 20 : 32, paddingBottom: compact ? 12 : 40 }}>
            <KReveal style={{ display: 'grid', gap: 18 }}>
              <span className="d-scard__num">{s.num} · {t('Service', 'خدمة')}</span>
              <h1 className="t-h1" style={{ margin: 0, fontSize: compact ? 'var(--fs-h1-m)' : undefined, lineHeight: compact ? 'var(--lh-h1-m)' : undefined }}>{tx(s.title)}</h1>
              <p className="t-body-lg" style={{ margin: 0, color: 'var(--fg-2)' }}>{tx(s.desc)}</p>
              <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap' }}>
                <Button size="lg" iconEnd="arrow-right" onClick={() => document.getElementById('k-book') && app.toast('info', t('Scroll to booking form', 'انتقل لنموذج الحجز'))}>{t('Book a consultation', 'احجز استشارة')}</Button>
                <Button size="lg" variant="whatsapp" onClick={() => app.quote()}>{t('WhatsApp us', 'راسلنا واتساب')}</Button>
              </div>
            </KReveal>
            <ImageFrame ratio="4 / 3" radius="var(--radius-xl)" label={s.imageLabel} />
          </div>
        </div>
      </div>
      <KSection>
        <div style={{ display: 'grid', gridTemplateColumns: cols('1fr 1fr', '1fr'), gap: 40 }}>
          <div>
            <KHead eyebrow={{ en: 'What’s included', ar: 'ماذا تشمل الخدمة' }} title={{ en: 'A complete, buildable package', ar: 'باقة كاملة قابلة للتنفيذ' }} />
            <ul className="d-node-list t-body" style={{ color: 'var(--fg-1)', fontWeight: 600 }}>
              {[t('Site survey & measurements', 'معاينة الموقع والمقاسات'), t('Lux levels per room to EN 12464', 'مستويات الإضاءة لكل غرفة وفق EN 12464'), t('DIALux 3D renders', 'تصميمات DIALux ثلاثية الأبعاد'), t('Fixture schedule with 2 budget options', 'جدول وحدات بخيارين للميزانية'), t('Circuit & control plan (DALI / smart)', 'خطة الدوائر والتحكم (DALI / ذكي)'), t('On-site aiming at handover', 'ضبط الاتجاهات عند التسليم')].map((x) => <li key={x}>{x}</li>)}
            </ul>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
            <ImageFrame ratio="3 / 4" radius="var(--radius-lg)" label={t('PROJECT · villa, Sheikh Zayed', 'مشروع · فيلا الشيخ زايد')} />
            <div style={{ display: 'grid', gap: 12 }}>
              <ImageFrame ratio="4 / 3" radius="var(--radius-lg)" label={t('PROJECT · office lobby', 'مشروع · مدخل مكتب')} />
              <ImageFrame ratio="4 / 3" radius="var(--radius-lg)" label={t('RENDER · DIALux false-colour', 'تصميم · DIALux')} />
            </div>
          </div>
        </div>
      </KSection>
      <KSection tone="subtle"><KHead eyebrow={{ en: 'Process', ar: 'المراحل' }} title={{ en: 'From brief to handover in 3–6 weeks', ar: 'من الفكرة للتسليم خلال 3–6 أسابيع' }} /><KProcess /></KSection>
      <KSection>
        <div id="k-book" style={{ display: 'grid', gridTemplateColumns: cols('1.3fr 1fr', '1fr'), gap: 32, alignItems: 'start' }}>
          <form className="d-co__sec" onSubmit={(e) => { e.preventDefault(); app.toast('success', t('Request received', 'تم استلام طلبك'), t('An engineer will call you within one working day.', 'سيتصل بك مهندس خلال يوم عمل.')); }}>
            <div className="d-co__head"><span className="d-co__num"><Icon name="calendar-days" size={16} /></span><h3>{t('Book a consultation', 'احجز استشارة')}</h3></div>
            <div className="d-co__grid">
              <Input label={t('Full name', 'الاسم بالكامل')} />
              <Input label={t('Mobile', 'الموبايل')} dir="ltr" placeholder="01X XXXX XXXX" />
              <Select label={t('Project type', 'نوع المشروع')} options={[t('Apartment', 'شقة'), t('Villa', 'فيلا'), t('Office', 'مكتب'), t('Retail / F&B', 'محل / مطعم'), t('Hotel', 'فندق')]} />
              <Select label={t('Area', 'المساحة')} options={['< 150 m²', '150–400 m²', '400–1,000 m²', '> 1,000 m²']} />
              <Input className="span-2" multiline label={t('Tell us about the space', 'أخبرنا عن المساحة')} optional placeholder={t('Stage of construction, ceiling heights, style…', 'مرحلة التنفيذ، ارتفاع الأسقف، الطراز…')} />
            </div>
            <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap' }}><Button type="submit" size="lg">{t('Send request', 'إرسال الطلب')}</Button><Button size="lg" variant="quote-outline" onClick={() => app.quote()}>{t('Or WhatsApp', 'أو واتساب')}</Button></div>
          </form>
          <Accordion items={K_FAQ} defaultOpen={['f2']} />
        </div>
      </KSection>
    </>
  );
}

