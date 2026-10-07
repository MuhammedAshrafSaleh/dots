
import React from 'react';

/* Customer accounts: login, register, forgot/reset password, account area (overview, orders, order detail, wishlist, addresses, profile).
   Guest checkout stays the default; accounts are optional. Reuses KOBox / KOTimeline / KO_* from Orders.jsx. */

export const KA_USER = { name: kM('Ahmed Samir', 'أحمد سمير'), first: kM('Ahmed', 'أحمد'), mobile: '010 1234 5678', email: 'ahmed.samir@gmail.com', since: kM('Member since March 2024', 'عضو منذ مارس 2024') };
export const KA_WISH_START = ['p10', 'p4', 'p8', 'p9'];
const KA_GOV = [['Cairo', 'القاهرة'], ['Giza', 'الجيزة'], ['Alexandria', 'الإسكندرية'], ['Qalyubia', 'القليوبية'], ['Sharqia', 'الشرقية'], ['Red Sea', 'البحر الأحمر'], ['South Sinai', 'جنوب سيناء']];
const KA_LABEL = { home: { label: kM('Home', 'المنزل'), icon: 'house' }, office: { label: kM('Office', 'المكتب'), icon: 'building-2' }, other: { label: kM('Other', 'آخر'), icon: 'map-pin' } };
export const KA_ADDR = [
  { id: 'a1', label: 'home', name: kM('Ahmed Samir', 'أحمد سمير'), mobile: '010 1234 5678', gov: 0, city: kM('Maadi', 'المعادي'), street: kM('14 El-Nahda St, Building 9', '14 شارع النهضة، عقار 9'), floor: '3 / 6', landmark: kM('Near Victoria Square', 'بجوار ميدان فيكتوريا'), def: true },
  { id: 'a2', label: 'office', name: kM('Ahmed Samir', 'أحمد سمير'), mobile: '010 1234 5678', gov: 0, city: kM('New Cairo, 5th Settlement', 'القاهرة الجديدة، التجمع الخامس'), street: kM('North 90th St, Building B12', 'شارع التسعين الشمالي، مبنى B12'), floor: '2 / 204', landmark: kM('Opposite Concord Plaza', 'أمام كونكورد بلازا') },
];

function kaV(v, l) { return v && typeof v === 'object' ? (v[l] ?? v.en) : (v || ''); }
function kaAddrText(a) {
  const mk = (l) => [kaV(a.street, l), (l === 'ar' ? 'الدور / الشقة ' : 'Floor / apt ') + a.floor, kaV(a.city, l), KA_GOV[a.gov][l === 'ar' ? 1 : 0]].filter(Boolean).join(l === 'ar' ? '، ' : ', ');
  return { en: mk('en'), ar: mk('ar') };
}
/** Saved addresses → CheckoutForm `addresses` cards. */
export function kaCheckoutAddrs(list) { return list.map((a) => ({ id: a.id, def: a.def, icon: KA_LABEL[a.label].icon, title: KA_LABEL[a.label].label, lines: kaAddrText(a) })); }

export const kaMobileOk = (v) => /^01[0125]\d{8}$/.test(String(v).replace(/[\s-]/g, ''));
export const kaEmailOk = (v) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(v).trim());
export const kaPwOk = (v) => v.length >= 8 && /\d/.test(v) && /[^\d\s]/.test(v);

function kaItem(pid, qty, variant) {
  const p = K_PRODUCTS.find((x) => x.id === pid);
  return { id: 'i' + pid + qty, pid, name: p.name, variant: variant || p.variants || kM('Standard', 'قياسي'), sku: p.sku, price: p.price || 0, qty, image: p.image };
}
export const KA_ORDERS = [
  { no: 'DT-10482', date: kM('1 Oct 2026', '1 أكتوبر 2026'), items: KO_ITEMS, status: 3, pay: 'card', payBadge: ['success', kM('Paid · Card', 'مدفوع · بطاقة')], times: KO_TRACK['DT-10482'].times, addr: 'a1', del: 150, disc: KO_DISC },
  { no: 'DT-10477', date: kM('28 Sep 2026', '28 سبتمبر 2026'), items: [kaItem('p10', 1, kM('Brass · E27', 'نحاسي · E27')), kaItem('p4', 4, kM('Black · 3000K', 'أسود · 3000K'))], cancelled: 2, pay: 'card', payBadge: ['neutral', kM('Refunded', 'تم الاسترداد')], times: KO_TRACK['DT-10477'].times, addr: 'a1', del: 150 },
  { no: 'DT-10391', date: kM('9 Aug 2026', '9 أغسطس 2026'), items: [kaItem('p8', 3), kaItem('p9', 2), kaItem('p11', 6), kaItem('p7', 4)], status: 4, pay: 'cod', payBadge: ['success', kM('Paid · Cash', 'مدفوع · نقداً')], times: [kM('9 Aug, 10:14', '9 أغسطس، 10:14'), kM('9 Aug, 10:40', '9 أغسطس، 10:40'), kM('9 Aug, 15:05', '9 أغسطس، 15:05'), kM('10 Aug, 09:30', '10 أغسطس، 09:30'), kM('11 Aug, 13:12', '11 أغسطس، 13:12')], addr: 'a2', del: 150 },
  { no: 'DT-10340', date: kM('12 Jun 2026', '12 يونيو 2026'), items: [kaItem('p12', 8), kaItem('p3', 2)], status: 4, pay: 'instapay', payBadge: ['success', kM('Paid · InstaPay', 'مدفوع · إنستاباي')], times: [kM('12 Jun, 18:02', '12 يونيو، 18:02'), kM('12 Jun, 18:31', '12 يونيو، 18:31'), kM('13 Jun, 11:20', '13 يونيو، 11:20'), kM('13 Jun, 16:45', '13 يونيو، 16:45'), kM('14 Jun, 12:08', '14 يونيو، 12:08')], addr: 'a1', del: 0 },
];
function kaStatus(o) { if (o.cancelled != null) return ['error', kM('Cancelled', 'ملغي')]; if (o.status >= 4) return ['success', KO_STEPS[4]]; return ['dark', KO_STEPS[o.status]]; }
function kaTotals(o) { const sub = o.items.reduce((s, i) => s + i.price * i.qty, 0); const disc = o.disc || 0; const del = o.del ?? 150; return { sub, disc, del, total: sub - disc + del, n: o.items.reduce((s, i) => s + i.qty, 0) }; }

/* ---------- small shared pieces ---------- */
function KALink({ children, onClick, style }) {
  return <button type="button" onClick={onClick} style={{ border: 0, background: 'none', padding: 0, font: '800 14px/1.4 var(--font-body)', color: 'var(--fg-1)', textDecoration: 'underline', textDecorationColor: 'var(--accent)', textDecorationThickness: 2, textUnderlineOffset: 3, cursor: 'pointer', ...style }}>{children}</button>;
}

export function KAPassword({ label, value, onChange, error, help, autoComplete, placeholder, ...rest }) {
  const { t } = useLocale();
  const [show, setShow] = React.useState(false);
  return (
    <Input label={label} type={show ? 'text' : 'password'} value={value} onChange={(e) => onChange(e.target.value)} error={error} help={help} autoComplete={autoComplete} placeholder={placeholder} icon="lock" dir="ltr" {...rest}
      trailing={<button type="button" onClick={() => setShow(!show)} aria-pressed={show} aria-label={show ? t('Hide password', 'إخفاء كلمة المرور') : t('Show password', 'إظهار كلمة المرور')} title={show ? t('Hide password', 'إخفاء كلمة المرور') : t('Show password', 'إظهار كلمة المرور')} style={{ pointerEvents: 'auto', border: 0, background: 'none', padding: 8, margin: -8, cursor: 'pointer', color: 'var(--fg-2)', display: 'grid', placeItems: 'center' }}><Icon name={show ? 'eye-off' : 'eye'} size={18} /></button>} />
  );
}

function KAKit({ value, onChange, tabs }) {
  const { t } = useLocale();
  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 10, flexWrap: 'wrap', padding: '8px 12px', border: '1px dashed var(--border-2)', borderRadius: 'var(--radius-md)', justifySelf: 'start' }}>
      <span style={{ font: '700 12px var(--font-body)', color: 'var(--fg-3)' }}>{t('Kit preview · state', 'معاينة · الحالة')}</span>
      <Tabs variant="pill" value={value} onChange={onChange} tabs={tabs} />
    </div>
  );
}

function KAAlert({ tone = 'error', icon, title, children }) {
  const c = { error: ['var(--error-100)', 'var(--error-600)'], success: ['var(--success-100)', 'var(--success-600)'] }[tone];
  return (
    <div role="alert" style={{ display: 'grid', gridTemplateColumns: 'auto minmax(0,1fr)', gap: 12, padding: 14, borderRadius: 'var(--radius-md)', background: c[0], alignItems: 'start' }}>
      <span style={{ width: 32, height: 32, borderRadius: '50%', background: 'var(--white)', color: c[1], display: 'grid', placeItems: 'center' }}><Icon name={icon} size={18} /></span>
      <div style={{ display: 'grid', gap: 4, paddingTop: 5 }}><b style={{ font: '800 14px/1.35 var(--font-body)' }}>{title}</b>{children && <span style={{ font: '500 13px/1.5 var(--font-body)', color: 'var(--fg-2)' }}>{children}</span>}</div>
    </div>
  );
}

