
import React from 'react';

/* Order confirmation, payment failed and order tracking. Sample order DT-10482. */
const KO_ITEMS = [
  { id: 'o1', pid: 'p1', name: kM('Arc LED Pendant Ø450, dimmable', 'معلقة ليد آرك Ø450 قابلة للتعتيم'), variant: kM('Matte black · 3000K', 'أسود مطفي · 3000K'), sku: 'PH-ARC-450-30-BK', price: 4850, qty: 1 },
  { id: 'o2', pid: 'p2', name: kM('Trimless Downlight 12W, 36° beam', 'سبوت غاطس بدون إطار 12 وات، 36°'), variant: kM('White · 4000K', 'أبيض · 4000K'), sku: 'DL-TRM-12-40', price: 690, qty: 6 },
  { id: 'o3', pid: 'p3', name: kM('Smart Dimmer Switch, 1-gang, glass', 'مفتاح ديمر ذكي، خط واحد، زجاج'), variant: kM('Glass white', 'زجاج أبيض'), sku: 'LG-MYH-DIM1-W', price: 1150, qty: 1 },
];
KO_ITEMS.forEach((i) => { const p = K_PRODUCTS.find((x) => x.id === i.pid); i.image = p && p.image; });
const KO_SUB = KO_ITEMS.reduce((s, i) => s + i.price * i.qty, 0);
const KO_DISC = Math.round(KO_SUB * 0.1);
const KO_DEL = 150;
const KO_TOTAL = KO_SUB - KO_DISC + KO_DEL;
const KO_PAY = {
  cod: { icon: 'banknote', title: kM('Cash on delivery', 'الدفع عند الاستلام'), sub: kM('Pay the courier in cash', 'ادفع نقداً للمندوب') },
  card: { icon: 'credit-card', title: kM('Card · Paymob', 'بطاقة · Paymob'), sub: kM('Visa •••• 4417 · Txn 238 410 772', 'فيزا •••• 4417 · عملية 238 410 772') },
  instapay: { icon: 'smartphone', title: kM('InstaPay via WhatsApp', 'إنستاباي عبر واتساب'), sub: kM('Transfer details sent on WhatsApp', 'بيانات التحويل تُرسل على واتساب') },
};

function KOLines() {
  const { t, tx, price } = useLocale();
  return (
    <aside className="d-summary">
      <h3>{t('Order summary', 'ملخص الطلب')}</h3>
      <div>{KO_ITEMS.map((it) => (
        <div className="d-line" key={it.id}>
          <div className="d-line__img"><ImageFrame src={it.image} style={{ width: '100%', height: '100%', borderRadius: 6 }} /><span className="d-summary__qty">{it.qty}</span></div>
          <div style={{ minWidth: 0 }}>
            <p className="d-line__name" style={{ fontSize: 13 }}>{tx(it.name)}</p>
            <div className="d-line__var">{tx(it.variant)} · {t('Qty', 'الكمية')} {it.qty}</div>
            <div className="d-line__var" style={{ fontFamily: 'var(--font-mono)' }}>{it.sku}</div>
          </div>
          <div className="d-line__price" style={{ fontSize: 14 }}>{price(it.price * it.qty)}</div>
        </div>))}</div>
      <div className="d-totals">
        <div><span>{t('Subtotal', 'الإجمالي الفرعي')}</span><b>{price(KO_SUB)}</b></div>
        <div><span style={{ display: 'inline-flex', alignItems: 'center', gap: 8 }}>{t('Discount', 'الخصم')} <Badge tone="outline">DOTS10 · −10%</Badge></span><b style={{ color: 'var(--success-600)' }}>−{price(KO_DISC)}</b></div>
        <div><span>{t('Delivery', 'التوصيل')}</span><b>{price(KO_DEL)}</b></div>
        <div className="d-totals__grand"><span>{t('Total', 'الإجمالي')}</span><b>{price(KO_TOTAL)}</b></div>
        <span style={{ font: '500 12px var(--font-body)', color: 'var(--fg-3)' }}>{t('Prices include 14% VAT', 'الأسعار شاملة ضريبة القيمة المضافة 14%')}</span>
      </div>
    </aside>
  );
}

function KOBox({ title, children, style }) {
  return (
    <section style={{ border: '1px solid var(--border-1)', borderRadius: 'var(--radius-lg)', background: 'var(--white)', padding: 24, display: 'grid', gap: 16, ...style }}>
      {title && <h2 style={{ margin: 0, font: '600 20px/1.25 var(--font-display)', letterSpacing: 'var(--tracking-heading)' }}>{title}</h2>}
      {children}
    </section>
  );
}

