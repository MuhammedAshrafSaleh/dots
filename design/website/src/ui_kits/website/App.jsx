
import React from 'react';

const K_PAGES = [
  ['home', 'Home'], ['services', 'Services'], ['service', 'Service detail'], ['products', 'Products'], ['product', 'Product detail'],
  ['brands', 'Brands'], ['brand', 'Brand detail'], ['category', 'Category'], ['cart', 'Cart'], ['checkout', 'Checkout'],
  ['blog', 'Blog'], ['about', 'About'], ['projects-current', 'Current projects'], ['project', 'Project detail (current)'], ['projects-previous', 'Previous projects'], ['project-done', 'Project detail (previous)'],
  ['confirm', 'Order confirmation'], ['payment-failed', 'Payment failed'], ['track', 'Track order'], ['content', 'Content page'],
  ['login', 'Login'], ['register', 'Register'], ['forgot', 'Forgot password'], ['reset', 'Reset password'],
  ['acc-overview', 'Account · Overview'], ['acc-orders', 'My orders'], ['acc-order', 'Order detail'], ['acc-wishlist', 'Wishlist'], ['acc-addresses', 'Addresses'], ['acc-profile', 'Profile & password'],
];
const K_SCREENS = { home: KHome, services: KServices, service: KServiceDetail, products: KProducts, product: KProductDetail, brands: KBrands, brand: KBrandDetail, category: KCategory, cart: KCart, checkout: KCheckout, blog: KBlog, article: KArticle, about: KAbout, 'projects-current': KProjectsCurrent, 'projects-previous': KProjectsPrevious, project: KProjectDetail, confirm: KOrderConfirm, 'payment-failed': KPaymentFailed, track: KTrack, content: KContentPage, login: KLogin, register: KRegister, forgot: KForgot, reset: KReset, account: KAccount };
const K_ACTIVE = { products: 'products', product: 'products', category: 'products', brands: 'brands', brand: 'brands', services: 'services', service: 'services', blog: 'blog', article: 'blog', about: 'about', 'projects-current': 'projects', 'projects-previous': 'projects', project: 'projects' };

function KToolbar({ route, go, lang, setLang, mobile, setMobile, replay, signedIn, setSignedIn }) {
  const seg = (items, val, set) => (
    <div className="d-tabs d-tabs--pill" style={{ flex: 'none' }}>{items.map(([v, l]) => <button key={String(v)} type="button" className="d-tab" aria-selected={v === val} onClick={() => set(v)}>{l}</button>)}</div>
  );
  return (
    <div style={{ position: 'sticky', top: 0, zIndex: 200, background: 'var(--white)', borderBottom: '1px solid var(--border-1)', display: 'flex', alignItems: 'center', gap: 16, padding: '8px 16px', overflowX: 'auto' }} dir="ltr">
      <span style={{ font: '800 12px var(--font-body)', letterSpacing: '.12em', textTransform: 'uppercase', color: 'var(--fg-3)', whiteSpace: 'nowrap' }}>UI kit</span>
      <div className="d-tabs d-tabs--pill" style={{ flex: 'none' }}>
        {K_PAGES.map(([k, l]) => <button key={k} type="button" className="d-tab" aria-selected={route.page === k || (k === 'project-done' && route.page === 'project' && String(route.id).startsWith('d')) || (route.page === 'account' && 'acc-' + (route.tab || 'overview') === k)} onClick={() => go(k, {}, true)}>{l}</button>)}
      </div>
      <span style={{ marginInlineStart: 'auto' }} />
      <button type="button" className="d-tab" style={{ flex: 'none' }} onClick={replay}>↻ Preloader</button>
      {seg([[false, 'Guest'], [true, 'Signed in']], signedIn, setSignedIn)}
      {seg([[false, 'Desktop'], [true, 'Mobile']], mobile, setMobile)}
      {seg([['en', 'EN · LTR'], ['ar', 'AR · RTL']], lang, setLang)}
    </div>
  );
}