function KAEmpty({ icon, title, text, action }) {
  const { compact } = useLocale();
  return (
    <div style={{ border: '1px dashed var(--border-2)', borderRadius: 'var(--radius-lg)', padding: compact ? '40px 20px' : '64px 32px', display: 'grid', justifyItems: 'center', textAlign: 'center', gap: 12, background: 'var(--white)' }}>
      <span style={{ width: 64, height: 64, borderRadius: '50%', background: 'var(--yellow-100)', display: 'grid', placeItems: 'center', marginBottom: 4 }}><Icon name={icon} size={28} /></span>
      <h2 style={{ margin: 0, font: '600 22px/1.25 var(--font-display)', letterSpacing: 'var(--tracking-heading)' }}>{title}</h2>
      <p style={{ margin: 0, maxWidth: 440, font: '500 15px/1.6 var(--font-body)', color: 'var(--fg-2)', textWrap: 'pretty' }}>{text}</p>
      {action && <div style={{ marginTop: 6 }}>{action}</div>}
    </div>
  );
}

function KAThumbs({ items, size = 52, max = 4 }) {
  const more = items.length - max;
  return (
    <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
      {items.slice(0, max).map((it) => (
        <span key={it.id} style={{ position: 'relative', flex: 'none' }}>
          <ImageFrame src={it.image} contain style={{ width: size, height: size, borderRadius: 8, border: '1px solid var(--border-1)', background: 'var(--white)' }} />
          {it.qty > 1 && <span className="d-summary__qty">{it.qty}</span>}
        </span>
      ))}
      {more > 0 && <span style={{ width: size, height: size, borderRadius: 8, background: 'var(--ink-50)', display: 'grid', placeItems: 'center', font: '800 13px var(--font-body)', color: 'var(--fg-2)' }}>+{more}</span>}
    </div>
  );
}

/** Product-detail wishlist toggle with the same logged-out prompt as ProductCard. */
export function KFavButton({ app, product }) {
  const { t } = useLocale();
  const [tip, setTip] = React.useState(false);
  const on = app.wish.includes(product.id);
  return (
    <span style={{ position: 'relative', display: 'inline-flex', flex: 'none' }}>
      <button type="button" className="d-btn d-btn--outline d-btn--icon d-btn--lg" aria-pressed={on} aria-label={on ? t('Remove from wishlist', 'إزالة من المفضلة') : t('Save to wishlist', 'أضف للمفضلة')} title={on ? t('Remove from wishlist', 'إزالة من المفضلة') : t('Save to wishlist', 'أضف للمفضلة')} onClick={() => { if (app.toggleWish(product) === false) setTip(true); }}>
        <Icon name="heart" size={20} style={on ? { fill: 'var(--accent)', stroke: 'var(--ink-900)' } : undefined} />
      </button>
      {tip && <WishlistPrompt style={{ top: 'calc(100% + 10px)', insetInlineEnd: 6, maxWidth: 'none' }} onClose={() => setTip(false)} onLogin={() => app.go('login')} onRegister={() => app.go('register')} />}
    </span>
  );
}

/* ---------- auth screens ---------- */
function KAAuth({ image, children }) {
  const { t, compact } = useK();
  const perks = (
    <ul className="d-node-list" style={{ font: '600 14px/1.4 var(--font-body)', color: 'var(--fg-2)' }}>
      <li>{t('Track every order and payment in one place', 'تابع كل طلباتك ومدفوعاتك في مكان واحد')}</li>
      <li>{t('Check out faster with saved addresses', 'ادفع أسرع بعناوينك المحفوظة')}</li>
      <li>{t('Keep a wishlist on all your devices', 'احتفظ بالمفضلة على كل أجهزتك')}</li>
    </ul>
  );
  return (
    <div style={{ background: 'var(--bg-subtle)' }}>
      <div className="d-container" style={{ paddingBlock: compact ? 16 : 48 }}>
        <div style={{ display: 'grid', gridTemplateColumns: compact ? '1fr' : 'minmax(0,1fr) minmax(0,1.05fr)', background: 'var(--white)', border: '1px solid var(--border-1)', borderRadius: 'var(--radius-xl)', overflow: 'hidden' }}>
          {compact && <ImageFrame src={kPh(image, 900)} ratio="16 / 7" />}
          <div style={{ padding: compact ? '24px 20px 28px' : '56px 64px', display: 'grid', alignContent: 'center' }}>
            <div style={{ width: '100%', maxWidth: 420, marginInline: 'auto', display: 'grid', gap: 22 }}>{children}</div>
          </div>
          {!compact && (
            <ImageFrame src={kPh(image, 1400)} style={{ height: '100%', minHeight: 660 }}>
              <div style={{ position: 'absolute', insetInline: 24, bottom: 24, background: 'var(--white)', borderRadius: 'var(--radius-lg)', padding: 22, display: 'grid', gap: 12, boxShadow: 'var(--shadow-lg)' }}>
                <Eyebrow>{t('Your Dots account', 'حسابك في دوتس')}</Eyebrow>
                {perks}
                <span style={{ font: '500 12px/1.5 var(--font-body)', color: 'var(--fg-3)' }}>{t('Optional — guest checkout is always available.', 'اختياري — الشراء كضيف متاح دائماً.')}</span>
              </div>
            </ImageFrame>
          )}
        </div>
      </div>
    </div>
  );
}

function KAHead({ eyebrow, title, lede, icon, tone }) {
  const { compact } = useLocale();
  return (
    <div style={{ display: 'grid', gap: 10 }}>
      {icon && <span style={{ width: 56, height: 56, borderRadius: '50%', background: tone === 'success' ? 'var(--success-100)' : 'var(--yellow-100)', color: tone === 'success' ? 'var(--success-600)' : 'var(--ink-900)', display: 'grid', placeItems: 'center', marginBottom: 4 }}><Icon name={icon} size={26} stroke={tone === 'success' ? 2 : 1.5} /></span>}
      {eyebrow && <Eyebrow>{eyebrow}</Eyebrow>}
      <h1 className="t-h2" style={{ margin: 0, fontSize: compact ? 'var(--fs-h2-m)' : undefined, lineHeight: compact ? 'var(--lh-h2-m)' : undefined, textWrap: 'balance' }}>{title}</h1>
      {lede && <p style={{ margin: 0, font: '500 15px/1.6 var(--font-body)', color: 'var(--fg-2)', textWrap: 'pretty' }}>{lede}</p>}
    </div>
  );
}

export function KLogin({ app, route }) {
  const { t } = useK();
  const next = route && route.next;
  const [kit, setKit] = React.useState('default');
  const [id, setId] = React.useState('');
  const [pw, setPw] = React.useState('');
  const [rem, setRem] = React.useState(true);
  const [err, setErr] = React.useState({});
  const setState = (k) => { setKit(k); setErr({}); if (k === 'error') { setId('ahmed.samir@gmail.com'); setPw('dots2025'); } else { setId(''); setPw(''); } };
  const submit = (e) => {
    e.preventDefault();
    const x = {};
    if (!id.trim()) x.id = t('Enter your mobile number or email.', 'أدخل رقم الموبايل أو البريد الإلكتروني.');
    else if (!kaMobileOk(id) && !kaEmailOk(id)) x.id = t('Use an Egyptian mobile (01X XXXX XXXX) or a valid email.', 'استخدم رقم موبايل مصري (01X XXXX XXXX) أو بريداً صحيحاً.');
    if (!pw) x.pw = t('Enter your password.', 'أدخل كلمة المرور.');
    setErr(x);
    if (Object.keys(x).length || kit === 'error') return;
    app.login(KA_USER, next || 'acc-overview');
  };
  return (
    <KAAuth image={0}>
      <KAKit value={kit} onChange={setState} tabs={[{ id: 'default', label: kM('Default', 'افتراضي') }, { id: 'error', label: kM('Wrong password', 'كلمة مرور خاطئة') }]} />
      <KAHead eyebrow={t('Welcome back', 'أهلاً بعودتك')} title={t('Log in to your account', 'تسجيل الدخول لحسابك')} lede={next === 'checkout' ? t('Log in to use your saved details — or continue as a guest.', 'سجّل الدخول لاستخدام بياناتك المحفوظة — أو تابع كضيف.') : null} />
      {kit === 'error' && <KAAlert icon="circle-alert" title={t('The mobile/email or password is incorrect.', 'رقم الموبايل/البريد أو كلمة المرور غير صحيحة.')}>{t('Check for typos, or reset your password by email. 4 attempts left before a 15-minute pause.', 'تأكد من الكتابة، أو أعد تعيين كلمة المرور عبر البريد. متبقٍ 4 محاولات قبل إيقاف مؤقت 15 دقيقة.')}</KAAlert>}
      <form onSubmit={submit} noValidate style={{ display: 'grid', gap: 16 }}>
        <Input label={t('Mobile number or email', 'رقم الموبايل أو البريد الإلكتروني')} icon="user" value={id} onChange={(e) => setId(e.target.value)} error={err.id} placeholder="01X XXXX XXXX · name@example.com" autoComplete="username" dir="ltr" />
        <div style={{ display: 'grid', gap: 8 }}>
          <KAPassword label={t('Password', 'كلمة المرور')} value={pw} onChange={setPw} error={err.pw} aria-invalid={kit === 'error' ? 'true' : undefined} autoComplete="current-password" />
        </div>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap' }}>
          <Checkbox label={t('Remember me', 'تذكرني')} checked={rem} onChange={setRem} />
          <KALink onClick={() => app.go('forgot')}>{t('Forgot password?', 'نسيت كلمة المرور؟')}</KALink>
        </div>
        <Button type="submit" size="lg" block iconEnd="arrow-right">{t('Log in', 'تسجيل الدخول')}</Button>
      </form>
      <div style={{ display: 'grid', gap: 14, paddingTop: 18, borderTop: '1px solid var(--border-1)' }}>
        <span style={{ font: '600 14px/1.5 var(--font-body)', color: 'var(--fg-2)', display: 'flex', gap: 6, flexWrap: 'wrap' }}>{t('New to Dots?', 'جديد في دوتس؟')} <KALink onClick={() => app.go('register', next ? { next } : {})}>{t('Create an account', 'أنشئ حساباً')}</KALink></span>
        {next === 'checkout' && <Button variant="outline" size="lg" block onClick={() => app.go('checkout')}>{t('Continue as guest', 'المتابعة كضيف')}</Button>}
        <span className="d-help">{t('Kit demo: any valid mobile/email and password logs you in.', 'للتجربة: أي موبايل/بريد صحيح وكلمة مرور تسجّل دخولك.')}</span>
      </div>
    </KAAuth>
  );
}