function KOSteps({ steps }) {
  const { tx } = useLocale();
  return (
    <ol style={{ listStyle: 'none', margin: 0, padding: 0, display: 'grid', gap: 18 }}>
      {steps.map((s, i) => (
        <li key={i} style={{ display: 'grid', gridTemplateColumns: '32px minmax(0,1fr)', gap: 14, alignItems: 'start' }}>
          <span className="d-co__num" style={i === 0 ? undefined : { background: 'var(--white)', color: 'var(--ink-900)', boxShadow: 'inset 0 0 0 1.5px var(--ink-900)' }}>{i + 1}</span>
          <div style={{ display: 'grid', gap: 4, paddingTop: 4 }}>
            <b style={{ font: '800 15px/1.35 var(--font-body)' }}>{tx(s[0])}</b>
            <span style={{ font: '500 14px/1.55 var(--font-body)', color: 'var(--fg-2)', textWrap: 'pretty' }}>{tx(s[1])}</span>
          </div>
        </li>
      ))}
    </ol>
  );
}

function KONext({ pay, app }) {
  const { t, price } = useLocale();
  const amt = price(KO_TOTAL);
  if (pay === 'card') return (
    <KOBox title={t('What happens next', 'الخطوات التالية')}>
      <KOSteps steps={[
        [kM('Payment received', 'تم استلام الدفع'), kM(`We charged ${amt} to your Visa •••• 4417. The receipt is in your email.`, `تم خصم ${amt} من فيزا •••• 4417. الإيصال في بريدك الإلكتروني.`)],
        [kM('We prepare your order', 'نجهز طلبك'), kM('Our warehouse checks every fitting before packing — usually the same working day.', 'المخزن يفحص كل وحدة قبل التغليف — غالباً في نفس يوم العمل.')],
        [kM('Delivery — nothing to pay', 'التوصيل — بدون أي مبلغ'), kM('Next-day in Cairo & Giza, 2–4 working days elsewhere. The courier calls 1 hour before arriving.', 'اليوم التالي في القاهرة والجيزة، و2–4 أيام عمل لباقي المحافظات. المندوب يتصل قبل الوصول بساعة.')],
      ]} />
    </KOBox>
  );
  if (pay === 'instapay') return (
    <KOBox title={t('What happens next', 'الخطوات التالية')} style={{ borderColor: 'var(--wa-600)', boxShadow: 'inset 0 0 0 1px var(--wa-600)' }}>
      <KOSteps steps={[
        [kM('Continue on WhatsApp', 'تابع على واتساب'), kM(`We send the InstaPay address and the exact amount — ${amt} — in a chat with order DT-10482 attached.`, `نرسل عنوان إنستاباي والمبلغ بالضبط — ${amt} — في محادثة مرفق بها الطلب DT-10482.`)],
        [kM('Send the transfer screenshot', 'أرسل صورة التحويل'), kM('Reply in the same chat. We hold your items for 24 hours while we wait.', 'رد في نفس المحادثة. نحجز المنتجات لمدة 24 ساعة.')],
        [kM('We confirm and prepare', 'نؤكد ونجهز'), kM('Confirmation within 30 minutes during working hours, then delivery as usual.', 'التأكيد خلال 30 دقيقة في مواعيد العمل، ثم التوصيل كالمعتاد.')],
      ]} />
      <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap', alignItems: 'center' }}>
        <Button variant="whatsapp" size="lg" onClick={() => app.quote()}>{t('Continue on WhatsApp', 'تابع على واتساب')}</Button>
        <span style={{ font: '600 13px var(--font-body)', color: 'var(--fg-3)' }}>{t('Sun–Thu 9:00–18:00 · Sat 11:00–17:00', 'الأحد–الخميس 9–6 · السبت 11–5')}</span>
      </div>
    </KOBox>
  );
  return (
    <KOBox title={t('What happens next', 'الخطوات التالية')}>
      <KOSteps steps={[
        [kM('We confirm by WhatsApp', 'نؤكد عبر واتساب'), kM('A Dots team member messages you within 30 minutes to confirm items and address.', 'أحد فريق دوتس يراسلك خلال 30 دقيقة لتأكيد المنتجات والعنوان.')],
        [kM('We prepare and ship', 'نجهز ونشحن'), kM('Next-day in Cairo & Giza, 2–4 working days elsewhere. The courier calls 1 hour before arriving.', 'اليوم التالي في القاهرة والجيزة، و2–4 أيام عمل لباقي المحافظات. المندوب يتصل قبل الوصول بساعة.')],
        [kM(`Pay ${amt} in cash`, `ادفع ${amt} نقداً`), kM('Check the boxes with the courier first. Exact change helps — couriers carry limited cash.', 'افحص الطلب مع المندوب أولاً. يفضل تجهيز المبلغ بالضبط.')],
      ]} />
    </KOBox>
  );
}

