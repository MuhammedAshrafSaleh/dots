
import React from 'react';

/* Content page template — Delivery, Returns, Warranty, Careers. Blocks: [type, {en, ar} | [{en, ar}]] */
const KC_PAGES = {
  delivery: { title: kM('Delivery', 'التوصيل'), updated: kM('14 Sep 2026', '14 سبتمبر 2026'), lede: kM('Where we deliver, how long it takes and what it costs.', 'أين نوصّل، وكم يستغرق، وكم يكلف.'), body: [
    ['h2', kM('Delivery times', 'مواعيد التوصيل')],
    ['p', kM('Orders confirmed before 15:00 on a working day leave our Nasr City warehouse the same day. The courier calls you one hour before arriving.', 'الطلبات المؤكدة قبل الساعة 3 عصراً في يوم عمل تخرج من مخزن مدينة نصر في نفس اليوم. المندوب يتصل بك قبل الوصول بساعة.')],
    ['ul', [kM('Cairo & Giza — next working day', 'القاهرة والجيزة — يوم العمل التالي'), kM('Alexandria, Delta & Canal cities — 2–3 working days', 'الإسكندرية والدلتا ومدن القناة — 2–3 أيام عمل'), kM('Upper Egypt, Red Sea & Sinai — 3–5 working days', 'الصعيد والبحر الأحمر وسيناء — 3–5 أيام عمل')]],
    ['h2', kM('Delivery fees', 'رسوم التوصيل')],
    ['p', kM('Standard delivery is EGP 150 and free on orders over EGP 5,000. Express same-day delivery in Cairo & Giza is EGP 300 for orders confirmed before 12:00.', 'التوصيل العادي بـ 150 ج.م ومجاني للطلبات فوق 5,000 ج.م. التوصيل السريع في نفس اليوم بالقاهرة والجيزة بـ 300 ج.م للطلبات المؤكدة قبل 12 ظهراً.')],
    ['callout', kM('Project orders and site deliveries are scheduled with our project team — send your BOQ on WhatsApp and we agree a date and unloading point.', 'طلبات المشروعات والتوصيل للموقع تُجدول مع فريق المشروعات — أرسل المقايسة على واتساب ونتفق على الموعد ومكان التفريغ.')],
    ['h3', kM('When your order arrives', 'عند وصول الطلب')],
    ['ol', [kM('Check that the number of boxes matches the delivery note.', 'تأكد أن عدد الكراتين مطابق لإذن التسليم.'), kM('Open fragile items — glass and pendants — in front of the courier.', 'افتح المنتجات القابلة للكسر — الزجاج والمعلقات — أمام المندوب.'), kM('Sign only when everything is complete and undamaged.', 'وقّع فقط عندما يكون كل شيء كاملاً وسليماً.')]],
  ] },
  returns: { title: kM('Returns', 'الاسترجاع'), updated: kM('2 Sep 2026', '2 سبتمبر 2026'), lede: kM('14 days to change your mind on unused items in their original packaging.', '14 يوماً لتغيير رأيك في المنتجات غير المستخدمة بتغليفها الأصلي.'), body: [
    ['h2', kM('What you can return', 'ما يمكن استرجاعه')],
    ['p', kM('Most items can be returned within 14 days of delivery if they are unused, uninstalled and in the original box with all accessories.', 'يمكن استرجاع معظم المنتجات خلال 14 يوماً من التوصيل إذا كانت غير مستخدمة وغير مركبة وفي علبتها الأصلية بكل ملحقاتها.')],
    ['ul', [kM('Cut-to-length LED strip and cable cannot be returned', 'شرائط الليد والكابلات المقطوعة بالطول لا تُسترجع'), kM('Special-order and project items follow the quote terms', 'المنتجات المطلوبة خصيصاً ومنتجات المشروعات تخضع لشروط عرض السعر'), kM('Installed fittings are covered by warranty, not returns', 'الوحدات المركبة يغطيها الضمان وليس الاسترجاع')]],
    ['callout', kM('Refunds go back to the original payment method within 5–7 working days. Cash and InstaPay orders are refunded by InstaPay or bank transfer.', 'يُرد المبلغ لنفس طريقة الدفع خلال 5–7 أيام عمل. الطلبات النقدية وإنستاباي تُرد بإنستاباي أو تحويل بنكي.')],
    ['h2', kM('How to return', 'طريقة الاسترجاع')],
    ['ol', [kM('Send your order number on WhatsApp.', 'أرسل رقم الطلب على واتساب.'), kM('We book a courier pickup — free for faulty or wrong items.', 'نحجز مندوب استلام — مجاناً للمنتجات المعيبة أو الخاطئة.'), kM('We inspect the item and confirm your refund within 2 working days.', 'نفحص المنتج ونؤكد الاسترداد خلال يومي عمل.')]],
  ] },
  warranty: { title: kM('Warranty', 'الضمان'), updated: kM('20 Aug 2026', '20 أغسطس 2026'), lede: kM('Official manufacturer warranty on everything we sell — handled by us, not passed back to you.', 'ضمان رسمي من الشركة المصنعة على كل ما نبيعه — نتابعه نحن بدلاً منك.'), body: [
    ['h2', kM('Coverage', 'التغطية')],
    ['ul', [kM('LED fittings and drivers — 2 to 5 years by brand', 'وحدات وأجهزة تشغيل الليد — من 2 إلى 5 سنوات حسب العلامة'), kM('Smart home devices — 2 years', 'أجهزة المنزل الذكي — سنتان'), kM('Switches, sockets & breakers — 1 to 10 years by range', 'المفاتيح والبرايز والقواطع — من سنة إلى 10 سنوات حسب الفئة'), kM('Dots installation workmanship — 1 year', 'أعمال التركيب من دوتس — سنة واحدة')]],
    ['p', kM('Warranty does not cover damage from voltage surges without protection, incorrect installation by others, or water ingress above the stated IP rating.', 'لا يغطي الضمان التلف الناتج عن ارتفاع الجهد بدون حماية، أو التركيب الخاطئ من الغير، أو دخول المياه فوق درجة IP المحددة.')],
    ['callout', kM('Keep your order number — it is your warranty card. We look up the batch and date for you.', 'احتفظ برقم الطلب — فهو بطاقة الضمان. نبحث نحن عن الدفعة والتاريخ.')],
    ['h2', kM('Making a claim', 'تقديم طلب ضمان')],
    ['ol', [kM('Send a photo or short video of the fault with your order number.', 'أرسل صورة أو فيديو قصير للعطل مع رقم الطلب.'), kM('Our technician diagnoses remotely or books a visit.', 'الفني يشخص عن بعد أو يحجز زيارة.'), kM('We repair or replace — usually within 7 working days.', 'نصلح أو نستبدل — غالباً خلال 7 أيام عمل.')]],
  ] },
  careers: { title: kM('Careers', 'الوظائف'), updated: kM('25 Sep 2026', '25 سبتمبر 2026'), lede: kM('Engineers, installers and client people who like getting the details right.', 'مهندسون وفنيون وفريق خدمة عملاء يحبون الدقة في التفاصيل.'), body: [
    ['h2', kM('Working at Dots', 'العمل في دوتس')],
    ['p', kM('We design, supply and install lighting, smart home and electrical systems across Egypt — from single apartments to hotels. Teams are small, training is paid, and every installer works with a design engineer.', 'نصمم ونورد ونركب أنظمة الإضاءة والمنزل الذكي والكهرباء في كل مصر — من الشقق إلى الفنادق. الفرق صغيرة، والتدريب مدفوع، وكل فني يعمل مع مهندس تصميم.')],
    ['h2', kM('Open roles', 'الوظائف المتاحة')],
    ['ul', [kM('Lighting design engineer — New Cairo · full time', 'مهندس تصميم إضاءة — القاهرة الجديدة · دوام كامل'), kM('Smart home installer (KNX / Zigbee) — Cairo & Giza', 'فني تركيب منزل ذكي (KNX / Zigbee) — القاهرة والجيزة'), kM('Showroom sales consultant — Sheikh Zayed', 'استشاري مبيعات بالمعرض — الشيخ زايد'), kM('Warehouse coordinator — Nasr City', 'منسق مخزن — مدينة نصر')]],
    ['callout', kM('Send your CV and the role title on WhatsApp or to careers@dots.eg. We reply to every application within 10 working days.', 'أرسل سيرتك الذاتية واسم الوظيفة على واتساب أو إلى careers@dots.eg. نرد على كل طلب خلال 10 أيام عمل.')],
  ] },
};
const KC_ORDER = ['delivery', 'returns', 'warranty', 'careers'];