export function KRegister({ app, route }) {
  const { t } = useK();
  const next = route && route.next;
  const blank = { name: '', mobile: '', email: '', pw: '', terms: false };
  const [v, setV] = React.useState(blank);
  const [err, setErr] = React.useState({});
  const [kit, setKit] = React.useState('default');
  const set = (k) => (e) => setV((s) => ({ ...s, [k]: e && e.target ? e.target.value : e }));
  const validate = (d) => {
    const x = {};
    if (d.name.trim().split(/\s+/).length < 2) x.name = t('Enter your first and last name.', 'أدخل الاسم الأول واسم العائلة.');
    if (!kaMobileOk(d.mobile)) x.mobile = t('Enter an 11-digit Egyptian mobile starting with 010, 011, 012 or 015.', 'أدخل رقم موبايل مصري من 11 رقم يبدأ بـ 010 أو 011 أو 012 أو 015.');
    if (!kaEmailOk(d.email)) x.email = t('Enter a valid email — we send password reset links here.', 'أدخل بريداً صحيحاً — نرسل عليه روابط إعادة تعيين كلمة المرور.');
    if (!kaPwOk(d.pw)) x.pw = t('Use at least 8 characters, including a number.', 'استخدم 8 أحرف على الأقل، منها رقم.');
    if (!d.terms) x.terms = t('Please accept the terms to continue.', 'يرجى الموافقة على الشروط للمتابعة.');
    return x;
  };
  const setState = (k) => { setKit(k); if (k === 'errors') { const d = { name: 'Ahmed', mobile: '0101234', email: 'ahmed.samir@gmail', pw: 'dots', terms: false }; setV(d); setErr(validate(d)); } else { setV(blank); setErr({}); } };
  const submit = (e) => {
    e.preventDefault();
    const x = validate(v); setErr(x);
    if (Object.keys(x).length) return;
    const name = v.name.trim();
    app.login({ name, first: name.split(/\s+/)[0], mobile: v.mobile, email: v.email.trim() }, next || 'acc-overview', t('Account created', 'تم إنشاء الحساب'));
  };
  return (
    <KAAuth image={3}>
      <KAKit value={kit} onChange={setState} tabs={[{ id: 'default', label: kM('Default', 'افتراضي') }, { id: 'errors', label: kM('Validation errors', 'أخطاء التحقق') }]} />
      <KAHead eyebrow={t('Create account', 'حساب جديد')} title={t('Join Dots in under a minute', 'انضم لدوتس في أقل من دقيقة')} />
      <form onSubmit={submit} noValidate style={{ display: 'grid', gap: 16 }}>
        <Input label={t('Full name', 'الاسم بالكامل')} icon="user" value={v.name} onChange={set('name')} error={err.name} placeholder={t('e.g. Mona Adel', 'مثال: منى عادل')} autoComplete="name" />
        <Input label={t('Mobile number', 'رقم الموبايل')} icon="phone" value={v.mobile} onChange={set('mobile')} error={err.mobile} help={t('We confirm orders on WhatsApp.', 'نؤكد الطلبات على واتساب.')} placeholder="01X XXXX XXXX" inputMode="tel" autoComplete="tel" dir="ltr" />
        <Input label={t('Email', 'البريد الإلكتروني')} icon="mail" type="email" value={v.email} onChange={set('email')} error={err.email} placeholder="name@example.com" autoComplete="email" dir="ltr" />
        <KAPassword label={t('Password', 'كلمة المرور')} value={v.pw} onChange={set('pw')} error={err.pw} help={t('At least 8 characters, including a number.', '8 أحرف على الأقل، منها رقم.')} autoComplete="new-password" />
        <div style={{ display: 'grid', gap: 6 }}>
          <Checkbox checked={v.terms} onChange={set('terms')} aria-invalid={err.terms ? 'true' : undefined} label={<span style={{ fontWeight: 500, color: 'var(--fg-2)' }}>{t('I agree to the ', 'أوافق على ')}<b style={{ color: 'var(--fg-1)' }}>{t('Terms of use', 'شروط الاستخدام')}</b>{t(' and ', ' و')}<b style={{ color: 'var(--fg-1)' }}>{t('Privacy policy', 'سياسة الخصوصية')}</b></span>} />
          {err.terms && <span className="d-help d-help--error"><Icon name="circle-alert" size={14} />{err.terms}</span>}
        </div>
        <Button type="submit" size="lg" block iconEnd="arrow-right">{t('Create account', 'إنشاء الحساب')}</Button>
      </form>
      <span style={{ font: '600 14px/1.5 var(--font-body)', color: 'var(--fg-2)', display: 'flex', gap: 6, flexWrap: 'wrap', paddingTop: 18, borderTop: '1px solid var(--border-1)' }}>{t('Already have an account?', 'عندك حساب بالفعل؟')} <KALink onClick={() => app.go('login', next ? { next } : {})}>{t('Log in', 'سجّل الدخول')}</KALink></span>
    </KAAuth>
  );
}

function kaMask(e) { const [u, d] = e.split('@'); return (u ? u[0] + '•••' + (u.length > 2 ? u[u.length - 1] : '') : '') + '@' + (d || ''); }

export function KForgot({ app }) {
  const { t } = useK();
  const [st, setSt] = React.useState('form');
  const [email, setEmail] = React.useState('');
  const [err, setErr] = React.useState('');
  const [wait, setWait] = React.useState(0);
  React.useEffect(() => { if (!wait) return; const h = setTimeout(() => setWait(wait - 1), 1000); return () => clearTimeout(h); }, [wait]);
  const submit = (e) => { e.preventDefault(); if (!kaEmailOk(email)) { setErr(t('Enter the email on your Dots account.', 'أدخل البريد المسجل في حسابك.')); return; } setErr(''); setSt('sent'); setWait(60); };
  const kitSet = (k) => { setSt(k); if (k === 'sent') { setEmail(email || KA_USER.email); setWait(60); } };
  const shown = email || KA_USER.email;
  return (
    <KAAuth image={9}>
      <KAKit value={st} onChange={kitSet} tabs={[{ id: 'form', label: kM('Enter email', 'إدخال البريد') }, { id: 'sent', label: kM('Check your email', 'تحقق من بريدك') }]} />
      {st === 'form' ? (
        <>
          <KAHead icon="key-round" title={t('Forgot your password?', 'نسيت كلمة المرور؟')} lede={t('Enter the email on your account and we’ll send you a link to set a new one.', 'أدخل البريد المسجل في حسابك وسنرسل لك رابطاً لتعيين كلمة مرور جديدة.')} />
          <form onSubmit={submit} noValidate style={{ display: 'grid', gap: 16 }}>
            <Input label={t('Email', 'البريد الإلكتروني')} icon="mail" type="email" value={email} onChange={(e) => setEmail(e.target.value)} error={err} placeholder="name@example.com" autoComplete="email" dir="ltr" />
            <Button type="submit" size="lg" block>{t('Send reset link', 'إرسال رابط إعادة التعيين')}</Button>
          </form>
        </>
      ) : (
        <>
          <KAHead icon="mail-check" title={t('Check your email', 'تحقق من بريدك')} lede={<>{t('If an account uses ', 'إذا كان هناك حساب بالبريد ')}<b dir="ltr" style={{ color: 'var(--fg-1)' }}>{kaMask(shown)}</b>{t(', a reset link is on its way. It works once and expires in 30 minutes.', '، فرابط إعادة التعيين في طريقه إليك. يعمل مرة واحدة وينتهي خلال 30 دقيقة.')}</>} />
          <div style={{ display: 'grid', gap: 10 }}>
            <Button size="lg" block onClick={() => app.go('login')}>{t('Back to log in', 'العودة لتسجيل الدخول')}</Button>
            <Button size="lg" variant="outline" block disabled={wait > 0} onClick={() => { setWait(60); app.toast('success', t('Link sent again', 'تم إرسال الرابط مرة أخرى'), kaMask(shown)); }}>{wait > 0 ? t(`Resend link in ${wait}s`, `إعادة الإرسال بعد ${wait} ث`) : t('Resend link', 'إعادة إرسال الرابط')}</Button>
          </div>
          <div style={{ display: 'grid', gap: 8, font: '500 13px/1.55 var(--font-body)', color: 'var(--fg-2)' }}>
            <span>{t('Nothing after 5 minutes? Check spam or promotions.', 'لم يصلك شيء بعد 5 دقائق؟ راجع البريد المزعج أو العروض.')}</span>
            <span style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}><KALink onClick={() => setSt('form')} style={{ fontSize: 13 }}>{t('Use a different email', 'استخدم بريداً آخر')}</KALink> · <KALink onClick={() => app.quote()} style={{ fontSize: 13 }}>{t('Get help on WhatsApp', 'مساعدة عبر واتساب')}</KALink></span>
          </div>
          <span className="d-help" style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>{t('Kit demo:', 'للتجربة:')} <KALink onClick={() => app.go('reset')} style={{ fontSize: 12 }}>{t('open the reset link', 'افتح رابط إعادة التعيين')}</KALink></span>
        </>
      )}
    </KAAuth>
  );
}