function KODetails({ pay }) {
  const { t, tx, cols } = useK();
  const p = KO_PAY[pay];
  const cell = (icon, label, title, lines) => (
    <div style={{ display: 'grid', gap: 6, alignContent: 'start' }}>
      <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8, font: '800 12px/1 var(--font-body)', letterSpacing: 'var(--tracking-overline)', textTransform: 'var(--text-transform-overline)', color: 'var(--fg-3)' }}><Icon name={icon} size={16} />{label}</span>
      <b style={{ font: '800 15px/1.35 var(--font-body)' }}>{title}</b>
      {lines.map((l, i) => <span key={i} style={{ font: '500 14px/1.5 var(--font-body)', color: 'var(--fg-2)' }}>{l}</span>)}
    </div>
  );
  return (
    <KOBox>
      <div style={{ display: 'grid', gridTemplateColumns: cols('repeat(3, minmax(0,1fr))', '1fr'), gap: 24 }}>
        {cell('truck', t('Delivery', 'التوصيل'), t('Standard delivery', 'توصيل عادي'), [t('1–2 working days · EGP 150', '1–2 يوم عمل · 150 ج.م'), t('Expected Thu 2 Oct', 'متوقع الخميس 2 أكتوبر')])}
        {cell('map-pin', t('Address', 'العنوان'), t('Ahmed Samir', 'أحمد سمير'), [t('14 El-Nahda St, Apt 6, Maadi, Cairo', '14 شارع النهضة، شقة 6، المعادي، القاهرة'), <span dir="ltr">010 1234 5678</span>])}
        {cell(p.icon, t('Payment', 'الدفع'), tx(p.title), [tx(p.sub)])}
      </div>
    </KOBox>
  );
}

/** Guests only: create an account from this order with a single password (name, mobile, email come from the order). */
function KOSaveAccount({ app }) {
  const { t, compact } = useK();
  const [pw, setPw] = React.useState('');
  const [err, setErr] = React.useState('');
  const [done, setDone] = React.useState(false);
  if (app.user && !done) return null;
  if (done) return (
    <KOBox style={{ padding: compact ? 20 : 24, background: 'var(--success-100)', borderColor: 'transparent' }}>
      <div style={{ display: 'grid', gridTemplateColumns: 'auto minmax(0,1fr)', gap: 14, alignItems: 'start' }}>
        <span style={{ width: 40, height: 40, borderRadius: '50%', background: 'var(--white)', color: 'var(--success-600)', display: 'grid', placeItems: 'center' }}><Icon name="check" size={20} stroke={2} /></span>
        <div style={{ display: 'grid', gap: 6 }}>
          <b style={{ font: '800 16px/1.35 var(--font-body)' }}>{t('Account created — you’re logged in.', 'تم إنشاء الحساب — أنت مسجل الدخول الآن.')}</b>
          <span style={{ font: '500 14px/1.55 var(--font-body)', color: 'var(--fg-2)' }}>{t('DT-10482 and your address are saved. Next time, log in with 010 1234 5678 or ahmed.samir@gmail.com.', 'تم حفظ الطلب DT-10482 وعنوانك. في المرة القادمة سجّل الدخول بـ 010 1234 5678 أو ahmed.samir@gmail.com.')}</span>
          <div style={{ marginTop: 4 }}><Button variant="outline" size="sm" iconEnd="arrow-right" onClick={() => app.go('acc-overview')}>{t('Go to my account', 'اذهب لحسابي')}</Button></div>
        </div>
      </div>
    </KOBox>
  );
  const create = (e) => {
    e.preventDefault();
    if (!kaPwOk(pw)) { setErr(t('Use at least 8 characters, including a number.', 'استخدم 8 أحرف على الأقل، منها رقم.')); return; }
    setErr(''); setDone(true); app.login(KA_USER, null, t('Account created', 'تم إنشاء الحساب'));
  };
  const chip = (icon, v, ltr) => <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, padding: '6px 10px', borderRadius: 'var(--radius-pill)', background: 'var(--ink-50)', font: '600 13px var(--font-body)', color: 'var(--fg-1)' }}><Icon name={icon} size={14} /><span dir={ltr ? 'ltr' : undefined}>{v}</span></span>;
  return (
    <form onSubmit={create} noValidate>
      <KOBox style={{ padding: compact ? 20 : 24, gap: 16 }}>
        <div style={{ display: 'grid', gridTemplateColumns: 'auto minmax(0,1fr)', gap: 14, alignItems: 'start' }}>
          <span className="d-nav__mi-ico"><Icon name="user" size={20} /></span>
          <div style={{ display: 'grid', gap: 6 }}>
            <h2 style={{ margin: 0, font: '600 20px/1.25 var(--font-display)', letterSpacing: 'var(--tracking-heading)' }}>{t('Save your details for next time', 'احفظ بياناتك للمرة القادمة')}</h2>
            <span style={{ font: '500 14px/1.55 var(--font-body)', color: 'var(--fg-2)', textWrap: 'pretty' }}>{t('Add a password and we’ll create your account from this order — track it, reorder and check out faster.', 'أضف كلمة مرور وسننشئ حسابك من هذا الطلب — تابعه وأعد الطلب وادفع أسرع.')}</span>
          </div>
        </div>
        <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>{chip('user', t('Ahmed Samir', 'أحمد سمير'))}{chip('phone', '010 1234 5678', true)}{chip('mail', 'ahmed.samir@gmail.com', true)}</div>
        <div style={{ display: 'grid', gridTemplateColumns: compact ? '1fr' : 'minmax(0,1fr) auto', gap: 12, alignItems: 'start' }}>
          <KAPassword label={t('Choose a password', 'اختر كلمة مرور')} value={pw} onChange={setPw} error={err} help={err ? undefined : t('At least 8 characters, including a number.', '8 أحرف على الأقل، منها رقم.')} autoComplete="new-password" />
          <Button type="submit" size="lg" style={{ marginTop: compact ? 0 : 27 }} block={compact}>{t('Create account', 'إنشاء حساب')}</Button>
        </div>
      </KOBox>
    </form>
  );
}