export function KContentPage({ app, route }) {
  const { t, tx, cols, compact } = useK();
  const id = KC_PAGES[route.id] ? route.id : 'delivery';
  const pg = KC_PAGES[id];
  const block = ([k, v], i) => {
    if (k === 'ul' || k === 'ol') { const L = k; return <L key={i}>{v.map((x, j) => <li key={j}>{tx(x)}</li>)}</L>; }
    if (k === 'callout') return <div key={i} className="d-callout" style={{ display: 'flex', gap: 12, alignItems: 'flex-start' }}><Icon name="info" size={20} style={{ color: 'var(--yellow-800)', marginTop: 2 }} /><span>{tx(v)}</span></div>;
    const T = k; return <T key={i}>{tx(v)}</T>;
  };
  return (
    <>
      <KPageHead crumbs={[{ label: { en: 'Home', ar: 'الرئيسية' }, onClick: () => app.go('home') }, { label: { en: 'Help', ar: 'المساعدة' } }, { label: pg.title }]} title={pg.title} lede={pg.lede}>
        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, font: '600 13px var(--font-body)', color: 'var(--fg-3)' }}><Icon name="calendar" size={16} />{t('Last updated', 'آخر تحديث')} {tx(pg.updated)}</span>
      </KPageHead>
      <div className="d-container" style={{ paddingBlock: compact ? 24 : 56, display: 'grid', gridTemplateColumns: cols('200px minmax(0,1fr) 300px', '1fr'), gap: compact ? 24 : 48, alignItems: 'start' }}>
        <nav aria-label={t('Help pages', 'صفحات المساعدة')} style={{ position: compact ? 'static' : 'sticky', top: 24, display: 'flex', flexDirection: compact ? 'row' : 'column', gap: compact ? 8 : 2, overflowX: compact ? 'auto' : undefined }}>
          {KC_ORDER.map((k) => compact
            ? <Chip key={k} active={k === id} onClick={() => app.go('content', { id: k })}>{tx(KC_PAGES[k].title)}</Chip>
            : <button key={k} type="button" className={cx('d-nav__mi', k === id && 'd-mega__cat--on')} onClick={() => app.go('content', { id: k })} style={{ font: '700 14px var(--font-body)', padding: '10px 12px' }} aria-current={k === id ? 'page' : undefined}>{tx(KC_PAGES[k].title)}</button>)}
          {!compact && <button type="button" className="d-nav__mi" onClick={() => app.go('track')} style={{ font: '700 14px var(--font-body)', padding: '10px 12px', marginTop: 8, borderTop: '1px solid var(--border-1)', borderRadius: 0 }}><Icon name="package-search" size={18} />{t('Track order', 'تتبع الطلب')}</button>}
        </nav>
        <article className="d-prose" style={{ maxWidth: 720 }}>{pg.body.map(block)}</article>
        <aside style={{ position: compact ? 'static' : 'sticky', top: 24, border: '1px solid var(--border-1)', borderRadius: 'var(--radius-lg)', padding: 24, display: 'grid', gap: 14, background: 'var(--white)' }}>
          <span style={{ width: 44, height: 44, borderRadius: '50%', background: 'var(--wa-100)', color: 'var(--wa-600)', display: 'grid', placeItems: 'center' }}><Icon name="message-circle" size={22} /></span>
          <h3 style={{ margin: 0, font: '600 20px/1.3 var(--font-display)' }}>{t('Still have a question?', 'لديك سؤال آخر؟')}</h3>
          <p style={{ margin: 0, font: '500 14px/1.55 var(--font-body)', color: 'var(--fg-2)' }}>{t('Our team answers on WhatsApp within 2 working hours.', 'فريقنا يرد على واتساب خلال ساعتين عمل.')}</p>
          <Button variant="whatsapp" block onClick={() => app.quote()}>{t('Chat on WhatsApp', 'تواصل على واتساب')}</Button>
          <span style={{ font: '600 13px var(--font-body)', color: 'var(--fg-3)', display: 'inline-flex', gap: 6, alignItems: 'center' }}><Icon name="phone" size={16} /><span dir="ltr">19 345</span> · {t('Sun–Thu 9:00–18:00', 'الأحد–الخميس 9–6')}</span>
        </aside>
      </div>
    </>
  );
}