export function KReset({ app }) {
  const { t } = useK();
  const [st, setSt] = React.useState('form');
  const [pw, setPw] = React.useState('');
  const [pw2, setPw2] = React.useState('');
  const [err, setErr] = React.useState({});
  const submit = (e) => {
    e.preventDefault();
    const x = {};
    if (!kaPwOk(pw)) x.pw = t('Use at least 8 characters, including a number.', 'استخدم 8 أحرف على الأقل، منها رقم.');
    if (!pw2) x.pw2 = t('Re-enter the new password.', 'أعد إدخال كلمة المرور الجديدة.');
    else if (pw2 !== pw) x.pw2 = t('Passwords don’t match.', 'كلمتا المرور غير متطابقتين.');
    setErr(x);
    if (!Object.keys(x).length) setSt('done');
  };
  return (
    <KAAuth image={27}>
      <KAKit value={st} onChange={setSt} tabs={[{ id: 'form', label: kM('New password', 'كلمة مرور جديدة') }, { id: 'done', label: kM('Success', 'تم بنجاح') }]} />
      {st === 'form' ? (
        <>
          <KAHead icon="lock" title={t('Set a new password', 'عيّن كلمة مرور جديدة')} lede={<>{t('For ', 'للحساب ')}<b dir="ltr" style={{ color: 'var(--fg-1)' }}>{KA_USER.email}</b></>} />
          <form onSubmit={submit} noValidate style={{ display: 'grid', gap: 16 }}>
            <KAPassword label={t('New password', 'كلمة المرور الجديدة')} value={pw} onChange={setPw} error={err.pw} help={t('At least 8 characters, including a number.', '8 أحرف على الأقل، منها رقم.')} autoComplete="new-password" />
            <KAPassword label={t('Confirm new password', 'تأكيد كلمة المرور الجديدة')} value={pw2} onChange={setPw2} error={err.pw2} autoComplete="new-password" />
            <Button type="submit" size="lg" block>{t('Update password', 'تحديث كلمة المرور')}</Button>
          </form>
        </>
      ) : (
        <>
          <KAHead icon="check" tone="success" title={t('Password updated', 'تم تحديث كلمة المرور')} lede={t('Use your new password next time you log in. For your security, we logged you out on other devices.', 'استخدم كلمة المرور الجديدة عند تسجيل الدخول. لأمانك، سجّلنا خروجك من الأجهزة الأخرى.')} />
          <Button size="lg" block iconEnd="arrow-right" onClick={() => app.go('login')}>{t('Log in', 'تسجيل الدخول')}</Button>
        </>
      )}
    </KAAuth>
  );
}

/* ---------- account area ---------- */
const KA_TABS = [
  { id: 'overview', label: kM('Overview', 'نظرة عامة'), icon: 'layout-dashboard' },
  { id: 'orders', label: kM('My orders', 'طلباتي'), icon: 'package' },
  { id: 'wishlist', label: kM('Wishlist', 'المفضلة'), icon: 'heart' },
  { id: 'addresses', label: kM('Addresses', 'العناوين'), icon: 'map-pin' },
  { id: 'profile', label: kM('Profile & password', 'الملف الشخصي وكلمة المرور'), icon: 'user' },
];

function KASide({ app, cur, counts }) {
  const { t, tx } = useLocale();
  const u = app.user;
  return (
    <aside style={{ position: 'sticky', top: 24, display: 'grid', gap: 16 }}>
      <div style={{ display: 'flex', gap: 12, alignItems: 'center', padding: 16, background: 'var(--white)', border: '1px solid var(--border-1)', borderRadius: 'var(--radius-lg)' }}>
        <span className="d-nav__acct-av" style={{ width: 48, height: 48, fontSize: 18, flex: 'none' }}>{tx(u.first).charAt(0)}</span>
        <span style={{ display: 'grid', gap: 3, minWidth: 0 }}>
          <b style={{ font: '800 15px/1.3 var(--font-body)' }}>{tx(u.name)}</b>
          <small dir="ltr" style={{ font: '500 13px var(--font-body)', color: 'var(--fg-3)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', textAlign: 'start' }}>{u.email}</small>
        </span>
      </div>
      <nav aria-label={t('Account', 'الحساب')} style={{ background: 'var(--white)', border: '1px solid var(--border-1)', borderRadius: 'var(--radius-lg)', padding: 8, display: 'grid', gap: 2 }}>
        {KA_TABS.map((x) => (
          <button key={x.id} type="button" className={cx('d-mega__cat', cur === x.id && 'd-mega__cat--on')} aria-current={cur === x.id ? 'page' : undefined} onClick={() => app.go('acc-' + x.id)}>
            <Icon name={x.icon} size={20} /><span>{tx(x.label)}</span>
            {counts[x.id] != null && <span style={{ marginInlineStart: 'auto', font: '700 12px var(--font-body)', color: 'var(--fg-3)' }}>{counts[x.id]}</span>}
          </button>
        ))}
        <span style={{ height: 1, background: 'var(--border-1)', margin: '6px 4px' }} />
        <button type="button" className="d-mega__cat" onClick={() => app.go('logout')}><Icon name="log-out" size={20} /><span>{t('Log out', 'تسجيل الخروج')}</span></button>
      </nav>
      <div style={{ padding: 16, borderRadius: 'var(--radius-lg)', background: 'var(--wa-100)', display: 'grid', gap: 8 }}>
        <b style={{ font: '800 14px/1.3 var(--font-body)' }}>{t('Question about an order?', 'سؤال عن طلب؟')}</b>
        <span style={{ font: '500 13px/1.5 var(--font-body)', color: 'var(--fg-2)' }}>{t('We reply on WhatsApp within 30 minutes during working hours.', 'نرد على واتساب خلال 30 دقيقة في مواعيد العمل.')}</span>
        <div><Button variant="whatsapp" size="sm" onClick={() => app.quote()}>{t('WhatsApp us', 'راسلنا واتساب')}</Button></div>
      </div>
    </aside>
  );
}

export function KAccount({ app, route }) {
  const { t, tx, compact, cols } = useK();
  if (!app.user) return <KLogin app={app} route={{ next: 'acc-' + (route.tab || 'overview') }} />;
  const tab = route.tab || 'overview';
  const cur = tab === 'order' ? 'orders' : tab;
  const meta = KA_TABS.find((x) => x.id === cur) || KA_TABS[0];
  const counts = { orders: KA_ORDERS.length, wishlist: app.wish.length, addresses: app.addresses.length };
  const order = tab === 'order' ? (KA_ORDERS.find((o) => o.no === route.id) || KA_ORDERS[0]) : null;
  const crumbs = [{ label: { en: 'Home', ar: 'الرئيسية' }, onClick: () => app.go('home') }, { label: { en: 'My account', ar: 'حسابي' }, onClick: cur !== 'overview' ? () => app.go('acc-overview') : undefined }];
  if (cur !== 'overview') crumbs.push({ label: meta.label, onClick: order ? () => app.go('acc-orders') : undefined });
  if (order) crumbs.push({ label: order.no });
  const body = { overview: KAOverview, orders: KAOrders, order: KAOrderDetail, wishlist: KAWishlist, addresses: KAAddresses, profile: KAProfile }[tab] || KAOverview;
  const Body = body;
  return (
    <div style={{ background: 'var(--bg-subtle)' }}>
      <div className="d-container" style={{ paddingBlock: compact ? '16px 40px' : '28px 80px', display: 'grid', gap: compact ? 14 : 24 }}>
        <Breadcrumbs items={crumbs} />
        {compact && (
          <div className="d-acc-tabs" style={{ marginInline: -16, overflowX: 'auto', scrollbarWidth: 'none', borderBottom: '1px solid var(--border-1)', background: 'var(--white)' }}>
            <Tabs value={cur} onChange={(id) => app.go(id === 'logout' ? 'logout' : 'acc-' + id)} style={{ paddingInline: 16, width: 'max-content', minWidth: '100%', borderBottom: 0, flexWrap: 'nowrap' }}
              tabs={[...KA_TABS.map((x) => ({ id: x.id, label: x.id === 'profile' ? kM('Profile', 'الملف الشخصي') : x.label, count: counts[x.id] })), { id: 'logout', label: kM('Log out', 'خروج') }]} />
          </div>
        )}
        <div style={{ display: 'grid', gridTemplateColumns: cols('272px minmax(0,1fr)', 'minmax(0,1fr)'), gap: compact ? 16 : 40, alignItems: 'start' }}>
          {!compact && <KASide app={app} cur={cur} counts={counts} />}
          <div style={{ display: 'grid', gap: compact ? 16 : 24, minWidth: 0 }}>
            {!order && cur !== 'overview' && <h1 className="t-h2" style={{ margin: 0, fontSize: compact ? 'var(--fs-h2-m)' : undefined, lineHeight: compact ? 'var(--lh-h2-m)' : undefined }}>{tx(meta.label)}</h1>}
            <Body app={app} o={order} />
          </div>
        </div>
      </div>
    </div>
  );
}