export function KOrderConfirm({ app, route }) {
  const { t, cols, compact } = useK();
  const [pay, setPay] = React.useState(route.pay || 'cod');
  const status = { cod: ['neutral', t('Pay on delivery', 'الدفع عند الاستلام')], card: ['success', t('Paid', 'مدفوع')], instapay: ['warning', t('Awaiting payment', 'في انتظار الدفع')] }[pay];
  return (
    <div style={{ background: 'var(--bg-subtle)' }}>
      <div className="d-container" style={{ paddingBlock: compact ? 20 : 40, display: 'grid', gap: compact ? 16 : 24 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 10, flexWrap: 'wrap', padding: '8px 12px', border: '1px dashed var(--border-2)', borderRadius: 'var(--radius-md)', justifySelf: 'start' }}>
          <span style={{ font: '700 12px var(--font-body)', color: 'var(--fg-3)' }}>{t('Kit preview · payment case', 'معاينة · حالة الدفع')}</span>
          <Tabs variant="pill" value={pay} onChange={setPay} tabs={[{ id: 'cod', label: kM('Cash on delivery', 'عند الاستلام') }, { id: 'card', label: kM('Card paid', 'بطاقة') }, { id: 'instapay', label: kM('InstaPay', 'إنستاباي') }]} />
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: cols('minmax(0,1fr) 400px', '1fr'), gap: compact ? 16 : 32, alignItems: 'start' }}>
          <div style={{ display: 'grid', gap: compact ? 16 : 20 }}>
            <KOBox style={{ padding: compact ? 20 : 32, gap: 18 }}>
              <span style={{ width: 56, height: 56, borderRadius: '50%', background: pay === 'instapay' ? 'var(--yellow-100)' : 'var(--success-100)', color: pay === 'instapay' ? 'var(--yellow-800)' : 'var(--success-600)', display: 'grid', placeItems: 'center' }}><Icon name={pay === 'instapay' ? 'clock' : 'check'} size={28} stroke={2} /></span>
              <div style={{ display: 'grid', gap: 10 }}>
                <Eyebrow>{pay === 'instapay' ? t('Order reserved', 'تم حجز الطلب') : t('Order placed', 'تم الطلب')}</Eyebrow>
                <h1 className="t-h2" style={{ margin: 0, fontSize: compact ? 'var(--fs-h2-m)' : undefined, lineHeight: compact ? 'var(--lh-h2-m)' : undefined, textWrap: 'balance' }}>
                  {pay === 'instapay' ? t('One step left, Ahmed — finish payment on WhatsApp.', 'خطوة أخيرة يا أحمد — أكمل الدفع على واتساب.') : t('Thank you, Ahmed — your order is in.', 'شكراً يا أحمد — تم استلام طلبك.')}
                </h1>
                <p style={{ margin: 0, font: '500 15px/1.6 var(--font-body)', color: 'var(--fg-2)' }}>{t('We sent the details to 010 1234 5678 by SMS and WhatsApp.', 'أرسلنا التفاصيل إلى 010 1234 5678 برسالة وواتساب.')}</p>
              </div>
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: '10px 24px', alignItems: 'center', paddingTop: 16, borderTop: '1px solid var(--border-1)' }}>
                <span style={{ display: 'grid', gap: 4 }}><span style={{ font: '600 12px var(--font-body)', color: 'var(--fg-3)' }}>{t('Order number', 'رقم الطلب')}</span><b style={{ font: '500 18px/1 var(--font-mono)' }}>DT-10482</b></span>
                <span style={{ display: 'grid', gap: 4 }}><span style={{ font: '600 12px var(--font-body)', color: 'var(--fg-3)' }}>{t('Placed', 'تاريخ الطلب')}</span><b style={{ font: '700 15px/1 var(--font-body)' }}>{t('1 Oct 2026, 14:32', '1 أكتوبر 2026، 14:32')}</b></span>
                <span style={{ display: 'grid', gap: 4 }}><span style={{ font: '600 12px var(--font-body)', color: 'var(--fg-3)' }}>{t('Payment', 'الدفع')}</span><Badge tone={status[0]} dot>{status[1]}</Badge></span>
              </div>
            </KOBox>
            <KOSaveAccount app={app} />
            <KONext pay={pay} app={app} />
            <KODetails pay={pay} />
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 10 }}>
              <Button size="lg" icon="package-search" onClick={() => app.go('track')} block={compact}>{t('Track this order', 'تتبع هذا الطلب')}</Button>
              <Button size="lg" variant="outline" onClick={() => app.go('products')} block={compact}>{t('Continue shopping', 'متابعة التسوق')}</Button>
              <Button size="lg" variant="quote-outline" onClick={() => app.quote()} block={compact}>{t('WhatsApp us', 'راسلنا واتساب')}</Button>
            </div>
          </div>
          <div style={{ position: compact ? 'static' : 'sticky', top: 24 }}><KOLines /></div>
        </div>
      </div>
    </div>
  );
}