export function KApp() {
  const saved = React.useMemo(() => { try { return JSON.parse(localStorage.getItem('dots-kit') || '{}'); } catch (e) { return {}; } }, []);
  const [route, setRoute] = React.useState(saved.route || { page: 'home' });
  const [lang, setLang] = React.useState(saved.lang || 'en');
  const [mobile, setMobile] = React.useState(!!saved.mobile);
  const [cart, setCart] = React.useState(K_CART_START);
  const [drawer, setDrawer] = React.useState(false);
  const [toasts, setToasts] = React.useState([]);
  const scroller = React.useRef(null);
  const tm = React.useRef([]);
  const [curtain, setCurtain] = React.useState({ phase: 'idle' });
  const [ready, setReady] = React.useState(false);
  const [preKey, setPreKey] = React.useState(0);
  const [user, setUserS] = React.useState(saved.user ? KA_USER : null);
  const userRef = React.useRef(user);
  const setUser = (u) => { userRef.current = u; setUserS(u); };
  const [wish, setWish] = React.useState(saved.wish || KA_WISH_START);
  const [addresses, setAddresses] = React.useState(KA_ADDR);
  React.useEffect(() => { localStorage.setItem('dots-kit', JSON.stringify({ route, lang, mobile, user: !!user, wish })); }, [route, lang, mobile, user, wish]);
  const ar = lang === 'ar';
  const tt = (en, a) => (ar ? a : en);
  const go = (page, params = {}, fromKit) => {
    if (page === 'quote') { app.quote(); return; }
    if (page === 'logout') { setUser(null); toast('info', tt('You’re logged out', 'تم تسجيل الخروج'), tt('Your cart stays on this device.', 'سلتك محفوظة على هذا الجهاز.')); page = 'home'; }
    const key = page;
    if (page.startsWith('acc-')) {
      if (fromKit && !userRef.current) setUser(KA_USER);
      params = { tab: page.slice(4), ...params }; page = 'account';
      if (params.tab === 'order' && !params.id) params.id = 'DT-10482';
    }
    if (page === 'account' && !userRef.current) { params = { next: 'acc-' + params.tab }; page = 'login'; }
    if (page === 'project-done') { page = 'project'; params = { id: params.id || 'd1' }; }
    if (['delivery', 'returns', 'warranty', 'careers'].includes(page)) { params = { id: page }; page = 'content'; }
    const lbl = page === 'article' ? 'Blog' : (K_PAGES.find((p) => p[0] === key) || K_PAGES.find((p) => p[0] === page) || [0, ''])[1];
    clearTimeout(tm.current[0]); clearTimeout(tm.current[1]);
    setCurtain({ phase: 'in', label: lbl });
    tm.current[0] = setTimeout(() => { setRoute({ page, ...params }); setDrawer(false); (scroller.current || window).scrollTo(0, 0); setCurtain({ phase: 'out', label: lbl }); tm.current[1] = setTimeout(() => setCurtain({ phase: 'idle' }), 850); }, 600);
  };
  const toast = (tone, title, message, actions) => {
    const id = Math.random();
    setToasts((x) => [...x.slice(-2), { id, tone, title, message, actions }]);
    setTimeout(() => setToasts((x) => x.filter((y) => y.id !== id)), 4200);
  };
  const app = {
    go, toast, cart,
    add: (p, q = 1) => {
      setCart((c) => { const ex = c.find((x) => x.pid === p.id); return ex ? c.map((x) => (x.pid === p.id ? { ...x, qty: x.qty + q } : x)) : [...c, { id: 'l' + p.id + Date.now(), pid: p.id, name: p.name, variant: p.variants, price: p.price || 0, qty: q }]; });
      toast('cart', tt('Added to cart', 'تمت الإضافة للسلة'), p.name, [{ label: tt('View cart', 'عرض السلة'), onClick: () => setDrawer(true) }, { label: tt('Checkout', 'الدفع'), onClick: () => go('checkout') }]);
    },
    quote: (p) => toast('success', tt('Opening WhatsApp…', 'جارٍ فتح واتساب…'), p ? p.sku + ' — ' + tt('we’ll reply with a quote within 2 working hours.', 'سنرد بعرض سعر خلال ساعتين عمل.') : tt('Our project team replies within 2 working hours.', 'فريق المشروعات يرد خلال ساعتين عمل.')),
    qty: (it, q) => setCart((c) => c.map((x) => (x.id === it.id ? { ...x, qty: q } : x))),
    remove: (it) => setCart((c) => c.filter((x) => x.id !== it.id)),
    user, wish, addresses, setAddresses,
    login: (u, next, title) => {
      setUser(u || KA_USER);
      const first = (u || KA_USER).first;
      toast('success', title || tt('Welcome back', 'أهلاً بعودتك'), typeof first === 'object' ? first[lang] : first);
      if (next) go(next);
    },
    updateUser: (patch) => setUser({ ...userRef.current, ...patch }),
    toggleWish: (p) => {
      if (!userRef.current) return false;
      const had = wish.includes(p.id);
      setWish((w) => (w.includes(p.id) ? w.filter((x) => x !== p.id) : [...w, p.id]));
      if (had) toast('info', tt('Removed from wishlist', 'تمت الإزالة من المفضلة'), p.name, [{ label: tt('Undo', 'تراجع'), onClick: () => setWish((w) => (w.includes(p.id) ? w : [...w, p.id])) }]);
      else toast('success', tt('Saved to wishlist', 'تم الحفظ في المفضلة'), p.name, [{ label: tt('View wishlist', 'عرض المفضلة'), onClick: () => go('acc-wishlist') }]);
      return true;
    },
    addLines: (lines, ref) => {
      setCart((c) => { let out = [...c]; lines.forEach((l) => { const ex = out.find((x) => x.pid === l.pid); out = ex ? out.map((x) => (x.pid === l.pid ? { ...x, qty: x.qty + l.qty } : x)) : [...out, { id: 'l' + l.pid + Date.now(), pid: l.pid, name: l.name, variant: l.variant, price: l.price || 0, qty: l.qty }]; }); return out; });
      const n = lines.reduce((s, l) => s + l.qty, 0);
      toast('cart', ref ? tt('Order ' + ref + ' added to cart', 'تمت إضافة الطلب ' + ref + ' للسلة') : tt('Added to cart', 'تمت الإضافة للسلة'), tt(n + ' items', n + ' منتجات'), [{ label: tt('View cart', 'عرض السلة'), onClick: () => setDrawer(true) }, { label: tt('Checkout', 'الدفع'), onClick: () => go('checkout') }]);
    },
  };
  const Screen = K_SCREENS[route.page] || KHome;
  const nav = (k) => go(k);
  const count = cart.reduce((s, i) => s + i.qty, 0);
  const body = (
    <LocaleProvider lang={lang} compact={mobile}>
      <Navbar user={user} logoSrc={(window.__resources || {}).logo || '../../assets/logo/dots-wordmark.png'} active={K_ACTIVE[route.page]} cartCount={count} onCart={() => setDrawer(true)} onLang={setLang} onNavigate={nav} />
      {ready && <main key={route.page + (route.id || '') + (route.pay || '') + (route.tab || '') + (route.next || '') + lang} className="d-pagein"><Screen app={app} route={route} /></main>}
      <Footer logoSrc={(window.__resources || {}).logo || '../../assets/logo/dots-wordmark.png'} onNavigate={nav} />
      <CartDrawer open={drawer} items={cart} onClose={() => setDrawer(false)} onQty={app.qty} onRemove={app.remove} onCheckout={() => go('checkout')} onViewCart={() => go('cart')} onQuote={() => app.quote()} />
      <KCurtain phase={curtain.phase} label={curtain.label} />
      <KPreloader key={preKey} logoSrc={(window.__resources || {}).logo || '../../assets/logo/dots-wordmark.png'} onReveal={() => setReady(true)} />
      <div className="d-toasts">{toasts.map((x) => <Toast key={x.id} {...x} onClose={() => setToasts((y) => y.filter((z) => z.id !== x.id))} />)}</div>
    </LocaleProvider>
  );
  return (
    <>
      <KCursor enabled={!mobile} />
      <KToolbar signedIn={!!user} setSignedIn={(v) => setUser(v ? KA_USER : null)} replay={() => { setReady(false); setPreKey((k) => k + 1); }} route={route} go={go} lang={lang} setLang={setLang} mobile={mobile} setMobile={setMobile} />
      {mobile ? (
        <div style={{ background: '#E9E8E3', padding: '28px 0 40px', display: 'grid', placeItems: 'center' }}>
          <div style={{ width: 390, height: 844, borderRadius: 44, border: '10px solid #181816', overflow: 'hidden', transform: 'translateZ(0)', background: '#fff', boxShadow: 'var(--shadow-lg)' }}>
            <div ref={scroller} style={{ height: '100%', overflowY: 'auto' }}>{body}</div>
          </div>
        </div>
      ) : body}
    </>
  );
}