function KAOverview({ app }) {
  const { t, tx, compact, cols, price } = useK();
  const o = KA_ORDERS[0]; const st = kaStatus(o); const tot = kaTotals(o);
  const wl = app.wish.map((id) => K_PRODUCTS.find((p) => p.id === id)).filter(Boolean);
  const tiles = [
    ['acc-orders', 'package', t('My orders', 'طلباتي'), t(`${KA_ORDERS.length} orders`, `${KA_ORDERS.length} طلبات`)],
    ['acc-addresses', 'map-pin', t('Addresses', 'العناوين'), t(`${app.addresses.length} saved`, `${app.addresses.length} محفوظة`)],
    ['acc-profile', 'user', t('Profile & password', 'الملف وكلمة المرور'), t('Name, mobile, email', 'الاسم، الموبايل، البريد')],
    ['quote', 'message-circle', t('WhatsApp support', 'دعم واتساب'), t('Reply within 30 min', 'رد خلال 30 دقيقة')],
  ];
  return (
    <>
      <KReveal style={{ display: 'grid', gap: 8 }}>
        {app.user.since && <Eyebrow>{tx(app.user.since)}</Eyebrow>}
        <h1 className="t-h2" style={{ margin: 0, fontSize: compact ? 'var(--fs-h2-m)' : undefined, lineHeight: compact ? 'var(--lh-h2-m)' : undefined }}>{t('Welcome back, ', 'أهلاً بعودتك يا ')}{tx(app.user.first)}</h1>
        <p style={{ margin: 0, font: '500 15px/1.6 var(--font-body)', color: 'var(--fg-2)' }}>{t('Your latest order is on its way — the courier calls 1 hour before arriving.', 'طلبك الأخير في الطريق — المندوب يتصل قبل الوصول بساعة.')}</p>
      </KReveal>
      <div style={{ display: 'grid', gridTemplateColumns: cols('minmax(0,1.45fr) minmax(0,1fr)', 'minmax(0,1fr)'), gap: compact ? 16 : 20, alignItems: 'start' }}>
        <KReveal delay={60}>
          <KOBox style={{ padding: compact ? 18 : 24, gap: 18 }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap' }}>
              <div style={{ display: 'grid', gap: 6 }}>
                <Eyebrow>{t('Latest order', 'آخر طلب')}</Eyebrow>
                <b style={{ font: '500 20px/1 var(--font-mono)' }} dir="ltr">{o.no}</b>
                <span style={{ font: '600 13px var(--font-body)', color: 'var(--fg-3)' }}>{tx(o.date)} · {tot.n} {t('items', 'منتجات')} · {price(tot.total)}</span>
              </div>
              <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', alignSelf: 'start' }}><Badge tone={st[0]} dot>{tx(st[1])}</Badge><Badge tone={o.payBadge[0]} dot>{tx(o.payBadge[1])}</Badge></div>
            </div>
            <KAThumbs items={o.items} />
            <Progress value={Math.round((o.status / 4) * 100)} steps={KO_STEPS} current={o.status} />
            <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap' }}>
              <Button iconEnd="arrow-right" onClick={() => app.go('acc-order', { id: o.no })} block={compact}>{t('View order', 'عرض الطلب')}</Button>
              <Button variant="outline" icon="package-search" onClick={() => app.go('track')} block={compact}>{t('Track delivery', 'تتبع التوصيل')}</Button>
            </div>
          </KOBox>
        </KReveal>
        <KReveal delay={120}>
          <KOBox style={{ padding: compact ? 18 : 24, gap: 14 }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12 }}>
              <h2 style={{ margin: 0, font: '600 20px/1.25 var(--font-display)', letterSpacing: 'var(--tracking-heading)' }}>{t('Wishlist', 'المفضلة')} <span style={{ font: '700 14px var(--font-body)', color: 'var(--fg-3)' }}>{wl.length}</span></h2>
              {wl.length > 0 && <KALink onClick={() => app.go('acc-wishlist')} style={{ fontSize: 13 }}>{t('View all', 'عرض الكل')}</KALink>}
            </div>
            {wl.length ? wl.slice(0, 3).map((p) => (
              <button key={p.id} type="button" onClick={() => app.go('product', { id: p.id })} style={{ display: 'grid', gridTemplateColumns: '56px minmax(0,1fr) auto', gap: 12, alignItems: 'center', border: 0, borderTop: '1px solid var(--border-1)', paddingTop: 12, background: 'none', textAlign: 'start', cursor: 'pointer', color: 'var(--fg-1)' }}>
                <ImageFrame src={p.image} contain style={{ width: 56, height: 56, borderRadius: 8, border: '1px solid var(--border-1)', background: 'var(--white)' }} />
                <span style={{ display: 'grid', gap: 3, minWidth: 0 }}><span className="d-pcard__brand">{p.brand}</span><span style={{ font: '700 13px/1.35 var(--font-body)', overflow: 'hidden', display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical' }}>{tx(p.name)}</span></span>
                <b style={{ font: '800 13px var(--font-body)', whiteSpace: 'nowrap' }}>{p.price != null ? price(p.price) : t('On request', 'عند الطلب')}</b>
              </button>
            )) : (
              <span style={{ font: '500 14px/1.55 var(--font-body)', color: 'var(--fg-2)' }}>{t('Tap the heart on any product to save it here.', 'اضغط على القلب في أي منتج لحفظه هنا.')} <KALink onClick={() => app.go('products')} style={{ fontSize: 14 }}>{t('Browse products', 'تصفح المنتجات')}</KALink></span>
            )}
          </KOBox>
        </KReveal>
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: cols('repeat(4, minmax(0,1fr))', 'repeat(2, minmax(0,1fr))'), gap: compact ? 10 : 16 }}>
        {tiles.map(([k, ic, l, d], i) => (
          <KReveal key={k} delay={i * 50}>
            <button type="button" className="d-card" onClick={() => app.go(k)} style={{ width: '100%', height: '100%', padding: compact ? 14 : 18, display: 'grid', gap: 10, justifyItems: 'start', textAlign: 'start', cursor: 'pointer', font: 'inherit', color: 'var(--fg-1)' }}>
              <span className="d-nav__mi-ico" style={k === 'quote' ? { background: 'var(--wa-100)', color: 'var(--wa-600)' } : undefined}><Icon name={ic} size={20} /></span>
              <span style={{ display: 'grid', gap: 3 }}><b style={{ font: '800 14px/1.3 var(--font-body)' }}>{l}</b><span style={{ font: '500 13px/1.4 var(--font-body)', color: 'var(--fg-3)' }}>{d}</span></span>
            </button>
          </KReveal>
        ))}
      </div>
    </>
  );
}

function KAOrderRow({ app, o }) {
  const { t, tx, compact, price } = useK();
  const st = kaStatus(o); const tot = kaTotals(o);
  return (
    <article style={{ border: '1px solid var(--border-1)', borderRadius: 'var(--radius-lg)', background: 'var(--white)', padding: compact ? 16 : 20, display: 'grid', gap: 14 }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap', alignItems: 'start' }}>
        <div style={{ display: 'grid', gap: 5 }}>
          <b style={{ font: '500 17px/1 var(--font-mono)' }} dir="ltr">{o.no}</b>
          <span style={{ font: '600 13px var(--font-body)', color: 'var(--fg-3)' }}>{t('Placed', 'تاريخ الطلب')} {tx(o.date)} · {tot.n} {t('items', 'منتجات')}</span>
        </div>
        <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}><Badge tone={st[0]} dot>{tx(st[1])}</Badge><Badge tone={o.payBadge[0]} dot>{tx(o.payBadge[1])}</Badge></div>
      </div>
      <div style={{ display: 'flex', alignItems: 'center', gap: 16, flexWrap: 'wrap', paddingTop: 14, borderTop: '1px solid var(--border-1)' }}>
        <KAThumbs items={o.items} size={compact ? 44 : 52} max={compact ? 3 : 4} />
        <span style={{ marginInlineStart: 'auto', display: 'grid', gap: 3, textAlign: 'end' }}><span style={{ font: '600 12px var(--font-body)', color: 'var(--fg-3)' }}>{t('Total', 'الإجمالي')}</span><b style={{ font: '800 16px/1 var(--font-body)' }}>{price(tot.total)}</b></span>
        <Button variant="outline" size="sm" iconEnd="chevron-right" onClick={() => app.go('acc-order', { id: o.no })} block={compact}>{t('View order', 'عرض الطلب')}</Button>
      </div>
    </article>
  );
}