export function KPaymentFailed({ app }) {
  const { t, cols, compact, price } = useK();
  const [opt, setOpt] = React.useState('retry');
  const cta = { retry: t('Retry card payment', 'إعادة محاولة الدفع بالبطاقة'), cod: t('Switch to cash on delivery', 'التحويل للدفع عند الاستلام'), instapay: t('Pay with InstaPay on WhatsApp', 'ادفع بإنستاباي عبر واتساب') }[opt];
  const go = () => {
    if (opt === 'retry') { app.toast('info', t('Opening Paymob…', 'جارٍ فتح Paymob…'), t('You’ll come back here after your bank’s check.', 'ستعود هنا بعد تأكيد البنك.')); return; }
    app.toast('success', t('Payment method updated', 'تم تغيير طريقة الدفع'), 'DT-10482');
    app.go('confirm', { pay: opt });
  };
  return (
    <div style={{ background: 'var(--bg-subtle)' }}>
      <div className="d-container" style={{ paddingBlock: compact ? 20 : 40, display: 'grid', gridTemplateColumns: cols('minmax(0,1fr) 400px', '1fr'), gap: compact ? 16 : 32, alignItems: 'start' }}>
        <div style={{ display: 'grid', gap: compact ? 16 : 20 }}>
          <KOBox style={{ padding: compact ? 20 : 32, gap: 18 }}>
            <span style={{ width: 56, height: 56, borderRadius: '50%', background: 'var(--error-100)', color: 'var(--error-600)', display: 'grid', placeItems: 'center' }}><Icon name="circle-alert" size={28} stroke={2} /></span>
            <div style={{ display: 'grid', gap: 10 }}>
              <Eyebrow>{t('Payment failed', 'فشل الدفع')}</Eyebrow>
              <h1 className="t-h2" style={{ margin: 0, fontSize: compact ? 'var(--fs-h2-m)' : undefined, lineHeight: compact ? 'var(--lh-h2-m)' : undefined, textWrap: 'balance' }}>{t('Your payment didn’t go through.', 'لم تتم عملية الدفع.')}</h1>
              <p style={{ margin: 0, font: '500 15px/1.6 var(--font-body)', color: 'var(--fg-2)', textWrap: 'pretty' }}>{t('Your bank declined the card (code 51 · insufficient funds). No money was taken. Order DT-10482 is saved for 24 hours.', 'رفض البنك البطاقة (كود 51 · رصيد غير كافٍ). لم يتم خصم أي مبلغ. الطلب DT-10482 محفوظ لمدة 24 ساعة.')}</p>
            </div>
          </KOBox>
          <KOBox title={t('How would you like to pay?', 'كيف تحب تدفع؟')}>
            <div style={{ display: 'grid', gap: 10 }}>
              <RadioCard name="kpf" value="retry" checked={opt === 'retry'} onChange={setOpt} icon="credit-card" title={t('Try the card again', 'حاول بالبطاقة مرة أخرى')} desc={t('Or use a different Visa, Mastercard or Meeza card', 'أو استخدم بطاقة فيزا أو ماستركارد أو ميزة أخرى')} />
              <RadioCard name="kpf" value="cod" checked={opt === 'cod'} onChange={setOpt} icon="banknote" title={t('Cash on delivery', 'الدفع عند الاستلام')} desc={t('Pay the courier when your order arrives', 'ادفع للمندوب عند وصول الطلب')} meta={price(KO_TOTAL)} />
              <RadioCard name="kpf" value="instapay" checked={opt === 'instapay'} onChange={setOpt} icon="smartphone" title={t('InstaPay via WhatsApp', 'إنستاباي عبر واتساب')} desc={t('We send transfer details in a chat', 'نرسل بيانات التحويل في محادثة')} />
            </div>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 10 }}>
              <Button size="lg" variant={opt === 'instapay' ? 'whatsapp' : 'primary'} icon={opt === 'retry' ? 'rotate-ccw' : undefined} onClick={go} block={compact}>{cta}</Button>
              <Button size="lg" variant="ghost" onClick={() => app.quote()} block={compact}>{t('Get help on WhatsApp', 'مساعدة عبر واتساب')}</Button>
            </div>
          </KOBox>
        </div>
        <div style={{ position: compact ? 'static' : 'sticky', top: 24 }}><KOLines /></div>
      </div>
    </div>
  );
}

