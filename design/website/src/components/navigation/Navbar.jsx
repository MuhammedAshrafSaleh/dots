
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';
import { IconButton } from '../buttons/IconButton.jsx';
import { SearchBar } from '../forms/SearchBar.jsx';
import { LangSwitch } from './LangSwitch.jsx';
import { MegaMenu } from './MegaMenu.jsx';

/**
 * Site header: utility strip · main bar (logo, search, EN/AR, account, cart) · nav row (Categories mega-menu,
 * Products, Brands, Services, Projects ▾ [Current / Previous], Blog, About). Collapses to a mobile bar under `compact`.
 * active: products | brands | services | projects | blog | about
 * user: null (logged out → account icon navigates to 'login') or {name, first, email} (account dropdown).
 * onNavigate keys added: login · register · acc-orders · acc-wishlist · acc-addresses · acc-profile · acc-overview · logout
 */
export function Navbar({ logoSrc, active, cartCount = 0, onCart, onLang, onNavigate, megaOpen, onMegaToggle, categories, user }) {
  const { t, tx, lang, compact } = useLocale();
  const [acc, setAcc] = React.useState(false);
  const accItems = [['acc-orders', t('My orders', 'طلباتي'), 'package'], ['acc-wishlist', t('Wishlist', 'المفضلة'), 'heart'], ['acc-addresses', t('Addresses', 'العناوين'), 'map-pin'], ['acc-profile', t('Profile & password', 'الملف الشخصي وكلمة المرور'), 'user']];
  const [q, setQ] = React.useState('');
  const [openLocal, setOpenLocal] = React.useState(false);
  const [proj, setProj] = React.useState(false);
  const open = megaOpen ?? openLocal;
  const toggle = (v) => (onMegaToggle ? onMegaToggle(v) : setOpenLocal(v));
  const nav = (x) => { toggle(false); setProj(false); setAcc(false); onNavigate && onNavigate(x); };
  const projects = [
    ['projects-current', t('Current projects', 'مشروعات جارية'), t('Sites we’re delivering now', 'مواقع ننفذها الآن'), 'hard-hat'],
    ['projects-previous', t('Previous projects', 'مشروعات سابقة'), t('Completed work & case studies', 'أعمال منجزة ودراسات حالة'), 'badge-check'],
  ];
  const logo = (
    <a href="#" className="d-nav__logo" onClick={(e) => { e.preventDefault(); nav('home'); }} aria-label="Dots — home">
      {logoSrc ? <img src={logoSrc} alt="Dots" /> : <span style={{ font: '700 26px/1 var(--font-display)' }}>Dots</span>}
    </a>
  );
  const link = (k, l) => (
    <a key={k} href="#" className={cx('d-nav__link', active === k && 'd-nav__link--active')} onClick={(e) => { e.preventDefault(); nav(k); }}>{l}</a>
  );
  if (compact) {
    const items = [['products', t('All products', 'كل المنتجات'), 'layout-grid'], ['category', t('Indoor Lighting', 'إضاءة داخلية'), 'lamp-ceiling'], ['category', t('Smart Home', 'المنزل الذكي'), 'wifi'], ['category', t('Electrical', 'مستلزمات كهربائية'), 'plug-zap'], ['brands', t('Brands', 'العلامات التجارية'), 'badge-check'], ['services', t('Services', 'الخدمات'), 'wrench'], ['projects-current', t('Current projects', 'مشروعات جارية'), 'hard-hat'], ['projects-previous', t('Previous projects', 'مشروعات سابقة'), 'building-2'], ['blog', t('Blog', 'المدونة'), 'newspaper'], ['about', t('About Dots', 'عن دوتس'), 'info']];
    return (
      <header className="d-nav d-nav--compact">
        <div className="d-container d-nav__main">
          <IconButton icon={open ? 'x' : 'menu'} label={t('Menu', 'القائمة')} onClick={() => toggle(!open)} />
          {logo}
          <div className="d-nav__actions">
            <LangSwitch value={lang} onChange={onLang} />
            <IconButton icon="user" label={user ? tx(user.name) : t('Log in', 'تسجيل الدخول')} className={user ? 'd-nav__acct--in' : undefined} onClick={() => (user ? toggle(true) : nav('login'))} />
            <IconButton icon="shopping-bag" label={t('Cart', 'السلة')} count={cartCount} onClick={onCart} />
          </div>
        </div>
        <div className="d-container" style={{ paddingBottom: 12 }}>
          <SearchBar scopes={false} size="sm" iconOnly value={q} onChange={setQ} placeholder={t('Search products, SKUs…', 'ابحث عن منتج أو كود…')} />
        </div>
        {open && (
          <nav style={{ borderTop: '1px solid var(--border-1)', padding: '8px 16px 16px', display: 'grid', gap: 2 }}>
            {user ? (
              <div style={{ display: 'grid', gap: 2, paddingBottom: 8, marginBottom: 6, borderBottom: '1px solid var(--border-1)' }}>
                <button type="button" className="d-nav__mi" onClick={() => nav('acc-overview')}>
                  <span className="d-nav__mi-ico" style={{ font: '800 14px var(--font-body)' }}>{tx(user.first).charAt(0)}</span>
                  <span><b>{tx(user.name)}</b><small dir="ltr" style={{ textAlign: 'start' }}>{user.email}</small></span>
                </button>
                {accItems.map(([k, l, ic]) => <button key={k} type="button" className="d-mega__cat" onClick={() => nav(k)}><Icon name={ic} size={20} />{l}<Icon name="chevron-right" size={16} /></button>)}
                <button type="button" className="d-mega__cat" onClick={() => nav('logout')}><Icon name="log-out" size={20} /><span>{t('Log out', 'تسجيل الخروج')}</span></button>
              </div>
            ) : (
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8, paddingBottom: 12, marginBottom: 6, borderBottom: '1px solid var(--border-1)' }}>
                <button type="button" className="d-btn d-btn--primary" onClick={() => nav('login')}>{t('Log in', 'تسجيل الدخول')}</button>
                <button type="button" className="d-btn d-btn--outline" onClick={() => nav('register')}>{t('Create account', 'إنشاء حساب')}</button>
              </div>
            )}
            {items.map(([k, l, ic], i) => (
              <button key={i} type="button" className="d-mega__cat" onClick={() => nav(k)}><Icon name={ic} size={20} />{l}<Icon name="chevron-right" size={16} /></button>
            ))}
          </nav>
        )}
      </header>
    );
  }
  return (
    <header className="d-nav" onMouseLeave={() => { open && toggle(false); setProj(false); }}>
      <div className="d-nav__util">
        <div className="d-container d-nav__util-in">
          <span><Icon name="phone" size={14} />{t('Hotline', 'الخط الساخن')} 19XXX</span>
          <span><Icon name="map-pin" size={14} />{t('Showroom · New Cairo', 'المعرض · القاهرة الجديدة')}</span>
          <span><Icon name="truck" size={14} />{t('Delivery across Egypt', 'توصيل لكل المحافظات')}</span>
          <a href="#" style={{ marginInlineStart: 'auto', color: 'inherit', textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: 6 }} onClick={(e) => { e.preventDefault(); nav('track'); }}><Icon name="package-search" size={14} />{t('Track order', 'تتبع الطلب')}</a>
        </div>
      </div>
      <div className="d-container d-nav__main">
        {logo}
        <SearchBar className="d-nav__search" value={q} onChange={setQ} />
        <div className="d-nav__actions">
          <LangSwitch value={lang} onChange={onLang} />
          {user ? (
            <div className="d-nav__dd" onMouseLeave={() => setAcc(false)}>
              <button type="button" className="d-btn d-btn--ghost d-nav__acct" aria-haspopup="menu" aria-expanded={acc} onClick={() => setAcc(!acc)}>
                <span className="d-nav__acct-av">{tx(user.first).charAt(0)}</span>{tx(user.first)}<Icon name="chevron-down" size={16} style={{ transition: 'transform var(--dur-base) var(--ease-out)', transform: acc ? 'rotate(180deg)' : undefined }} />
              </button>
              {acc && (
                <div className="d-nav__menu" role="menu" style={{ insetInlineStart: 'auto', insetInlineEnd: 0, top: 'calc(100% + 4px)', width: 288 }}>
                  <button type="button" role="menuitem" className="d-nav__mi" onClick={() => nav('acc-overview')} style={{ borderBottom: '1px solid var(--border-1)', borderRadius: 0, paddingBottom: 14, marginBottom: 4 }}>
                    <span className="d-nav__mi-ico" style={{ font: '800 15px var(--font-body)' }}>{tx(user.first).charAt(0)}</span>
                    <span style={{ minWidth: 0 }}><b>{tx(user.name)}</b><small dir="ltr" style={{ textAlign: 'start' }}>{user.email}</small></span>
                  </button>
                  {accItems.map(([k, l, ic]) => <button key={k} type="button" role="menuitem" className="d-nav__mi" onClick={() => nav(k)} style={{ font: '700 14px var(--font-body)' }}><Icon name={ic} size={18} />{l}</button>)}
                  <button type="button" role="menuitem" className="d-nav__mi" onClick={() => nav('logout')} style={{ font: '700 14px var(--font-body)', borderTop: '1px solid var(--border-1)', borderRadius: 0, marginTop: 4, paddingTop: 14 }}><Icon name="log-out" size={18} />{t('Log out', 'تسجيل الخروج')}</button>
                </div>
              )}
            </div>
          ) : (
            <IconButton icon="user" label={t('Log in / Register', 'تسجيل الدخول / حساب جديد')} onClick={() => nav('login')} />
          )}
          <IconButton icon="shopping-bag" label={t('Cart', 'السلة')} count={cartCount} onClick={onCart} />
        </div>
      </div>
      <div className="d-nav__row">
        <div className="d-container d-nav__row-in">
          <button type="button" className={cx('d-nav__link', open && 'd-nav__link--open')} aria-expanded={open} onClick={() => { setProj(false); toggle(!open); }}>
            <Icon name={open ? 'x' : 'layout-grid'} size={18} />{t('Categories', 'الأقسام')}
          </button>
          <span className="d-nav__sep" />
          {link('products', t('Products', 'المنتجات'))}
          {link('brands', t('Brands', 'العلامات التجارية'))}
          {link('services', t('Services', 'الخدمات'))}
          <div className="d-nav__dd" onMouseEnter={() => { toggle(false); setProj(true); }} onMouseLeave={() => setProj(false)}>
            <button type="button" className={cx('d-nav__link', active === 'projects' && 'd-nav__link--active')} aria-expanded={proj} onClick={() => setProj(!proj)}>
              {t('Projects', 'المشروعات')}<Icon name="chevron-down" size={16} style={{ transition: 'transform var(--dur-base) var(--ease-out)', transform: proj ? 'rotate(180deg)' : undefined }} />
            </button>
            {proj && (
              <div className="d-nav__menu" role="menu">
                {projects.map(([k, l, d, ic]) => (
                  <button key={k} type="button" role="menuitem" className="d-nav__mi" onClick={() => nav(k)}>
                    <span className="d-nav__mi-ico"><Icon name={ic} size={20} /></span>
                    <span><b>{l}</b><small>{d}</small></span>
                  </button>
                ))}
              </div>
            )}
          </div>
          {link('blog', t('Blog', 'المدونة'))}
          {link('about', t('About', 'من نحن'))}
          <a href="#" className="d-nav__quote" onClick={(e) => { e.preventDefault(); nav('quote'); }}><Icon name="message-circle" size={16} />{t('Request a quote', 'اطلب عرض سعر')}</a>
        </div>
      </div>
      {open && <MegaMenu categories={categories} onNavigate={(x) => nav(x.service ? 'services' : 'category')} />}
    </header>
  );
}