function KAOrders({ app }) {
  const { t } = useK();
  const [kit, setKit] = React.useState('list');
  return (
    <>
      <KAKit value={kit} onChange={setKit} tabs={[{ id: 'list', label: kM('Orders', 'طلبات') }, { id: 'empty', label: kM('Empty', 'فارغة') }]} />
      {kit === 'list' ? (
        <div style={{ display: 'grid', gap: 12 }}>{KA_ORDERS.map((o, i) => <KReveal key={o.no} delay={i * 50}><KAOrderRow app={app} o={o} /></KReveal>)}</div>
      ) : (
        <KAEmpty icon="package" title={t('No orders yet', 'لا توجد طلبات بعد')} text={t('When you order, it shows up here with live status — including guest orders placed with this mobile number.', 'عند الطلب سيظهر هنا بحالته المباشرة — بما فيها الطلبات كضيف بنفس رقم الموبايل.')} action={<Button iconEnd="arrow-right" onClick={() => app.go('products')}>{t('Browse products', 'تصفح المنتجات')}</Button>} />
      )}
    </>
  );
}

function KAOrderDetail({ app, o }) {
  const { t, tx, compact, cols, price } = useK();
  const st = kaStatus(o); const tot = kaTotals(o); const pay = KO_PAY[o.pay];
  const a = app.addresses.find((x) => x.id === o.addr) || KA_ADDR.find((x) => x.id === o.addr) || KA_ADDR[0];
  return (
    <>
      <div style={{ display: 'grid', gap: 14 }}>
        <KALink onClick={() => app.go('acc-orders')} style={{ justifySelf: 'start', display: 'inline-flex', alignItems: 'center', gap: 6, textDecoration: 'none', fontSize: 13 }}><Icon name="arrow-left" size={16} />{t('All orders', 'كل الطلبات')}</KALink>
        <div style={{ display: 'flex', justifyContent: 'space-between', gap: 16, flexWrap: 'wrap', alignItems: 'end' }}>
          <div style={{ display: 'grid', gap: 10 }}>
            <h1 className="t-h2" style={{ margin: 0, fontSize: compact ? 'var(--fs-h2-m)' : undefined, lineHeight: compact ? 'var(--lh-h2-m)' : undefined }}>{t('Order', 'طلب')} <span dir="ltr" style={{ fontFamily: 'var(--font-mono)', fontWeight: 500 }}>{o.no}</span></h1>
            <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', alignItems: 'center' }}>
              <span style={{ font: '600 14px var(--font-body)', color: 'var(--fg-2)' }}>{t('Placed', 'تاريخ الطلب')} {tx(o.times[0])}</span>
              <Badge tone={st[0]} dot>{tx(st[1])}</Badge><Badge tone={o.payBadge[0]} dot>{tx(o.payBadge[1])}</Badge>
            </div>
          </div>
          <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap', width: compact ? '100%' : undefined }}>
            <Button icon="rotate-ccw" block={compact} onClick={() => app.addLines(o.items, o.no)}>{t('Reorder', 'إعادة الطلب')}</Button>
            <Button variant="whatsapp" block={compact} onClick={() => app.quote()}>{t('Help with this order', 'مساعدة في هذا الطلب')}</Button>
          </div>
        </div>
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: cols('minmax(0,1fr) 340px', 'minmax(0,1fr)'), gap: compact ? 16 : 20, alignItems: 'start' }}>
        <div style={{ display: 'grid', gap: compact ? 16 : 20 }}>
          <KOBox title={t('Order status', 'حالة الطلب')} style={{ padding: compact ? 18 : 24 }}>
            {o.cancelled == null && <Progress value={Math.round((o.status / 4) * 100)} steps={KO_STEPS} current={o.status} />}
            <KOTimeline o={o} />
          </KOBox>
          <KOBox title={`${t('Items', 'المنتجات')} · ${tot.n}`} style={{ padding: compact ? 18 : 24, gap: 0 }}>
            {o.items.map((it, i) => (
              <div key={it.id} style={{ display: 'grid', gridTemplateColumns: (compact ? '56px' : '72px') + ' minmax(0,1fr) auto', gap: 14, alignItems: 'center', paddingBlock: 14, borderTop: i ? '1px solid var(--border-1)' : 0 }}>
                <ImageFrame src={it.image} contain style={{ width: compact ? 56 : 72, height: compact ? 56 : 72, borderRadius: 8, border: '1px solid var(--border-1)', background: 'var(--white)' }} />
                <div style={{ display: 'grid', gap: 3, minWidth: 0 }}>
                  <a href="#" onClick={(e) => { e.preventDefault(); app.go('product', { id: it.pid }); }} style={{ font: '700 14px/1.35 var(--font-body)', color: 'var(--fg-1)', textDecoration: 'none' }}>{tx(it.name)}</a>
                  <span style={{ font: '500 13px var(--font-body)', color: 'var(--fg-2)' }}>{tx(it.variant)} · {t('Qty', 'الكمية')} {it.qty} × {price(it.price)}</span>
                  <span style={{ font: '500 12px var(--font-mono)', color: 'var(--fg-3)' }} dir="ltr">{it.sku}</span>
                </div>
                <b style={{ font: '800 14px var(--font-body)', whiteSpace: 'nowrap' }}>{price(it.price * it.qty)}</b>
              </div>
            ))}
          </KOBox>
        </div>
        <div style={{ display: 'grid', gap: compact ? 16 : 20 }}>
          <KOBox title={t('Payment summary', 'ملخص الدفع')} style={{ padding: compact ? 18 : 24 }}>
            <div className="d-totals" style={{ borderTop: 0, paddingTop: 0 }}>
              <div><span>{t('Subtotal', 'الإجمالي الفرعي')}</span><b>{price(tot.sub)}</b></div>
              {tot.disc > 0 && <div><span>{t('Discount', 'الخصم')}</span><b style={{ color: 'var(--success-600)' }}>−{price(tot.disc)}</b></div>}
              <div><span>{t('Delivery', 'التوصيل')}</span><b>{tot.del ? price(tot.del) : t('Free', 'مجاناً')}</b></div>
              <div className="d-totals__grand"><span>{t('Total', 'الإجمالي')}</span><b>{price(tot.total)}</b></div>
              <span style={{ font: '500 12px var(--font-body)', color: 'var(--fg-3)' }}>{t('Prices include 14% VAT', 'الأسعار شاملة ضريبة القيمة المضافة 14%')}</span>
            </div>
          </KOBox>
          <KOBox title={t('Delivery address', 'عنوان التوصيل')} style={{ padding: compact ? 18 : 24, gap: 10 }}>
            <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8, font: '800 14px var(--font-body)' }}><Icon name={KA_LABEL[a.label].icon} size={18} />{tx(KA_LABEL[a.label].label)} · {tx(a.name)}</span>
            <span style={{ font: '500 14px/1.55 var(--font-body)', color: 'var(--fg-2)' }}>{tx(kaAddrText(a))}</span>
            <span dir="ltr" style={{ font: '500 14px var(--font-body)', color: 'var(--fg-2)', textAlign: 'start' }}>{a.mobile}</span>
          </KOBox>
          <KOBox title={t('Payment method', 'طريقة الدفع')} style={{ padding: compact ? 18 : 24 }}>
            <div style={{ display: 'flex', gap: 12, alignItems: 'start' }}>
              <span style={{ width: 40, height: 40, borderRadius: '50%', background: 'var(--ink-50)', display: 'grid', placeItems: 'center', flex: 'none' }}><Icon name={pay.icon} size={20} /></span>
              <span style={{ display: 'grid', gap: 3 }}><b style={{ font: '800 14px/1.35 var(--font-body)' }}>{tx(pay.title)}</b><span style={{ font: '500 13px/1.5 var(--font-body)', color: 'var(--fg-2)' }}>{tx(pay.sub)}</span></span>
            </div>
          </KOBox>
        </div>
      </div>
    </>
  );
}

function KAWishlist({ app }) {
  const { t, compact } = useK();
  const [kit, setKit] = React.useState('items');
  const list = kit === 'empty' ? [] : app.wish.map((id) => K_PRODUCTS.find((p) => p.id === id)).filter(Boolean);
  const n = compact ? 2 : 3;
  const buyable = list.filter((p) => p.price != null);
  return (
    <>
      <KAKit value={kit} onChange={setKit} tabs={[{ id: 'items', label: kM('Saved items', 'عناصر محفوظة') }, { id: 'empty', label: kM('Empty', 'فارغة') }]} />
      {list.length ? (
        <>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap' }}>
            <span style={{ font: '600 14px var(--font-body)', color: 'var(--fg-2)' }}>{list.length} {t('saved items', 'عناصر محفوظة')}</span>
            {buyable.length > 1 && <Button variant="outline" size="sm" icon="shopping-bag" onClick={() => app.addLines(buyable.map((p) => ({ pid: p.id, name: p.name, variant: p.variants, price: p.price, qty: 1 })))}>{t('Add all to cart', 'أضف الكل للسلة')}</Button>}
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: `repeat(${n}, minmax(0,1fr))`, gap: compact ? 12 : 20 }}>
            {list.map((p, i) => (
              <KReveal key={p.id} delay={(i % n) * 60} style={{ display: 'grid', gap: 8, alignContent: 'start' }}>
                <ProductCard product={p} saved onSave={app.toggleWish} onOpen={() => app.go('product', { id: p.id })} onAdd={() => app.add(p)} onQuote={() => app.quote(p)} />
                <Button variant="ghost" size="sm" icon="trash-2" onClick={() => app.toggleWish(p)}>{t('Remove', 'إزالة')}</Button>
              </KReveal>
            ))}
          </div>
        </>
      ) : (
        <KAEmpty icon="heart" title={t('Your wishlist is empty', 'المفضلة فارغة')} text={t('Tap the heart on any product to save it for later — handy when you’re planning a room or comparing fittings.', 'اضغط على القلب في أي منتج لحفظه لوقت لاحق — مفيد عند تخطيط غرفة أو مقارنة الوحدات.')} action={<Button iconEnd="arrow-right" onClick={() => app.go('products')}>{t('Browse products', 'تصفح المنتجات')}</Button>} />
      )}
    </>
  );
}