const KO_TRACK = {
  'DT-10482': { status: 3, pay: ['success', kM('Paid · Card', 'مدفوع · بطاقة')], times: [kM('1 Oct, 14:32', '1 أكتوبر، 14:32'), kM('1 Oct, 14:51', '1 أكتوبر، 14:51'), kM('1 Oct, 17:10', '1 أكتوبر، 17:10'), kM('2 Oct, 09:05', '2 أكتوبر، 09:05'), kM('Expected 2 Oct, by 18:00', 'متوقع 2 أكتوبر، قبل 6 م')] },
  'DT-10477': { cancelled: 2, pay: ['neutral', kM('Refunded', 'تم الاسترداد')], times: [kM('28 Sep, 11:02', '28 سبتمبر، 11:02'), kM('28 Sep, 11:20', '28 سبتمبر، 11:20'), kM('28 Sep, 15:44', '28 سبتمبر، 15:44')] },
};
const KO_STEPS = [kM('New', 'جديد'), kM('Confirmed', 'تم التأكيد'), kM('Preparing', 'قيد التجهيز'), kM('Shipped', 'تم الشحن'), kM('Delivered', 'تم التوصيل')];
const KO_STEP_NOTE = [kM('We received your order.', 'استلمنا طلبك.'), kM('Items and address confirmed on WhatsApp.', 'تم تأكيد المنتجات والعنوان على واتساب.'), kM('Checked and packed at our Nasr City warehouse.', 'تم الفحص والتغليف في مخزن مدينة نصر.'), kM('With Bosta · AWB 7741 0928 33. The courier calls 1 hour before.', 'مع بوسطة · بوليصة 7741 0928 33. المندوب يتصل قبل الوصول بساعة.'), kM('Signed for at the door.', 'تم الاستلام.')];