function KAAddrForm({ app, init, onClose, onSave }) {
  const { t, tx, lang } = useK();
  const [v, setV] = React.useState(() => init ? { label: init.label, name: kaV(init.name, lang), mobile: init.mobile, gov: init.gov, city: kaV(init.city, lang), street: kaV(init.street, lang), floor: init.floor, landmark: kaV(init.landmark, lang), def: !!init.def } : { label: 'home', name: tx(app.user.name), mobile: app.user.mobile, gov: 0, city: '', street: '', floor: '', landmark: '', def: !app.addresses.length });
  const [err, setErr] = React.useState({});
  const set = (k) => (e) => setV((s) => ({ ...s, [k]: e && e.target ? e.target.value : e }));
  const save = () => {
    const x = {};
    if (!v.name.trim()) x.name = t('Enter the recipient’s name.', 'أدخل اسم المستلم.');
    if (!kaMobileOk(v.mobile)) x.mobile = t('Enter an 11-digit Egyptian mobile.', 'أدخل رقم موبايل مصري من 11 رقم.');
    if (!v.city.trim()) x.city = t('Enter the city or district.', 'أدخل المدينة أو الحي.');
    if (!v.street.trim()) x.street = t('Enter the street and building number.', 'أدخل الشارع ورقم العقار.');
    if (!v.floor.trim()) x.floor = t('Enter floor and apartment.', 'أدخل الدور والشقة.');
    setErr(x);
    if (!Object.keys(x).length) onSave({ ...(init || {}), ...v, gov: Number(v.gov), id: init ? init.id : 'a' + Date.now() });
  };
  return (
    <>
      <div className="d-overlay" onClick={onClose} />
      <aside className="d-drawer" role="dialog" aria-modal="true" aria-label={init ? t('Edit address', 'تعديل العنوان') : t('Add address', 'إضافة عنوان')}>
        <div className="d-drawer__head"><Icon name="map-pin" size={20} /><h3>{init ? t('Edit address', 'تعديل العنوان') : t('Add a new address', 'إضافة عنوان جديد')}</h3><IconButton icon="x" label={t('Close', 'إغلاق')} onClick={onClose} style={{ marginInlineStart: 'auto' }} /></div>
        <div className="d-drawer__body" style={{ paddingBlock: 20, display: 'grid', gap: 16, alignContent: 'start' }}>
          <div className="d-field"><span className="d-label">{t('Label', 'التسمية')}</span><Tabs variant="pill" value={v.label} onChange={set('label')} tabs={Object.keys(KA_LABEL).map((k) => ({ id: k, label: KA_LABEL[k].label }))} /></div>
          <Input label={t('Recipient name', 'اسم المستلم')} value={v.name} onChange={set('name')} error={err.name} />
          <Input label={t('Mobile number', 'رقم الموبايل')} value={v.mobile} onChange={set('mobile')} error={err.mobile} placeholder="01X XXXX XXXX" inputMode="tel" dir="ltr" />
          <Select label={t('Governorate', 'المحافظة')} value={v.gov} onChange={set('gov')} options={KA_GOV.map((g, i) => ({ value: i, label: lang === 'ar' ? g[1] : g[0] }))} />
          <Input label={t('City / district', 'المدينة / الحي')} value={v.city} onChange={set('city')} error={err.city} placeholder={t('e.g. New Cairo, 5th Settlement', 'مثال: التجمع الخامس')} />
          <Input label={t('Street & building', 'الشارع والعقار')} value={v.street} onChange={set('street')} error={err.street} placeholder={t('Street, building no.', 'الشارع، رقم العقار')} />
          <Input label={t('Floor / apartment', 'الدور / الشقة')} value={v.floor} onChange={set('floor')} error={err.floor} placeholder="3 / 12" />
          <Input label={t('Landmark', 'علامة مميزة')} optional value={v.landmark} onChange={set('landmark')} placeholder={t('Near…', 'بجوار…')} />
          <Checkbox label={t('Use as my default address', 'استخدمه كعنوان افتراضي')} checked={v.def} onChange={set('def')} />
        </div>
        <div className="d-drawer__foot">
          <Button size="lg" block onClick={save}>{init ? t('Save changes', 'حفظ التغييرات') : t('Save address', 'حفظ العنوان')}</Button>
          <Button variant="ghost" block onClick={onClose}>{t('Cancel', 'إلغاء')}</Button>
        </div>
      </aside>
    </>
  );
}