function KOTimeline({ o }) {
  const { tx } = useLocale();
  const rows = o.cancelled != null
    ? [...KO_STEPS.slice(0, o.cancelled).map((s, i) => ({ s, note: KO_STEP_NOTE[i], time: o.times[i], st: 'done' })), { s: kM('Cancelled', 'ملغي'), note: kM('Cancelled at your request. The card refund reaches your bank in 5–7 working days.', 'تم الإلغاء بناءً على طلبك. يصل المبلغ المسترد لبنكك خلال 5–7 أيام عمل.'), time: o.times[o.cancelled], st: 'cancel' }]
    : KO_STEPS.map((s, i) => ({ s, note: KO_STEP_NOTE[i], time: o.times[i], st: i < o.status ? 'done' : i === o.status ? 'now' : 'todo' }));
  return (
    <ol style={{ listStyle: 'none', margin: 0, padding: 0 }}>
      {rows.map((r, i) => {
        const last = i === rows.length - 1;
        const dot = { done: { background: 'var(--ink-900)', borderColor: 'var(--ink-900)' }, now: { background: 'var(--accent)', borderColor: 'var(--ink-900)', animation: 'd-pulse-node 2.4s var(--ease-in-out) infinite' }, todo: { background: 'var(--white)', borderColor: 'var(--border-control)' }, cancel: { background: 'var(--error-600)', borderColor: 'var(--error-600)' } }[r.st];
        return (
          <li key={i} style={{ display: 'grid', gridTemplateColumns: '20px minmax(0,1fr)', gap: 14 }}>
            <span style={{ position: 'relative', display: 'flex', justifyContent: 'center' }}>
              <i style={{ width: 14, height: 14, borderRadius: '50%', border: '2px solid', marginTop: 3, zIndex: 1, ...dot }} />
              {!last && <i style={{ position: 'absolute', top: 18, bottom: -3, width: 2, background: r.st === 'done' ? 'var(--ink-900)' : 'var(--border-1)' }} />}
            </span>
            <div style={{ display: 'grid', gap: 3, paddingBottom: last ? 0 : 22 }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap' }}>
                <b style={{ font: '800 15px/1.3 var(--font-body)', color: r.st === 'todo' ? 'var(--fg-3)' : r.st === 'cancel' ? 'var(--error-600)' : 'var(--fg-1)' }}>{tx(r.s)}</b>
                <span style={{ font: '600 13px/1.3 var(--font-body)', color: r.st === 'todo' || r.st === 'now' ? 'var(--fg-3)' : 'var(--fg-2)', fontVariantNumeric: 'tabular-nums' }}>{tx(r.time) || '—'}</span>
              </div>
              {r.st !== 'todo' && <span style={{ font: '500 14px/1.5 var(--font-body)', color: 'var(--fg-2)' }}>{tx(r.note)}</span>}
            </div>
          </li>
        );
      })}
    </ol>
  );
}

export function KTrack({ app }) {
  const { t, tx, cols, compact, price } = useK();
  const [no, setNo] = React.useState('DT-10482');
  const [mob, setMob] = React.useState('010 1234 5678');
  const [res, setRes] = React.useState(null);
  const submit = (e) => { e.preventDefault(); const k = no.trim().toUpperCase(); setRes(KO_TRACK[k] && mob.replace(/\D/g, '').length >= 10 ? { k, o: KO_TRACK[k] } : 'none'); };
  const o = res && res !== 'none' ? res.o : null;
  const stTone = o ? (o.cancelled != null ? ['error', kM('Cancelled', 'ملغي')] : ['dark', KO_STEPS[o.status]]) : null;
  return (
    <>
      <KPageHead crumbs={[{ label: { en: 'Home', ar: 'الرئيسية' }, onClick: () => app.go('home') }, { label: { en: 'Track order', ar: 'تتبع الطلب' } }]} title={{ en: 'Track your order', ar: 'تتبع طلبك' }} lede={{ en: 'Enter the order number from your confirmation SMS and the mobile you ordered with.', ar: 'أدخل رقم الطلب من رسالة التأكيد ورقم الموبايل الذي طلبت به.' }} />
      <div className="d-container" style={{ paddingBlock: compact ? 20 : 40, display: 'grid', gap: compact ? 16 : 24 }}>
        <form onSubmit={submit} style={{ border: '1px solid var(--border-1)', borderRadius: 'var(--radius-lg)', padding: compact ? 18 : 24, display: 'grid', gridTemplateColumns: cols('minmax(0,1fr) minmax(0,1fr) auto', '1fr'), gap: 16, alignItems: 'end' }}>
          <Input label={t('Order number', 'رقم الطلب')} icon="hash" value={no} onChange={(e) => setNo(e.target.value)} placeholder="DT-10482" dir="ltr" />
          <Input label={t('Mobile number', 'رقم الموبايل')} icon="phone" value={mob} onChange={(e) => setMob(e.target.value)} placeholder="01X XXXX XXXX" inputMode="tel" dir="ltr" />
          <Button type="submit" size="lg" icon="package-search" block={compact}>{t('Track order', 'تتبع الطلب')}</Button>
          <span className="d-help" style={{ gridColumn: '1 / -1' }}>{t('Kit demo: DT-10482 (shipped) · DT-10477 (cancelled) · any other number shows the empty state.', 'للتجربة: DT-10482 (تم الشحن) · DT-10477 (ملغي) · أي رقم آخر يعرض حالة عدم التطابق.')}</span>
        </form>
        {res === 'none' && (
          <div role="alert" style={{ display: 'grid', gridTemplateColumns: 'auto minmax(0,1fr)', gap: 16, padding: compact ? 18 : 24, borderRadius: 'var(--radius-lg)', background: 'var(--error-100)', alignItems: 'start' }}>
            <span style={{ width: 40, height: 40, borderRadius: '50%', background: 'var(--white)', color: 'var(--error-600)', display: 'grid', placeItems: 'center' }}><Icon name="search-x" size={20} /></span>
            <div style={{ display: 'grid', gap: 8 }}>
              <b style={{ font: '800 16px/1.3 var(--font-body)' }}>{t('No order matches this number and mobile', 'لا يوجد طلب بهذا الرقم وهذا الموبايل')}</b>
              <span style={{ font: '500 14px/1.55 var(--font-body)', color: 'var(--fg-2)' }}>{t('Order numbers start with DT- and are in your confirmation SMS. Use the same mobile you entered at checkout.', 'أرقام الطلبات تبدأ بـ DT- وموجودة في رسالة التأكيد. استخدم نفس الموبايل الذي أدخلته عند الطلب.')}</span>
              <div><Button variant="quote-outline" size="sm" onClick={() => app.quote()}>{t('Ask us on WhatsApp', 'اسألنا على واتساب')}</Button></div>
            </div>
          </div>
        )}
        {o && (
          <div style={{ display: 'grid', gridTemplateColumns: cols('minmax(0,1fr) 380px', '1fr'), gap: compact ? 16 : 32, alignItems: 'start' }}>
            <KOBox style={{ padding: compact ? 18 : 28, gap: 22 }}>
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: 12, alignItems: 'center', justifyContent: 'space-between' }}>
                <div style={{ display: 'grid', gap: 4 }}>
                  <b style={{ font: '500 20px/1 var(--font-mono)' }}>{res.k}</b>
                  <span style={{ font: '600 13px var(--font-body)', color: 'var(--fg-3)' }}>{t('Placed', 'تاريخ الطلب')} {tx(o.times[0])}</span>
                </div>
                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}><Badge tone={stTone[0]} dot>{tx(stTone[1])}</Badge><Badge tone={o.pay[0]} dot>{tx(o.pay[1])}</Badge></div>
              </div>
              {o.cancelled == null && <Progress value={Math.round((o.status / 4) * 100)} steps={KO_STEPS} current={o.status} />}
              <KOTimeline o={o} />
            </KOBox>
            <div style={{ display: 'grid', gap: 16 }}>
              <KOBox style={{ padding: 20, gap: 14 }}>
                <div style={{ display: 'grid', gap: 4 }}>
                  <span style={{ font: '700 12px var(--font-body)', color: 'var(--fg-3)' }}>{t('Delivery', 'التوصيل')}</span>
                  <b style={{ font: '800 15px/1.3 var(--font-body)' }}>{t('Standard delivery · Maadi, Cairo', 'توصيل عادي · المعادي، القاهرة')}</b>
                </div>
                <div style={{ borderTop: '1px solid var(--border-1)', paddingTop: 12, display: 'grid', gap: 10 }}>
                  <span style={{ font: '700 12px var(--font-body)', color: 'var(--fg-3)' }}>{t('Items', 'المنتجات')} · {KO_ITEMS.reduce((s, i) => s + i.qty, 0)}</span>
                  {KO_ITEMS.map((it) => (
                    <div key={it.id} style={{ display: 'grid', gridTemplateColumns: '44px minmax(0,1fr) auto', gap: 12, alignItems: 'center' }}>
                      <ImageFrame src={it.image} style={{ width: 44, height: 44, borderRadius: 6, border: '1px solid var(--border-1)' }} />
                      <span style={{ font: '700 13px/1.35 var(--font-body)', minWidth: 0 }}>{tx(it.name)}<span style={{ display: 'block', font: '500 12px var(--font-body)', color: 'var(--fg-3)' }}>{tx(it.variant)} · ×{it.qty}</span></span>
                      <b style={{ font: '800 13px var(--font-body)', whiteSpace: 'nowrap' }}>{price(it.price * it.qty)}</b>
                    </div>
                  ))}
                  <div className="d-totals"><div className="d-totals__grand"><span>{t('Total', 'الإجمالي')}</span><b style={{ fontSize: 18 }}>{price(KO_TOTAL)}</b></div></div>
                </div>
              </KOBox>
              <div style={{ display: 'flex', gap: 12, alignItems: 'center', padding: 16, borderRadius: 'var(--radius-lg)', background: 'var(--wa-100)', flexWrap: 'wrap' }}>
                <span style={{ flex: 1, minWidth: 160, font: '600 14px/1.4 var(--font-body)' }}>{t('Need to change the address or time?', 'تحتاج تغيير العنوان أو الموعد؟')}</span>
                <Button variant="whatsapp" size="sm" onClick={() => app.quote()}>{t('WhatsApp us', 'راسلنا')}</Button>
              </div>
            </div>
          </div>
        )}
      </div>
    </>
  );
}