function KAAddresses({ app }) {
  const { t, tx, compact, cols, lang } = useK();
  const [kit, setKit] = React.useState('list');
  const [edit, setEdit] = React.useState(null);
  const [del, setDel] = React.useState(null);
  const list = kit === 'empty' ? [] : app.addresses;
  const save = (a) => {
    app.setAddresses((xs) => { let out = xs.some((x) => x.id === a.id) ? xs.map((x) => (x.id === a.id ? a : x)) : [...xs, a]; if (a.def) out = out.map((x) => ({ ...x, def: x.id === a.id })); if (!out.some((x) => x.def) && out.length) out[0] = { ...out[0], def: true }; return out; });
    app.toast('success', edit === 'new' ? t('Address saved', 'تم حفظ العنوان') : t('Address updated', 'تم تحديث العنوان'), tx(KA_LABEL[a.label].label));
    setEdit(null); setKit('list');
  };
  const remove = (a) => {
    app.setAddresses((xs) => { const out = xs.filter((x) => x.id !== a.id); if (a.def && out.length) out[0] = { ...out[0], def: true }; return out; });
    app.toast('info', t('Address deleted', 'تم حذف العنوان'), tx(KA_LABEL[a.label].label));
    setDel(null);
  };
  const makeDef = (a) => { app.setAddresses((xs) => xs.map((x) => ({ ...x, def: x.id === a.id }))); app.toast('success', t('Default address updated', 'تم تغيير العنوان الافتراضي'), tx(KA_LABEL[a.label].label)); };
  return (
    <>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 12, flexWrap: 'wrap' }}>
        <KAKit value={kit} onChange={setKit} tabs={[{ id: 'list', label: kM('Saved', 'محفوظة') }, { id: 'empty', label: kM('Empty', 'فارغة') }]} />
        {list.length > 0 && <Button icon="plus" onClick={() => setEdit('new')} block={compact}>{t('Add address', 'إضافة عنوان')}</Button>}
      </div>
      {list.length ? (
        <div style={{ display: 'grid', gridTemplateColumns: cols('repeat(2, minmax(0,1fr))', 'minmax(0,1fr)'), gap: compact ? 12 : 20 }}>
          {list.map((a, i) => (
            <KReveal key={a.id} delay={i * 60}>
              <article style={{ height: '100%', boxSizing: 'border-box', border: a.def ? '1.5px solid var(--ink-900)' : '1px solid var(--border-1)', borderRadius: 'var(--radius-lg)', background: 'var(--white)', padding: compact ? 18 : 22, display: 'grid', gap: 14, alignContent: 'start' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                  <span className="d-nav__mi-ico" style={{ width: 36, height: 36 }}><Icon name={KA_LABEL[a.label].icon} size={18} /></span>
                  <b style={{ font: '800 16px var(--font-body)' }}>{tx(KA_LABEL[a.label].label)}</b>
                  {a.def && <Badge tone="accent">{t('Default', 'الافتراضي')}</Badge>}
                </div>
                <dl style={{ margin: 0, display: 'grid', gap: 8, font: '500 14px/1.5 var(--font-body)', color: 'var(--fg-2)' }}>
                  <div><b style={{ color: 'var(--fg-1)', fontWeight: 800 }}>{tx(a.name)}</b> · <span dir="ltr">{a.mobile}</span></div>
                  {[[t('Governorate', 'المحافظة'), KA_GOV[a.gov][lang === 'ar' ? 1 : 0]], [t('City / district', 'المدينة / الحي'), tx(a.city)], [t('Street & building', 'الشارع والعقار'), tx(a.street)], [t('Floor / apartment', 'الدور / الشقة'), a.floor], [t('Landmark', 'علامة مميزة'), tx(a.landmark)]].filter((r) => r[1]).map(([k, val]) => (
                    <div key={k} style={{ display: 'grid', gridTemplateColumns: '128px minmax(0,1fr)', gap: 10 }}><dt style={{ font: '600 12px/1.6 var(--font-body)', color: 'var(--fg-3)' }}>{k}</dt><dd style={{ margin: 0, color: 'var(--fg-1)' }}>{val}</dd></div>
                  ))}
                </dl>
                <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap', paddingTop: 12, borderTop: '1px solid var(--border-1)', marginTop: 'auto' }}>
                  <Button variant="ghost" size="sm" icon="pencil" onClick={() => setEdit(a)}>{t('Edit', 'تعديل')}</Button>
                  <Button variant="ghost" size="sm" icon="trash-2" onClick={() => setDel(a)}>{t('Delete', 'حذف')}</Button>
                  {!a.def && <Button variant="ghost" size="sm" icon="circle-check" onClick={() => makeDef(a)} style={{ marginInlineStart: 'auto' }}>{t('Set as default', 'اجعله افتراضياً')}</Button>}
                </div>
              </article>
            </KReveal>
          ))}
          <button type="button" onClick={() => setEdit('new')} style={{ minHeight: 180, border: '1.5px dashed var(--border-2)', borderRadius: 'var(--radius-lg)', background: 'transparent', display: 'grid', placeItems: 'center', alignContent: 'center', gap: 10, cursor: 'pointer', font: '800 14px var(--font-body)', color: 'var(--fg-1)' }}>
            <span className="d-nav__mi-ico"><Icon name="plus" size={20} /></span>{t('Add a new address', 'إضافة عنوان جديد')}
          </button>
        </div>
      ) : (
        <KAEmpty icon="map-pin" title={t('No saved addresses', 'لا توجد عناوين محفوظة')} text={t('Save your home, office or site address once and pick it in one tap at checkout.', 'احفظ عنوان المنزل أو المكتب أو الموقع مرة واحدة واختره بضغطة عند الدفع.')} action={<Button icon="plus" onClick={() => setEdit('new')}>{t('Add address', 'إضافة عنوان')}</Button>} />
      )}
      {edit && <KAAddrForm app={app} init={edit === 'new' ? null : edit} onClose={() => setEdit(null)} onSave={save} />}
      {del && (
        <>
          <div className="d-overlay" onClick={() => setDel(null)} />
          <div role="alertdialog" aria-modal="true" aria-labelledby="ka-del-t" style={{ position: 'fixed', zIndex: 'var(--z-drawer)', top: '50%', left: '50%', transform: 'translate(-50%, -50%)', width: 'min(420px, calc(100% - 32px))', boxSizing: 'border-box', background: 'var(--white)', borderRadius: 'var(--radius-lg)', boxShadow: 'var(--shadow-lg)', padding: 24, display: 'grid', gap: 14, animation: 'd-fade-in var(--dur-base) var(--ease-out)' }}>
            <span style={{ width: 48, height: 48, borderRadius: '50%', background: 'var(--error-100)', color: 'var(--error-600)', display: 'grid', placeItems: 'center' }}><Icon name="trash-2" size={22} /></span>
            <h3 id="ka-del-t" style={{ margin: 0, font: '600 20px/1.3 var(--font-display)' }}>{t('Delete this address?', 'حذف هذا العنوان؟')}</h3>
            <p style={{ margin: 0, font: '500 14px/1.55 var(--font-body)', color: 'var(--fg-2)' }}><b style={{ color: 'var(--fg-1)' }}>{tx(KA_LABEL[del.label].label)}</b> · {tx(kaAddrText(del))}. {t('Orders already placed aren’t affected.', 'الطلبات السابقة لن تتأثر.')}</p>
            <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', flexWrap: 'wrap' }}>
              <Button variant="outline" onClick={() => setDel(null)}>{t('Cancel', 'إلغاء')}</Button>
              <Button variant="dark" icon="trash-2" onClick={() => remove(del)}>{t('Delete address', 'حذف العنوان')}</Button>
            </div>
          </div>
        </>
      )}
    </>
  );
}

function KAProfile({ app }) {
  const { t, tx, compact, cols } = useK();
  const [v, setV] = React.useState({ name: tx(app.user.name), mobile: app.user.mobile, email: app.user.email });
  const [err, setErr] = React.useState({});
  const [p, setP] = React.useState({ cur: '', pw: '', pw2: '' });
  const [perr, setPerr] = React.useState({});
  const set = (k) => (e) => setV((s) => ({ ...s, [k]: e.target.value }));
  const setPw = (k) => (val) => setP((s) => ({ ...s, [k]: val }));
  const saveInfo = (e) => {
    e.preventDefault();
    const x = {};
    if (v.name.trim().split(/\s+/).length < 2) x.name = t('Enter your first and last name.', 'أدخل الاسم الأول واسم العائلة.');
    if (!kaMobileOk(v.mobile)) x.mobile = t('Enter an 11-digit Egyptian mobile starting with 010, 011, 012 or 015.', 'أدخل رقم موبايل مصري من 11 رقم يبدأ بـ 010 أو 011 أو 012 أو 015.');
    if (!kaEmailOk(v.email)) x.email = t('Enter a valid email.', 'أدخل بريداً صحيحاً.');
    setErr(x);
    if (Object.keys(x).length) return;
    const name = v.name.trim();
    app.updateUser({ name, first: name.split(/\s+/)[0], mobile: v.mobile, email: v.email.trim() });
    app.toast('success', t('Profile updated', 'تم تحديث الملف الشخصي'), v.mobile !== app.user.mobile ? t('We sent a code by SMS to confirm the new mobile.', 'أرسلنا كوداً برسالة لتأكيد الرقم الجديد.') : undefined);
  };
  const savePw = (e) => {
    e.preventDefault();
    const x = {};
    if (!p.cur) x.cur = t('Enter your current password.', 'أدخل كلمة المرور الحالية.');
    if (!kaPwOk(p.pw)) x.pw = t('Use at least 8 characters, including a number.', 'استخدم 8 أحرف على الأقل، منها رقم.');
    else if (p.pw === p.cur) x.pw = t('Choose a password you haven’t used here before.', 'اختر كلمة مرور مختلفة عن الحالية.');
    if (p.pw2 !== p.pw || !p.pw2) x.pw2 = t('Passwords don’t match.', 'كلمتا المرور غير متطابقتين.');
    setPerr(x);
    if (Object.keys(x).length) return;
    setP({ cur: '', pw: '', pw2: '' });
    app.toast('success', t('Password changed', 'تم تغيير كلمة المرور'), t('Other devices were logged out.', 'تم تسجيل الخروج من الأجهزة الأخرى.'));
  };
  return (
    <div style={{ display: 'grid', gridTemplateColumns: cols('minmax(0,1fr) minmax(0,1fr)', 'minmax(0,1fr)'), gap: compact ? 16 : 20, alignItems: 'start' }}>
      <KReveal>
        <form onSubmit={saveInfo} noValidate>
          <KOBox title={t('Personal details', 'البيانات الشخصية')} style={{ padding: compact ? 18 : 24 }}>
            <Input label={t('Full name', 'الاسم بالكامل')} icon="user" value={v.name} onChange={set('name')} error={err.name} autoComplete="name" />
            <Input label={t('Mobile number', 'رقم الموبايل')} icon="phone" value={v.mobile} onChange={set('mobile')} error={err.mobile} help={t('Changing it? We’ll text a code to confirm.', 'ستغيّره؟ سنرسل كوداً للتأكيد.')} inputMode="tel" dir="ltr" autoComplete="tel" />
            <Input label={t('Email', 'البريد الإلكتروني')} icon="mail" type="email" value={v.email} onChange={set('email')} error={err.email} help={t('Password reset links are sent here.', 'روابط إعادة تعيين كلمة المرور تُرسل هنا.')} dir="ltr" autoComplete="email" />
            <div><Button type="submit" block={compact}>{t('Save changes', 'حفظ التغييرات')}</Button></div>
          </KOBox>
        </form>
      </KReveal>
      <KReveal delay={80}>
        <form onSubmit={savePw} noValidate>
          <KOBox title={t('Change password', 'تغيير كلمة المرور')} style={{ padding: compact ? 18 : 24 }}>
            <KAPassword label={t('Current password', 'كلمة المرور الحالية')} value={p.cur} onChange={setPw('cur')} error={perr.cur} autoComplete="current-password" />
            <KAPassword label={t('New password', 'كلمة المرور الجديدة')} value={p.pw} onChange={setPw('pw')} error={perr.pw} help={t('At least 8 characters, including a number.', '8 أحرف على الأقل، منها رقم.')} autoComplete="new-password" />
            <KAPassword label={t('Confirm new password', 'تأكيد كلمة المرور الجديدة')} value={p.pw2} onChange={setPw('pw2')} error={perr.pw2} autoComplete="new-password" />
            <div style={{ display: 'flex', alignItems: 'center', gap: 14, flexWrap: 'wrap' }}>
              <Button type="submit" variant="dark" block={compact}>{t('Update password', 'تحديث كلمة المرور')}</Button>
              <KALink onClick={() => app.go('forgot')} style={{ fontSize: 13 }}>{t('Forgot current password?', 'نسيت كلمة المرور الحالية؟')}</KALink>
            </div>
          </KOBox>
        </form>
      </KReveal>
    </div>
  );
}

