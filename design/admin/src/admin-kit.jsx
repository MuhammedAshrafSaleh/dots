

const DotsLocaleCtx = React.createContext({ lang: 'en', dir: 'ltr', compact: false });

function cx(...a) { return a.filter(Boolean).join(' '); }

function formatPrice(n, lang = 'en') {
  const s = Number(n).toLocaleString('en-US');
  return lang === 'ar' ? s + ' ج.م' : 'EGP ' + s;
}

function LocaleProvider({ lang = 'en', compact = false, children, style, className }) {
  const value = React.useMemo(() => ({ lang, dir: lang === 'ar' ? 'rtl' : 'ltr', compact }), [lang, compact]);
  return (
    <DotsLocaleCtx.Provider value={value}>
      <div dir={value.dir} lang={lang} className={cx('d-root', compact && 'd-compact', className)} style={style}>{children}</div>
    </DotsLocaleCtx.Provider>
  );
}

function useLocale() {
  const c = React.useContext(DotsLocaleCtx);
  const tx = (v) => (v && typeof v === 'object' && !React.isValidElement(v) ? (v[c.lang] ?? v.en) : v);
  return { ...c, t: (en, ar) => (c.lang === 'ar' && ar != null ? ar : en), tx, price: (n) => formatPrice(n, c.lang) };
}

;



const DOTS_DIRECTIONAL = new Set(['chevron-right', 'chevron-left', 'arrow-right', 'arrow-left', 'arrow-up-right', 'move-right', 'undo-2', 'redo-2', 'log-out', 'list-filter']);
const DOTS_ALIASES = { home: 'house', 'alert-circle': 'circle-alert', 'check-circle': 'circle-check', 'alert-triangle': 'triangle-alert', whatsapp: 'message-circle' };

function dotsPascal(n) { return n.split('-').map((p) => p.charAt(0).toUpperCase() + p.slice(1)).join(''); }

/** Outlined Lucide icon — 1.5px stroke, round caps/joins. Directional icons mirror in RTL. */
function Icon({ name, size = 20, stroke = 1.5, flip, className, style, title, ...rest }) {
  const { dir } = useLocale();
  const key = DOTS_ALIASES[name] || name;
  const lib = typeof window !== 'undefined' ? window.lucide : null;
  const node = lib && lib.icons ? (lib.icons[dotsPascal(key)] || lib.icons[dotsPascal(name)]) : null;
  const kids = node ? (node[0] === 'svg' ? node[2] : node) : [['circle', { cx: 12, cy: 12, r: 3 }]];
  const mirror = flip ?? (dir === 'rtl' && DOTS_DIRECTIONAL.has(key));
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={stroke}
      strokeLinecap="round" strokeLinejoin="round" className={cx('d-icon', className)}
      style={{ flex: 'none', transform: mirror ? 'scaleX(-1)' : undefined, ...style }}
      aria-hidden={title ? undefined : 'true'} role={title ? 'img' : undefined} {...rest}>
      {title && <title>{title}</title>}
      {kids.map(([tag, attrs], i) => React.createElement(tag, { key: i, ...attrs }))}
    </svg>
  );
}

;



/**
 * Image container. `src` renders a covered photo; `video` renders a muted looping background video (src = poster).
 * Without media — or if it fails to load — shows a labelled placeholder stating the shot required.
 */
function ImageFrame({ src, video, alt = '', label, ratio, contain, radius, className, style, children }) {
  const { tx } = useLocale();
  const [failed, setFailed] = React.useState(false);
  const ok = src && !failed;
  return (
    <div className={cx('d-img', contain && 'd-img--contain', className)} style={{ aspectRatio: ratio, borderRadius: radius, ...style }}>
      {video && !failed ? (
        <video src={video} poster={src} autoPlay muted loop playsInline aria-hidden="true" onError={() => {}} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
      ) : ok ? <img src={src} alt={tx(alt)} loading="lazy" onError={() => setFailed(true)} /> : (
        <div className="d-img__ph" aria-hidden="true">{label && <span className="d-img__label">{tx(label)}</span>}</div>
      )}
      {children}
    </div>
  );
}

;




/** variant: primary (yellow) | dark | outline | ghost | whatsapp (Request a Quote) | quote-outline */
function Button({ variant = 'primary', size = 'md', icon, iconEnd, block, children, className, as = 'button', type, ...rest }) {
  const El = as;
  const isz = size === 'sm' ? 16 : size === 'lg' ? 20 : 18;
  const lead = icon ?? (variant === 'whatsapp' || variant === 'quote-outline' ? 'message-circle' : null);
  return (
    <El
      className={cx('d-btn', `d-btn--${variant}`, size !== 'md' && `d-btn--${size}`, block && 'd-btn--block', !children && 'd-btn--icon', className)}
      type={El === 'button' ? type || 'button' : undefined}
      {...rest}
    >
      {lead && <Icon name={lead} size={isz} />}
      {children}
      {iconEnd && <Icon name={iconEnd} size={isz} />}
    </El>
  );
}

;




/** Square icon-only button. Always pass `label` (becomes aria-label). Optional `count` shows a yellow badge (cart). */
function IconButton({ icon, label, variant = 'ghost', size = 'md', count, className, ...rest }) {
  const isz = size === 'sm' ? 18 : size === 'lg' ? 22 : 20;
  return (
    <button type="button" aria-label={label} title={label}
      className={cx('d-btn', `d-btn--${variant}`, 'd-btn--icon', size !== 'md' && `d-btn--${size}`, count != null && 'd-btn--has-count', className)} {...rest}>
      <Icon name={icon} size={isz} />
      {count != null && count > 0 && <span className="d-btn__count">{count}</span>}
    </button>
  );
}

;



/** tone: accent | dark | neutral | outline | success | warning | error | info | sale */
function Badge({ tone = 'neutral', dot, children, className, ...rest }) {
  return (
    <span className={cx('d-badge', `d-badge--${tone}`, className)} {...rest}>
      {dot && <span className="d-badge__dot" />}{children}
    </span>
  );
}

;




/** Filter chip. `active` + `onRemove` = active-filter chip with ✕. `facet` renders a muted "Brand:" prefix. */
function Chip({ children, facet, active, onRemove, onClick, className, ...rest }) {
  const { t } = useLocale();
  const El = onRemove ? 'span' : 'button';
  return (
    <El className={cx('d-chip', active && 'd-chip--active', className)} onClick={onClick} type={El === 'button' ? 'button' : undefined} {...rest}>
      {facet && <span className="d-chip__key">{facet}:</span>}
      {children}
      {onRemove && (
        <button type="button" className="d-chip__x" onClick={onRemove} aria-label={t('Remove filter', 'إزالة الفلتر')}>
          <Icon name="x" size={14} stroke={2} />
        </button>
      )}
    </El>
  );
}

;



const DOTS_TAGLINE = { en: ['Lighting', 'Smart Home', 'Electrical'], ar: ['إضاءة', 'منزل ذكي', 'كهرباء'] };

/** Brand tagline: uppercase, wide-tracked words separated by yellow dots. */
function Tagline({ items, className, style }) {
  const { lang } = useLocale();
  const list = items || DOTS_TAGLINE[lang] || DOTS_TAGLINE.en;
  return (
    <span className={cx('d-tagline', className)} style={style}>
      {list.map((w, i) => (
        <React.Fragment key={i}>{i > 0 && <span className="d-tagline__dot" aria-hidden="true" />}<span>{w}</span></React.Fragment>
      ))}
    </span>
  );
}

;



/** Section eyebrow with the circuit-node lead (● ─). Use above section titles. */
function Eyebrow({ children, className, style }) {
  return <span className={cx('d-eyebrow', className)} style={style}>{children}</span>;
}

;



/** Hairline divider that starts with two yellow circuit nodes. Use once per section, max. */
function NodeDivider({ className, style }) {
  return <hr className={cx('d-node-rule', className)} style={style} />;
}

;




/** Labelled text input / textarea with lead icon, trailing slot, help and error text. */
function Input({ label, help, error, icon, trailing, optional, size, multiline, id, className, style, ...rest }) {
  const { t } = useLocale();
  const auto = React.useId();
  const fid = id || auto;
  const Tag = multiline ? 'textarea' : 'input';
  return (
    <div className={cx('d-field', className)} style={style}>
      {label && <label className="d-label" htmlFor={fid}>{label}{optional && <span className="d-label__opt"> · {t('optional', 'اختياري')}</span>}</label>}
      <div className="d-control">
        {icon && <span className="d-control__lead"><Icon name={icon} size={18} /></span>}
        <Tag id={fid} className={cx('d-input', icon && 'd-input--lead', trailing && 'd-input--trail', size === 'sm' && 'd-input--sm')}
          aria-invalid={error ? 'true' : undefined} {...rest} />
        {trailing && <span className="d-control__trail">{trailing}</span>}
      </div>
      {error ? <span className="d-help d-help--error"><Icon name="circle-alert" size={14} />{error}</span> : help && <span className="d-help">{help}</span>}
    </div>
  );
}

;




/** Native select styled as a Dots input. options: string[] or {value,label}[]. */
function Select({ label, help, error, options = [], size, id, className, style, placeholder, ...rest }) {
  const auto = React.useId();
  const fid = id || auto;
  return (
    <div className={cx('d-field', className)} style={style}>
      {label && <label className="d-label" htmlFor={fid}>{label}</label>}
      <div className="d-control">
        <select id={fid} className={cx('d-input', 'd-select', size === 'sm' && 'd-input--sm')} aria-invalid={error ? 'true' : undefined} {...rest}>
          {placeholder && <option value="">{placeholder}</option>}
          {options.map((o) => { const v = typeof o === 'string' ? { value: o, label: o } : o; return <option key={v.value} value={v.value} disabled={v.disabled}>{v.label}</option>; })}
        </select>
        <span className="d-control__trail"><Icon name="chevron-down" size={18} /></span>
      </div>
      {error ? <span className="d-help d-help--error">{error}</span> : help && <span className="d-help">{help}</span>}
    </div>
  );
}

;




/** Catalog search: optional scope select (All / Lighting / Smart Home / Electrical / Brands) + field + yellow submit. */
function SearchBar({ scopes, scope, onScope, value, onChange, onSubmit, placeholder, size, iconOnly, className, style }) {
  const { t } = useLocale();
  const list = scopes === false ? null : scopes || [t('All', 'الكل'), t('Lighting', 'إضاءة'), t('Smart Home', 'منزل ذكي'), t('Electrical', 'كهرباء'), t('Brands', 'العلامات')];
  return (
    <form role="search" className={cx('d-search', size === 'sm' && 'd-search--sm', className)} style={style}
      onSubmit={(e) => { e.preventDefault(); onSubmit && onSubmit(value); }}>
      {list && (
        <div className="d-search__scope">
          <select aria-label={t('Search in', 'البحث في')} value={scope} onChange={(e) => onScope && onScope(e.target.value)}>
            {list.map((s) => <option key={s}>{s}</option>)}
          </select>
          <Icon name="chevron-down" size={14} />
        </div>
      )}
      <label className="d-search__field">
        <Icon name="search" size={18} />
        <input type="search" value={value} onChange={(e) => onChange && onChange(e.target.value)}
          placeholder={placeholder || t('Search 10,000+ products, SKUs, brands…', 'ابحث في أكثر من 10,000 منتج أو كود أو علامة…')}
          aria-label={t('Search', 'بحث')} />
      </label>
      <button type="submit" className="d-search__btn" aria-label={t('Search', 'بحث')}>
        {iconOnly ? <Icon name="arrow-right" size={18} /> : t('Search', 'بحث')}
      </button>
    </form>
  );
}

;



/** Checkbox with optional result count (filters). `row` stretches to full width so count aligns to the end edge. */
function Checkbox({ label, count, checked, onChange, disabled, row, className, ...rest }) {
  return (
    <label className={cx('d-check', row && 'd-check--row', disabled && 'd-check--disabled', className)}>
      <input type="checkbox" checked={!!checked} disabled={disabled} onChange={(e) => onChange && onChange(e.target.checked)} {...rest} />
      <span>{label}</span>
      {count != null && <span className="d-check__count">{count}</span>}
    </label>
  );
}

;



/** Radio — yellow fill with an ink centre node when selected. */
function Radio({ label, name, value, checked, onChange, disabled, className, ...rest }) {
  return (
    <label className={cx('d-check', disabled && 'd-check--disabled', className)}>
      <input type="radio" name={name} value={value} checked={!!checked} disabled={disabled} onChange={() => onChange && onChange(value)} {...rest} />
      <span>{label}</span>
    </label>
  );
}

;




/** Large selectable radio tile for delivery / payment methods. */
function RadioCard({ title, desc, meta, icon, name, value, checked, onChange, className }) {
  return (
    <label className={cx('d-radio-card', checked && 'd-radio-card--on', className)}>
      <input type="radio" name={name} value={value} checked={!!checked} onChange={() => onChange && onChange(value)} style={{ position: 'absolute', opacity: 0, pointerEvents: 'none' }} />
      <span className="d-check"><input type="radio" readOnly checked={!!checked} tabIndex={-1} aria-hidden="true" /></span>
      <span style={{ flex: 1, minWidth: 0, display: 'grid', gap: 3 }}>
        <span style={{ font: '800 14px/1.3 var(--font-body)', display: 'flex', alignItems: 'center', gap: 8 }}>{icon && <Icon name={icon} size={18} />}{title}</span>
        {desc && <span style={{ font: '500 13px/1.45 var(--font-body)', color: 'var(--fg-2)' }}>{desc}</span>}
      </span>
      {meta && <span style={{ font: '800 14px/1.3 var(--font-body)', whiteSpace: 'nowrap' }}>{meta}</span>}
    </label>
  );
}

;




/** − / qty / + stepper. Controlled via value + onChange. */
function QuantityStepper({ value = 1, onChange, min = 1, max = 999, size, disabled, className }) {
  const { t } = useLocale();
  const set = (n) => onChange && onChange(Math.max(min, Math.min(max, n)));
  const isz = size === 'sm' ? 14 : 16;
  return (
    <div className={cx('d-stepper', size === 'sm' && 'd-stepper--sm', disabled && 'd-stepper--disabled', className)}>
      <button type="button" onClick={() => set(value - 1)} disabled={disabled || value <= min} aria-label={t('Decrease quantity', 'تقليل الكمية')}><Icon name="minus" size={isz} stroke={2} /></button>
      <input type="number" inputMode="numeric" value={value} disabled={disabled} aria-label={t('Quantity', 'الكمية')}
        onChange={(e) => set(parseInt(e.target.value || String(min), 10))} />
      <button type="button" onClick={() => set(value + 1)} disabled={disabled || value >= max} aria-label={t('Increase quantity', 'زيادة الكمية')}><Icon name="plus" size={isz} stroke={2} /></button>
    </div>
  );
}

;



/**
 * Text-option variant picker (size, wattage, colour temperature, beam angle).
 * options: {value, label, sub?, chip? (css color), disabled?}. `tall` stacks label + sub (e.g. 3000K / Warm white).
 */
function VariantPicker({ label, value, options = [], onChange, tall, className }) {
  const { tx } = useLocale();
  const cur = options.find((o) => o.value === value);
  return (
    <div className={cx('d-variant', className)} role="group" aria-label={tx(label)}>
      <div className="d-variant__head">{tx(label)}{cur && <span>{tx(cur.label)}{cur.sub ? ' · ' + tx(cur.sub) : ''}</span>}</div>
      <div className="d-variant__opts">
        {options.map((o) => (
          <button key={o.value} type="button" className={cx('d-opt', tall && 'd-opt--tall')} aria-pressed={o.value === value}
            disabled={o.disabled} onClick={() => onChange && onChange(o.value)}>
            {o.chip && !tall && <span className="d-opt__chip" style={{ background: o.chip }} />}
            <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6 }}>{o.chip && tall && <span className="d-opt__chip" style={{ background: o.chip, width: 12, height: 12 }} />}{tx(o.label)}</span>
            {tall && o.sub && <span className="d-opt__sub">{tx(o.sub)}</span>}
          </button>
        ))}
      </div>
    </div>
  );
}

;



/** Round colour/finish swatches. options: {value, label, color (css background — may be a gradient for metals), disabled?}. */
function SwatchPicker({ label, value, options = [], onChange, className }) {
  const { tx } = useLocale();
  const cur = options.find((o) => o.value === value);
  return (
    <div className={cx('d-variant', className)} role="group" aria-label={tx(label)}>
      <div className="d-variant__head">{tx(label)}{cur && <span>{tx(cur.label)}</span>}</div>
      <div className="d-variant__opts" style={{ gap: 12, paddingInline: 3 }}>
        {options.map((o) => (
          <button key={o.value} type="button" className="d-swatch" style={{ background: o.color }} aria-pressed={o.value === value}
            aria-label={tx(o.label)} title={tx(o.label)} disabled={o.disabled} onClick={() => onChange && onChange(o.value)} />
        ))}
      </div>
    </div>
  );
}

;



/** EN / ع segmented language switch. Switching also flips document direction via LocaleProvider. */
function LangSwitch({ value, onChange }) {
  const { lang, t } = useLocale();
  const cur = value || lang;
  return (
    <div className="d-lang" role="group" aria-label={t('Language', 'اللغة')}>
      <button type="button" lang="en" aria-pressed={cur === 'en'} onClick={() => onChange && onChange('en')}>EN</button>
      <button type="button" lang="ar" aria-pressed={cur === 'ar'} onClick={() => onChange && onChange('ar')}>عربي</button>
    </div>
  );
}

;






const DOTS_MEGA_DEFAULT = [
  { id: 'indoor', icon: 'lamp-ceiling', name: { en: 'Indoor Lighting', ar: 'إضاءة داخلية' }, groups: [
    { title: { en: 'Ceiling', ar: 'أسقف' }, links: [{ en: 'Pendants', ar: 'معلقات' }, { en: 'Chandeliers', ar: 'نجف' }, { en: 'Downlights', ar: 'سبوتات غاطسة' }, { en: 'Panel lights', ar: 'بانلات' }, { en: 'Track lighting', ar: 'تراك لايت' }] },
    { title: { en: 'Wall & floor', ar: 'حوائط وأرضيات' }, links: [{ en: 'Wall lights', ar: 'أباليك' }, { en: 'Floor lamps', ar: 'أباجورات أرضية' }, { en: 'Table lamps', ar: 'أباجورات مكتب' }, { en: 'Step lights', ar: 'إضاءة سلالم' }] },
    { title: { en: 'Linear & strips', ar: 'خطية وشرائط' }, links: [{ en: 'LED strips', ar: 'شرائط LED' }, { en: 'Aluminium profiles', ar: 'بروفايلات ألومنيوم' }, { en: 'Drivers', ar: 'محولات' }, { en: 'Linear systems', ar: 'أنظمة خطية' }] },
  ] },
  { id: 'outdoor', icon: 'sun', name: { en: 'Outdoor Lighting', ar: 'إضاءة خارجية' }, groups: [
    { title: { en: 'Facade', ar: 'واجهات' }, links: [{ en: 'Wall washers', ar: 'وول واشر' }, { en: 'Up/down lights', ar: 'إضاءة أعلى/أسفل' }] },
    { title: { en: 'Landscape', ar: 'لاندسكيب' }, links: [{ en: 'Bollards', ar: 'أعمدة قصيرة' }, { en: 'Spike lights', ar: 'كشافات أرضية' }, { en: 'In-ground', ar: 'مدفونة' }] },
    { title: { en: 'Area', ar: 'مساحات' }, links: [{ en: 'Floodlights', ar: 'كشافات' }, { en: 'Street lights', ar: 'أعمدة إنارة' }] },
  ] },
  { id: 'smart', icon: 'wifi', name: { en: 'Smart Home', ar: 'المنزل الذكي' }, groups: [
    { title: { en: 'Control', ar: 'التحكم' }, links: [{ en: 'Smart switches', ar: 'مفاتيح ذكية' }, { en: 'Dimmers', ar: 'ديمر' }, { en: 'Scene panels', ar: 'لوحات مشاهد' }, { en: 'Hubs & gateways', ar: 'بوابات' }] },
    { title: { en: 'Sensors', ar: 'حساسات' }, links: [{ en: 'Motion', ar: 'حركة' }, { en: 'Door & window', ar: 'أبواب ونوافذ' }, { en: 'Climate', ar: 'مناخ' }] },
    { title: { en: 'Comfort', ar: 'الراحة' }, links: [{ en: 'Curtain motors', ar: 'موتورات ستائر' }, { en: 'Thermostats', ar: 'ثرموستات' }, { en: 'Smart bulbs', ar: 'لمبات ذكية' }] },
  ] },
  { id: 'electrical', icon: 'plug-zap', name: { en: 'Electrical', ar: 'مستلزمات كهربائية' }, groups: [
    { title: { en: 'Wiring devices', ar: 'مفاتيح وبرايز' }, links: [{ en: 'Switches', ar: 'مفاتيح' }, { en: 'Sockets', ar: 'برايز' }, { en: 'USB outlets', ar: 'مخارج USB' }] },
    { title: { en: 'Distribution', ar: 'توزيع' }, links: [{ en: 'Circuit breakers', ar: 'قواطع' }, { en: 'Distribution boards', ar: 'لوحات توزيع' }, { en: 'RCDs', ar: 'قواطع تسريب' }] },
    { title: { en: 'Cables', ar: 'كابلات' }, links: [{ en: 'Power cables', ar: 'كابلات قوى' }, { en: 'Conduits', ar: 'مواسير' }, { en: 'Accessories', ar: 'إكسسوارات' }] },
  ] },
  { id: 'lamps', icon: 'lightbulb', name: { en: 'Lamps & Bulbs', ar: 'لمبات' }, groups: [
    { title: { en: 'By base', ar: 'حسب القاعدة' }, links: [{ en: 'E27', ar: 'E27' }, { en: 'E14', ar: 'E14' }, { en: 'GU10', ar: 'GU10' }, { en: 'G9', ar: 'G9' }] },
    { title: { en: 'By type', ar: 'حسب النوع' }, links: [{ en: 'Filament', ar: 'فتيل' }, { en: 'Tubes', ar: 'ليد تيوب' }, { en: 'Spots', ar: 'سبوت' }] },
  ] },
];

/** Category mega-menu: category rail → subcategory columns → promo card. Hover/focus a category to switch. */
function MegaMenu({ categories = DOTS_MEGA_DEFAULT, onNavigate, promo, style }) {
  const { tx, t } = useLocale();
  const [active, setActive] = React.useState(categories[0]?.id);
  const cur = categories.find((c) => c.id === active) || categories[0];
  const go = (x) => (e) => { e.preventDefault(); onNavigate && onNavigate(x); };
  return (
    <div className="d-mega" role="region" aria-label={t('Categories', 'الأقسام')} style={style}>
      <div className="d-container d-mega__in">
        <div className="d-mega__cats">
          {categories.map((c) => (
            <button key={c.id} type="button" className={cx('d-mega__cat', c.id === active && 'd-mega__cat--on')}
              onMouseEnter={() => setActive(c.id)} onFocus={() => setActive(c.id)} onClick={() => onNavigate && onNavigate({ category: c })}>
              <Icon name={c.icon} size={20} />{tx(c.name)}<Icon name="chevron-right" size={16} />
            </button>
          ))}
        </div>
        <div style={{ display: 'flex', flexDirection: 'column' }}>
          <div className="d-mega__cols" key={cur.id}>
            {cur.groups.map((g, i) => (
              <div className="d-mega__col" key={i}>
                <h4><span className="d-dot" />{tx(g.title)}</h4>
                <ul>{g.links.map((l, j) => <li key={j}><a href="#" onClick={go({ category: cur, sub: l })}>{tx(l)}</a></li>)}</ul>
              </div>
            ))}
          </div>
          <div className="d-mega__foot" style={{ marginTop: 'auto' }}>
            <a href="#" onClick={go({ category: cur })} style={{ fontWeight: 800 }}>{t('View all', 'عرض كل')} {tx(cur.name)}</a>
            <span>{t('10,000+ products · 60+ brands', 'أكثر من 10,000 منتج · 60+ علامة')}</span>
          </div>
        </div>
        <div className="d-mega__promo">
          <ImageFrame ratio="4 / 3" radius="var(--radius-md)" src={promo?.image} label={promo?.label || { en: 'Photo: lit interior, warm 2700K scene', ar: 'صورة: مساحة داخلية بإضاءة دافئة' }} />
          <div style={{ font: '600 18px/1.3 var(--font-display)' }}>{tx(promo?.title) || t('Free lighting design for projects', 'تصميم إضاءة مجاني للمشروعات')}</div>
          <Button variant="dark" size="sm" iconEnd="arrow-right" onClick={() => onNavigate && onNavigate({ service: true })}>{t('Book a consultation', 'احجز استشارة')}</Button>
        </div>
      </div>
    </div>
  );
}

;








/**
 * Site header: utility strip · main bar (logo, search, EN/AR, account, cart) · nav row (Categories mega-menu,
 * Products, Brands, Services, Projects ▾ [Current / Previous], Blog, About). Collapses to a mobile bar under `compact`.
 * active: products | brands | services | projects | blog | about
 * user: null (logged out → account icon navigates to 'login') or {name, first, email} (account dropdown).
 * onNavigate keys added: login · register · acc-orders · acc-wishlist · acc-addresses · acc-profile · acc-overview · logout
 */
function Navbar({ logoSrc, active, cartCount = 0, onCart, onLang, onNavigate, megaOpen, onMegaToggle, categories, user }) {
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

;






/** Site footer on soft warm gray: brand + newsletter column, four link columns, legal/payment strip. */
function Footer({ logoSrc, onNavigate }) {
  const { t } = useLocale();
  const cols = [
    [t('Shop', 'تسوق'), [[t('Indoor lighting', 'إضاءة داخلية'), 'category'], [t('Outdoor lighting', 'إضاءة خارجية'), 'category'], [t('Smart home', 'المنزل الذكي'), 'category'], [t('Electrical', 'مستلزمات كهربائية'), 'category'], [t('All brands', 'كل العلامات'), 'brands']]],
    [t('Services', 'الخدمات'), [[t('Lighting design', 'تصميم الإضاءة'), 'service'], [t('Smart home installation', 'تركيب المنزل الذكي'), 'service'], [t('Electrical contracting', 'مقاولات كهربائية'), 'service'], [t('Maintenance', 'الصيانة'), 'service']]],
    [t('Company', 'الشركة'), [[t('About Dots', 'عن دوتس'), 'about'], [t('Current projects', 'مشروعات جارية'), 'projects-current'], [t('Previous projects', 'مشروعات سابقة'), 'projects-previous'], [t('Blog', 'المدونة'), 'blog'], [t('Careers', 'الوظائف'), 'careers']]],
    [t('Help', 'المساعدة'), [[t('Track order', 'تتبع الطلب'), 'track'], [t('Delivery', 'التوصيل'), 'delivery'], [t('Returns', 'الاسترجاع'), 'returns'], [t('Warranty', 'الضمان'), 'warranty'], [t('Request a quote', 'اطلب عرض سعر'), 'home']]],
  ];
  return (
    <footer className="d-footer">
      <div className="d-container d-footer__top">
        <div style={{ display: 'grid', gap: 18, alignContent: 'start' }}>
          {logoSrc ? <img src={logoSrc} alt="Dots" style={{ height: 34, width: 'auto' }} /> : <span style={{ font: '700 30px/1 var(--font-display)' }}>Dots</span>}
          <Tagline />
          <p style={{ margin: 0, font: '500 14px/1.6 var(--font-body)', color: 'var(--fg-2)', maxWidth: 340 }}>
            {t('Lighting, smart home and electrical supply — with design, installation and after-sales support across Egypt.', 'إضاءة ومنزل ذكي ومستلزمات كهربائية — مع التصميم والتركيب وخدمة ما بعد البيع في كل مصر.')}
          </p>
          <form style={{ display: 'flex', gap: 8, maxWidth: 360 }} onSubmit={(e) => e.preventDefault()}>
            <input className="d-input" type="email" placeholder={t('Email for offers & new arrivals', 'بريدك للعروض والجديد')} aria-label={t('Email', 'البريد')} />
            <Button variant="dark">{t('Join', 'اشترك')}</Button>
          </form>
        </div>
        {cols.map(([h, items]) => (
          <div key={h}>
            <h5>{h}</h5>
            <ul>{items.map(([l, k]) => <li key={l}><a href="#" onClick={(e) => { e.preventDefault(); onNavigate && onNavigate(k); }}>{l}</a></li>)}</ul>
          </div>
        ))}
      </div>
      <div className="d-container d-footer__bottom">
        <span>© 2026 Dots. {t('All rights reserved.', 'جميع الحقوق محفوظة.')}</span>
        <span>{t('Cash on delivery · Visa · Mastercard · Meeza · InstaPay', 'الدفع عند الاستلام · فيزا · ماستركارد · ميزة · إنستاباي')}</span>
        <div className="d-footer__social">
          <IconButton icon="instagram" label="Instagram" size="sm" variant="outline" />
          <IconButton icon="facebook" label="Facebook" size="sm" variant="outline" />
          <IconButton icon="linkedin" label="LinkedIn" size="sm" variant="outline" />
          <IconButton icon="youtube" label="YouTube" size="sm" variant="outline" />
        </div>
      </div>
    </footer>
  );
}

;




/** items: {label, onClick?}[] — last item is the current page. Chevrons mirror in RTL. */
function Breadcrumbs({ items = [] }) {
  const { tx, t } = useLocale();
  return (
    <nav aria-label={t('Breadcrumb', 'مسار التنقل')}>
      <ol className="d-crumbs">
        {items.map((it, i) => {
          const last = i === items.length - 1;
          return (
            <li key={i} style={{ display: 'inline-flex', alignItems: 'center', gap: 6 }}>
              {i > 0 && <Icon name="chevron-right" size={14} />}
              {last ? <span aria-current="page">{tx(it.label)}</span> : <a href="#" onClick={(e) => { e.preventDefault(); it.onClick && it.onClick(); }}>{tx(it.label)}</a>}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}

;




function dotsPageWindow(page, total) {
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
  const s = new Set([1, total, page - 1, page, page + 1]);
  const arr = [...s].filter((n) => n >= 1 && n <= total).sort((a, b) => a - b);
  const out = [];
  arr.forEach((n, i) => { if (i && n - arr[i - 1] > 1) out.push('…' + n); out.push(n); });
  return out;
}

/** Numbered pagination with prev/next; current page is yellow. Shows "Showing x–y of N" when `perPage` & `count` given. */
function Pagination({ page = 1, total = 1, onChange, count, perPage }) {
  const { t, compact } = useLocale();
  const go = (n) => onChange && onChange(Math.max(1, Math.min(total, n)));
  return (
    <nav aria-label={t('Pagination', 'الصفحات')} style={{ display: 'flex', alignItems: 'center', gap: 16, flexWrap: 'wrap', justifyContent: 'space-between' }}>
      {count != null && perPage && !compact && (
        <span style={{ font: '600 13px var(--font-body)', color: 'var(--fg-2)' }}>
          {t('Showing', 'عرض')} {(page - 1) * perPage + 1}–{Math.min(page * perPage, count)} {t('of', 'من')} {count.toLocaleString('en-US')}
        </span>
      )}
      <div className="d-pages">
        <button type="button" className="d-page" onClick={() => go(page - 1)} disabled={page <= 1} aria-label={t('Previous page', 'السابق')}><Icon name="chevron-left" size={16} />{!compact && t('Prev', 'السابق')}</button>
        {(compact ? [page] : dotsPageWindow(page, total)).map((n) => typeof n === 'string'
          ? <span key={n} className="d-page d-page--gap">…</span>
          : <button key={n} type="button" className="d-page" aria-current={n === page ? 'page' : undefined} onClick={() => go(n)}>{n}</button>)}
        {compact && <span className="d-page d-page--gap" style={{ minWidth: 'auto' }}>{t('of', 'من')} {total}</span>}
        <button type="button" className="d-page" onClick={() => go(page + 1)} disabled={page >= total} aria-label={t('Next page', 'التالي')}>{!compact && t('Next', 'التالي')}<Icon name="chevron-right" size={16} /></button>
      </div>
    </nav>
  );
}

;



/** Underline tabs (default) or pill segmented tabs. tabs: {id, label, count?, disabled?}[] */
function Tabs({ tabs = [], value, onChange, variant = 'line', className, style }) {
  const { tx } = useLocale();
  return (
    <div role="tablist" className={cx('d-tabs', variant === 'pill' && 'd-tabs--pill', className)} style={style}>
      {tabs.map((tb) => (
        <button key={tb.id} role="tab" type="button" className="d-tab" aria-selected={tb.id === value} disabled={tb.disabled}
          onClick={() => onChange && onChange(tb.id)}>
          {tx(tb.label)}{tb.count != null && <span className="d-tab__n">{tb.count}</span>}
        </button>
      ))}
    </div>
  );
}

;




/** FAQ / details accordion. items: {id, title, content, disabled?}[]. The + turns yellow and rotates to × when open. */
function Accordion({ items = [], defaultOpen = [], multiple = false, className }) {
  const { tx } = useLocale();
  const [open, setOpen] = React.useState(defaultOpen);
  const toggle = (id) => setOpen((o) => (o.includes(id) ? o.filter((x) => x !== id) : multiple ? [...o, id] : [id]));
  return (
    <div className={cx('d-acc', className)}>
      {items.map((it) => {
        const on = open.includes(it.id);
        return (
          <div key={it.id} className={cx('d-acc__item', on && 'd-acc__item--open')}>
            <button type="button" className="d-acc__btn" aria-expanded={on} disabled={it.disabled} onClick={() => toggle(it.id)}>
              {tx(it.title)}<span className="d-acc__ico"><Icon name="plus" size={16} stroke={2} /></span>
            </button>
            {on && <div className="d-acc__panel">{tx(it.content)}</div>}
          </div>
        );
      })}
    </div>
  );
}

;








/** Small anchored prompt shown when a logged-out visitor taps the wishlist heart. Position via className/style (default: under the card's heart). */
function WishlistPrompt({ onClose, onLogin, onRegister, style, className }) {
  const { t } = useLocale();
  React.useEffect(() => { const h = setTimeout(() => onClose && onClose(), 6000); return () => clearTimeout(h); }, []);
  return (
    <div className={cx('d-favtip', className)} role="dialog" aria-label={t('Log in to save', 'سجّل الدخول للحفظ')} style={style}>
      <div className="d-favtip__head"><Icon name="heart" size={16} /><b>{t('Save it for later', 'احفظه لوقت لاحق')}</b>
        <button type="button" className="d-favtip__x" aria-label={t('Close', 'إغلاق')} onClick={onClose}><Icon name="x" size={16} /></button></div>
      <span>{t('Log in to keep a wishlist on all your devices.', 'سجّل الدخول لتحتفظ بالمفضلة على كل أجهزتك.')}</span>
      <div className="d-favtip__acts"><Button size="sm" onClick={onLogin}>{t('Log in', 'تسجيل الدخول')}</Button><button type="button" className="d-favtip__link" onClick={onRegister}>{t('Create account', 'إنشاء حساب')}</button></div>
    </div>
  );
}

/**
 * Catalog product card. Add to Cart (yellow) always sits beside Request a Quote (WhatsApp green).
 * product: {name, brand, sku, price?, was?, image?, cover? (photo fills frame), imageLabel?, badges?: [{tone,label}], stock: 'in'|'low'|'order', finishes?: css[], variants?: string}
 * No price → "Price on request" and the quote button becomes the primary action.
 * Wishlist: pass saved + onSave(p); return false from onSave (logged out) to show the WishlistPrompt with onLogin / onRegister.
 */
function ProductCard({ product: p, onOpen, onAdd, onQuote, saved, onSave, onLogin, onRegister, className }) {
  const { t, tx, price, compact } = useLocale();
  const [favLocal, setFavLocal] = React.useState(false);
  const [tip, setTip] = React.useState(false);
  const fav = saved ?? favLocal;
  const toggleFav = () => { if (onSave) { if (onSave(p) === false) setTip(true); } else setFavLocal(!favLocal); };
  const stock = { in: [t('In stock', 'متوفر'), 'var(--success-600)'], low: [t('Only a few left', 'كمية محدودة'), 'var(--warning-600)'], order: [t('On order · 2–3 wks', 'بالطلب · 2–3 أسابيع'), 'var(--ink-600)'] }[p.stock || 'in'];
  const hasPrice = p.price != null;
  return (
    <article className={cx('d-card', 'd-pcard', compact && 'd-pcard--compact', className)}>
      <a href="#" className="d-card__link" aria-label={tx(p.name)} onClick={(e) => { e.preventDefault(); onOpen && onOpen(p); }} />
      <ImageFrame className="d-pcard__media" contain={!!p.image && !p.cover} src={p.image} label={p.imageLabel || { en: 'Product cut-out, white bg', ar: 'صورة المنتج على خلفية بيضاء' }} />
      {p.badges && <div className="d-pcard__badges">{p.badges.map((b, i) => <Badge key={i} tone={b.tone}>{tx(b.label)}</Badge>)}</div>}
      <button type="button" className="d-pcard__fav" aria-pressed={fav} aria-label={fav ? t('Remove from wishlist', 'إزالة من المفضلة') : t('Save to wishlist', 'أضف للمفضلة')} onClick={toggleFav}>
        <Icon name="heart" size={18} style={fav ? { fill: 'var(--accent)', stroke: 'var(--ink-900)' } : undefined} />
      </button>
      {tip && <WishlistPrompt onClose={() => setTip(false)} onLogin={onLogin} onRegister={onRegister} />}
      <div className="d-card__body">
        <span className="d-pcard__brand">{tx(p.brand)}</span>
        <h3 className="d-pcard__title">{tx(p.name)}</h3>
        <span className="d-pcard__sku">{p.sku}</span>
        {(p.finishes || p.variants) && (
          <div className="d-pcard__meta">
            {p.finishes && <span className="d-pcard__dots">{p.finishes.map((c, i) => <i key={i} style={{ background: c }} />)}</span>}
            {p.variants && <span>{tx(p.variants)}</span>}
          </div>
        )}
        <div className="d-pcard__price">
          {hasPrice ? <><span className="d-pcard__now">{price(p.price)}</span>{p.was && <span className="d-pcard__was">{price(p.was)}</span>}</> : <span className="d-pcard__por">{t('Price on request', 'السعر عند الطلب')}</span>}
        </div>
        <span className="d-pcard__stock" style={{ color: stock[1] }}><i />{stock[0]}</span>
      </div>
      <div className="d-pcard__actions">
        {hasPrice ? (
          <>
            <Button size="sm" icon="shopping-bag" onClick={() => onAdd && onAdd(p)}>{compact ? t('Add', 'أضف') : t('Add to cart', 'أضف للسلة')}</Button>
            <IconButton variant="whatsapp" size="sm" icon="message-circle" label={t('Request a quote on WhatsApp', 'اطلب عرض سعر عبر واتساب')} onClick={() => onQuote && onQuote(p)} />
          </>
        ) : (
          <Button size="sm" variant="whatsapp" block onClick={() => onQuote && onQuote(p)}>{t('Request a quote', 'اطلب عرض سعر')}</Button>
        )}
      </div>
    </article>
  );
}

;




/** Manufacturer card. brand: {name, origin, count, logo?}. Without a logo file the name is set as a neutral wordmark. */
function BrandCard({ brand: b, onOpen, className }) {
  const { t, tx } = useLocale();
  return (
    <article className={cx('d-card', className)}>
      <a href="#" className="d-card__link" aria-label={b.name} onClick={(e) => { e.preventDefault(); onOpen && onOpen(b); }} />
      <div className="d-bcard__logo">{b.logo ? <img src={b.logo} alt={b.name} style={{ maxHeight: 56, width: 'auto' }} /> : <span className="d-bcard__word">{b.name}</span>}</div>
      <div className="d-card__body" style={{ gap: 4 }}>
        <div className="d-bcard__row">
          <div>
            <h3 className="d-bcard__name">{b.name}</h3>
            <div className="d-bcard__origin" style={{ marginTop: 4 }}>{tx(b.origin)} · {b.count.toLocaleString('en-US')} {t('products', 'منتج')}</div>
          </div>
          <span className="d-bcard__arrow"><Icon name="arrow-right" size={16} /></span>
        </div>
      </div>
    </article>
  );
}

;





/** Category entry. variant "card" = lifestyle photo 4:3; "tile" = product cut-out on warm gray, square. */
function CategoryCard({ category: c, variant = 'card', onOpen, className }) {
  const { t, tx } = useLocale();
  return (
    <article className={cx('d-card', variant === 'tile' && 'd-ccard--tile', className)}>
      <a href="#" className="d-card__link" aria-label={tx(c.name)} onClick={(e) => { e.preventDefault(); onOpen && onOpen(c); }} />
      <ImageFrame className="d-ccard__media" src={c.image} contain={variant === 'tile' && !!c.image} label={c.imageLabel || { en: 'Category photo', ar: 'صورة القسم' }} />
      <div className="d-ccard__body">
        <div style={{ flex: 1, minWidth: 0 }}>
          <h3 className="d-ccard__name">{tx(c.name)}</h3>
          {c.count != null && <div className="d-ccard__count">{c.count.toLocaleString('en-US')} {t('products', 'منتج')}</div>}
        </div>
        <span className="d-bcard__arrow"><Icon name="arrow-right" size={16} /></span>
      </div>
    </article>
  );
}

;





/** Service card. service: {num, title, desc, image?, imageLabel?}. */
function ServiceCard({ service: s, onOpen, className }) {
  const { t, tx } = useLocale();
  return (
    <article className={cx('d-card', className)}>
      <a href="#" className="d-card__link" aria-label={tx(s.title)} onClick={(e) => { e.preventDefault(); onOpen && onOpen(s); }} />
      <ImageFrame className="d-scard__media" src={s.image} label={s.imageLabel || { en: 'Service photo: team on site', ar: 'صورة الخدمة' }} />
      <div className="d-scard__body">
        <span className="d-scard__num">{s.num}</span>
        <h3 className="d-scard__title">{tx(s.title)}</h3>
        <p className="d-scard__desc">{tx(s.desc)}</p>
        <span className="d-scard__more">{t('Learn more', 'اعرف المزيد')}<Icon name="arrow-right" size={16} /></span>
      </div>
    </article>
  );
}

;






/** Blog article card. article: {title, excerpt, category, date, read, image, author?}. variant "wide" = featured horizontal hero card. */
function ArticleCard({ article: a, variant, onOpen, className }) {
  const { t, tx } = useLocale();
  const wide = variant === 'wide';
  return (
    <article className={cx('d-card', 'd-acard', wide && 'd-acard--wide', className)}>
      <a href="#" className="d-card__link" aria-label={tx(a.title)} onClick={(e) => { e.preventDefault(); onOpen && onOpen(a); }} />
      <ImageFrame className="d-acard__media" src={a.image} alt={a.title} label={a.imageLabel} />
      {!wide && <span className="d-acard__tag"><Badge tone="outline">{tx(a.category)}</Badge></span>}
      <div className="d-card__body" style={{ gap: 10, padding: wide ? undefined : 20 }}>
        {wide && <div style={{ display: 'flex', gap: 8 }}><Badge tone="accent">{t('Featured', 'مميز')}</Badge><Badge tone="outline">{tx(a.category)}</Badge></div>}
        <h3 className="d-acard__title">{tx(a.title)}</h3>
        <p className="d-acard__ex">{tx(a.excerpt)}</p>
        <div className="d-acard__meta" style={{ marginTop: 'auto', paddingTop: 6 }}>
          {wide && a.author && <><span style={{ color: 'var(--fg-1)', fontWeight: 800 }}>{tx(a.author)}</span><i /></>}
          <span>{tx(a.date)}</span><i /><span style={{ display: 'inline-flex', alignItems: 'center', gap: 4 }}><Icon name="clock" size={13} />{a.read} {t('min read', 'دقائق قراءة')}</span>
        </div>
        {wide && <span className="d-scard__more" style={{ marginTop: 6 }}>{t('Read article', 'اقرأ المقال')}<Icon name="arrow-right" size={16} /></span>}
      </div>
    </article>
  );
}

;







/**
 * Project card. project: {title, location, type, status: 'progress'|'done', progress?, year?, image, client?}.
 * Current projects show an "In progress" badge + progress bar; completed show year. variant "overlay" = text on photo (masonry).
 */
function ProjectCard({ project: p, variant, onOpen, style, className }) {
  const { t, tx } = useLocale();
  const live = p.status === 'progress';
  const overlay = variant === 'overlay';
  return (
    <article className={cx('d-card', 'd-prcard', overlay && 'd-prcard--overlay', className)} style={style}>
      <a href="#" className="d-card__link" aria-label={tx(p.title)} onClick={(e) => { e.preventDefault(); onOpen && onOpen(p); }} />
      <ImageFrame className="d-prcard__media" src={p.image} alt={p.title} label={p.imageLabel} />
      <div className="d-prcard__badges">
        {live ? <Badge tone="accent" dot>{t('In progress', 'قيد التنفيذ')}</Badge> : <Badge tone="dark">{t('Completed', 'مكتمل')} {p.year}</Badge>}
        <Badge tone="outline">{tx(p.type)}</Badge>
      </div>
      <div className="d-card__body" style={{ gap: 10, padding: 20 }}>
        <span className="d-prcard__loc"><Icon name="map-pin" size={14} />{tx(p.location)}</span>
        <h3 className="d-prcard__title">{tx(p.title)}</h3>
        {live && !overlay && <Progress value={p.progress} label={tx(p.phase) || t('Progress', 'نسبة الإنجاز')} />}
        <div className="d-prcard__foot">
          <span>{live ? t('Handover', 'التسليم') + ' ' + tx(p.eta) : tx(p.client) || tx(p.scope)}</span>
          <span style={{ whiteSpace: 'nowrap', flex: 'none', display: 'inline-flex', alignItems: 'center', gap: 6, color: overlay ? '#fff' : 'var(--fg-1)', fontWeight: 800 }}>{t('View project', 'عرض المشروع')}<Icon name="arrow-right" size={14} /></span>
        </div>
      </div>
    </article>
  );
}

;





/** Image gallery: large stage with prev/next, counter, caption, and a thumbnail rail. images: {src, caption?, label?}[] */
function Gallery({ images = [], ratio = '16 / 10', thumbs = true }) {
  const { t, tx } = useLocale();
  const [i, setI] = React.useState(0);
  const cur = images[i] || {};
  const go = (d) => setI((x) => (x + d + images.length) % images.length);
  return (
    <div className="d-gallery">
      <div className="d-gallery__main">
        <ImageFrame key={i} ratio={ratio} src={cur.src} alt={cur.caption} label={cur.label} style={{ animation: 'd-fade-in 320ms var(--ease-out)' }} />
        {images.length > 1 && <>
          <IconButton className="d-gallery__nav d-gallery__nav--prev" variant="outline" icon="chevron-left" label={t('Previous image', 'الصورة السابقة')} onClick={() => go(-1)} style={{ borderRadius: '50%', borderColor: 'transparent' }} />
          <IconButton className="d-gallery__nav d-gallery__nav--next" variant="outline" icon="chevron-right" label={t('Next image', 'الصورة التالية')} onClick={() => go(1)} style={{ borderRadius: '50%', borderColor: 'transparent' }} />
        </>}
        {cur.caption && <span className="d-gallery__cap">{tx(cur.caption)}</span>}
        <span className="d-gallery__count">{i + 1} / {images.length}</span>
      </div>
      {thumbs && images.length > 1 && (
        <div className="d-gallery__thumbs">
          {images.map((im, k) => (
            <button key={k} type="button" className="d-gallery__thumb" aria-current={k === i} aria-label={tx(im.caption) || String(k + 1)} onClick={() => setI(k)}>
              <ImageFrame ratio="4 / 3" src={im.src} />
            </button>
          ))}
        </div>
      )}
    </div>
  );
}

;





/** Drag / keyboard before–after comparison slider. before/after: {src, label?}. */
function BeforeAfter({ before, after, ratio = '16 / 9', start = 50 }) {
  const { t } = useLocale();
  const [v, setV] = React.useState(start);
  return (
    <div className="d-ba" style={{ aspectRatio: ratio }} dir="ltr">
      <ImageFrame src={before.src} label={before.label} style={{ position: 'absolute', inset: 0 }} />
      <div className="d-ba__after" style={{ clipPath: `inset(0 0 0 ${v}%)` }}><ImageFrame src={after.src} label={after.label} style={{ position: 'absolute', inset: 0 }} /></div>
      <span className="d-ba__label d-ba__label--before">{t('Before', 'قبل')}</span>
      <span className="d-ba__label d-ba__label--after">{t('After', 'بعد')}</span>
      <div className="d-ba__line" style={{ left: v + '%' }}><span className="d-ba__knob"><Icon name="chevrons-left-right" size={20} stroke={2} flip={false} /></span></div>
      <input type="range" min="0" max="100" value={v} onChange={(e) => setV(+e.target.value)} aria-label={t('Compare before and after', 'قارن قبل وبعد')} />
    </div>
  );
}

;



/** Key-numbers strip. stats: {value: number, suffix?: string, label}[]. Counts up once when scrolled into view. */
function StatStrip({ stats = [], className }) {
  const { tx } = useLocale();
  const ref = React.useRef(null);
  const [k, setK] = React.useState(0);
  React.useEffect(() => {
    const el = ref.current; if (!el) return;
    let raf, t0, started = false;
    const run = (ts) => { t0 = t0 || ts; const p = Math.min(1, (ts - t0) / 1200); setK(1 - Math.pow(1 - p, 3)); if (p < 1) raf = requestAnimationFrame(run); };
    const check = () => { if (started) return; const r = el.getBoundingClientRect(); if (r.top < window.innerHeight && r.bottom > 0) { started = true; raf = requestAnimationFrame(run); setTimeout(() => setK(1), 1600); } };
    const iv = setInterval(check, 300); window.addEventListener('scroll', check, true); check();
    return () => { clearInterval(iv); window.removeEventListener('scroll', check, true); cancelAnimationFrame(raf); };
  }, []);
  return (
    <div ref={ref} className={cx('d-stats', className)}>
      {stats.map((s, i) => (
        <div key={i} className="d-stat">
          <span className="d-stat__n">{Math.round(s.value * k).toLocaleString('en-US')}{s.suffix && <sup>{s.suffix}</sup>}</span>
          <span className="d-stat__l">{tx(s.label)}</span>
        </div>
      ))}
    </div>
  );
}

;



/** Bordered grid of manufacturer logos (or neutral wordmarks when no logo file). brands: {id, name, logo?}[] */
function LogoStrip({ brands = [], onOpen, className }) {
  return (
    <div className={cx('d-logos', className)}>
      {brands.map((b) => (
        <button key={b.id || b.name} type="button" onClick={() => onOpen && onOpen(b)} aria-label={b.name}>
          {b.logo ? <img src={b.logo} alt={b.name} style={{ maxHeight: 40, maxWidth: '70%', filter: 'grayscale(1)' }} /> : b.name}
        </button>
      ))}
    </div>
  );
}

;




/** Share row for articles/projects: WhatsApp, Facebook, LinkedIn, X, copy link. */
function ShareBar({ onShare, vertical }) {
  const { t } = useLocale();
  const items = [['message-circle', 'WhatsApp'], ['facebook', 'Facebook'], ['linkedin', 'LinkedIn'], ['twitter', 'X'], ['link', t('Copy link', 'نسخ الرابط')]];
  return (
    <div className="d-share" style={vertical ? { flexDirection: 'column', alignItems: 'flex-start' } : undefined}>
      <span className="d-share__l">{t('Share', 'مشاركة')}</span>
      {items.map(([ic, l]) => <IconButton key={l} icon={ic} label={l} variant="outline" size="sm" style={{ borderRadius: '50%', borderColor: 'var(--border-2)', borderWidth: 1 }} onClick={() => onShare && onShare(l)} />)}
    </div>
  );
}

;






function DotsFSec({ title, open: o = true, children }) {
  const [open, setOpen] = React.useState(o);
  return (
    <div className={cx('d-fsec', open && 'd-fsec--open')}>
      <button type="button" className="d-fsec__btn" aria-expanded={open} onClick={() => setOpen(!open)}>{title}<Icon name="chevron-down" size={18} /></button>
      {open && <div className="d-fsec__body">{children}</div>}
    </div>
  );
}

/**
 * Catalog filter sidebar: category tree, manufacturer checklist (searchable), variant facets, price range, availability.
 * Controlled: value = {category, brands[], temps[], watts[], finishes[], inStock}; onChange(next).
 * facets = {categories:[{id,name,count,children?}], brands:[{name,count}], temps:[{value,label,chip,count}], watts:[{value,count}], finishes:[{value,label,color}]}
 */
function FilterSidebar({ facets, value, onChange, onClear, className, style }) {
  const { t, tx } = useLocale();
  const [bq, setBq] = React.useState('');
  const [allBrands, setAllBrands] = React.useState(false);
  const v = value || {};
  const toggle = (key, x) => { const cur = v[key] || []; onChange && onChange({ ...v, [key]: cur.includes(x) ? cur.filter((y) => y !== x) : [...cur, x] }); };
  const brands = (facets.brands || []).filter((b) => b.name.toLowerCase().includes(bq.toLowerCase()));
  const tree = (list) => (
    <ul className="d-tree">{list.map((c) => (
      <li key={c.id}>
        <button type="button" aria-current={v.category === c.id} onClick={() => onChange && onChange({ ...v, category: c.id })}>
          <span>{tx(c.name)}</span><span>{c.count}</span>
        </button>
        {c.children && tree(c.children)}
      </li>))}
    </ul>
  );
  return (
    <aside className={cx('d-filters', className)} style={style} aria-label={t('Filters', 'الفلاتر')}>
      <div className="d-filters__head">
        <h3><Icon name="sliders-horizontal" size={18} />{t('Filters', 'الفلاتر')}</h3>
        {onClear && <button type="button" className="d-fsec__more" onClick={onClear}>{t('Clear all', 'مسح الكل')}</button>}
      </div>
      {facets.categories && <DotsFSec title={t('Category', 'القسم')}>{tree(facets.categories)}</DotsFSec>}
      {facets.brands && (
        <DotsFSec title={t('Manufacturer', 'الشركة المصنعة')}>
          <Input size="sm" icon="search" placeholder={t('Search brands', 'ابحث عن علامة')} value={bq} onChange={(e) => setBq(e.target.value)} />
          {(allBrands ? brands : brands.slice(0, 5)).map((b) => (
            <Checkbox key={b.name} row label={b.name} count={b.count} checked={(v.brands || []).includes(b.name)} onChange={() => toggle('brands', b.name)} />
          ))}
          {brands.length > 5 && <button type="button" className="d-fsec__more" onClick={() => setAllBrands(!allBrands)}>{allBrands ? t('Show less', 'عرض أقل') : `${t('Show all', 'عرض الكل')} (${brands.length})`}</button>}
        </DotsFSec>
      )}
      {facets.temps && (
        <DotsFSec title={t('Colour temperature', 'درجة حرارة اللون')}>
          {facets.temps.map((o) => (
            <label key={o.value} className="d-check d-check--row">
              <input type="checkbox" checked={(v.temps || []).includes(o.value)} onChange={() => toggle('temps', o.value)} />
              <span className="d-opt__chip" style={{ background: o.chip }} />
              <span>{o.value} <span style={{ color: 'var(--fg-3)', fontWeight: 500 }}>· {tx(o.label)}</span></span>
              <span className="d-check__count">{o.count}</span>
            </label>
          ))}
        </DotsFSec>
      )}
      {facets.watts && (
        <DotsFSec title={t('Wattage', 'القدرة')}>
          <div className="d-variant__opts">{facets.watts.map((w) => (
            <button key={w.value} type="button" className="d-opt" style={{ minWidth: 0, height: 34, paddingInline: 10, fontSize: 13 }} aria-pressed={(v.watts || []).includes(w.value)} onClick={() => toggle('watts', w.value)}>{w.value}</button>
          ))}</div>
        </DotsFSec>
      )}
      {facets.finishes && (
        <DotsFSec title={t('Finish', 'التشطيب')}>
          <div className="d-variant__opts" style={{ gap: 12, paddingInline: 3 }}>{facets.finishes.map((f) => (
            <button key={f.value} type="button" className="d-swatch" style={{ background: f.color, width: 30, height: 30 }} aria-pressed={(v.finishes || []).includes(f.value)} aria-label={tx(f.label)} title={tx(f.label)} onClick={() => toggle('finishes', f.value)} />
          ))}</div>
        </DotsFSec>
      )}
      <DotsFSec title={t('Price (EGP)', 'السعر (ج.م)')}>
        <div className="d-range__track"><div className="d-range__fill" style={{ insetInlineStart: '8%', insetInlineEnd: '30%' }} /><span className="d-range__knob" style={{ left: '8%' }} /><span className="d-range__knob" style={{ left: '70%' }} /></div>
        <div className="d-range"><input className="d-input d-input--sm" defaultValue="250" aria-label={t('Min', 'من')} /><span style={{ color: 'var(--fg-3)' }}>–</span><input className="d-input d-input--sm" defaultValue="12,000" aria-label={t('Max', 'إلى')} /></div>
      </DotsFSec>
      <DotsFSec title={t('Availability', 'التوفر')}>
        <Checkbox label={t('In stock only', 'المتوفر فقط')} checked={!!v.inStock} onChange={(c) => onChange && onChange({ ...v, inStock: c })} />
        <Checkbox label={t('Ships in 24h (Cairo & Giza)', 'شحن خلال 24 ساعة (القاهرة والجيزة)')} />
      </DotsFSec>
    </aside>
  );
}

;




/** Row of removable active-filter chips + "Clear all". chips: {id, facet, label}[] */
function ActiveFilters({ chips = [], onRemove, onClear, count }) {
  const { t, tx } = useLocale();
  if (!chips.length) return null;
  return (
    <div className="d-active">
      {count != null && <span style={{ font: '800 13px var(--font-body)', marginInlineEnd: 4 }}>{count.toLocaleString('en-US')} {t('results', 'نتيجة')}</span>}
      {chips.map((c) => <Chip key={c.id} active facet={tx(c.facet)} onRemove={() => onRemove && onRemove(c)}>{tx(c.label)}</Chip>)}
      <button type="button" className="d-active__clear" onClick={onClear}>{t('Clear all', 'مسح الكل')}</button>
    </div>
  );
}

;



/** Technical specification table, zebra rows, grouped. groups: {title, rows: [label, value][]}[] */
function SpecTable({ groups = [], caption }) {
  const { tx } = useLocale();
  return (
    <table className="d-spec">
      {caption && <caption>{tx(caption)}</caption>}
      <tbody>
        {groups.map((g, gi) => (
          <React.Fragment key={gi}>
            {g.title && <tr className="d-spec__group"><th colSpan={2}>{tx(g.title)}</th></tr>}
            {g.rows.map(([k, val], i) => <tr key={i}><th scope="row">{tx(k)}</th><td>{tx(val)}</td></tr>)}
          </React.Fragment>
        ))}
      </tbody>
    </table>
  );
}

;




/** Datasheet / photometric (IES, LDT) / installation file list. files: {name, ext, size, href?, kind?}[] */
function FileDownloads({ files = [] }) {
  const { tx, t } = useLocale();
  const icon = { pdf: 'file-text', ies: 'lightbulb', ldt: 'lightbulb', dwg: 'pencil-ruler', zip: 'folder-archive' };
  return (
    <div className="d-files">
      {files.map((f, i) => (
        <a key={i} className="d-file" href={f.href || '#'} onClick={(e) => !f.href && e.preventDefault()} download>
          <span className="d-file__ico"><Icon name={icon[f.ext] || 'file'} size={20} /><span className="d-file__ext">{f.ext.toUpperCase()}</span></span>
          <span style={{ minWidth: 0 }}>
            <div className="d-file__name">{tx(f.name)}</div>
            <div className="d-file__meta">{tx(f.kind) || f.ext.toUpperCase()} · {f.size}</div>
          </span>
          <span className="d-file__dl" aria-label={t('Download', 'تحميل')}><Icon name="download" size={16} /></span>
        </a>
      ))}
    </div>
  );
}

;




/** Toast notification. tone: success | error | warning | info | cart. Place inside a `.d-toasts` container (bottom-end). */
function Toast({ tone = 'success', title, message, actions = [], onClose, style }) {
  const { t, tx } = useLocale();
  const ico = { success: 'check', error: 'circle-alert', warning: 'triangle-alert', info: 'info', cart: 'shopping-bag' }[tone];
  return (
    <div className={cx('d-toast', `d-toast--${tone}`)} role={tone === 'error' ? 'alert' : 'status'} style={style}>
      <span className="d-toast__ico"><Icon name={ico} size={16} stroke={2} /></span>
      <div style={{ minWidth: 0 }}>
        <div className="d-toast__title">{tx(title)}</div>
        {message && <div className="d-toast__msg">{tx(message)}</div>}
        {actions.length > 0 && <div className="d-toast__act">{actions.map((a, i) => <button key={i} type="button" onClick={a.onClick}>{tx(a.label)}</button>)}</div>}
      </div>
      {onClose && <button type="button" className="d-toast__x" onClick={onClose} aria-label={t('Dismiss', 'إغلاق')}><Icon name="x" size={16} /></button>}
    </div>
  );
}

;



/** Progress bar with pulsing node head; optional milestone steps. value 0–100; tone "light" for use on photos. */
function Progress({ value = 0, label, steps, current, tone, className }) {
  const { tx } = useLocale();
  const done = value >= 100;
  return (
    <div className={cx('d-progress', done && 'd-progress--done', tone === 'light' && 'd-progress--light', className)} role="progressbar" aria-valuenow={value} aria-valuemin={0} aria-valuemax={100} aria-label={tx(label)}>
      {label && <div className="d-progress__head"><span>{tx(label)}</span><b>{value}%</b></div>}
      <div className="d-progress__track"><div className="d-progress__fill" style={{ width: Math.min(100, value) + '%' }} /></div>
      {steps && (
        <div className="d-progress__steps">
          {steps.map((s, i) => <span key={i} className={cx('d-progress__step', i < current && 'd-progress__step--done', i === current && 'd-progress__step--now')}>{tx(s)}</span>)}
        </div>
      )}
    </div>
  );
}

;






/** One cart line: thumb, name, variant summary, stepper + remove, line total. item: {id, name, variant, price, qty, image?} */
function CartLine({ item, onQty, onRemove }) {
  const { t, tx, price } = useLocale();
  return (
    <div className="d-line">
      <ImageFrame className="d-line__img" src={item.image} contain={!!item.image} />
      <div style={{ minWidth: 0 }}>
        <p className="d-line__name">{tx(item.name)}</p>
        <div className="d-line__var">{tx(item.variant)}</div>
        <div className="d-line__row">
          <QuantityStepper size="sm" value={item.qty} onChange={(q) => onQty && onQty(item, q)} />
          <button type="button" className="d-line__rm" onClick={() => onRemove && onRemove(item)}><Icon name="trash-2" size={14} />{t('Remove', 'حذف')}</button>
        </div>
      </div>
      <div className="d-line__price">{price(item.price * item.qty)}</div>
    </div>
  );
}

;







/** Slide-in cart (from the end edge; mirrors in RTL). `inline` renders it in-flow for docs/previews. */
function CartDrawer({ open = true, inline, items = [], onClose, onQty, onRemove, onCheckout, onViewCart, onQuote, freeShipAt = 5000 }) {
  const { t, price } = useLocale();
  if (!open) return null;
  const sub = items.reduce((s, i) => s + i.price * i.qty, 0);
  const left = Math.max(0, freeShipAt - sub);
  const n = items.reduce((s, i) => s + i.qty, 0);
  const panel = (
    <aside className={'d-drawer' + (inline ? ' d-drawer--inline' : '')} role="dialog" aria-label={t('Cart', 'السلة')}>
      <div className="d-drawer__head">
        <Icon name="shopping-bag" size={22} />
        <h3>{t('Your cart', 'سلتك')} <span style={{ color: 'var(--fg-3)', fontFamily: 'var(--font-body)', fontSize: 15, fontWeight: 600 }}>({n})</span></h3>
        <IconButton icon="x" label={t('Close', 'إغلاق')} onClick={onClose} style={{ marginInlineStart: 'auto' }} />
      </div>
      <div className="d-ship">
        {left > 0 ? <>{t('Add', 'أضف')} <b>{price(left)}</b> {t('more for free delivery in Cairo & Giza', 'للحصول على توصيل مجاني للقاهرة والجيزة')}</> : <b>{t('You’ve unlocked free delivery', 'حصلت على توصيل مجاني')}</b>}
        <div className="d-ship__bar"><div className="d-ship__fill" style={{ width: Math.min(100, (sub / freeShipAt) * 100) + '%' }} /></div>
      </div>
      <div className="d-drawer__body">
        {items.length ? items.map((it) => <CartLine key={it.id} item={it} onQty={onQty} onRemove={onRemove} />) : (
          <div style={{ padding: '48px 0', textAlign: 'center', color: 'var(--fg-2)', font: '600 15px var(--font-body)' }}>{t('Your cart is empty.', 'سلتك فارغة.')}</div>
        )}
      </div>
      <div className="d-drawer__foot">
        <div className="d-totals">
          <div style={{ color: 'var(--fg-1)', font: '800 16px var(--font-body)' }}><span>{t('Subtotal', 'الإجمالي الفرعي')}</span><b style={{ fontSize: 20, fontWeight: 800 }}>{price(sub)}</b></div>
          <span style={{ font: '500 12px var(--font-body)', color: 'var(--fg-3)' }}>{t('Includes 14% VAT. Delivery calculated at checkout.', 'شامل ضريبة 14%. يُحسب التوصيل عند الدفع.')}</span>
        </div>
        <Button size="lg" block iconEnd="arrow-right" onClick={onCheckout} disabled={!items.length}>{t('Checkout', 'إتمام الشراء')}</Button>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 }}>
          <Button variant="outline" onClick={onViewCart}>{t('View cart', 'عرض السلة')}</Button>
          <Button variant="quote-outline" onClick={onQuote}>{t('Quote this cart', 'عرض سعر للسلة')}</Button>
        </div>
      </div>
    </aside>
  );
  if (inline) return panel;
  return <><div className="d-overlay" onClick={onClose} />{panel}</>;
}

;








function DotsCoSec({ n, title, done, children, aside }) {
  return (
    <section className={cx('d-co__sec', done && 'd-co__sec--done')}>
      <div className="d-co__head"><span className="d-co__num">{done ? <Icon name="check" size={16} stroke={2.25} /> : n}</span><h3>{title}</h3>{aside && <span style={{ marginInlineStart: 'auto' }}>{aside}</span>}</div>
      {children}
    </section>
  );
}

/**
 * Single-page checkout: contact, delivery address (Egyptian governorates), delivery method, payment.
 * Logged-in: pass contact {name, mobile, email} to prefill, and addresses [{id, label, title, lines, def}] to show saved
 * addresses as selectable cards + "Use a new address" (fields only render for the new-address choice).
 */
function CheckoutForm({ delivery, onDelivery, payment, onPayment, contact, addresses }) {
  const { t, tx, price } = useLocale();
  const saved = addresses && addresses.length ? addresses : null;
  const [addr, setAddr] = React.useState(saved ? (saved.find((a) => a.def) || saved[0]).id : 'new');
  const [d, setD] = React.useState(delivery || 'standard');
  const [p, setP] = React.useState(payment || 'cod');
  const setDel = (v) => { setD(v); onDelivery && onDelivery(v); };
  const setPay = (v) => { setP(v); onPayment && onPayment(v); };
  const gov = [t('Cairo', 'القاهرة'), t('Giza', 'الجيزة'), t('Alexandria', 'الإسكندرية'), t('Qalyubia', 'القليوبية'), t('Sharqia', 'الشرقية'), t('Red Sea', 'البحر الأحمر'), t('South Sinai', 'جنوب سيناء')];
  return (
    <form className="d-co" onSubmit={(e) => e.preventDefault()}>
      <DotsCoSec n={1} title={t('Contact', 'بيانات التواصل')}>
        <div className="d-co__grid">
          <Input label={t('Full name', 'الاسم بالكامل')} placeholder={t('e.g. Mona Adel', 'مثال: منى عادل')} defaultValue={contact ? tx(contact.name) : undefined} />
          <Input label={t('Mobile number', 'رقم الموبايل')} placeholder="01X XXXX XXXX" inputMode="tel" dir="ltr" help={t('We’ll confirm the order by WhatsApp.', 'سنؤكد الطلب عبر واتساب.')} defaultValue={contact ? contact.mobile : undefined} />
          <Input className="span-2" label={t('Email', 'البريد الإلكتروني')} optional type="email" placeholder="name@example.com" dir="ltr" defaultValue={contact ? contact.email : undefined} />
          <Checkbox className="span-2" label={t('This is a company / project order (add tax ID for invoice)', 'طلب شركة / مشروع (إضافة الرقم الضريبي للفاتورة)')} />
        </div>
      </DotsCoSec>
      <DotsCoSec n={2} title={t('Delivery address', 'عنوان التوصيل')}>
        {saved && (
          <div style={{ display: 'grid', gap: 10 }}>
            {saved.map((a) => <RadioCard key={a.id} name="addr" value={a.id} checked={addr === a.id} onChange={setAddr} icon={a.icon || 'map-pin'} title={<>{tx(a.title)}{a.def && <span className="d-badge d-badge--neutral" style={{ marginInlineStart: 4 }}>{t('Default', 'الافتراضي')}</span>}</>} desc={tx(a.lines)} />)}
            <RadioCard name="addr" value="new" checked={addr === 'new'} onChange={setAddr} icon="plus" title={t('Use a new address', 'استخدم عنواناً جديداً')} desc={t('It will be saved to your account', 'سيتم حفظه في حسابك')} />
          </div>
        )}
        {addr === 'new' && <div className="d-co__grid">
          <Select label={t('Governorate', 'المحافظة')} options={gov} defaultValue={gov[0]} />
          <Input label={t('City / district', 'المدينة / الحي')} placeholder={t('e.g. New Cairo, 5th Settlement', 'مثال: التجمع الخامس')} />
          <Input className="span-2" label={t('Street & building', 'الشارع والعقار')} placeholder={t('Street, building no.', 'الشارع، رقم العقار')} />
          <Input label={t('Floor / apartment', 'الدور / الشقة')} placeholder="3 / 12" />
          <Input label={t('Landmark', 'علامة مميزة')} optional placeholder={t('Near…', 'بجوار…')} />
        </div>}
      </DotsCoSec>
      <DotsCoSec n={3} title={t('Delivery method', 'طريقة التوصيل')}>
        <div style={{ display: 'grid', gap: 10 }}>
          <RadioCard name="del" value="standard" checked={d === 'standard'} onChange={setDel} icon="truck" title={t('Standard delivery', 'توصيل عادي')} desc={t('2–4 working days · all governorates', '2–4 أيام عمل · كل المحافظات')} meta={price(150)} />
          <RadioCard name="del" value="express" checked={d === 'express'} onChange={setDel} icon="zap" title={t('Next-day · Cairo & Giza', 'اليوم التالي · القاهرة والجيزة')} desc={t('Order before 4 pm', 'اطلب قبل الساعة 4 مساءً')} meta={price(300)} />
          <RadioCard name="del" value="pickup" checked={d === 'pickup'} onChange={setDel} icon="store" title={t('Pick up from showroom', 'استلام من المعرض')} desc={t('New Cairo · ready in 24h', 'القاهرة الجديدة · جاهز خلال 24 ساعة')} meta={t('Free', 'مجاناً')} />
          <RadioCard name="del" value="install" checked={d === 'install'} onChange={setDel} icon="wrench" title={t('Delivery + installation', 'توصيل + تركيب')} desc={t('Certified Dots technician, scheduled by phone', 'فني دوتس معتمد، يتم التنسيق هاتفياً')} meta={t('From', 'يبدأ من') + ' ' + price(450)} />
        </div>
      </DotsCoSec>
      <DotsCoSec n={4} title={t('Payment', 'الدفع')}>
        <div style={{ display: 'grid', gap: 10 }}>
          <RadioCard name="pay" value="cod" checked={p === 'cod'} onChange={setPay} icon="banknote" title={t('Cash on delivery', 'الدفع عند الاستلام')} desc={t('Pay the courier in cash or card', 'ادفع للمندوب نقداً أو بالكارت')} />
          <RadioCard name="pay" value="card" checked={p === 'card'} onChange={setPay} icon="credit-card" title={t('Credit / debit card', 'بطاقة ائتمان / خصم')} desc="Visa · Mastercard · Meeza" />
          <RadioCard name="pay" value="instapay" checked={p === 'instapay'} onChange={setPay} icon="smartphone" title="InstaPay / Fawry" desc={t('Reference sent by SMS', 'يُرسل الرقم المرجعي برسالة')} />
          <RadioCard name="pay" value="install" checked={p === 'install'} onChange={setPay} icon="calendar-days" title={t('Instalments', 'التقسيط')} desc={t('Up to 12 months with partner banks', 'حتى 12 شهر مع البنوك الشريكة')} />
        </div>
      </DotsCoSec>
    </form>
  );
}

;






/** Order summary panel: compact lines, promo code, totals (VAT-inclusive), CTA, trust row. */
function OrderSummary({ items = [], delivery = 150, discount = 0, cta, onCta, showLines = true }) {
  const { t, tx, price } = useLocale();
  const sub = items.reduce((s, i) => s + i.price * i.qty, 0);
  const total = sub + delivery - discount;
  return (
    <aside className="d-summary">
      <h3>{t('Order summary', 'ملخص الطلب')}</h3>
      {showLines && <div>{items.map((it) => (
        <div className="d-line" key={it.id}>
          <div className="d-line__img" style={{ position: 'relative' }}><ImageFrame src={it.image} contain style={{ width: '100%', height: '100%', borderRadius: 6 }} /><span className="d-summary__qty">{it.qty}</span></div>
          <div style={{ minWidth: 0 }}><p className="d-line__name" style={{ fontSize: 13 }}>{tx(it.name)}</p><div className="d-line__var">{tx(it.variant)}</div></div>
          <div className="d-line__price" style={{ fontSize: 14 }}>{price(it.price * it.qty)}</div>
        </div>))}</div>}
      <div className="d-promo">
        <input className="d-input" placeholder={t('Promo code', 'كود الخصم')} aria-label={t('Promo code', 'كود الخصم')} />
        <Button variant="outline">{t('Apply', 'تطبيق')}</Button>
      </div>
      <div className="d-totals">
        <div><span>{t('Subtotal', 'الإجمالي الفرعي')}</span><b>{price(sub)}</b></div>
        <div><span>{t('Delivery', 'التوصيل')}</span><b>{delivery ? price(delivery) : t('Free', 'مجاناً')}</b></div>
        {discount > 0 && <div><span>{t('Discount', 'الخصم')}</span><b style={{ color: 'var(--success-600)' }}>−{price(discount)}</b></div>}
        <div className="d-totals__grand"><span>{t('Total', 'الإجمالي')}</span><b>{price(total)}</b></div>
        <span style={{ font: '500 12px var(--font-body)', color: 'var(--fg-3)' }}>{t('Prices include 14% VAT', 'الأسعار شاملة ضريبة القيمة المضافة 14%')}</span>
      </div>
      {cta !== false && <Button size="lg" block iconEnd="arrow-right" onClick={onCta}>{cta || t('Place order', 'تأكيد الطلب')}</Button>}
      <div className="d-trust">
        <span><Icon name="shield-check" size={16} />{t('Official warranty', 'ضمان رسمي')}</span>
        <span><Icon name="rotate-ccw" size={16} />{t('14-day returns', 'استرجاع 14 يوم')}</span>
        <span><Icon name="lock" size={16} />{t('Secure payment', 'دفع آمن')}</span>
      </div>
    </aside>
  );
}

;


const kImg = (en, ar) => ({ en, ar: ar || en });

const K_PRODUCTS = [
  { id: 'p1', cat: 'pend', name: { en: 'Arc LED Pendant Ø450, dimmable', ar: 'معلقة ليد آرك Ø450 قابلة للتعتيم' }, brand: 'Philips', sku: 'PH-ARC-450-30', price: 4850, was: 5700, badges: [{ tone: 'sale', label: '−15%' }], finishes: ['#1E1E1C', '#F4F3EF', '#C9A45C'], variants: { en: '3 finishes · 2 CCT', ar: '3 تشطيبات · درجتين' }, stock: 'in', imageLabel: kImg('Cut-out: dome pendant, matte black', 'صورة: معلقة قبة سوداء') },
  { id: 'p2', cat: 'down', name: { en: 'Trimless Downlight 12W, 36° beam', ar: 'سبوت غاطس بدون إطار 12 وات، 36°' }, brand: 'Delta Light', sku: 'DL-TRM-12-40', price: 690, badges: [{ tone: 'accent', label: { en: 'New', ar: 'جديد' } }], variants: { en: '4 wattages · 3 CCT', ar: '4 قدرات · 3 درجات' }, stock: 'in', imageLabel: kImg('Cut-out: round recessed downlight') },
  { id: 'p3', cat: 'smart', name: { en: 'Smart Dimmer Switch, 1-gang, glass', ar: 'مفتاح ديمر ذكي، خط واحد، زجاج' }, brand: 'Legrand', sku: 'LG-MYH-DIM1-W', price: 1150, finishes: ['#F4F3EF', '#1E1E1C'], variants: { en: 'Works with Alexa & Google', ar: 'يعمل مع أليكسا وجوجل' }, stock: 'low', imageLabel: kImg('Cut-out: glass touch switch') },
  { id: 'p4', cat: 'track', name: { en: 'Magnetic Track Spot 10W, 24°', ar: 'سبوت تراك مغناطيسي 10 وات، 24°' }, brand: 'Osram', sku: 'OS-MAG-10-24', price: 1380, finishes: ['#1E1E1C', '#F4F3EF'], variants: { en: '2 finishes', ar: 'تشطيبين' }, stock: 'in', imageLabel: kImg('Cut-out: magnetic track spot') },
  { id: 'p5', cat: 'wall', name: { en: 'Up/Down Outdoor Wall Light, IP65', ar: 'أبليك خارجي أعلى/أسفل IP65' }, brand: 'Delta Light', sku: 'DL-UPD-IP65-AN', price: 2240, finishes: ['#4A4A47', '#1E1E1C'], variants: { en: 'Anthracite · 3000K', ar: 'أنثراسايت · 3000K' }, stock: 'in', imageLabel: kImg('Cut-out: cylinder wall light') },
  { id: 'p6', cat: 'pend', name: { en: 'Linear Suspension 1200mm, DALI', ar: 'إضاءة معلقة خطية 1200مم، DALI' }, brand: 'Artemide', sku: 'AR-LIN-1200-D', badges: [{ tone: 'dark', label: { en: 'Project', ar: 'مشروعات' } }], variants: { en: '3 lengths', ar: '3 أطوال' }, stock: 'order', imageLabel: kImg('Cut-out: linear suspension') },
  { id: 'p7', cat: 'elec', name: { en: 'Miniature Circuit Breaker 1P 16A, C-curve', ar: 'قاطع صغير 1P 16A منحنى C' }, brand: 'Schneider Electric', sku: 'SE-A9F-1P16C', price: 210, variants: { en: '6–63 A', ar: '6–63 أمبير' }, stock: 'in', imageLabel: kImg('Cut-out: DIN-rail breaker') },
  { id: 'p8', cat: 'strip', name: { en: 'LED Strip 24V, 14.4W/m, CRI 90, 5m', ar: 'شريط ليد 24 فولت، 14.4 وات/م، 5م' }, brand: 'Osram', sku: 'OS-LS-24-144-5', price: 1690, was: 1890, badges: [{ tone: 'sale', label: '−10%' }], variants: { en: '4 CCT', ar: '4 درجات' }, stock: 'in', imageLabel: kImg('Cut-out: LED strip reel') },
  { id: 'p9', cat: 'smart', name: { en: 'Zigbee Motion Sensor, ceiling', ar: 'حساس حركة زيجبي للسقف' }, brand: 'ABB', sku: 'ABB-ZB-PIR-C', price: 890, variants: { en: '360° · 7m range', ar: '360° · مدى 7م' }, stock: 'in', imageLabel: kImg('Cut-out: ceiling sensor') },
  { id: 'p10', cat: 'pend', name: { en: 'Opal Globe Pendant Ø300, E27', ar: 'معلقة كرة أوبال Ø300، E27' }, brand: 'Flos', sku: 'FL-GLB-300-E27', price: 7900, finishes: ['#C9A45C', '#1E1E1C'], variants: { en: '2 sizes · brass or black', ar: 'مقاسين · نحاسي أو أسود' }, stock: 'low', imageLabel: kImg('Cut-out: opal glass globe') },
  { id: 'p11', cat: 'elec', name: { en: 'Double Socket 2×16A with USB-C', ar: 'بريزة مزدوجة 2×16A مع USB-C' }, brand: 'Legrand', sku: 'LG-ARB-2S-USBC', price: 640, finishes: ['#F4F3EF', '#1E1E1C', '#9FA3A8'], variants: { en: '3 finishes', ar: '3 تشطيبات' }, stock: 'in', imageLabel: kImg('Cut-out: wall socket') },
  { id: 'p12', cat: 'down', name: { en: 'Adjustable Downlight 7W, anti-glare', ar: 'سبوت متحرك 7 وات مضاد للانبهار' }, brand: 'Philips', sku: 'PH-ADJ-7-27', price: 520, variants: { en: '2700K · 3000K', ar: '2700K · 3000K' }, stock: 'in', imageLabel: kImg('Cut-out: gimbal downlight') },
];

const K_BRANDS = [
  { id: 'philips', name: 'Philips', origin: { en: 'Netherlands', ar: 'هولندا' }, count: 1412 },
  { id: 'legrand', name: 'Legrand', origin: { en: 'France', ar: 'فرنسا' }, count: 1240 },
  { id: 'schneider', name: 'Schneider Electric', origin: { en: 'France', ar: 'فرنسا' }, count: 1106 },
  { id: 'delta', name: 'Delta Light', origin: { en: 'Belgium', ar: 'بلجيكا' }, count: 688 },
  { id: 'osram', name: 'Osram', origin: { en: 'Germany', ar: 'ألمانيا' }, count: 802 },
  { id: 'abb', name: 'ABB', origin: { en: 'Switzerland', ar: 'سويسرا' }, count: 954 },
  { id: 'flos', name: 'Flos', origin: { en: 'Italy', ar: 'إيطاليا' }, count: 196 },
  { id: 'artemide', name: 'Artemide', origin: { en: 'Italy', ar: 'إيطاليا' }, count: 174 },
];

const K_CATEGORIES = [
  { id: 'pend', name: { en: 'Pendants & chandeliers', ar: 'معلقات ونجف' }, count: 1148, imageLabel: kImg('Lifestyle: pendants over dining table, 2700K') },
  { id: 'down', name: { en: 'Downlights & spots', ar: 'سبوتات' }, count: 960, imageLabel: kImg('Lifestyle: kitchen ceiling, downlights on') },
  { id: 'out', name: { en: 'Outdoor lighting', ar: 'إضاءة خارجية' }, count: 1320, imageLabel: kImg('Lifestyle: villa facade at dusk') },
  { id: 'smart', name: { en: 'Smart home', ar: 'المنزل الذكي' }, count: 720, imageLabel: kImg('Lifestyle: hand on smart panel') },
  { id: 'elec', name: { en: 'Switches & sockets', ar: 'مفاتيح وبرايز' }, count: 2140, imageLabel: kImg('Detail: switch plate on plaster wall') },
  { id: 'strip', name: { en: 'LED strips & profiles', ar: 'شرائط ليد وبروفايلات' }, count: 540, imageLabel: kImg('Lifestyle: cove lighting in living room') },
];

const K_SUBCATS = [
  { id: 's1', name: { en: 'Pendants', ar: 'معلقات' }, count: 612, imageLabel: kImg('Cut-out: pendant') },
  { id: 's2', name: { en: 'Chandeliers', ar: 'نجف' }, count: 188, imageLabel: kImg('Cut-out: chandelier') },
  { id: 's3', name: { en: 'Linear suspensions', ar: 'معلقات خطية' }, count: 164, imageLabel: kImg('Cut-out: linear') },
  { id: 's4', name: { en: 'Cluster pendants', ar: 'معلقات عنقودية' }, count: 92, imageLabel: kImg('Cut-out: cluster') },
  { id: 's5', name: { en: 'Ceiling roses & kits', ar: 'قواعد وملحقات' }, count: 92, imageLabel: kImg('Cut-out: canopy') },
];

const K_SERVICES = [
  { id: 'design', num: '01', title: { en: 'Lighting design', ar: 'تصميم الإضاءة' }, desc: { en: 'Lux calculations, DIALux renders and a fixture schedule matched to your budget.', ar: 'حسابات شدة الإضاءة وتصميمات DIALux وجدول وحدات يناسب ميزانيتك.' }, imageLabel: kImg('Designer reviewing DIALux render with client') },
  { id: 'smart', num: '02', title: { en: 'Smart home installation', ar: 'تركيب المنزل الذكي' }, desc: { en: 'Lighting scenes, curtains, climate and security on one app — installed and programmed.', ar: 'مشاهد الإضاءة والستائر والتكييف والأمان في تطبيق واحد — تركيب وبرمجة.' }, imageLabel: kImg('Technician programming smart panel') },
  { id: 'contract', num: '03', title: { en: 'Electrical contracting', ar: 'مقاولات كهربائية' }, desc: { en: 'Wiring, distribution boards and certified testing for villas, offices and retail.', ar: 'تمديدات ولوحات توزيع واختبارات معتمدة للفيلات والمكاتب والمحلات.' }, imageLabel: kImg('Electrician at distribution board') },
  { id: 'maint', num: '04', title: { en: 'Maintenance & support', ar: 'الصيانة والدعم' }, desc: { en: 'Annual contracts, emergency call-outs and warranty handling for everything we supply.', ar: 'عقود سنوية وزيارات طارئة ومتابعة الضمان لكل ما نوفره.' }, imageLabel: kImg('Technician replacing driver on ladder') },
];

const K_FACETS = {
  categories: [
    { id: 'ind', name: { en: 'Indoor Lighting', ar: 'إضاءة داخلية' }, count: 4210, children: [{ id: 'pend', name: { en: 'Pendants', ar: 'معلقات' }, count: 1148 }, { id: 'down', name: { en: 'Downlights', ar: 'سبوتات' }, count: 960 }, { id: 'track', name: { en: 'Track lighting', ar: 'تراك لايت' }, count: 410 }] },
    { id: 'out', name: { en: 'Outdoor Lighting', ar: 'إضاءة خارجية' }, count: 1320 },
    { id: 'smart', name: { en: 'Smart Home', ar: 'المنزل الذكي' }, count: 720 },
  ],
  brands: K_BRANDS.map((b) => ({ name: b.name, count: Math.round(b.count / 6) })),
  temps: [{ value: '2700K', label: { en: 'Warm', ar: 'دافئ' }, chip: '#FFD08A', count: 410 }, { value: '3000K', label: { en: 'Warm white', ar: 'أبيض دافئ' }, chip: '#FFE2B0', count: 633 }, { value: '4000K', label: { en: 'Neutral', ar: 'محايد' }, chip: '#FFF6E6', count: 388 }, { value: '6500K', label: { en: 'Daylight', ar: 'نهاري' }, chip: '#E6EEFF', count: 92 }],
  watts: ['5W', '7W', '10W', '12W', '18W', '24W', '36W'].map((v) => ({ value: v })),
  finishes: [{ value: 'black', label: { en: 'Matte black', ar: 'أسود مطفي' }, color: '#1E1E1C' }, { value: 'white', label: { en: 'White', ar: 'أبيض' }, color: '#F4F3EF' }, { value: 'brass', label: { en: 'Brass', ar: 'نحاسي' }, color: 'linear-gradient(135deg,#C9A45C,#E8CF96 50%,#B08A45)' }, { value: 'chrome', label: { en: 'Chrome', ar: 'كروم' }, color: 'linear-gradient(135deg,#9FA3A8,#EEF0F2 50%,#8C9095)' }],
};

const K_TEMPS = [
  { value: '2700', label: '2700K', sub: { en: 'Warm', ar: 'دافئ' }, chip: '#FFD08A' },
  { value: '3000', label: '3000K', sub: { en: 'Warm white', ar: 'أبيض دافئ' }, chip: '#FFE2B0' },
  { value: '4000', label: '4000K', sub: { en: 'Neutral', ar: 'محايد' }, chip: '#FFF6E6', disabled: true },
];
const K_FINISHES = [
  { value: 'black', label: { en: 'Matte black', ar: 'أسود مطفي' }, color: '#1E1E1C' },
  { value: 'white', label: { en: 'Satin white', ar: 'أبيض ساتان' }, color: '#F4F3EF' },
  { value: 'brass', label: { en: 'Brushed brass', ar: 'نحاسي مصقول' }, color: 'linear-gradient(135deg,#C9A45C,#E8CF96 50%,#B08A45)' },
];

const K_CART_START = [
  { id: 'c1', name: { en: 'Arc LED Pendant Ø450', ar: 'معلقة ليد آرك Ø450' }, variant: { en: 'Matte black · 3000K', ar: 'أسود مطفي · 3000K' }, price: 4850, qty: 1 },
  { id: 'c2', name: { en: 'Trimless Downlight 12W', ar: 'سبوت غاطس 12 وات' }, variant: { en: 'White · 4000K · 36°', ar: 'أبيض · 4000K · 36°' }, price: 690, qty: 6 },
];

;


const K_PHOTO_IDS = ['1618221195710-dd6b41faaea6', '1600210492486-724fe5c67fb0', '1600607687939-ce8a6c25118c', '1600566753190-17f0baa2a6c3', '1600585154340-be6161a56a0c', '1600596542815-ffad4c1539a9', '1512917774080-9991f1c4c750', '1507473885765-e6ed057f782c', '1513506003901-1e6a229e2d15', '1524758631624-e2822e304c36', '1497366216548-37526070297c', '1497366811353-6870744d04b2', '1586023492125-27b2c045efd7', '1556909114-f6e7ad7d3136', '1484154218962-a197022b5858', '1505693416388-ac5ce068fe85', '1522708323590-d24dbb6b0267', '1558002038-1055907df827', '1621905251189-08b45d6a269e', '1581092160562-40aa08e78837', '1541888946425-d81bb19240f5', '1504307651254-35680f356dfd', '1566073771259-6a8506099945', '1551882547-ff40c63fe5fa', '1517248135467-4c7edcad34c4', '1555396273-367ea4eb4db5', '1600121848594-d8644e57abab', '1616486338812-3dadae4b4ace', '1565183997392-2f6f122e5912', '1558618666-fcd25c85cd64', '1497215728101-856f4ea42174', '1545324418-cc1a3fa10c00', '1486406146926-c627a92ad1ab', '1540932239986-30128078f3c5', '1519710164239-da123dc03ef4', '1567767292278-a4f21aa2d36e', '1583847268964-b28dc8f51f92', '1600573472550-8090b5e0745e', '1613490493576-7fde63acd811', '1600047509807-ba8f99d2cdde'];

/** Stand-in stock photography (Unsplash) — replace with Dots' own shoots. */
function kPh(i, w = 1200) { const __r = (window.__resources || {})['ph' + i]; if (__r) return __r; return `https://images.unsplash.com/photo-${K_PHOTO_IDS[i]}?auto=format&fit=crop&w=${w}&q=70`; }

const kM = (en, ar) => ({ en, ar });
const K_BLOG_CATS = [
  { id: 'all', label: kM('All', 'الكل') }, { id: 'guides', label: kM('Lighting guides', 'أدلة الإضاءة') }, { id: 'smart', label: kM('Smart home', 'المنزل الذكي') },
  { id: 'projects', label: kM('Project stories', 'قصص المشروعات') }, { id: 'tech', label: kM('Technical', 'تقني') }, { id: 'news', label: kM('News', 'أخبار') },
];

const K_ARTICLES = [
  { id: 'a1', cat: 'guides', image: kPh(33, 1600), category: kM('Lighting guides', 'أدلة الإضاءة'), read: 8, date: kM('12 Sep 2026', '12 سبتمبر 2026'), author: kM('Nour Hassan · Lighting designer', 'نور حسن · مصممة إضاءة'), title: kM('Layered lighting: how to light a living room in three layers', 'الإضاءة المتعددة الطبقات: كيف تضيء غرفة المعيشة بثلاث طبقات'), excerpt: kM('Ambient, task and accent — the simple framework our designers use on every home, with fixture picks and lux targets for each layer.', 'عامة ووظيفية وجمالية — الإطار البسيط الذي يستخدمه مصممونا في كل منزل، مع اختيارات الوحدات ومستويات الإضاءة لكل طبقة.') },
  { id: 'a2', cat: 'smart', image: kPh(17), category: kM('Smart home', 'المنزل الذكي'), read: 6, date: kM('3 Sep 2026', '3 سبتمبر 2026'), title: kM('Zigbee, Wi-Fi or KNX? Choosing a smart home backbone', 'زيجبي أم واي فاي أم KNX؟ اختيار أساس المنزل الذكي'), excerpt: kM('What each protocol is good at, what it costs to install in an Egyptian apartment or villa, and when to wire instead of going wireless.', 'مميزات كل بروتوكول، وتكلفة تركيبه في شقة أو فيلا في مصر، ومتى تختار التوصيل السلكي.') },
  { id: 'a3', cat: 'projects', image: kPh(23), category: kM('Project stories', 'قصص المشروعات'), read: 5, date: kM('27 Aug 2026', '27 أغسطس 2026'), title: kM('Relighting a Red Sea resort without closing a single room', 'إعادة إضاءة منتجع على البحر الأحمر دون إغلاق غرفة واحدة'), excerpt: kM('Phased night works, 1,400 fixtures and a 38% energy saving at a 220-key resort in El Gouna.', 'أعمال ليلية على مراحل و1,400 وحدة وتوفير 38% في الطاقة بمنتجع 220 غرفة في الجونة.') },
  { id: 'a4', cat: 'tech', image: kPh(8), category: kM('Technical', 'تقني'), read: 7, date: kM('19 Aug 2026', '19 أغسطس 2026'), title: kM('Reading a datasheet: lumens, CRI, UGR and beam angle explained', 'قراءة الداتا شيت: شرح اللومن وCRI وUGR وزاوية الإضاءة'), excerpt: kM('The six numbers that decide whether a fixture will look right in your space — and the ones you can ignore.', 'الأرقام الستة التي تحدد إن كانت الوحدة مناسبة لمساحتك — وتلك التي يمكنك تجاهلها.') },
  { id: 'a5', cat: 'guides', image: kPh(14), category: kM('Lighting guides', 'أدلة الإضاءة'), read: 4, date: kM('8 Aug 2026', '8 أغسطس 2026'), title: kM('Kitchen lighting that actually works for cooking', 'إضاءة مطبخ مناسبة فعلاً للطبخ'), excerpt: kM('Under-cabinet strips, island pendants and the right colour temperature for food prep.', 'شرائط تحت الدواليب ومعلقات الجزيرة ودرجة اللون المناسبة لتحضير الطعام.') },
  { id: 'a6', cat: 'news', image: kPh(12), category: kM('News', 'أخبار'), read: 3, date: kM('1 Aug 2026', '1 أغسطس 2026'), title: kM('Our new Sheikh Zayed showroom is open', 'افتتاح معرضنا الجديد في الشيخ زايد'), excerpt: kM('800 m² of live lighting scenes, a smart home demo flat and a trade counter for contractors.', '800 م² من مشاهد الإضاءة الحية وشقة عرض للمنزل الذكي وكاونتر للمقاولين.') },
  { id: 'a7', cat: 'tech', image: kPh(29), category: kM('Technical', 'تقني'), read: 6, date: kM('22 Jul 2026', '22 يوليو 2026'), title: kM('Sizing a distribution board for a modern villa', 'تحديد حجم لوحة التوزيع لفيلا حديثة'), excerpt: kM('Circuits, RCD protection and spare ways — a practical checklist from our electrical team.', 'الدوائر وحماية التسريب والخانات الاحتياطية — قائمة عملية من فريقنا الكهربائي.') },
  { id: 'a8', cat: 'smart', image: kPh(2), category: kM('Smart home', 'المنزل الذكي'), read: 5, date: kM('10 Jul 2026', '10 يوليو 2026'), title: kM('Five lighting scenes every smart home should have', 'خمسة مشاهد إضاءة يحتاجها كل منزل ذكي'), excerpt: kM('Morning, focus, dinner, movie and night path — with the exact dim levels we programme.', 'الصباح والتركيز والعشاء والأفلام والممر الليلي — بمستويات التعتيم التي نبرمجها.') },
  { id: 'a9', cat: 'projects', image: kPh(24), category: kM('Project stories', 'قصص المشروعات'), read: 4, date: kM('2 Jul 2026', '2 يوليو 2026'), title: kM('Warm light, fast turns: a Zamalek restaurant fit-out', 'إضاءة دافئة وتنفيذ سريع: تجهيز مطعم بالزمالك'), excerpt: kM('Designing for mood at night and cleanability by day, delivered in 19 days.', 'تصميم للأجواء ليلاً وسهولة التنظيف نهاراً، تم التنفيذ في 19 يوماً.') },
];

const kType = { res: kM('Residential', 'سكني'), com: kM('Commercial', 'تجاري'), out: kM('Outdoor', 'خارجي'), smart: kM('Smart home', 'منزل ذكي'), hosp: kM('Hospitality', 'ضيافة') };
const K_PROJECT_TYPES = [{ id: 'all', label: kM('All', 'الكل') }, { id: 'res', label: kType.res }, { id: 'com', label: kType.com }, { id: 'out', label: kType.out }, { id: 'smart', label: kType.smart }];

const K_PROJECTS = [
  { id: 'pr1', status: 'progress', t: 'res', type: kType.res, progress: 68, phase: kM('Second fix & fixtures', 'التشطيب النهائي والوحدات'), stepIdx: 2, image: kPh(38, 1600), gallery: [38, 5, 27, 34, 0, 15], title: kM('Villa 27 · Palm Hills', 'فيلا 27 · بالم هيلز'), location: kM('6th of October, Giza', '6 أكتوبر، الجيزة'), start: kM('Mar 2026', 'مارس 2026'), eta: kM('Nov 2026', 'نوفمبر 2026'), scope: kM('Lighting design, full electrical, KNX smart home', 'تصميم إضاءة، كهرباء كاملة، منزل ذكي KNX') },
  { id: 'pr2', status: 'progress', t: 'com', type: kType.com, progress: 42, phase: kM('First fix & containment', 'التمديدات والمواسير'), stepIdx: 1, image: kPh(11, 1600), gallery: [11, 10, 30, 9, 32], title: kM('Tech HQ · 4 floors', 'مقر شركة تقنية · 4 أدوار'), location: kM('New Administrative Capital', 'العاصمة الإدارية الجديدة'), start: kM('May 2026', 'مايو 2026'), eta: kM('Feb 2027', 'فبراير 2027'), scope: kM('DALI office lighting, power, data containment', 'إضاءة مكاتب DALI، قوى، مسارات بيانات') },
  { id: 'pr3', status: 'progress', t: 'out', type: kType.out, progress: 85, phase: kM('Commissioning', 'التشغيل والاختبار'), stepIdx: 3, image: kPh(4, 1600), gallery: [4, 6, 39, 5], title: kM('Compound landscape & facades', 'لاندسكيب وواجهات كمباوند'), location: kM('New Cairo', 'القاهرة الجديدة'), start: kM('Jan 2026', 'يناير 2026'), eta: kM('Oct 2026', 'أكتوبر 2026'), scope: kM('Facade wash, bollards, street lighting, astro-timers', 'إضاءة واجهات وأعمدة قصيرة وإنارة شوارع ومؤقتات') },
  { id: 'pr4', status: 'progress', t: 'smart', type: kType.smart, progress: 24, phase: kM('Design approval', 'اعتماد التصميم'), stepIdx: 0, image: kPh(36, 1600), gallery: [36, 17, 28, 35], title: kM('Penthouse smart retrofit', 'تحويل بنتهاوس لمنزل ذكي'), location: kM('Zamalek, Cairo', 'الزمالك، القاهرة'), start: kM('Aug 2026', 'أغسطس 2026'), eta: kM('Dec 2026', 'ديسمبر 2026'), scope: kM('Wireless retrofit, scenes, curtains, climate', 'تحويل لاسلكي، مشاهد، ستائر، تكييف') },
  { id: 'pr5', status: 'progress', t: 'com', type: kType.hosp, progress: 55, phase: kM('Fixture installation', 'تركيب الوحدات'), stepIdx: 2, image: kPh(25, 1600), gallery: [25, 24, 35], title: kM('All-day café chain · 3 branches', 'سلسلة مقاهي · 3 فروع'), location: kM('Sheikh Zayed & Maadi', 'الشيخ زايد والمعادي'), start: kM('Jun 2026', 'يونيو 2026'), eta: kM('Oct 2026', 'أكتوبر 2026'), scope: kM('Decorative & track lighting, controls', 'إضاءة ديكورية وتراك وتحكم') },
  { id: 'pr6', status: 'progress', t: 'res', type: kType.res, progress: 12, phase: kM('Site survey', 'المعاينة'), stepIdx: 0, image: kPh(3, 1600), gallery: [3, 16, 26], title: kM('Twin houses · Hacienda', 'توين هاوس · هاسيندا'), location: kM('North Coast', 'الساحل الشمالي'), start: kM('Sep 2026', 'سبتمبر 2026'), eta: kM('Apr 2027', 'أبريل 2027'), scope: kM('Supply & install, outdoor, pool lighting', 'توريد وتركيب، إضاءة خارجية وحمامات سباحة') },
];

const K_DONE = [
  { id: 'd1', status: 'done', t: 'out', type: kType.hosp, year: 2026, image: kPh(23, 1600), before: 22, after: 23, gallery: [23, 22, 6, 5], title: kM('Red Sea resort relight', 'إعادة إضاءة منتجع البحر الأحمر'), location: kM('El Gouna', 'الجونة'), client: kM('Private resort group', 'مجموعة منتجعات خاصة'), scope: kM('1,400 fixtures · −38% energy', '1,400 وحدة · توفير 38%'), h: 460 },
  { id: 'd2', status: 'done', t: 'com', type: kType.com, year: 2025, image: kPh(24, 1600), gallery: [24, 25, 35], title: kM('Restaurant, Zamalek', 'مطعم بالزمالك'), location: kM('Zamalek, Cairo', 'الزمالك، القاهرة'), scope: kM('Fit-out in 19 days', 'تنفيذ في 19 يوماً'), h: 320 },
  { id: 'd3', status: 'done', t: 'res', type: kType.res, year: 2025, image: kPh(0, 1600), gallery: [0, 13, 15, 34], title: kM('Apartment, Mivida', 'شقة في ميفيدا'), location: kM('New Cairo', 'القاهرة الجديدة'), scope: kM('Lighting design + supply', 'تصميم + توريد'), h: 380 },
  { id: 'd4', status: 'done', t: 'com', type: kType.com, year: 2024, image: kPh(9, 1600), gallery: [9, 11, 30], title: kM('Co-working campus', 'مساحة عمل مشتركة'), location: kM('Smart Village', 'القرية الذكية'), client: kM('Public listing', 'متاح للنشر'), scope: kM('DALI + daylight sensors', 'DALI + حساسات ضوء نهار'), h: 300 },
  { id: 'd5', status: 'done', t: 'smart', type: kType.smart, year: 2026, image: kPh(2, 1600), gallery: [2, 17, 27], title: kM('Smart villa, Allegria', 'فيلا ذكية، أليجريا'), location: kM('Sheikh Zayed', 'الشيخ زايد'), scope: kM('KNX, 64 scenes', 'KNX، 64 مشهد'), h: 420 },
  { id: 'd6', status: 'done', t: 'out', type: kType.out, year: 2024, image: kPh(5, 1600), gallery: [5, 6, 38], title: kM('Villa pool & garden', 'حمام سباحة وحديقة فيلا'), location: kM('Marassi, North Coast', 'مراسي، الساحل الشمالي'), scope: kM('Underwater & landscape', 'إضاءة تحت الماء ولاندسكيب'), h: 340 },
  { id: 'd7', status: 'done', t: 'res', type: kType.res, year: 2023, image: kPh(14, 1600), gallery: [14, 13, 1], title: kM('Family kitchen, Maadi', 'مطبخ عائلي، المعادي'), location: kM('Maadi, Cairo', 'المعادي، القاهرة'), scope: kM('Task & pendant lighting', 'إضاءة وظيفية ومعلقات'), h: 360 },
  { id: 'd8', status: 'done', t: 'com', type: kType.com, year: 2023, image: kPh(32, 1600), gallery: [32, 31, 10], title: kM('Office tower lobby', 'مدخل برج إداري'), location: kM('Downtown Cairo', 'وسط القاهرة'), scope: kM('Architectural + emergency', 'معمارية + طوارئ'), h: 440 },
];

const kProdPh = { p1: 33, p2: 8, p3: 17, p4: 7, p5: 4, p6: 28, p7: 29, p8: 2, p9: 17, p10: 33, p11: 12, p12: 14 };
K_PRODUCTS.forEach((p) => { p.image = kPh(kProdPh[p.id] ?? 8, 700); p.cover = true; });
const kCatPh = { pend: 33, down: 14, out: 4, smart: 17, elec: 29, strip: 2 };
K_CATEGORIES.forEach((c) => { c.image = kPh(kCatPh[c.id], 900); });
[7, 8, 28, 34, 35].forEach((n, i) => { if (K_SUBCATS[i]) K_SUBCATS[i].image = kPh(n, 600); });
const kSvcPh = { design: 19, smart: 17, contract: 18, maint: 29 };
K_SERVICES.forEach((x) => { x.image = kPh(kSvcPh[x.id], 900); });

;


/* Admin sample data. Reuses K_PRODUCTS / K_BRANDS / kPh from ../website/data*.jsx */
const aM = (en, ar) => ({ en, ar });
function aT(en, ar) { return aM(en, ar); }

const A_STATUS = {
  order: { new: ['info', aM('New', 'جديد')], confirmed: ['accent', aM('Confirmed', 'مؤكد')], preparing: ['warning', aM('Preparing', 'قيد التجهيز')], shipped: ['dark', aM('Shipped', 'تم الشحن')], delivered: ['success', aM('Delivered', 'تم التوصيل')], cancelled: ['error', aM('Cancelled', 'ملغي')] },
  pay: { pending: ['warning', aM('Pending', 'معلق')], awaiting: ['info', aM('Awaiting transfer', 'في انتظار التحويل')], paid: ['success', aM('Paid', 'مدفوع')], failed: ['error', aM('Failed', 'فشل')], refunded: ['neutral', aM('Refunded', 'مسترد')] },
  lead: { new: ['info', aM('New', 'جديد')], contacted: ['warning', aM('Contacted', 'تم التواصل')], converted: ['success', aM('Converted', 'تم التحويل')], closed: ['neutral', aM('Closed', 'مغلق')] },
  stock: { in: ['success', aM('In stock', 'متوفر')], low: ['warning', aM('Low stock', 'كمية قليلة')], out: ['error', aM('Out of stock', 'غير متوفر')], order: ['neutral', aM('On order', 'بالطلب')] },
  product: { draft: ['neutral', aM('Draft', 'مسودة')], published: ['success', aM('Published', 'منشور')], archived: ['outline', aM('Archived', 'مؤرشف')] },
  active: { on: ['success', aM('Active', 'مفعل')], off: ['neutral', aM('Inactive', 'غير مفعل')], invited: ['info', aM('Invited', 'تمت الدعوة')] },
};

const A_PAY_METHODS = { cod: { icon: 'banknote', label: aM('Cash on delivery', 'عند الاستلام') }, card: { icon: 'credit-card', label: aM('Card · Paymob', 'بطاقة · Paymob') }, instapay: { icon: 'smartphone', label: aM('InstaPay', 'إنستاباي') } };

const aQty = { p1: 42, p2: 318, p3: 6, p4: 87, p5: 24, p6: 0, p7: 1240, p8: 3, p9: 56, p10: 4, p11: 410, p12: 0 };
const aCat = { pend: aM('Pendants', 'معلقات'), down: aM('Downlights', 'سبوتات'), smart: aM('Smart switches', 'مفاتيح ذكية'), track: aM('Track lighting', 'تراك لايت'), wall: aM('Outdoor wall', 'أبليك خارجي'), elec: aM('Breakers & sockets', 'قواطع وبرايز'), strip: aM('LED strips', 'شرائط ليد') };
const A_PRODUCTS = K_PRODUCTS.map((p, i) => {
  const qty = aQty[p.id];
  return { ...p, qty, catName: aCat[p.cat], stockState: p.stock === 'order' ? 'order' : qty === 0 ? 'out' : qty < 10 ? 'low' : 'in', status: ['published', 'published', 'published', 'draft', 'published', 'published', 'published', 'hidden', 'published', 'published', 'published', 'draft'][i], updated: aM(`${28 - i} Sep 2026`, `${28 - i} سبتمبر 2026`) };
});

const aGov = { cai: aM('Cairo', 'القاهرة'), giz: aM('Giza', 'الجيزة'), alx: aM('Alexandria', 'الإسكندرية'), qal: aM('Qalyubia', 'القليوبية'), red: aM('Red Sea', 'البحر الأحمر'), dak: aM('Dakahlia', 'الدقهلية'), sha: aM('Sharqia', 'الشرقية'), mat: aM('Matrouh', 'مطروح'), ssi: aM('South Sinai', 'جنوب سيناء'), ism: aM('Ismailia', 'الإسماعيلية'), ast: aM('Assiut', 'أسيوط'), lux: aM('Luxor', 'الأقصر') };
const A_GOVS = Object.entries(aGov).map(([id, name]) => ({ id, name }));

const A_ORDERS = [
  { id: 'DT-10482', date: aM('1 Oct, 14:32', '1 أكتوبر، 14:32'), name: aM('Ahmed Samir', 'أحمد سمير'), phone: '010 1234 5678', gov: 'cai', items: 8, total: 9276, method: 'card', pay: 'paid', status: 'new' },
  { id: 'DT-10481', date: aM('1 Oct, 13:05', '1 أكتوبر، 13:05'), name: aM('Mariam Fawzy', 'مريم فوزي'), phone: '012 7788 1902', gov: 'giz', items: 3, total: 3640, method: 'cod', pay: 'pending', status: 'confirmed' },
  { id: 'DT-10480', date: aM('1 Oct, 11:48', '1 أكتوبر، 11:48'), name: aM('Omar El-Sayed', 'عمر السيد'), phone: '011 4520 3381', gov: 'alx', items: 12, total: 18420, method: 'instapay', pay: 'awaiting', status: 'new' },
  { id: 'DT-10479', date: aM('1 Oct, 10:21', '1 أكتوبر، 10:21'), name: aM('Nadine Khalil', 'نادين خليل'), phone: '010 9934 0017', gov: 'cai', items: 2, total: 1690, method: 'card', pay: 'failed', status: 'new' },
  { id: 'DT-10478', date: aM('30 Sep, 18:40', '30 سبتمبر، 18:40'), name: aM('Karim Adel', 'كريم عادل'), phone: '015 5501 7720', gov: 'red', items: 24, total: 42800, method: 'instapay', pay: 'paid', status: 'preparing' },
  { id: 'DT-10477', date: aM('28 Sep, 11:02', '28 سبتمبر، 11:02'), name: aM('Hana Mostafa', 'هنا مصطفى'), phone: '012 3001 4456', gov: 'giz', items: 1, total: 7900, method: 'card', pay: 'refunded', status: 'cancelled' },
  { id: 'DT-10476', date: aM('28 Sep, 09:15', '28 سبتمبر، 09:15'), name: aM('Youssef Nabil', 'يوسف نبيل'), phone: '010 2244 8890', gov: 'qal', items: 6, total: 4320, method: 'cod', pay: 'pending', status: 'shipped' },
  { id: 'DT-10475', date: aM('27 Sep, 16:30', '27 سبتمبر، 16:30'), name: aM('Salma Hegazy', 'سلمى حجازي'), phone: '011 7765 2210', gov: 'cai', items: 4, total: 5560, method: 'card', pay: 'paid', status: 'delivered' },
  { id: 'DT-10474', date: aM('27 Sep, 12:02', '27 سبتمبر، 12:02'), name: aM('Tarek Mansour', 'طارق منصور'), phone: '010 6612 9043', gov: 'dak', items: 9, total: 11240, method: 'cod', pay: 'paid', status: 'delivered' },
  { id: 'DT-10473', date: aM('26 Sep, 20:11', '26 سبتمبر، 20:11'), name: aM('Laila Ramadan', 'ليلى رمضان'), phone: '012 8890 3345', gov: 'ssi', items: 2, total: 2380, method: 'instapay', pay: 'paid', status: 'shipped' },
];

const A_LEAD_SOURCES = { product: ['package', aM('Product', 'منتج')], cart: ['shopping-cart', aM('Cart', 'السلة')], instapay: ['smartphone', aM('InstaPay order', 'طلب إنستاباي')], boq: ['file-spreadsheet', aM('BOQ / drawings', 'مقايسة / رسومات')], brand: ['award', aM('Brand', 'علامة تجارية')], service: ['wrench', aM('Service', 'خدمة')], project: ['building-2', aM('Project', 'مشروع')], article: ['newspaper', aM('Article', 'مقال')], order: ['life-buoy', aM('Order help', 'مساعدة في طلب')], general: ['message-circle', aM('General', 'عام')] };

const A_LEADS = [
  { id: 'L-2291', date: aM('1 Oct, 14:10', '1 أكتوبر، 14:10'), name: aM('Mostafa Ali', 'مصطفى علي'), phone: '010 4431 2200', source: 'product', ref: aM('Linear Suspension 1200mm, DALI · AR-LIN-1200-D', 'إضاءة معلقة خطية 1200مم · AR-LIN-1200-D'), status: 'new', notes: [] },
  { id: 'L-2290', date: aM('1 Oct, 13:42', '1 أكتوبر، 13:42'), name: aM('Engy Samy', 'إنجي سامي'), phone: '012 0098 7712', source: 'boq', ref: aM('Villa, New Cairo — 86-line BOQ (XLSX)', 'فيلا، القاهرة الجديدة — مقايسة 86 بند'), status: 'contacted', notes: [{ by: 'Mona', at: aM('1 Oct, 13:55', '1 أكتوبر، 13:55'), text: aM('Received BOQ. Pricing Delta Light alternatives, quote by Thursday.', 'استلمنا المقايسة. نسعّر بدائل Delta Light، العرض يوم الخميس.') }] },
  { id: 'L-2289', date: aM('1 Oct, 12:20', '1 أكتوبر، 12:20'), name: aM('Omar El-Sayed', 'عمر السيد'), phone: '011 4520 3381', source: 'instapay', ref: aM('Order DT-10480 · EGP 18,420', 'الطلب DT-10480 · 18,420 ج.م'), status: 'new', notes: [] },
  { id: 'L-2288', date: aM('1 Oct, 11:03', '1 أكتوبر، 11:03'), name: aM('Reem Fathy', 'ريم فتحي'), phone: '010 7712 6650', source: 'cart', ref: aM('Cart · 14 items · EGP 23,900', 'السلة · 14 منتج · 23,900 ج.م'), status: 'converted', notes: [{ by: 'Hossam', at: aM('1 Oct, 11:40', '1 أكتوبر، 11:40'), text: aM('Trade discount 8% approved. Became order DT-10483.', 'تمت الموافقة على خصم 8%. أصبح الطلب DT-10483.') }] },
  { id: 'L-2287', date: aM('30 Sep, 19:12', '30 سبتمبر، 19:12'), name: aM('Ali Hamdy', 'علي حمدي'), phone: '015 3320 1188', source: 'service', ref: aM('Smart home installation', 'تركيب المنزل الذكي'), status: 'contacted', notes: [] },
  { id: 'L-2286', date: aM('30 Sep, 16:48', '30 سبتمبر، 16:48'), name: aM('Dina Wael', 'دينا وائل'), phone: '012 6654 0091', source: 'brand', ref: aM('Flos', 'Flos'), status: 'new', notes: [] },
  { id: 'L-2285', date: aM('30 Sep, 14:30', '30 سبتمبر، 14:30'), name: aM('Sherif Gamal', 'شريف جمال'), phone: '010 1180 4477', source: 'project', ref: aM('Red Sea resort relight', 'إعادة إضاءة منتجع البحر الأحمر'), status: 'closed', notes: [{ by: 'Mona', at: aM('30 Sep, 15:10', '30 سبتمبر، 15:10'), text: aM('Looking for a contractor in Hurghada only — out of scope.', 'يبحث عن مقاول في الغردقة فقط — خارج النطاق.') }] },
  { id: 'L-2284', date: aM('30 Sep, 10:05', '30 سبتمبر، 10:05'), name: aM('Nour Hassan', 'نور حسن'), phone: '011 0023 9981', source: 'article', ref: aM('How to light a kitchen in three layers', 'كيف تضيء المطبخ بثلاث طبقات'), status: 'new', notes: [] },
  { id: 'L-2283', date: aM('29 Sep, 21:40', '29 سبتمبر، 21:40'), name: aM('Hazem Ibrahim', 'حازم إبراهيم'), phone: '010 5567 3321', source: 'general', ref: aM('Home page · floating button', 'الصفحة الرئيسية · الزر العائم'), status: 'contacted', notes: [] },
];

const A_CONSULT_TYPES = { design: ['lamp-ceiling', aM('Lighting design', 'تصميم إضاءة')], smart: ['house', aM('Smart home', 'منزل ذكي')], electrical: ['plug-zap', aM('Electrical', 'كهرباء')], visit: ['map-pin', aM('Site visit', 'زيارة موقع')] };
const A_CONSULTS = [
  { id: 'C-0418', date: aM('1 Oct, 12:44', '1 أكتوبر، 12:44'), name: aM('Rana Ezzat', 'رنا عزت'), phone: '010 3345 7781', source: 'design', ref: aM('Apartment 240 m² · Zamalek · prefers Sat 11:00', 'شقة 240م² · الزمالك · تفضل السبت 11 ص'), status: 'new', notes: [] },
  { id: 'C-0417', date: aM('1 Oct, 09:30', '1 أكتوبر، 09:30'), name: aM('Khaled Nour', 'خالد نور'), phone: '012 4410 9020', source: 'smart', ref: aM('Villa · Sheikh Zayed · KNX retrofit', 'فيلا · الشيخ زايد · تحويل KNX'), status: 'new', notes: [] },
  { id: 'C-0416', date: aM('30 Sep, 17:15', '30 سبتمبر، 17:15'), name: aM('Aya Selim', 'آية سليم'), phone: '011 9087 1123', source: 'visit', ref: aM('Clinic fit-out · Heliopolis', 'تجهيز عيادة · مصر الجديدة'), status: 'contacted', notes: [{ by: 'Hossam', at: aM('30 Sep, 18:00', '30 سبتمبر، 18:00'), text: aM('Visit booked Sun 5 Oct, 10:00 with Eng. Walid.', 'زيارة محجوزة الأحد 5 أكتوبر 10 ص مع م. وليد.') }] },
  { id: 'C-0415', date: aM('29 Sep, 13:02', '29 سبتمبر، 13:02'), name: aM('Bassem Fouad', 'باسم فؤاد'), phone: '010 6620 4419', source: 'electrical', ref: aM('Office 3 floors · New Capital', 'مكتب 3 أدوار · العاصمة الإدارية'), status: 'converted', notes: [] },
  { id: 'C-0414', date: aM('28 Sep, 10:40', '28 سبتمبر، 10:40'), name: aM('Mai Tawfik', 'مي توفيق'), phone: '012 1209 8876', source: 'design', ref: aM('Restaurant · Maadi', 'مطعم · المعادي'), status: 'closed', notes: [] },
];

const A_COUPONS = [
  { id: 'cp1', code: 'DOTS10', type: 'pct', value: 10, min: 2000, start: '2026-09-01', end: '2026-10-31', limit: 500, used: 214, active: true },
  { id: 'cp2', code: 'WELCOME250', type: 'fixed', value: 250, min: 1500, start: '2026-01-01', end: '2026-12-31', limit: 0, used: 1032, active: true },
  { id: 'cp3', code: 'SMARTHOME15', type: 'pct', value: 15, min: 5000, start: '2026-10-01', end: '2026-10-15', limit: 100, used: 0, active: true },
  { id: 'cp4', code: 'RAMADAN26', type: 'pct', value: 12, min: 3000, start: '2026-02-18', end: '2026-03-20', limit: 300, used: 300, active: false },
  { id: 'cp5', code: 'TRADE500', type: 'fixed', value: 500, min: 10000, start: '2026-06-01', end: '2026-12-31', limit: 50, used: 17, active: true },
];

const A_DELIVERY = [
  { id: 'dm1', name: aM('Standard delivery', 'توصيل عادي'), desc: aM('1–2 working days in Cairo & Giza, 2–5 elsewhere', '1–2 يوم عمل في القاهرة والجيزة، 2–5 لباقي المحافظات'), price: 150, govs: Object.keys(aGov), note: aM('Free over EGP 5,000', 'مجاني فوق 5,000 ج.م'), order: 1, active: true },
  { id: 'dm2', name: aM('Express same day', 'توصيل سريع في نفس اليوم'), desc: aM('Order before 12:00, delivered by 21:00', 'اطلب قبل 12 ظهراً، يصلك قبل 9 مساءً'), price: 300, govs: ['cai', 'giz'], note: aM('', ''), order: 2, active: true },
  { id: 'dm3', name: aM('Showroom pickup', 'استلام من المعرض'), desc: aM('Ready in 2 hours · Sheikh Zayed showroom', 'جاهز خلال ساعتين · معرض الشيخ زايد'), price: 0, govs: ['cai', 'giz', 'qal'], note: aM('Bring your order number', 'أحضر رقم الطلب'), order: 3, active: true },
  { id: 'dm4', name: aM('Delivery + installation', 'توصيل وتركيب'), desc: aM('Our technician installs and tests on site', 'الفني يركب ويختبر في الموقع'), price: 450, govs: ['cai', 'giz', 'alx'], note: aM('Per visit, up to 10 fittings', 'للزيارة، حتى 10 وحدات'), order: 4, active: false },
];

const A_USERS = [
  { id: 'u1', name: 'Mona Adel', email: 'mona@dots.eg', last: aM('Now', 'الآن'), status: 'on', you: true },
  { id: 'u2', name: 'Hossam Farouk', email: 'hossam@dots.eg', last: aM('12 min ago', 'منذ 12 دقيقة'), status: 'on' },
  { id: 'u3', name: 'Walid Sherif', email: 'walid@dots.eg', last: aM('Yesterday, 18:20', 'أمس، 18:20'), status: 'on' },
  { id: 'u4', name: 'Sara Magdy', email: 'sara@dots.eg', last: aM('—', '—'), status: 'invited' },
  { id: 'u5', name: 'Amr Zaki', email: 'amr@dots.eg', last: aM('14 Aug 2026', '14 أغسطس 2026'), status: 'off' },
];

const A_CATS = [
  { id: 'c1', icon: 'lamp-ceiling', name: aM('Indoor lighting', 'إضاءة داخلية'), count: 4210, visible: true, children: [
    { id: 'c11', name: aM('Ceiling', 'أسقف'), count: 2318, visible: true, children: [{ id: 'c111', name: aM('Pendants', 'معلقات'), count: 612, visible: true }, { id: 'c112', name: aM('Chandeliers', 'نجف'), count: 188, visible: true }, { id: 'c113', name: aM('Downlights', 'سبوتات غاطسة'), count: 960, visible: true }, { id: 'c114', name: aM('Panel lights', 'بانل لايت'), count: 558, visible: false }] },
    { id: 'c12', name: aM('Wall', 'حوائط'), count: 702, visible: true, children: [{ id: 'c121', name: aM('Wall lights', 'أبليكات'), count: 488, visible: true }, { id: 'c122', name: aM('Picture lights', 'إضاءة لوحات'), count: 214, visible: true }] },
    { id: 'c13', name: aM('Track & linear', 'تراك وخطي'), count: 410, visible: true, children: [{ id: 'c131', name: aM('Magnetic track', 'تراك مغناطيسي'), count: 236, visible: true }, { id: 'c132', name: aM('Linear profiles', 'بروفايلات خطية'), count: 174, visible: true }] },
  ] },
  { id: 'c2', icon: 'trees', name: aM('Outdoor lighting', 'إضاءة خارجية'), count: 1320, visible: true, children: [
    { id: 'c21', name: aM('Facade', 'واجهات'), count: 540, visible: true, children: [{ id: 'c211', name: aM('Up/down lights', 'أعلى/أسفل'), count: 320, visible: true }, { id: 'c212', name: aM('Wall washers', 'وول واشر'), count: 220, visible: true }] },
    { id: 'c22', name: aM('Garden', 'حدائق'), count: 780, visible: true, children: [{ id: 'c221', name: aM('Bollards', 'أعمدة قصيرة'), count: 310, visible: true }, { id: 'c222', name: aM('Spike spots', 'سبوتات أرضية'), count: 470, visible: true }] },
  ] },
  { id: 'c3', icon: 'house', name: aM('Smart home', 'المنزل الذكي'), count: 720, visible: true, children: [
    { id: 'c31', name: aM('Control', 'التحكم'), count: 420, visible: true, children: [{ id: 'c311', name: aM('Smart switches', 'مفاتيح ذكية'), count: 260, visible: true }, { id: 'c312', name: aM('Sensors', 'حساسات'), count: 160, visible: true }] },
  ] },
  { id: 'c4', icon: 'plug-zap', name: aM('Electrical', 'مستلزمات كهربائية'), count: 3760, visible: true, children: [
    { id: 'c41', name: aM('Wiring devices', 'مفاتيح وبرايز'), count: 2140, visible: true, children: [{ id: 'c411', name: aM('Switches', 'مفاتيح'), count: 1180, visible: true }, { id: 'c412', name: aM('Sockets', 'برايز'), count: 960, visible: true }] },
    { id: 'c42', name: aM('Protection', 'الحماية'), count: 1620, visible: true, children: [{ id: 'c421', name: aM('Breakers', 'قواطع'), count: 1240, visible: true }, { id: 'c422', name: aM('Distribution boards', 'لوحات توزيع'), count: 380, visible: true }] },
  ] },
];

const A_TEMPLATES = [
  { id: 'product', name: aM('Product', 'منتج'), en: 'Hello Dots, I’d like a quote for {product_name} ({sku}).\nQuantity: \nLink: {product_url}', ar: 'مرحباً دوتس، أريد عرض سعر لـ {product_name} ({sku}).\nالكمية: \nالرابط: {product_url}', vars: ['product_name', 'sku', 'product_url'] },
  { id: 'cart', name: aM('Cart', 'السلة'), en: 'Hello Dots, please quote my cart ({cart_count} items, {cart_total}):\n{cart_lines}', ar: 'مرحباً دوتس، أريد عرض سعر لسلتي ({cart_count} منتج، {cart_total}):\n{cart_lines}', vars: ['cart_count', 'cart_total', 'cart_lines'] },
  { id: 'instapay', name: aM('InstaPay order', 'طلب إنستاباي'), en: 'Hello Dots, I placed order {order_no} for {total} and want to pay with InstaPay.', ar: 'مرحباً دوتس، قمت بالطلب {order_no} بقيمة {total} وأريد الدفع بإنستاباي.', vars: ['order_no', 'total', 'customer_name'] },
  { id: 'boq', name: aM('BOQ / drawings', 'مقايسة / رسومات'), en: 'Hello Dots, I have a BOQ / drawings for {project_type} in {location}. Can you price it?', ar: 'مرحباً دوتس، لدي مقايسة / رسومات لـ {project_type} في {location}. هل يمكنكم تسعيرها؟', vars: ['project_type', 'location', 'page_url'] },
  { id: 'general', name: aM('General', 'عام'), en: 'Hello Dots, I have a question about {page_title}.', ar: 'مرحباً دوتس، لدي سؤال بخصوص {page_title}.', vars: ['page_title', 'page_url'] },
  { id: 'update', name: aM('Order update (to customer)', 'تحديث الطلب (للعميل)'), en: 'Hi {customer_name}, this is Dots. Order {order_no} is now: {order_status}. {customer_note}\nTrack it: {tracking_url}', ar: 'أهلاً {customer_name}، معك دوتس. حالة الطلب {order_no} الآن: {order_status}. {customer_note}\nتتبع الطلب: {tracking_url}', vars: ['customer_name', 'order_no', 'order_status', 'customer_note', 'expected_date', 'tracking_url'] },
];

;


/* Admin sample data, part 2 — attributes, brands, services, projects, blog, content pages, customers, newsletter. */
const bM = (en, ar) => ({ en, ar });

Object.assign(A_STATUS, {
  post: { draft: ['neutral', bM('Draft', 'مسودة')], published: ['success', bM('Published', 'منشور')], scheduled: ['info', bM('Scheduled', 'مجدول')] },
  project: { current: ['accent', bM('Current', 'جاري')], completed: ['success', bM('Completed', 'مكتمل')] },
  customer: { active: ['success', bM('Active', 'نشط')], disabled: ['error', bM('Disabled', 'معطل')] },
  sub: { subscribed: ['success', bM('Subscribed', 'مشترك')], unsubscribed: ['neutral', bM('Unsubscribed', 'ألغى الاشتراك')] },
});

/* Swatch values are CSS strings built from Dots tokens so the kit never introduces new colours. */
const A_SWATCHES = [
  ['var(--ink-900)', bM('Black', 'أسود')], ['var(--white)', bM('White', 'أبيض')], ['var(--ink-400)', bM('Grey', 'رمادي')],
  ['linear-gradient(135deg, var(--yellow-300), var(--yellow-800))', bM('Brass', 'نحاسي')], ['linear-gradient(135deg, var(--ink-100), var(--ink-400))', bM('Chrome', 'كروم')],
  ['linear-gradient(135deg, var(--ink-700), var(--ink-900))', bM('Graphite', 'جرافيت')],
];
const A_ATTRIBUTES = [
  { id: 'cct', code: 'cct', name: bM('Colour temperature', 'درجة حرارة اللون'), type: 'chip', unit: 'K', filterable: true, variants: true, values: [['2700', bM('2700K · Warm', '2700K · دافئ')], ['3000', bM('3000K · Warm white', '3000K · أبيض دافئ')], ['4000', bM('4000K · Neutral', '4000K · محايد')], ['6500', bM('6500K · Daylight', '6500K · نهاري')]] },
  { id: 'watt', code: 'wattage', name: bM('Wattage', 'القدرة'), type: 'button', unit: 'W', filterable: true, variants: true, values: [['12', bM('12 W', '12 وات')], ['18', bM('18 W', '18 وات')], ['24', bM('24 W', '24 وات')], ['36', bM('36 W', '36 وات')]] },
  { id: 'size', code: 'size', name: bM('Size', 'المقاس'), type: 'button', unit: 'mm', filterable: true, variants: true, values: [['300', bM('Ø300', 'Ø300')], ['450', bM('Ø450', 'Ø450')], ['600', bM('Ø600', 'Ø600')]] },
  { id: 'finish', code: 'finish', name: bM('Finish', 'التشطيب'), type: 'swatch', unit: '', filterable: true, variants: true, values: [['black', bM('Matte black', 'أسود مطفي'), A_SWATCHES[0][0]], ['white', bM('White', 'أبيض'), A_SWATCHES[1][0]], ['brass', bM('Brushed brass', 'نحاسي مصقول'), A_SWATCHES[3][0]], ['chrome', bM('Chrome', 'كروم'), A_SWATCHES[4][0]]] },
  { id: 'beam', code: 'beam_angle', name: bM('Beam angle', 'زاوية الشعاع'), type: 'chip', unit: '°', filterable: true, variants: false, values: [['24', bM('24°', '24°')], ['36', bM('36°', '36°')], ['60', bM('60°', '60°')]] },
  { id: 'ip', code: 'ip_rating', name: bM('IP rating', 'درجة الحماية'), type: 'chip', unit: '', filterable: true, variants: false, values: [['20', bM('IP20', 'IP20')], ['44', bM('IP44', 'IP44')], ['65', bM('IP65', 'IP65')]] },
  { id: 'dim', code: 'dimming', name: bM('Dimming', 'التعتيم'), type: 'chip', unit: '', filterable: true, variants: false, values: [['none', bM('Non-dim', 'بدون تعتيم')], ['phase', bM('Phase-cut', 'قطع الطور')], ['dali', bM('DALI', 'DALI')]] },
].map((a) => ({ ...a, values: a.values.map(([v, label, swatch]) => ({ v, label, swatch })) }));

const A_BRANDS = [
  ['philips', 'Philips', bM('Netherlands', 'هولندا'), 2140, 7], ['legrand', 'Legrand', bM('France', 'فرنسا'), 1860, 29], ['schneider', 'Schneider Electric', bM('France', 'فرنسا'), 1720, 18],
  ['delta', 'Delta Light', bM('Belgium', 'بلجيكا'), 640, 8], ['osram', 'Osram', bM('Germany', 'ألمانيا'), 980, 2], ['abb', 'ABB', bM('Switzerland', 'سويسرا'), 1310, 17],
  ['flos', 'Flos', bM('Italy', 'إيطاليا'), 320, 33], ['artemide', 'Artemide', bM('Italy', 'إيطاليا'), 280, 12],
].map(([id, name, origin, count, ph], i) => ({ id, name, origin, count, logo: kPh(ph, 300), cover: kPh([0, 13, 18, 14, 2, 17, 33, 27][i], 1200), featured: i === 0, active: i !== 7, authorised: i < 6, about: i < 6, home: i < 6, sort: i + 1, catalogue: i < 5 ? `${id}-catalogue-2026.pdf` : null }));

const A_SERVICES = [
  { id: 'design', num: '01', title: bM('Lighting design', 'تصميم الإضاءة'), excerpt: bM('Lux calculations, 3D renders and a fixture schedule priced in two budgets.', 'حسابات الإضاءة وتصميمات ثلاثية الأبعاد وجدول وحدات بميزانيتين.'), img: 13, included: 6, footer: true, active: true },
  { id: 'smart', num: '02', title: bM('Smart home', 'المنزل الذكي'), excerpt: bM('KNX and Zigbee systems for lighting, shading and climate — one app, one installer.', 'أنظمة KNX وZigbee للإضاءة والستائر والتكييف — تطبيق واحد وفريق واحد.'), img: 18, included: 5, footer: true, active: true },
  { id: 'electrical', num: '03', title: bM('Electrical installation', 'التركيبات الكهربائية'), excerpt: bM('Distribution boards, wiring and testing by licensed electricians.', 'لوحات التوزيع والتمديدات والاختبار بواسطة كهربائيين معتمدين.'), img: 17, included: 6, footer: true, active: true },
  { id: 'care', num: '04', title: bM('Maintenance & aftercare', 'الصيانة وخدمة ما بعد البيع'), excerpt: bM('Scheduled visits, driver replacements and warranty handling.', 'زيارات دورية واستبدال أجهزة التشغيل ومتابعة الضمان.'), img: 4, included: 4, footer: false, active: true },
];
const A_SERVICE_INCLUDED = [
  bM('Site survey & measurements', 'معاينة الموقع والمقاسات'), bM('Lux levels per room to EN 12464', 'مستويات الإضاءة لكل غرفة وفق EN 12464'), bM('DIALux 3D renders', 'تصميمات DIALux ثلاثية الأبعاد'),
  bM('Fixture schedule with 2 budget options', 'جدول وحدات بخيارين للميزانية'), bM('Circuit & control plan (DALI / smart)', 'خطة الدوائر والتحكم (DALI / ذكي)'), bM('On-site aiming at handover', 'ضبط الاتجاهات عند التسليم'),
];
const A_FAQ = [
  { q: bM('How much does a lighting design cost?', 'كم تكلفة تصميم الإضاءة؟'), a: bM('Residential designs start from EGP 6,000 and are credited against a supply order above EGP 60,000.', 'تبدأ التصميمات السكنية من 6,000 ج.م وتُخصم عند طلب توريد يتجاوز 60,000 ج.م.') },
  { q: bM('Which areas do you install in?', 'ما المناطق التي تركبون بها؟'), a: bM('Greater Cairo, Alexandria, the North Coast, Sokhna and Hurghada. Other governorates on request.', 'القاهرة الكبرى والإسكندرية والساحل الشمالي والسخنة والغردقة. باقي المحافظات حسب الطلب.') },
  { q: bM('Can you work from my contractor’s drawings?', 'هل يمكنكم العمل على رسومات المقاول؟'), a: bM('Yes — send DWG or PDF plans and we return a lighting layout and priced schedule.', 'نعم — أرسل رسومات DWG أو PDF ونرسل لك توزيع الإضاءة وجدول أسعار.') },
];

const A_PHASES = [bM('Design', 'التصميم'), bM('First fix', 'التمديدات'), bM('Installation', 'التركيب'), bM('Commissioning', 'التشغيل والاختبار'), bM('Handover', 'التسليم')];
const A_PROJECT_TYPES = { res: bM('Residential', 'سكني'), com: bM('Commercial', 'تجاري'), out: bM('Outdoor', 'خارجي'), smart: bM('Smart home', 'منزل ذكي'), hosp: bM('Hospitality', 'ضيافة') };
const A_PROJECTS = [
  { id: 'pr1', st: 'current', type: 'res', title: bM('Villa K — Sodic West', 'فيلا K — سوديك ويست'), loc: bM('Sheikh Zayed', 'الشيخ زايد'), client: '', progress: 62, stage: 2, img: 0, featured: true, active: true, start: '2026-04-12', expected: '2026-12-15' },
  { id: 'pr2', st: 'current', type: 'hosp', title: bM('Azure Bay Hotel lobby', 'لوبي فندق أزور باي'), loc: bM('El Gouna', 'الجونة'), client: 'Azure Hotels', progress: 35, stage: 1, img: 27, featured: true, active: true, start: '2026-06-01', expected: '2027-02-28' },
  { id: 'pr3', st: 'current', type: 'com', title: bM('Nile Tower offices, floors 8–11', 'مكاتب برج النيل، الأدوار 8–11'), loc: bM('Garden City', 'جاردن سيتي'), client: 'Nile Tower Co.', progress: 84, stage: 3, img: 9, featured: false, active: true, start: '2026-02-20', expected: '2026-10-30' },
  { id: 'pr4', st: 'completed', type: 'smart', title: bM('Penthouse M — Zamalek', 'بنتهاوس M — الزمالك'), loc: bM('Zamalek', 'الزمالك'), client: '', progress: 100, stage: 4, img: 1, featured: true, active: true, year: 2026 },
  { id: 'pr5', st: 'completed', type: 'out', title: bM('Hacienda Bay landscape', 'تنسيق هاسيندا باي'), loc: bM('North Coast', 'الساحل الشمالي'), client: 'Palm Hills', progress: 100, stage: 4, img: 22, featured: false, active: true, year: 2025 },
  { id: 'pr6', st: 'completed', type: 'com', title: bM('Kemet Clinic', 'عيادة كيميت'), loc: bM('Heliopolis', 'مصر الجديدة'), client: 'Kemet Medical', progress: 100, stage: 4, img: 11, featured: false, active: true, year: 2025 },
  { id: 'pr7', st: 'completed', type: 'hosp', title: bM('Fayrouz restaurant', 'مطعم فيروز'), loc: bM('Maadi', 'المعادي'), client: 'Fayrouz Group', progress: 100, stage: 4, img: 30, featured: false, active: false, year: 2024 },
];

const A_BLOG_CATS = [
  { id: 'guides', name: bM('Lighting guides', 'أدلة الإضاءة'), slug: 'guides', order: 1, count: 14 },
  { id: 'smart', name: bM('Smart home', 'المنزل الذكي'), slug: 'smart-home', order: 2, count: 8 },
  { id: 'projects', name: bM('Project stories', 'قصص المشروعات'), slug: 'projects', order: 3, count: 6 },
  { id: 'news', name: bM('News', 'الأخبار'), slug: 'news', order: 4, count: 4 },
];
const A_TAGS = [
  ['kitchen', bM('Kitchen', 'المطبخ')], ['dimming', bM('Dimming', 'التعتيم')], ['cct', bM('Colour temperature', 'درجة اللون')], ['knx', bM('KNX', 'KNX')],
  ['outdoor', bM('Outdoor', 'خارجي')], ['energy', bM('Energy saving', 'توفير الطاقة')], ['villa', bM('Villa', 'فيلا')], ['office', bM('Office', 'مكاتب')],
].map(([slug, name], i) => ({ id: slug, slug, name, order: i + 1, count: [9, 7, 5, 4, 6, 3, 5, 2][i] }));
const A_POSTS = [
  { id: 'b1', title: bM('How to light a kitchen in three layers', 'كيف تضيء المطبخ بثلاث طبقات'), cat: 'guides', status: 'published', featured: true, date: '2026-09-03', img: 13, read: 6, tags: ['kitchen', 'dimming'] },
  { id: 'b2', title: bM('2700K or 3000K? Choosing warm white', '2700K أم 3000K؟ اختيار الأبيض الدافئ'), cat: 'guides', status: 'published', featured: true, date: '2026-08-27', img: 28, read: 4, tags: ['cct'] },
  { id: 'b3', title: bM('KNX vs Zigbee for an Egyptian villa', 'KNX أم Zigbee لفيلا في مصر'), cat: 'smart', status: 'scheduled', featured: false, date: '2026-10-12', img: 18, read: 8, tags: ['knx', 'villa'] },
  { id: 'b4', title: bM('Inside Penthouse M: scenes, not switches', 'داخل بنتهاوس M: مشاهد بدلاً من المفاتيح'), cat: 'projects', status: 'published', featured: false, date: '2026-08-19', img: 1, read: 5, tags: ['knx', 'villa'] },
  { id: 'b5', title: bM('Outdoor IP ratings explained', 'شرح درجات الحماية للإضاءة الخارجية'), cat: 'guides', status: 'published', featured: false, date: '2026-08-08', img: 22, read: 5, tags: ['outdoor'] },
  { id: 'b6', title: bM('Dots opens a second showroom in New Cairo', 'دوتس تفتتح معرضاً ثانياً في القاهرة الجديدة'), cat: 'news', status: 'draft', featured: false, date: '', img: 9, read: 2, tags: [] },
  { id: 'b7', title: bM('Cutting office lighting bills by 40%', 'خفض فاتورة إضاءة المكاتب 40%'), cat: 'guides', status: 'published', featured: false, date: '2026-07-22', img: 11, read: 7, tags: ['office', 'energy'] },
];

const A_CONTENT_PAGES = [
  { id: 'delivery', title: bM('Delivery', 'التوصيل'), slug: bM('delivery', 'التوصيل'), updated: '2026-09-14', footer: true, active: true, sort: 1 },
  { id: 'returns', title: bM('Returns', 'الاسترجاع'), slug: bM('returns', 'الاسترجاع'), updated: '2026-09-02', footer: true, active: true, sort: 2 },
  { id: 'warranty', title: bM('Warranty', 'الضمان'), slug: bM('warranty', 'الضمان'), updated: '2026-08-20', footer: true, active: true, sort: 3 },
  { id: 'careers', title: bM('Careers', 'الوظائف'), slug: bM('careers', 'الوظائف'), updated: '2026-09-25', footer: true, active: true, sort: 4 },
  { id: 'terms', title: bM('Terms of use', 'شروط الاستخدام'), slug: bM('terms', 'الشروط'), updated: '2026-01-01', footer: false, active: true, sort: 5 },
  { id: 'privacy', title: bM('Privacy policy', 'سياسة الخصوصية'), slug: bM('privacy', 'الخصوصية'), updated: '2026-01-01', footer: false, active: false, sort: 6 },
];

const cRaw = [
  ['Ahmed Samir', 'أحمد سمير', '010 1234 5678', 'ahmed.samir@gmail.com', 4, 30756, '2025-11-04', 'cai'],
  ['Mariam Fawzy', 'مريم فوزي', '012 7788 1902', 'mariam.fawzy@outlook.com', 2, 6820, '2026-03-18', 'giz'],
  ['Nadine Khalil', 'نادين خليل', '010 9934 0017', 'nadine.k@yahoo.com', 3, 9140, '2026-01-22', 'cai'],
  ['Karim Adel', 'كريم عادل', '015 5501 7720', 'karim@adelstudio.com', 7, 128600, '2024-06-12', 'red'],
  ['Hana Mostafa', 'هنا مصطفى', '012 3001 4456', 'hana.mostafa@gmail.com', 1, 0, '2026-09-28', 'giz'],
  ['Youssef Nabil', 'يوسف نبيل', '010 2244 8890', 'y.nabil@gmail.com', 5, 21300, '2025-04-30', 'qal'],
  ['Salma Hegazy', 'سلمى حجازي', '011 7765 2210', 'salma.hegazy@gmail.com', 2, 7940, '2025-12-09', 'cai'],
  ['Tarek Mansour', 'طارق منصور', '010 6612 9043', 'tarek.mansour@mansourco.com', 9, 86450, '2024-02-17', 'dak'],
  ['Hazem Ibrahim', 'حازم إبراهيم', '010 5567 3321', 'hazem.ib@gmail.com', 0, 0, '2026-08-11', 'cai'],
];
const A_CUSTOMERS = cRaw.map(([en, ar, phone, email, orders, spent, joined, gov], i) => ({ id: 'cu' + (i + 1), name: bM(en, ar), phone, email, orders, spent, joined, gov, status: i === 8 ? 'disabled' : 'active', company: i === 3 ? { name: 'Adel Studio', tax: '512-338-901' } : null }));
/** Orders placed without an account. */
const A_GUEST_ORDERS = ['DT-10480', 'DT-10473'];

const A_SUB_SOURCES = { footer: bM('Footer', 'الفوتر'), blog: bM('Blog', 'المدونة') };
const A_SUBSCRIBERS = [
  ['reem.fathy@gmail.com', 'footer', '2026-10-01', 'subscribed', 'ar'], ['m.ali@outlook.com', 'blog', '2026-09-30', 'subscribed', 'ar'], ['dina.wael@gmail.com', 'blog', '2026-09-29', 'subscribed', 'en'],
  ['sherif.g@company.eg', 'footer', '2026-09-27', 'unsubscribed', 'en'], ['nour.h@gmail.com', 'blog', '2026-09-24', 'subscribed', 'ar'], ['bassem.f@gmail.com', 'footer', '2026-09-20', 'subscribed', 'ar'],
  ['aya.selim@clinic.eg', 'footer', '2026-09-18', 'subscribed', 'en'], ['k.nour@gmail.com', 'footer', '2026-09-11', 'subscribed', 'ar'], ['mai.tawfik@gmail.com', 'blog', '2026-09-02', 'unsubscribed', 'ar'],
  ['omar.hesham@gmail.com', 'footer', '2026-08-29', 'subscribed', 'ar'], ['lina.g@studio.eg', 'blog', '2026-08-21', 'subscribed', 'en'],
].map(([email, source, date, status, lang], i) => ({ id: 'n' + i, email, source, date, status, lang }));

/* Patches to part-1 data for the updated spec. */
A_LEADS.unshift(
  { id: 'L-2293', date: bM('1 Oct, 15:02', '1 أكتوبر، 15:02'), name: bM('', ''), phone: '', source: 'order', ref: bM('Track order page · DT-10476', 'صفحة تتبع الطلب · DT-10476'), status: 'new', notes: [] },
);
A_DELIVERY.forEach((d) => Object.assign(d, { icon: { dm1: 'truck', dm2: 'zap', dm3: 'store', dm4: 'wrench' }[d.id], from: d.id === 'dm4', freeOver: d.id === 'dm1' ? 5000 : '', addr: d.id !== 'dm3' }));

;


/* Admin shell + shared primitives (prefix A). */
function APill({ kind, v }) {
  const { tx } = useLocale();
  const s = (A_STATUS[kind] || {})[v];
  if (!s) return null;
  return <Badge tone={s[0]} dot={s[0] !== 'outline'}>{tx(s[1])}</Badge>;
}

function ASwitch({ checked, onChange, label, help, disabled }) {
  return (
    <label className="a-switch">
      <input type="checkbox" role="switch" checked={!!checked} disabled={disabled} onChange={(e) => onChange && onChange(e.target.checked)} onClick={(e) => e.stopPropagation()} />
      {(label || help) && <span>{label}{help && <small>{help}</small>}</span>}
    </label>
  );
}

function ACard({ title, icon, action, children, body = true, style, foot }) {
  return (
    <section className="a-card" style={style}>
      {(title || action) && <header className="a-card__head">{title && <h3>{icon && <Icon name={icon} size={18} />}{title}</h3>}{action}</header>}
      {body ? <div className="a-card__body">{children}</div> : children}
      {foot && <footer className="a-card__foot">{foot}</footer>}
    </section>
  );
}

function AHead({ title, sub, crumbs, children, extra }) {
  return (
    <div style={{ display: 'grid', gap: 10 }}>
      {crumbs && <Breadcrumbs items={crumbs} />}
      <div className="a-head">
        <div style={{ minWidth: 0 }}><h1>{title}{extra}</h1>{sub && <p>{sub}</p>}</div>
        {children && <div className="a-head__act">{children}</div>}
      </div>
    </div>
  );
}

function AGrid({ cols = 2, children, style, gap = 16 }) {
  const { compact } = useLocale();
  return <div style={{ display: 'grid', gridTemplateColumns: compact ? 'minmax(0,1fr)' : `repeat(${cols}, minmax(0,1fr))`, gap, ...style }}>{children}</div>;
}

/** EN + AR inputs for one field. Each input keeps its own direction regardless of UI language. */
function ABi({ label, en, ar, multiline, rows, counter }) {
  const { t, compact } = useLocale();
  const [v, setV] = React.useState({ en: en || '', ar: ar || '' });
  const cnt = (s) => counter && <span className={cx('a-count', s.length > counter && 'is-over')}>{s.length}/{counter}</span>;
  return (
    <div style={{ display: 'grid', gridTemplateColumns: compact ? 'minmax(0,1fr)' : 'repeat(2, minmax(0,1fr))', gap: compact ? 10 : 16 }}>
      <Input label={<span style={{ display: 'flex', justifyContent: 'space-between', gap: 8 }}><span>{label} <span className="d-label__opt">· EN</span></span>{cnt(v.en)}</span>} value={v.en} onChange={(e) => setV({ ...v, en: e.target.value })} multiline={multiline} rows={rows} dir="ltr" lang="en" style={{ direction: 'ltr' }} />
      <Input label={<span style={{ display: 'flex', justifyContent: 'space-between', gap: 8 }}><span>{label} <span className="d-label__opt">· {t('AR', 'عربي')}</span></span>{cnt(v.ar)}</span>} value={v.ar} onChange={(e) => setV({ ...v, ar: e.target.value })} multiline={multiline} rows={rows} dir="rtl" lang="ar" style={{ direction: 'rtl', fontFamily: "'IBM Plex Sans Arabic', sans-serif" }} />
    </div>
  );
}

function AAlert({ tone = 'info', title, children, action }) {
  const ico = { error: 'circle-alert', warning: 'triangle-alert', info: 'info', success: 'circle-check' }[tone];
  return <div className={cx('a-alert', `a-alert--${tone}`)} role={tone === 'error' ? 'alert' : 'status'}><Icon name={ico} size={20} /><div>{title && <b>{title}</b>}{children}</div>{action || <span />}</div>;
}

function AEmpty({ icon = 'search-x', title, body, action }) {
  return (
    <div style={{ display: 'grid', justifyItems: 'center', textAlign: 'center', gap: 10, padding: '56px 24px' }}>
      <span style={{ width: 56, height: 56, borderRadius: '50%', background: 'var(--yellow-100)', display: 'grid', placeItems: 'center' }}><Icon name={icon} size={26} /></span>
      <b style={{ font: '800 16px/1.3 var(--font-body)' }}>{title}</b>
      {body && <span style={{ font: '500 14px/1.5 var(--font-body)', color: 'var(--fg-2)', maxWidth: 380 }}>{body}</span>}
      {action}
    </div>
  );
}

function AMenu({ items, icon = 'ellipsis', label }) {
  const { t } = useLocale();
  const [open, setOpen] = React.useState(false);
  const ref = React.useRef(null);
  React.useEffect(() => { if (!open) return; const h = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); }; document.addEventListener('mousedown', h); return () => document.removeEventListener('mousedown', h); }, [open]);
  return (
    <div ref={ref} style={{ position: 'relative', display: 'inline-flex' }} onClick={(e) => e.stopPropagation()}>
      <IconButton icon={icon} size="sm" label={label || t('More actions', 'إجراءات أخرى')} onClick={() => setOpen(!open)} />
      {open && <div className="a-menu">{items.map((it, i) => it === '-' ? <hr key={i} /> : <button key={i} type="button" className={it.danger ? 'is-danger' : undefined} onClick={() => { setOpen(false); it.onClick && it.onClick(); }}><Icon name={it.icon} size={16} />{it.label}</button>)}</div>}
    </div>
  );
}

function ADrawer({ open, onClose, title, sub, children, foot, width = 480 }) {
  const { t } = useLocale();
  if (!open) return null;
  return (
    <>
      <div className="d-overlay" onClick={onClose} />
      <aside className="d-drawer" role="dialog" aria-label={typeof title === 'string' ? title : undefined} style={{ width }}>
        <div className="d-drawer__head"><div style={{ minWidth: 0, flex: 1 }}><h3>{title}</h3>{sub && <div className="a-sub" style={{ marginTop: 6 }}>{sub}</div>}</div><IconButton icon="x" label={t('Close', 'إغلاق')} onClick={onClose} /></div>
        <div className="d-drawer__body" style={{ padding: 20, display: 'grid', gap: 18, alignContent: 'start' }}>{children}</div>
        {foot && <div className="d-drawer__foot" style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', flexWrap: 'wrap' }}>{foot}</div>}
      </aside>
    </>
  );
}

/** Dense table; on mobile each row becomes a card (primary column on top, the rest as a key/value grid). */
function ATable({ columns, rows, onRow, selected, onSelect, sort, onSort, empty, rowKey = 'id' }) {
  const { compact } = useLocale();
  const sel = selected ? new Set(selected) : null;
  const all = sel && rows.length > 0 && rows.every((r) => sel.has(r[rowKey]));
  const toggle = (id) => { const n = new Set(sel); n.has(id) ? n.delete(id) : n.add(id); onSelect([...n]); };
  if (!rows.length) return empty || null;
  if (compact) {
    const prim = columns.find((c) => c.primary) || columns[0];
    const rest = columns.filter((c) => c !== prim && !c.hideMobile);
    return (
      <div className="a-rows">{rows.map((r) => (
        <div key={r[rowKey]} className={cx('a-rowcard', sel && sel.has(r[rowKey]) && 'is-sel')} onClick={() => onRow && onRow(r)}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
            {sel && <span onClick={(e) => e.stopPropagation()}><Checkbox checked={sel.has(r[rowKey])} onChange={() => toggle(r[rowKey])} aria-label="select" /></span>}
            <div style={{ flex: 1, minWidth: 0 }}>{prim.render(r)}</div>
          </div>
          <div className="a-rowcard__kv">{rest.map((c) => <div key={c.id} style={c.wide ? { gridColumn: '1 / -1' } : undefined}>{c.label && <span>{c.label}</span>}{c.render(r)}</div>)}</div>
        </div>
      ))}</div>
    );
  }
  return (
    <div className="a-scroll">
      <table className="a-table">
        <thead><tr>
          {sel && <th className="is-shrink"><Checkbox checked={all} onChange={() => onSelect(all ? [] : rows.map((r) => r[rowKey]))} aria-label="select all" /></th>}
          {columns.map((c) => (
            <th key={c.id} className={cx(c.num && 'is-num', c.shrink && 'is-shrink')} style={{ width: c.w }} aria-sort={sort && sort.id === c.id ? (sort.dir > 0 ? 'ascending' : 'descending') : undefined}>
              {c.sort ? <button type="button" className={cx('a-sort', sort && sort.id === c.id && 'is-on')} onClick={() => onSort({ id: c.id, dir: sort && sort.id === c.id ? -sort.dir : 1 })}>{c.label}<Icon name={sort && sort.id === c.id ? (sort.dir > 0 ? 'arrow-up' : 'arrow-down') : 'arrow-up-down'} size={13} /></button> : c.label}
            </th>
          ))}
        </tr></thead>
        <tbody>{rows.map((r) => (
          <tr key={r[rowKey]} className={cx(onRow && 'is-click', sel && sel.has(r[rowKey]) && 'is-sel')} onClick={() => onRow && onRow(r)}>
            {sel && <td className="is-shrink" onClick={(e) => e.stopPropagation()}><Checkbox checked={sel.has(r[rowKey])} onChange={() => toggle(r[rowKey])} aria-label="select" /></td>}
            {columns.map((c) => <td key={c.id} className={cx(c.num && 'is-num', c.shrink && 'is-shrink')}>{c.render(r)}</td>)}
          </tr>
        ))}</tbody>
      </table>
    </div>
  );
}

function useReorder(initial) {
  const [list, setList] = React.useState(initial);
  const [drag, setDrag] = React.useState(null);
  const [over, setOver] = React.useState(null);
  const props = (i) => ({
    draggable: true,
    onDragStart: (e) => { setDrag(i); e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', String(i)); } catch (x) {} },
    onDragOver: (e) => { e.preventDefault(); if (over !== i) setOver(i); },
    onDragLeave: () => setOver((o) => (o === i ? null : o)),
    onDrop: (e) => { e.preventDefault(); if (drag == null || drag === i) { setDrag(null); setOver(null); return; } setList((l) => { const n = [...l]; const [m] = n.splice(drag, 1); n.splice(i, 0, m); return n; }); setDrag(null); setOver(null); },
    onDragEnd: () => { setDrag(null); setOver(null); },
    className: cx(drag === i && 'a-dragging', over === i && drag !== i && 'a-over'),
  });
  return [list, setList, props];
}

function AFieldLabel({ children }) {
  return <span className="d-label">{children}</span>;
}

const A_NAV = [
  [null, [['dashboard', 'layout-dashboard', aM('Dashboard', 'لوحة التحكم')]]],
  [aM('Catalog', 'الكتالوج'), [['products', 'package', aM('Products', 'المنتجات')], ['categories', 'folder-tree', aM('Categories', 'الأقسام')], ['brands', 'award', aM('Brands', 'العلامات التجارية')], ['attributes', 'sliders-horizontal', aM('Attributes', 'الخصائص')]]],
  [aM('Content', 'المحتوى'), [['pagecontent', 'layout-template', aM('Page content', 'محتوى الصفحات')], ['services', 'wrench', aM('Services', 'الخدمات')], ['projects', 'building-2', aM('Projects', 'المشروعات')], ['blog', 'newspaper', aM('Blog', 'المدونة')], ['contentpages', 'file-text', aM('Content pages', 'صفحات المحتوى')]]],
  [aM('Sales', 'المبيعات'), [['orders', 'shopping-bag', aM('Orders', 'الطلبات'), 4], ['customers', 'users-round', aM('Customers', 'العملاء')], ['coupons', 'ticket-percent', aM('Coupons', 'الكوبونات')]]],
  [aM('Leads', 'العملاء المحتملون'), [['leads', 'message-circle', aM('WhatsApp leads', 'طلبات واتساب'), 5], ['consultations', 'calendar-check', aM('Consultations', 'الاستشارات'), 2], ['newsletter', 'mail', aM('Newsletter', 'النشرة البريدية')]]],
  [aM('Settings', 'الإعدادات'), [['delivery', 'truck', aM('Delivery methods', 'طرق التوصيل')], ['payments', 'credit-card', aM('Payment methods', 'طرق الدفع')], ['settings', 'settings', aM('Settings', 'الإعدادات')], ['users', 'users', aM('Users', 'المستخدمون')]]],
];
const A_SECTION = { product: 'products', order: 'orders', brand: 'brands', attribute: 'attributes', service: 'services', project: 'projects', post: 'blog', blogtax: 'blog', contentpage: 'contentpages', customer: 'customers' };

function ASidebar({ page, go, onClose }) {
  const { t, tx } = useLocale();
  const cur = A_SECTION[page] || page;
  return (
    <nav className="a-side" aria-label={t('Admin navigation', 'قائمة الإدارة')}>
      <div className="a-side__logo">
        <img src={(window.__resources||{}).logo} alt="Dots" />
        <Badge tone="dark">{t('Admin', 'الإدارة')}</Badge>
        {onClose && <IconButton icon="x" size="sm" label={t('Close menu', 'إغلاق القائمة')} onClick={onClose} style={{ marginInlineStart: 'auto' }} />}
      </div>
      <div className="a-side__nav">
        {A_NAV.map(([g, items], gi) => (
          <React.Fragment key={gi}>
            {g && <div className="a-side__group">{tx(g)}</div>}
            {items.map(([k, ic, l, n]) => <button key={k} type="button" className="a-side__link" aria-current={cur === k ? 'page' : undefined} onClick={() => go(k)}><Icon name={ic} size={18} />{tx(l)}{n && <span className="a-side__n">{n}</span>}</button>)}
          </React.Fragment>
        ))}
      </div>
      <div className="a-side__foot">
        <a href="../website/index.html" style={{ display: 'inline-flex', alignItems: 'center', gap: 6, font: '700 13px var(--font-body)', color: 'var(--fg-1)', textDecoration: 'none' }}><Icon name="external-link" size={16} />{t('View store', 'عرض المتجر')}</a>
        <span>Dots Admin · v1.0</span>
      </div>
    </nav>
  );
}

function ATopbar({ onMenu, onLang, go, toast }) {
  const { t, compact } = useLocale();
  const [q, setQ] = React.useState('');
  const [open, setOpen] = React.useState(false);
  const ref = React.useRef(null);
  React.useEffect(() => { if (!open) return; const h = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); }; document.addEventListener('mousedown', h); return () => document.removeEventListener('mousedown', h); }, [open]);
  const search = <SearchBar size="sm" scopes={false} iconOnly value={q} onChange={setQ} onSubmit={() => toast('info', t('Search', 'بحث'), q ? `“${q}” · ${t('3 orders, 12 products', '3 طلبات، 12 منتج')}` : t('Type an order number, SKU or phone', 'اكتب رقم طلب أو كود أو موبايل'))} placeholder={t('Search orders, SKUs, customers…', 'ابحث في الطلبات والأكواد والعملاء…')} style={{ flex: 1, maxWidth: compact ? 'none' : 460, minWidth: 0 }} />;
  return (
    <header className="a-top">
      {compact && <IconButton icon="menu" label={t('Open menu', 'فتح القائمة')} onClick={onMenu} />}
      {compact ? <img src={(window.__resources||{}).logo} alt="Dots" style={{ height: 20 }} /> : search}
      <span style={{ marginInlineStart: 'auto' }} />
      <IconButton icon="bell" label={t('Notifications', 'الإشعارات')} count={3} onClick={() => go('orders')} />
      <LangSwitch onChange={onLang} />
      <div className="a-user" ref={ref}>
        <button type="button" onClick={() => setOpen(!open)} aria-expanded={open}>
          <span className="a-avatar a-avatar--node">MA</span>
          {!compact && <span>Mona Adel<span className="a-sub">{t('Admin', 'مدير')}</span></span>}
          {!compact && <Icon name="chevron-down" size={16} />}
        </button>
        {open && <div className="a-menu">
          <button type="button" onClick={() => { setOpen(false); go('users'); }}><Icon name="user" size={16} />{t('My profile', 'ملفي الشخصي')}</button>
          <button type="button" onClick={() => { setOpen(false); go('settings'); }}><Icon name="settings" size={16} />{t('Settings', 'الإعدادات')}</button>
          <hr />
          <button type="button" className="is-danger" onClick={() => { setOpen(false); go('login'); }}><Icon name="log-out" size={16} />{t('Log out', 'تسجيل الخروج')}</button>
        </div>}
      </div>
      {compact && <div style={{ flexBasis: '100%', display: 'flex' }}>{search}</div>}
    </header>
  );
}

;


/* Shared admin field helpers: image field, rich text, repeatable rows, collapsible section, product picker, SEO, list page pattern. */

/** Image field — preview with Replace / Remove; drop zone when empty. */
function AImage({ label, src, ratio = '16 / 9', help, contain, round }) {
  const { t } = useLocale();
  const [img, setImg] = React.useState(src || null);
  return (
    <div className="d-field">
      {label && <span className="d-label">{label}</span>}
      {img ? (
        <div className="a-card" style={{ overflow: 'hidden' }}>
          <div style={{ background: contain ? 'var(--ink-50)' : undefined, padding: contain ? 16 : 0 }}>
            <ImageFrame src={img} ratio={ratio} contain={contain} radius={round ? '50%' : undefined} style={round ? { width: 96, margin: '0 auto' } : undefined} />
          </div>
          <div style={{ display: 'flex', gap: 6, alignItems: 'center', padding: '6px 8px', borderTop: '1px solid var(--border-1)' }}>
            <span className="a-count" style={{ flex: 1, minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{help || 'image.webp'}</span>
            <Button size="sm" variant="ghost" icon="upload" onClick={() => setImg(kPh(Math.floor(Math.random() * 39), 800))}>{t('Replace', 'استبدال')}</Button>
            <IconButton icon="trash-2" size="sm" label={t('Remove', 'حذف')} onClick={() => setImg(null)} />
          </div>
        </div>
      ) : (
        <button type="button" className="a-drop" style={{ aspectRatio: ratio, width: '100%' }} onClick={() => setImg(kPh(Math.floor(Math.random() * 39), 800))}>
          <Icon name="image-plus" size={22} /><b style={{ color: 'var(--fg-1)' }}>{t('Drop an image or browse', 'أفلت صورة أو تصفح')}</b><span>JPG, PNG, WebP · 5 MB</span>
        </button>
      )}
    </div>
  );
}

/** File field (PDF etc.) with replace/remove. */
function AFile({ label, file, ext = 'PDF' }) {
  const { t } = useLocale();
  const [f, setF] = React.useState(file || null);
  return (
    <div className="d-field">
      {label && <span className="d-label">{label}</span>}
      <div className="d-file" style={f ? undefined : { borderStyle: 'dashed', background: 'var(--ink-50)' }}>
        <span className="d-file__ico"><Icon name="file-text" size={20} /><span className="d-file__ext">{ext}</span></span>
        <span style={{ minWidth: 0, flex: 1 }}><span className="d-file__name" style={{ display: 'block' }}>{f || t('No file uploaded', 'لم يتم رفع ملف')}</span><span className="d-file__meta">{f ? '4.8 MB' : t('Up to 25 MB', 'حتى 25 ميجا')}</span></span>
        {f ? <span style={{ display: 'flex', gap: 4 }}><Button size="sm" variant="ghost">{t('Replace', 'استبدال')}</Button><IconButton icon="trash-2" size="sm" label={t('Remove', 'حذف')} onClick={() => setF(null)} /></span>
          : <Button size="sm" variant="outline" icon="upload" onClick={() => setF('catalogue-2026.pdf')}>{t('Upload', 'رفع')}</Button>}
      </div>
    </div>
  );
}

/** Gallery with drag to reorder, remove, add. */
function AGallery({ label, photos, help }) {
  const { t, compact } = useLocale();
  const [list, setList, props] = useReorder(photos);
  return (
    <div className="d-field">
      {label && <span className="d-label" style={{ display: 'flex', justifyContent: 'space-between', gap: 8 }}>{label}<span className="a-count">{help || t('Drag to reorder · first is the cover', 'اسحب لإعادة الترتيب · الأولى هي الغلاف')}</span></span>}
      <div style={{ display: 'grid', gridTemplateColumns: compact ? 'repeat(2, minmax(0,1fr))' : 'repeat(auto-fill, minmax(140px, 1fr))', gap: 10 }}>
        {list.map((n, i) => { const p = props(i); return (
          <div key={n + '-' + i} {...p} className={cx('a-card', p.className)} style={{ overflow: 'hidden', cursor: 'grab', position: 'relative' }}>
            <ImageFrame src={kPh(n, 400)} ratio="4 / 3" />
            <span style={{ position: 'absolute', top: 6, insetInlineStart: 6 }}><Badge tone={i === 0 ? 'accent' : 'outline'}>{i === 0 ? t('Cover', 'غلاف') : i + 1}</Badge></span>
            <div style={{ display: 'flex', alignItems: 'center', padding: '2px 4px' }}><span className="a-grip"><Icon name="grip-vertical" size={16} /></span><span style={{ flex: 1 }} /><IconButton icon="trash-2" size="sm" label={t('Remove', 'حذف')} onClick={() => setList(list.filter((_, j) => j !== i))} /></div>
          </div>); })}
        <button type="button" className="a-drop" style={{ minHeight: 120 }} onClick={() => setList([...list, Math.floor(Math.random() * 39)])}><Icon name="image-plus" size={22} />{t('Add images', 'إضافة صور')}</button>
      </div>
    </div>
  );
}

const AR_TOOLS = [['heading-2', 'formatBlock', 'h2'], ['heading-3', 'formatBlock', 'h3'], ['pilcrow', 'formatBlock', 'p'], '|', ['bold', 'bold'], ['italic', 'italic'], ['link', 'createLink', 'https://dots.eg'], '|', ['list', 'insertUnorderedList'], ['list-ordered', 'insertOrderedList'], ['text-quote', 'formatBlock', 'blockquote'], '|', ['image', 'img'], ['info', 'callout']];

/** Rich text editor, one language at a time (AR / EN tabs). Toolbar: headings, bold, italic, link, lists, quote, image, callout. */
function ARich({ label, en = '', ar = '', minH = 220 }) {
  const { t, lang } = useLocale();
  const [l, setL] = React.useState(lang);
  const refs = { en: React.useRef(null), ar: React.useRef(null) };
  const run = (tool) => {
    const el = refs[l].current; if (!el) return; el.focus();
    const [, cmd, arg] = tool;
    if (cmd === 'img') document.execCommand('insertHTML', false, `<figure><img src="${kPh(13, 900)}" alt="" style="width:100%;border-radius:8px"/><figcaption>${l === 'ar' ? 'تعليق الصورة' : 'Image caption'}</figcaption></figure><p></p>`);
    else if (cmd === 'callout') document.execCommand('insertHTML', false, `<div class="d-callout">${l === 'ar' ? 'نص التنبيه' : 'Callout text'}</div><p></p>`);
    else document.execCommand(cmd, false, arg);
  };
  return (
    <div className="d-field">
      <span className="d-label" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 8, flexWrap: 'wrap' }}>{label}
        <span className="d-tabs d-tabs--pill" style={{ flex: 'none' }}>{[['ar', 'العربية'], ['en', 'English']].map(([k, n]) => <button key={k} type="button" className="d-tab" aria-selected={l === k} onClick={() => setL(k)}>{n}</button>)}</span>
      </span>
      <div style={{ border: '1px solid var(--border-control)', borderRadius: 'var(--radius-md)', overflow: 'hidden', background: 'var(--white)' }}>
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 2, padding: 6, borderBottom: '1px solid var(--border-1)', background: 'var(--ink-50)' }} dir="ltr">
          {AR_TOOLS.map((x, i) => x === '|' ? <span key={i} style={{ width: 1, background: 'var(--border-1)', margin: '4px 4px' }} /> : <IconButton key={i} icon={x[0]} size="sm" label={x[0]} onMouseDown={(e) => e.preventDefault()} onClick={() => run(x)} />)}
        </div>
        {['en', 'ar'].map((k) => (
          <div key={k} ref={refs[k]} className="d-prose" contentEditable suppressContentEditableWarning dir={k === 'ar' ? 'rtl' : 'ltr'} lang={k}
            style={{ display: l === k ? 'block' : 'none', minHeight: minH, padding: '14px 16px', outline: 'none', fontSize: 15, fontFamily: k === 'ar' ? "'IBM Plex Sans Arabic', sans-serif" : undefined }}
            dangerouslySetInnerHTML={{ __html: k === 'ar' ? ar : en }} />
        ))}
      </div>
    </div>
  );
}

/** Repeatable rows with drag handle and remove. render(item, i, update) returns fields. */
function ARepeat({ initial, blank, render, addLabel, max }) {
  const { t } = useLocale();
  const [list, setList, props] = useReorder(initial.map((x, i) => ({ _k: i + 1, ...x })));
  const upd = (i) => (patch) => setList(list.map((x, j) => (j === i ? { ...x, ...patch } : x)));
  return (
    <div style={{ display: 'grid', gap: 10 }}>
      {list.map((it, i) => { const p = props(i); return (
        <div key={it._k} {...p} className={cx('a-card', p.className)} style={{ padding: 12, display: 'grid', gridTemplateColumns: '20px minmax(0,1fr) auto', gap: 10, alignItems: 'start' }}>
          <span className="a-grip" style={{ paddingTop: 30, cursor: 'grab' }}><Icon name="grip-vertical" size={16} /></span>
          <div style={{ display: 'grid', gap: 10, minWidth: 0 }}>{render(it, i, upd(i))}</div>
          <IconButton icon="trash-2" size="sm" label={t('Remove', 'حذف')} onClick={() => setList(list.filter((_, j) => j !== i))} style={{ marginTop: 24 }} />
        </div>); })}
      {(!max || list.length < max) && <div><Button size="sm" variant="outline" icon="plus" onClick={() => setList([...list, { _k: Date.now(), ...blank }])}>{addLabel || t('Add item', 'إضافة عنصر')}</Button></div>}
    </div>
  );
}

/** Collapsible section card for long editors. */
function ACollapse({ title, sub, icon, children, open: o0 = false, count }) {
  const [open, setOpen] = React.useState(o0);
  return (
    <section className="a-card">
      <button type="button" className="a-card__head" onClick={() => setOpen(!open)} aria-expanded={open} style={{ width: '100%', border: 0, background: 'none', cursor: 'pointer', textAlign: 'start', borderBottom: open ? undefined : 0 }}>
        <h3>{icon && <Icon name={icon} size={18} />}{title}{count != null && <Badge tone="neutral">{count}</Badge>}</h3>
        {sub && <span className="a-count" style={{ flex: 1 }}>{sub}</span>}
        <Icon name={open ? 'chevron-up' : 'chevron-down'} size={18} style={{ marginInlineStart: 'auto' }} />
      </button>
      {open && <div className="a-card__body">{children}</div>}
    </section>
  );
}

/** Product picker — searchable select + draggable chosen list. */
function AProductPicker({ initial = [], label }) {
  const { t, tx, price } = useLocale();
  const [list, setList, props] = useReorder(initial);
  const opts = A_PRODUCTS.filter((p) => !list.includes(p.id)).map((p) => ({ value: p.id, label: `${p.sku} — ${tx(p.name)}` }));
  return (
    <div className="d-field">
      {label && <span className="d-label">{label}</span>}
      <Select size="sm" placeholder={t('Add a product by name or SKU…', 'أضف منتجاً بالاسم أو الكود…')} options={opts} value="" onChange={(e) => e.target.value && setList([...list, e.target.value])} aria-label={t('Add product', 'إضافة منتج')} />
      <div style={{ display: 'grid', gap: 8, marginTop: 4 }}>
        {list.length === 0 && <span className="d-help">{t('No products selected.', 'لم يتم اختيار منتجات.')}</span>}
        {list.map((id, i) => { const p = A_PRODUCTS.find((x) => x.id === id); const pr = props(i); return (
          <div key={id} {...pr} className={cx('d-file', pr.className)} style={{ cursor: 'grab' }}>
            <span className="a-grip"><Icon name="grip-vertical" size={16} /></span>
            <ImageFrame src={p.image} style={{ width: 36, height: 36, borderRadius: 6, flex: 'none' }} />
            <span style={{ minWidth: 0, flex: 1 }}><span className="d-file__name" style={{ display: 'block' }}>{tx(p.name)}</span><span className="d-file__meta a-mono">{p.sku} · {p.price ? price(p.price) : t('On request', 'عند الطلب')}</span></span>
            <IconButton icon="x" size="sm" label={t('Remove', 'حذف')} onClick={() => setList(list.filter((x) => x !== id))} />
          </div>); })}
      </div>
    </div>
  );
}

/** Multi-select as toggle chips. */
function AChips({ label, options, initial = [] }) {
  const { tx } = useLocale();
  const [v, setV] = React.useState(initial);
  return (
    <div className="d-field">
      {label && <span className="d-label">{label}</span>}
      <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>{options.map((o) => { const on = v.includes(o.value); return <Chip key={o.value} active={on} aria-pressed={on} onClick={() => setV(on ? v.filter((x) => x !== o.value) : [...v, o.value])}>{on && <Icon name="check" size={14} stroke={2} />}{tx(o.label)}</Chip>; })}</div>
    </div>
  );
}

/** EN + AR slugs. */
function ASlugs({ slug, path = '' }) {
  const { t } = useLocale();
  const v = typeof slug === 'string' ? { en: slug, ar: '' } : slug || { en: '', ar: '' };
  return (
    <AGrid cols={2}>
      <Input label={<span>{t('Slug', 'الرابط')} <span className="d-label__opt">· EN</span></span>} defaultValue={v.en} dir="ltr" help={`dots.eg/en${path}/${v.en}`} />
      <Input label={<span>{t('Slug', 'الرابط')} <span className="d-label__opt">· {t('AR', 'عربي')}</span></span>} defaultValue={v.ar} dir="rtl" help={`dots.eg/ar${path}/${v.ar}`} />
    </AGrid>
  );
}

/** SEO card: slug per language (optional), meta title + description per language, preview. */
function ASeo({ title = aM('', ''), desc = aM('', ''), slug, path = '' }) {
  const { t, tx } = useLocale();
  return (
    <ACard title={t('SEO', 'تحسين محركات البحث')} icon="search">
      {slug != null && <ASlugs slug={slug} path={path} />}
      <ABi label={t('Meta title', 'عنوان الصفحة')} counter={60} en={title.en} ar={title.ar} />
      <ABi label={t('Meta description', 'وصف الصفحة')} counter={160} multiline rows={3} en={desc.en} ar={desc.ar} />
      <div style={{ border: '1px solid var(--border-1)', borderRadius: 'var(--radius-md)', padding: 14, display: 'grid', gap: 4, background: 'var(--ink-50)' }}>
        <span className="a-count">{t('Google preview', 'معاينة جوجل')}</span>
        <b style={{ font: '600 17px/1.3 var(--font-body)', color: 'var(--info-600)' }}>{tx(title) || t('Page title', 'عنوان الصفحة')}</b>
        <span style={{ font: '500 13px/1.5 var(--font-body)', color: 'var(--fg-2)' }}>{tx(desc) || t('Page description', 'وصف الصفحة')}</span>
      </div>
    </ACard>
  );
}

/** Video field — poster preview with Replace / Remove. */
function AVideo({ label, file, poster }) {
  const { t } = useLocale();
  const [f, setF] = React.useState(file || null);
  return (
    <div className="d-field">
      {label && <span className="d-label">{label}</span>}
      {f ? (
        <div className="a-card" style={{ overflow: 'hidden' }}>
          <ImageFrame src={poster} ratio="16 / 7"><span style={{ position: 'absolute', inset: 0, display: 'grid', placeItems: 'center' }}><span style={{ width: 48, height: 48, borderRadius: '50%', background: 'var(--white)', display: 'grid', placeItems: 'center', boxShadow: 'var(--shadow-md)' }}><Icon name="play" size={20} /></span></span></ImageFrame>
          <div style={{ display: 'flex', gap: 6, alignItems: 'center', padding: '6px 8px', borderTop: '1px solid var(--border-1)' }}>
            <span className="a-count" style={{ flex: 1, minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{f} · MP4 · 0:18</span>
            <Button size="sm" variant="ghost" icon="upload">{t('Replace', 'استبدال')}</Button>
            <IconButton icon="trash-2" size="sm" label={t('Remove', 'حذف')} onClick={() => setF(null)} />
          </div>
        </div>
      ) : (
        <button type="button" className="a-drop" style={{ minHeight: 120, width: '100%' }} onClick={() => setF('hero.mp4')}><Icon name="film" size={22} /><b style={{ color: 'var(--fg-1)' }}>{t('Upload a video', 'ارفع فيديو')}</b><span>MP4/WebM · ≤ 20 s · ≤ 12 MB</span></button>
      )}
    </div>
  );
}

const A_ICON_SET = ['truck', 'store', 'zap', 'package', 'shield-check', 'badge-check', 'lightbulb', 'lamp-ceiling', 'lamp', 'house', 'plug-zap', 'cable', 'wrench', 'ruler', 'pencil-ruler', 'award', 'clock', 'headset', 'message-circle', 'map-pin', 'sun', 'trees', 'leaf', 'cpu', 'sparkles', 'hand-coins', 'banknote', 'credit-card', 'smartphone', 'users', 'building-2', 'hammer', 'rotate-ccw'];
/** Icon picker — preview + select from the Lucide set used across Dots. */
function AIconPick({ label, value }) {
  const [v, setV] = React.useState(value || A_ICON_SET[0]);
  return (
    <div className="d-field">
      {label && <span className="d-label">{label}</span>}
      <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
        <span className="a-kpi__ico" style={{ width: 40, height: 40 }}><Icon name={v} size={18} /></span>
        <div style={{ flex: 1, minWidth: 0 }}><Select size="sm" options={A_ICON_SET} value={v} onChange={(e) => setV(e.target.value)} aria-label={typeof label === 'string' ? label : 'icon'} /></div>
      </div>
    </div>
  );
}

/** Button field: label EN/AR + link. */
function ABtn({ label, en, ar, link }) {
  const { t } = useLocale();
  return (
    <div className="a-card" style={{ padding: 12, display: 'grid', gap: 10, background: 'var(--ink-50)' }}>
      <span className="a-count" style={{ display: 'flex', alignItems: 'center', gap: 6 }}><Icon name="mouse-pointer-click" size={14} />{label}</span>
      <ABi label={t('Label', 'النص')} en={en} ar={ar} />
      <Input label={t('Link', 'الرابط')} defaultValue={link} dir="ltr" icon="link" />
    </div>
  );
}

/** Standard list page: tabs, search, filters, sortable table, row actions, pagination, empty state.
 *  filters: [{id, label, options:[{value,label}], test:(row, v)=>bool}] · tabs: [{id,label,test}] · columns may carry sortVal(row). */
function AList({ title, sub, actions, tabs, filters = [], columns, rows, searchText, onRow, emptyIcon, emptyTitle, perPage = 8, crumbs, selectable }) {
  const { t } = useLocale();
  const [tab, setTab] = React.useState(tabs ? tabs[0].id : null);
  const [q, setQ] = React.useState('');
  const [fv, setFv] = React.useState({});
  const [sort, setSort] = React.useState(null);
  const [page, setPage] = React.useState(1);
  const [sel, setSel] = React.useState([]);
  const tabObj = tabs && tabs.find((x) => x.id === tab);
  let list = rows.filter((r) => (!tabObj || !tabObj.test || tabObj.test(r)) && (!q || searchText(r).toLowerCase().includes(q.toLowerCase())) && filters.every((f) => !fv[f.id] || f.test(r, fv[f.id])));
  if (sort) { const c = columns.find((x) => x.id === sort.id); list = [...list].sort((a, b) => { const x = c.sortVal(a), y = c.sortVal(b); return (x > y ? 1 : x < y ? -1 : 0) * sort.dir; }); }
  const pages = Math.max(1, Math.ceil(list.length / perPage));
  const shown = list.slice((page - 1) * perPage, page * perPage);
  const clear = () => { setQ(''); setFv({}); if (tabs) setTab(tabs[0].id); };
  const cols = columns.map((c) => (c.sortVal ? { ...c, sort: true } : c));
  return (
    <div className="a-page">
      <AHead title={title} sub={sub} crumbs={crumbs}>{actions}</AHead>
      <section className="a-card">
        {tabs && <div style={{ padding: '0 18px' }}><Tabs value={tab} onChange={(v) => { setTab(v); setPage(1); }} tabs={tabs.map((x) => ({ id: x.id, label: x.label, count: rows.filter((r) => !x.test || x.test(r)).length }))} /></div>}
        <div className="a-bar">
          <Input className="a-grow" size="sm" icon="search" placeholder={t('Search…', 'بحث…')} value={q} onChange={(e) => { setQ(e.target.value); setPage(1); }} aria-label={t('Search', 'بحث')} />
          {filters.map((f) => <Select key={f.id} size="sm" aria-label={f.label} placeholder={f.label} options={f.options} value={fv[f.id] || ''} onChange={(e) => { setFv({ ...fv, [f.id]: e.target.value }); setPage(1); }} />)}
        </div>
        {selectable && sel.length > 0 && (
          <div className="a-bulk">
            <span style={{ paddingInline: 6 }}>{sel.length} {t('selected', 'محدد')}</span>
            {selectable.map((b) => <Button key={b.label} variant="ghost" icon={b.icon} onClick={() => { b.onClick(sel); setSel([]); }}>{b.label}</Button>)}
            <IconButton icon="x" size="sm" label={t('Clear selection', 'إلغاء التحديد')} onClick={() => setSel([])} style={{ marginInlineStart: 'auto', color: 'var(--white)' }} />
          </div>
        )}
        <div style={{ marginTop: selectable && sel.length ? 12 : 0, borderTop: selectable && sel.length ? '1px solid var(--border-1)' : 0 }}>
          <ATable rows={shown} columns={cols} sort={sort} onSort={setSort} onRow={onRow} selected={selectable ? sel : undefined} onSelect={setSel}
            empty={<AEmpty icon={emptyIcon} title={emptyTitle || t('Nothing matches these filters', 'لا توجد نتائج تطابق الفلاتر')} body={t('Try a shorter search or clear the filters.', 'جرّب بحثاً أقصر أو امسح الفلاتر.')} action={<Button variant="outline" size="sm" onClick={clear}>{t('Clear filters', 'مسح الفلاتر')}</Button>} />} />
        </div>
        {list.length > 0 && <div className="a-card__foot"><Pagination page={page} total={pages} onChange={setPage} count={list.length} perPage={perPage} /></div>}
      </section>
    </div>
  );
}

/** Row actions: edit + overflow menu. */
function ARowActions({ onEdit, items = [] }) {
  const { t } = useLocale();
  return <span style={{ display: 'inline-flex' }} onClick={(e) => e.stopPropagation()}><IconButton icon="pencil" size="sm" label={t('Edit', 'تعديل')} onClick={onEdit} />{items.length > 0 && <AMenu items={items} />}</span>;
}

/** Editor page layout: main column + sticky aside. */
function AEditor({ crumbs, title, extra, actions, aside, children }) {
  return (
    <div className="a-page">
      <AHead crumbs={crumbs} title={title} extra={extra}>{actions}</AHead>
      <div className="a-split" style={{ '--a-aside': '320px' }}>
        <div style={{ display: 'grid', gap: 16, minWidth: 0 }}>{children}</div>
        <div style={{ display: 'grid', gap: 16, position: 'sticky', top: 88 }}>{aside}</div>
      </div>
    </div>
  );
}

;


function ALogin({ app }) {
  const { t, compact } = useLocale();
  const [email, setEmail] = React.useState('mona@dots.eg');
  const [pw, setPw] = React.useState('');
  const [show, setShow] = React.useState(false);
  const [remember, setRemember] = React.useState(true);
  const [err, setErr] = React.useState(false);
  const submit = (e) => { e.preventDefault(); if (pw.length < 6) { setErr(true); return; } setErr(false); app.go('dashboard'); };
  return (
    <div style={{ minHeight: '100%', background: 'var(--bg-subtle)', display: 'grid', placeItems: 'center', padding: compact ? '24px 16px' : 40, position: 'relative' }}>
      <div style={{ position: 'absolute', top: 16, insetInlineEnd: 16 }}><LangSwitch onChange={app.setLang} /></div>
      <div style={{ width: '100%', maxWidth: 420, display: 'grid', gap: 24 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 10, justifyContent: 'center' }}><img src={(window.__resources||{}).logo} alt="Dots" style={{ height: 30 }} /><Badge tone="dark">{t('Admin', 'الإدارة')}</Badge></div>
        <form onSubmit={submit} className="a-card" style={{ padding: compact ? 22 : 32, display: 'grid', gap: 18 }} noValidate>
          <div style={{ display: 'grid', gap: 6 }}>
            <h1 style={{ margin: 0, font: '600 24px/1.25 var(--font-display)', letterSpacing: 'var(--tracking-heading)' }}>{t('Sign in', 'تسجيل الدخول')}</h1>
            <p style={{ margin: 0, font: '500 14px/1.5 var(--font-body)', color: 'var(--fg-3)' }}>{t('Admin access for the Dots team.', 'دخول الإدارة لفريق دوتس.')}</p>
          </div>
          {err && <AAlert tone="error" title={t('Email or password is incorrect', 'البريد أو كلمة المرور غير صحيحة')}>{t('4 attempts left before a 15-minute lock.', 'متبقي 4 محاولات قبل الإيقاف لمدة 15 دقيقة.')}</AAlert>}
          <Input label={t('Email', 'البريد الإلكتروني')} type="email" icon="mail" value={email} onChange={(e) => setEmail(e.target.value)} autoComplete="username" dir="ltr" aria-invalid={err ? 'true' : undefined} />
          <Input label={t('Password', 'كلمة المرور')} type={show ? 'text' : 'password'} icon="lock" value={pw} onChange={(e) => setPw(e.target.value)} autoComplete="current-password" dir="ltr" error={err ? t('Check the password and try again', 'راجع كلمة المرور وحاول مرة أخرى') : undefined}
            trailing={<button type="button" onClick={() => setShow(!show)} aria-label={show ? t('Hide password', 'إخفاء') : t('Show password', 'إظهار')} style={{ pointerEvents: 'auto', border: 0, background: 'none', padding: 0, cursor: 'pointer', color: 'var(--fg-2)', display: 'flex' }}><Icon name={show ? 'eye-off' : 'eye'} size={18} /></button>} />
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 12, flexWrap: 'wrap' }}>
            <Checkbox label={t('Remember me for 30 days', 'تذكرني لمدة 30 يوماً')} checked={remember} onChange={setRemember} />
            <button type="button" className="a-link" onClick={() => app.toast('info', t('Reset link sent', 'تم إرسال رابط إعادة التعيين'), email)}>{t('Forgot password?', 'نسيت كلمة المرور؟')}</button>
          </div>
          <Button type="submit" size="lg" block>{t('Sign in', 'دخول')}</Button>
          <span className="d-help" style={{ textAlign: 'center' }}>{t('Kit demo: any password under 6 characters shows the error state.', 'للتجربة: أي كلمة مرور أقل من 6 أحرف تعرض حالة الخطأ.')}</span>
        </form>
      </div>
    </div>
  );
}

function ADashboard({ app }) {
  const { t, tx, price, compact } = useLocale();
  const kpis = [
    ['shopping-bag', t('Today’s orders', 'طلبات اليوم'), '18', ['a-up', '+4', t('vs yesterday', 'عن أمس')]],
    ['banknote', t('Revenue this month', 'إيراد الشهر'), price(1284600), ['a-up', '+12%', t('vs September', 'عن سبتمبر')]],
    ['message-circle', t('WhatsApp clicks today', 'نقرات واتساب اليوم'), '146', ['a-down', '−8%', t('vs 7-day avg', 'عن متوسط 7 أيام')]],
    ['calendar-check', t('New consultations', 'استشارات جديدة'), '5', [null, '2', t('awaiting first call', 'في انتظار أول مكالمة')]],
  ];
  const low = A_PRODUCTS.filter((p) => p.stockState === 'low' || p.stockState === 'out').slice(0, 5);
  return (
    <div className="a-page">
      <AHead title={t('Good afternoon, Mona', 'مساء الخير يا منى')} sub={t('Wednesday 1 October 2026 · 4 orders need confirmation', 'الأربعاء 1 أكتوبر 2026 · 4 طلبات تحتاج تأكيد')}>
        <Button variant="outline" icon="external-link" onClick={() => app.toast('info', t('Opening store…', 'جارٍ فتح المتجر…'))}>{t('View store', 'عرض المتجر')}</Button>
        <Button icon="plus" onClick={() => app.go('product')}>{t('Add product', 'إضافة منتج')}</Button>
      </AHead>
      <div className="a-kpis">
        {kpis.map(([ic, l, v, d]) => (
          <div key={l} className="a-card a-kpi" style={compact ? { padding: 14 } : undefined}>
            <div className="a-kpi__l">{!compact && <span className="a-kpi__ico"><Icon name={ic} size={18} /></span>}{l}</div>
            <div className="a-kpi__v">{v}</div>
            <div className="a-kpi__d"><b className={d[0] || undefined}>{d[1]}</b>{d[2]}</div>
          </div>
        ))}
      </div>
      <div className="a-split a-split--wide">
        <ACard title={t('Recent orders', 'أحدث الطلبات')} body={false} action={<Button variant="ghost" size="sm" iconEnd="arrow-right" onClick={() => app.go('orders')}>{t('All orders', 'كل الطلبات')}</Button>}>
          <ATable rows={A_ORDERS.slice(0, 7)} onRow={(r) => app.go('order', { id: r.id })} columns={[
            { id: 'id', label: t('Order', 'الطلب'), primary: true, render: (r) => <span><span className="a-mono" style={{ color: 'var(--fg-1)', fontWeight: 500 }}>{r.id}</span><span className="a-sub">{tx(r.date)}</span></span> },
            { id: 'c', label: t('Customer', 'العميل'), render: (r) => tx(r.name) },
            { id: 'tot', label: t('Total', 'الإجمالي'), num: true, render: (r) => <b>{price(r.total)}</b> },
            { id: 'pay', label: t('Payment', 'الدفع'), render: (r) => <APill kind="pay" v={r.pay} /> },
            { id: 'st', label: t('Status', 'الحالة'), render: (r) => <APill kind="order" v={r.status} /> },
          ]} />
        </ACard>
        <div style={{ display: 'grid', gap: 16 }}>
          <ACard title={t('Low stock', 'مخزون منخفض')} body={false} action={<Button variant="ghost" size="sm" iconEnd="arrow-right" onClick={() => app.go('products')}>{t('Products', 'المنتجات')}</Button>}>
            {low.map((p) => (
              <button key={p.id} type="button" onClick={() => app.go('product', { id: p.id })} style={{ display: 'grid', gridTemplateColumns: '40px minmax(0,1fr) auto', gap: 12, alignItems: 'center', width: '100%', padding: '10px 18px', border: 0, borderBottom: '1px solid var(--border-1)', background: 'none', textAlign: 'start', cursor: 'pointer' }}>
                <ImageFrame src={p.image} style={{ width: 40, height: 40, borderRadius: 6, border: '1px solid var(--border-1)' }} />
                <span style={{ minWidth: 0, font: '700 13px/1.3 var(--font-body)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{tx(p.name)}<span className="a-sub a-mono">{p.sku}</span></span>
                <span style={{ textAlign: 'end' }}><b style={{ font: '800 15px var(--font-body)', color: p.qty === 0 ? 'var(--error-600)' : 'var(--warning-600)' }}>{p.qty}</b><span className="a-sub">{t('left', 'متبقي')}</span></span>
              </button>
            ))}
          </ACard>
          <ACard title={t('Latest WhatsApp leads', 'أحدث طلبات واتساب')} body={false} action={<Button variant="ghost" size="sm" iconEnd="arrow-right" onClick={() => app.go('leads')}>{t('All leads', 'الكل')}</Button>}>
            {A_LEADS.slice(0, 4).map((l) => (
              <button key={l.id} type="button" onClick={() => app.go('leads')} style={{ display: 'grid', gridTemplateColumns: '32px minmax(0,1fr) auto', gap: 12, alignItems: 'center', width: '100%', padding: '10px 18px', border: 0, borderBottom: '1px solid var(--border-1)', background: 'none', textAlign: 'start', cursor: 'pointer' }}>
                <span style={{ width: 32, height: 32, borderRadius: '50%', background: 'var(--wa-100)', color: 'var(--wa-600)', display: 'grid', placeItems: 'center' }}><Icon name={A_LEAD_SOURCES[l.source][0]} size={16} /></span>
                <span style={{ minWidth: 0, font: '700 13px/1.3 var(--font-body)' }}>{tx(l.name)} · <span style={{ color: 'var(--fg-3)', fontWeight: 600 }}>{tx(A_LEAD_SOURCES[l.source][1])}</span><span className="a-sub" style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{tx(l.ref)}</span></span>
                <APill kind="lead" v={l.status} />
              </button>
            ))}
          </ACard>
        </div>
      </div>
    </div>
  );
}

;


/* Products list, product edit, categories tree. */
function AProducts({ app }) {
  const { t, tx, price, compact } = useLocale();
  const [tab, setTab] = React.useState('all');
  const [q, setQ] = React.useState('');
  const [cat, setCat] = React.useState('');
  const [brand, setBrand] = React.useState('');
  const [stock, setStock] = React.useState('');
  const [sort, setSort] = React.useState({ id: 'name', dir: 1 });
  const [sel, setSel] = React.useState([]);
  const [page, setPage] = React.useState(1);
  const cats = [...new Set(A_PRODUCTS.map((p) => p.cat))].map((c) => ({ value: c, label: tx(A_PRODUCTS.find((p) => p.cat === c).catName) }));
  const brands = [...new Set(A_PRODUCTS.map((p) => p.brand))];
  let rows = A_PRODUCTS.filter((p) => (tab === 'all' || p.status === tab) && (!cat || p.cat === cat) && (!brand || p.brand === brand) && (!stock || p.stockState === stock) && (!q || (tx(p.name) + p.sku + p.brand).toLowerCase().includes(q.toLowerCase())));
  const key = { name: (p) => tx(p.name), price: (p) => p.price || 0, qty: (p) => p.qty };
  rows = [...rows].sort((a, b) => { const x = key[sort.id](a), y = key[sort.id](b); return (x > y ? 1 : x < y ? -1 : 0) * sort.dir; });
  const cnt = (s) => A_PRODUCTS.filter((p) => s === 'all' || p.status === s).length;
  const active = [cat && { k: t('Category', 'القسم'), v: cats.find((c) => c.value === cat).label, x: () => setCat('') }, brand && { k: t('Brand', 'العلامة'), v: brand, x: () => setBrand('') }, stock && { k: t('Stock', 'المخزون'), v: tx(A_STATUS.stock[stock][1]), x: () => setStock('') }].filter(Boolean);
  const clear = () => { setQ(''); setCat(''); setBrand(''); setStock(''); setTab('all'); };
  const bulk = (label) => { app.toast('success', label, `${sel.length} ${t('products updated', 'منتجات تم تحديثها')}`); setSel([]); };
  return (
    <div className="a-page">
      <AHead title={t('Products', 'المنتجات')} sub={t('10,214 products · 8 brands synced today 06:00', '10,214 منتج · تمت مزامنة 8 علامات اليوم 6:00 ص')}>
        <Button variant="outline" icon="upload" onClick={() => app.toast('info', t('Import CSV', 'استيراد CSV'), t('Columns: sku, name_en, name_ar, price, stock', 'الأعمدة: sku، name_en، name_ar، price، stock'))}>{t('Import CSV', 'استيراد CSV')}</Button>
        <Button icon="plus" onClick={() => app.go('product')}>{t('Add product', 'إضافة منتج')}</Button>
      </AHead>
      <section className="a-card">
        <div style={{ padding: '0 18px' }}><Tabs value={tab} onChange={(v) => { setTab(v); setSel([]); }} tabs={[{ id: 'all', label: t('All', 'الكل'), count: cnt('all') }, { id: 'published', label: t('Published', 'منشور'), count: cnt('published') }, { id: 'draft', label: t('Draft', 'مسودة'), count: cnt('draft') }, { id: 'archived', label: t('Archived', 'مؤرشف'), count: cnt('archived') }]} /></div>
        <div className="a-bar">
          <Input className="a-grow" size="sm" icon="search" placeholder={t('Name, SKU or brand', 'الاسم أو الكود أو العلامة')} value={q} onChange={(e) => setQ(e.target.value)} aria-label={t('Search products', 'بحث المنتجات')} />
          <Select size="sm" aria-label={t('Category', 'القسم')} placeholder={t('All categories', 'كل الأقسام')} options={cats} value={cat} onChange={(e) => setCat(e.target.value)} />
          <Select size="sm" aria-label={t('Brand', 'العلامة')} placeholder={t('All brands', 'كل العلامات')} options={brands} value={brand} onChange={(e) => setBrand(e.target.value)} />
          <Select size="sm" aria-label={t('Stock', 'المخزون')} placeholder={t('Any stock', 'أي مخزون')} options={Object.entries(A_STATUS.stock).map(([v, s]) => ({ value: v, label: tx(s[1]) }))} value={stock} onChange={(e) => setStock(e.target.value)} />
        </div>
        {active.length > 0 && <div className="d-active" style={{ padding: '12px 18px 0' }}>{active.map((a) => <Chip key={a.k} facet={a.k} active onRemove={a.x}>{a.v}</Chip>)}<button type="button" className="d-active__clear" onClick={clear}>{t('Clear all', 'مسح الكل')}</button></div>}
        {sel.length > 0 && (
          <div className="a-bulk">
            <span style={{ paddingInline: 6 }}>{sel.length} {t('selected', 'محدد')}</span>
            <Button variant="ghost" icon="eye" onClick={() => bulk(t('Published', 'تم النشر'))}>{t('Publish', 'نشر')}</Button>
            <Button variant="ghost" icon="eye-off" onClick={() => bulk(t('Hidden', 'تم الإخفاء'))}>{t('Hide', 'إخفاء')}</Button>
            <Button variant="ghost" icon="tag" onClick={() => bulk(t('Badge set', 'تم تعيين الشارة'))}>{t('Set badge', 'تعيين شارة')}</Button>
            <Button variant="ghost" icon="trash-2" onClick={() => bulk(t('Deleted', 'تم الحذف'))}>{t('Delete', 'حذف')}</Button>
            <IconButton icon="x" size="sm" label={t('Clear selection', 'إلغاء التحديد')} onClick={() => setSel([])} style={{ marginInlineStart: 'auto', color: 'var(--white)' }} />
          </div>
        )}
        <div style={{ marginTop: 12, borderTop: '1px solid var(--border-1)' }}>
          <ATable rows={rows} selected={sel} onSelect={setSel} sort={sort} onSort={setSort} onRow={(r) => app.go('product', { id: r.id })}
            empty={<AEmpty title={t('No products match these filters', 'لا توجد منتجات تطابق هذه الفلاتر')} body={t('Try a shorter search or remove a filter. SKUs match from the start, e.g. “PH-ARC”.', 'جرّب بحثاً أقصر أو أزل فلتراً. الأكواد تطابق من البداية، مثل “PH-ARC”.')} action={<Button variant="outline" size="sm" onClick={clear}>{t('Clear filters', 'مسح الفلاتر')}</Button>} />}
            columns={[
              { id: 'name', label: t('Product', 'المنتج'), sort: true, primary: true, render: (p) => (
                <span style={{ display: 'flex', alignItems: 'center', gap: 12, minWidth: compact ? 0 : 260 }}>
                  <ImageFrame src={p.image} style={{ width: 40, height: 40, borderRadius: 6, border: '1px solid var(--border-1)', flex: 'none' }} />
                  <span style={{ minWidth: 0 }}><b style={{ font: '700 13px/1.35 var(--font-body)' }}>{tx(p.name)}</b>{compact && <span className="a-sub a-mono">{p.sku}</span>}</span>
                </span>) },
              { id: 'sku', label: 'SKU', hideMobile: true, render: (p) => <span className="a-mono">{p.sku}</span> },
              { id: 'brand', label: t('Brand', 'العلامة'), render: (p) => p.brand },
              { id: 'cat', label: t('Category', 'القسم'), render: (p) => tx(p.catName) },
              { id: 'price', label: t('Price', 'السعر'), sort: true, num: true, render: (p) => p.price ? <span><b>{price(p.price)}</b>{p.was && <span className="a-sub" style={{ textDecoration: 'line-through' }}>{price(p.was)}</span>}</span> : <span style={{ color: 'var(--fg-3)', fontWeight: 600 }}>{t('On request', 'عند الطلب')}</span> },
              { id: 'qty', label: t('Stock', 'المخزون'), sort: true, render: (p) => <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8 }}><APill kind="stock" v={p.stockState} /><span className="a-count">{p.qty}</span></span> },
              { id: 'status', label: t('Status', 'الحالة'), render: (p) => <APill kind="product" v={p.status} /> },
              { id: 'act', label: '', shrink: true, hideMobile: true, render: (p) => <span style={{ display: 'inline-flex' }} onClick={(e) => e.stopPropagation()}><IconButton icon="pencil" size="sm" label={t('Edit', 'تعديل')} onClick={() => app.go('product', { id: p.id })} /><AMenu items={[{ icon: 'copy', label: t('Duplicate', 'تكرار'), onClick: () => app.toast('success', t('Duplicated as draft', 'تم النسخ كمسودة'), p.sku + '-COPY') }, { icon: 'external-link', label: t('View on store', 'عرض في المتجر') }, '-', { icon: 'trash-2', label: t('Delete', 'حذف'), danger: true, onClick: () => app.toast('success', t('Product deleted', 'تم حذف المنتج'), p.sku) }]} /></span> },
            ]} />
        </div>
        {rows.length > 0 && <div className="a-card__foot"><Pagination page={page} total={852} onChange={setPage} count={10214} perPage={12} /></div>}
      </section>
    </div>
  );
}


;


/* Product edit + Categories. */
const apCode = (code, v) => code === 'finish' ? v.slice(0, 2).toUpperCase() : code === 'cct' ? v.slice(0, 2) : v;

function APVariants({ app }) {
  const { t, tx, compact } = useLocale();
  const pool = A_ATTRIBUTES.filter((a) => a.variants);
  const [por, setPor] = React.useState(false);
  const [axes, setAxes] = React.useState([{ id: 'cct', vals: ['2700', '3000'] }, { id: 'finish', vals: ['black', 'white', 'brass'] }]);
  const [over, setOver] = React.useState({});
  const [def, setDef] = React.useState(null);
  const free = pool.filter((a) => !axes.some((x) => x.id === a.id));
  const attr = (id) => A_ATTRIBUTES.find((a) => a.id === id);
  const combos = axes.filter((x) => x.vals.length).reduce((acc, x) => acc.flatMap((c) => x.vals.map((v) => [...c, [x.id, v]])), [[]]);
  const rows = combos.map((c) => {
    const id = c.map((x) => x[1]).join('/') || 'default';
    const base = { id, c, sku: 'PH-ARC-450' + c.map(([k, v]) => '-' + apCode(attr(k).code, v)).join(''), price: 4850 + (c.some((x) => x[1] === 'brass') ? 650 : 0), was: 5700, qty: 12, low: '', active: true };
    return { ...base, ...over[id] };
  });
  const defId = def && rows.some((r) => r.id === def) ? def : rows[0] && rows[0].id;
  const set = (id, k, v) => setOver({ ...over, [id]: { ...over[id], [k]: v } });
  const togVal = (ai, v) => setAxes(axes.map((x, i) => (i === ai ? { ...x, vals: x.vals.includes(v) ? x.vals.filter((y) => y !== v) : [...x.vals, v] } : x)));
  const num = (r, k, w) => <input className="d-input d-input--sm" type="number" style={{ width: w }} value={r[k]} placeholder={k === 'low' ? t('Default', 'افتراضي') : undefined} disabled={por && (k === 'price' || k === 'was')} onChange={(e) => set(r.id, k, e.target.value === '' ? '' : +e.target.value)} dir="ltr" aria-label={k} />;
  return (
    <>
      <ACard>
        <ASwitch checked={por} onChange={setPor} label={t('Price on request', 'السعر عند الطلب')} help={t('Hides prices on every variant and shows “Request a quote” instead of “Add to cart”.', 'يخفي سعر كل المتغيرات ويعرض "اطلب عرض سعر" بدلاً من "أضف للسلة".')} />
      </ACard>
      <ACard title={t('Attribute axes', 'محاور الخصائص')} action={<Button size="sm" variant="ghost" iconEnd="arrow-right" onClick={() => app.go('attributes')}>{t('Manage attributes', 'إدارة الخصائص')}</Button>}>
        {axes.length === 0 && <span className="d-help">{t('No axes — the product has one default variant.', 'بدون محاور — للمنتج متغير افتراضي واحد.')}</span>}
        {axes.map((x, ai) => { const a = attr(x.id); return (
          <div key={x.id} style={{ display: 'grid', gridTemplateColumns: compact ? 'minmax(0,1fr) auto' : '180px minmax(0,1fr) auto', gap: 10, alignItems: 'center', paddingBottom: 14, borderBottom: '1px solid var(--border-1)' }}>
            <b style={{ font: '800 14px var(--font-body)' }}>{tx(a.name)}<span className="a-sub a-mono">{a.code}</span></b>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8, gridColumn: compact ? '1 / -1' : undefined, gridRow: compact ? 2 : undefined }}>
              {a.values.map((v) => { const on = x.vals.includes(v.v); return <Chip key={v.v} active={on} aria-pressed={on} onClick={() => togVal(ai, v.v)}>{a.type === 'swatch' && <span style={{ width: 14, height: 14, borderRadius: '50%', background: v.swatch, boxShadow: 'inset 0 0 0 1px var(--border-2)' }} />}{tx(v.label)}</Chip>; })}
            </div>
            <IconButton icon="trash-2" size="sm" label={t('Remove axis', 'حذف المحور')} onClick={() => setAxes(axes.filter((_, i) => i !== ai))} />
          </div>); })}
        {free.length > 0 && <div style={{ maxWidth: 320 }}><Select size="sm" placeholder={t('+ Add axis from attributes…', '+ أضف محوراً من الخصائص…')} options={free.map((a) => ({ value: a.id, label: tx(a.name) }))} value="" onChange={(e) => e.target.value && setAxes([...axes, { id: e.target.value, vals: [attr(e.target.value).values[0].v] }])} aria-label={t('Add axis', 'إضافة محور')} /></div>}
      </ACard>
      <ACard title={<>{t('Variants', 'المتغيرات')} <Badge tone="neutral">{rows.length}</Badge></>} body={false} action={rows.length > 1 && !por ? <Button size="sm" variant="outline" onClick={() => setOver(Object.fromEntries(rows.map((r) => [r.id, { ...over[r.id], price: rows[0].price, was: rows[0].was }])))}>{t('Apply first price to all', 'تطبيق السعر الأول على الكل')}</Button> : null}>
        {por && <div style={{ padding: '12px 18px 0' }}><AAlert tone="info" title={t('Prices are hidden on the store', 'الأسعار مخفية في المتجر')}>{t('Quote requests go to WhatsApp leads with source “Product”.', 'طلبات عرض السعر تذهب لطلبات واتساب بمصدر "منتج".')}</AAlert></div>}
        <ATable rows={rows} columns={[
          { id: 'v', label: t('Variant', 'المتغير'), primary: true, render: (r) => r.c.length ? <span style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>{r.c.map(([k, v]) => <Badge key={k} tone="outline">{tx(attr(k).values.find((y) => y.v === v).label)}</Badge>)}</span> : <Badge tone="neutral">{t('Default variant', 'المتغير الافتراضي')}</Badge> },
          { id: 'sku', label: 'SKU', wide: true, render: (r) => <input className="d-input d-input--sm" style={{ fontFamily: 'var(--font-mono)', fontSize: 12, minWidth: 180 }} value={r.sku} onChange={(e) => set(r.id, 'sku', e.target.value)} dir="ltr" aria-label="SKU" /> },
          { id: 'p', label: t('Price', 'السعر'), render: (r) => num(r, 'price', 100) },
          { id: 'w', label: t('Compare-at', 'قبل الخصم'), render: (r) => num(r, 'was', 100) },
          { id: 'q', label: t('Stock qty', 'الكمية'), render: (r) => num(r, 'qty', 80) },
          { id: 'l', label: t('Low-stock at', 'حد التنبيه'), render: (r) => num(r, 'low', 90) },
          { id: 'd', label: t('Default', 'افتراضي'), render: (r) => <input type="radio" name="ap-default" checked={defId === r.id} onChange={() => setDef(r.id)} style={{ width: 18, height: 18, accentColor: 'var(--ink-900)' }} aria-label={t('Default variant', 'المتغير الافتراضي')} /> },
          { id: 'a', label: t('Active', 'مفعل'), render: (r) => <ASwitch checked={r.active} onChange={(v) => set(r.id, 'active', v)} /> },
        ]} />
        <div className="a-card__foot"><span className="d-help">{t('An empty low-stock threshold uses the default from Settings → Commerce.', 'حد التنبيه الفارغ يستخدم القيمة الافتراضية من الإعدادات ← التجارة.')}</span></div>
      </ACard>
    </>
  );
}

const AP_SPECS = [['Luminous flux', 'الفيض الضوئي', '2,850 lm', '2,850 لومن'], ['CRI', 'مؤشر تجسيد اللون', '≥ 90', '≥ 90'], ['Beam angle', 'زاوية الشعاع', '110°', '110°'], ['Power', 'القدرة', '24 W', '24 وات'], ['Dimming', 'التعتيم', 'Phase-cut, to 1%', 'قطع الطور، حتى 1%'], ['Diameter', 'القطر', '450 mm', '450 مم'], ['IP rating', 'درجة الحماية', 'IP20', 'IP20']];
function APSpecs() {
  const { t } = useLocale();
  const [rows, setRows, props] = useReorder(AP_SPECS.map((r, i) => ({ k: i, r })));
  const upd = (i, ci, v) => setRows(rows.map((x, j) => (j === i ? { ...x, r: x.r.map((c, k) => (k === ci ? v : c)) } : x)));
  return (
    <ACard title={t('Specifications', 'المواصفات')} action={<span className="a-count">{t('Drag rows to sort', 'اسحب الصفوف للترتيب')}</span>}>
      <div className="a-scroll">
        <table className="a-table" style={{ minWidth: 680 }}>
          <thead><tr><th className="is-shrink" /><th>{t('Name', 'الاسم')} · EN</th><th>{t('Name', 'الاسم')} · {t('AR', 'عربي')}</th><th>{t('Value', 'القيمة')} · EN</th><th>{t('Value', 'القيمة')} · {t('AR', 'عربي')}</th><th className="is-shrink" /></tr></thead>
          <tbody>{rows.map((x, i) => { const p = props(i); return (
            <tr key={x.k} {...p}>
              <td className="is-shrink"><span className="a-grip"><Icon name="grip-vertical" size={16} /></span></td>
              {x.r.map((c, ci) => <td key={ci} style={{ padding: 6 }}><input className="d-input d-input--sm" value={c} dir={ci % 2 ? 'rtl' : 'ltr'} onChange={(e) => upd(i, ci, e.target.value)} aria-label={['Name EN', 'Name AR', 'Value EN', 'Value AR'][ci]} /></td>)}
              <td className="is-shrink"><IconButton icon="trash-2" size="sm" label={t('Remove row', 'حذف الصف')} onClick={() => setRows(rows.filter((_, j) => j !== i))} /></td>
            </tr>); })}</tbody>
        </table>
      </div>
      <div><Button size="sm" variant="outline" icon="plus" onClick={() => setRows([...rows, { k: Date.now(), r: ['', '', '', ''] }])}>{t('Add row', 'إضافة صف')}</Button></div>
    </ACard>
  );
}

function APImages() {
  const { t, compact } = useLocale();
  const [list, setList, props] = useReorder([33, 8, 28, 14]);
  return (
    <ACard title={t('Images', 'الصور')} action={<span className="a-count">{t('Drag to reorder · first image is the main one', 'اسحب لإعادة الترتيب · الصورة الأولى هي الرئيسية')}</span>}>
      <div style={{ display: 'grid', gridTemplateColumns: compact ? 'minmax(0,1fr)' : 'repeat(auto-fill, minmax(220px, 1fr))', gap: 12 }}>
        {list.map((n, i) => { const p = props(i); return (
          <div key={n} {...p} className={cx('a-card', p.className)} style={{ overflow: 'hidden', position: 'relative', borderColor: i === 0 ? 'var(--ink-900)' : undefined }}>
            <div style={{ cursor: 'grab' }}><ImageFrame src={kPh(n, 500)} ratio="4 / 3" /></div>
            <div style={{ position: 'absolute', top: 8, insetInlineStart: 8 }}>{i === 0 ? <Badge tone="accent">{t('Main', 'رئيسية')}</Badge> : <Badge tone="outline">{i + 1}</Badge>}</div>
            <div style={{ display: 'grid', gap: 6, padding: 8 }}>
              <input className="d-input d-input--sm" placeholder={t('Caption · EN', 'التعليق · EN')} defaultValue={i === 0 ? 'Arc Ø450 in matte black' : ''} dir="ltr" aria-label="Caption EN" />
              <input className="d-input d-input--sm" placeholder={t('Caption · AR', 'التعليق · عربي')} defaultValue={i === 0 ? 'آرك Ø450 أسود مطفي' : ''} dir="rtl" aria-label="Caption AR" />
              <div style={{ display: 'flex', alignItems: 'center', gap: 4 }}><span className="a-grip"><Icon name="grip-vertical" size={16} /></span><span className="a-count" style={{ flex: 1 }}>arc-450-{n}.webp</span><Button size="sm" variant="ghost">{t('Replace', 'استبدال')}</Button><IconButton icon="trash-2" size="sm" label={t('Remove', 'حذف')} onClick={() => setList(list.filter((_, j) => j !== i))} /></div>
            </div>
          </div>); })}
        <button type="button" className="a-drop" style={{ minHeight: 200 }} onClick={() => setList([...list, Math.floor(Math.random() * 39)])}><Icon name="image-plus" size={24} /><b style={{ color: 'var(--fg-1)' }}>{t('Drop images', 'أفلت الصور هنا')}</b><span>{t('or browse · JPG, PNG, WebP · 5 MB', 'أو تصفح · JPG، PNG، WebP · 5 ميجا')}</span></button>
      </div>
    </ACard>
  );
}

function APFileRow({ ext, name, f, onUp, onRm }) {
  const { t } = useLocale();
  return (
    <div className="d-file" style={f ? undefined : { borderStyle: 'dashed', background: 'var(--ink-50)' }}>
      <span className="d-file__ico"><Icon name="file-text" size={20} /><span className="d-file__ext">{ext}</span></span>
      <span style={{ minWidth: 0, flex: 1 }}><span className="d-file__name" style={{ display: 'block' }}>{name}</span><span className="d-file__meta">{f || t('Not uploaded', 'لم يتم الرفع')}</span></span>
      {f ? <span style={{ display: 'flex', gap: 4 }}><Button size="sm" variant="ghost" onClick={onUp}>{t('Replace', 'استبدال')}</Button><IconButton icon="trash-2" size="sm" label={t('Remove', 'حذف')} onClick={onRm} /></span> : <Button size="sm" variant="outline" icon="upload" onClick={onUp}>{t('Upload', 'رفع')}</Button>}
    </div>
  );
}

function APFiles() {
  const { t } = useLocale();
  const [slots, setSlots] = React.useState([['datasheet', t('Datasheet', 'الداتا شيت'), 'PDF', 'PH-ARC-450-datasheet.pdf · 1.2 MB'], ['ies', 'IES', 'IES', 'PH-ARC-450-30.ies · 18 KB'], ['ldt', 'LDT', 'LDT', null], ['guide', t('Installation guide', 'دليل التركيب'), 'PDF', null]]);
  const [extra, setExtra] = React.useState([{ k: 1, en: 'Dimmer compatibility list', ar: 'قائمة الديمرات المتوافقة', file: 'arc-dimmers.pdf · 220 KB' }]);
  const setSlot = (i, f) => setSlots(slots.map((s, j) => (j === i ? [s[0], s[1], s[2], f] : s)));
  return (
    <ACard title={t('Files', 'الملفات')} action={<span className="a-count">{t('Shown under “Downloads” on the product page', 'تظهر تحت "التحميلات" في صفحة المنتج')}</span>}>
      <div className="d-files">{slots.map(([k, l, ext, f], i) => <APFileRow key={k} ext={ext} name={l} f={f} onUp={() => setSlot(i, `${k}.${ext.toLowerCase()} · 640 KB`)} onRm={() => setSlot(i, null)} />)}</div>
      {extra.map((x, i) => (
        <div key={x.k} className="a-card" style={{ padding: 12, display: 'grid', gap: 10, background: 'var(--ink-50)' }}>
          <ABi label={t('File title', 'عنوان الملف')} en={x.en} ar={x.ar} />
          <APFileRow ext="PDF" name={x.en || t('Untitled file', 'ملف بدون عنوان')} f={x.file} onUp={() => setExtra(extra.map((y, j) => (j === i ? { ...y, file: 'file.pdf · 300 KB' } : y)))} onRm={() => setExtra(extra.filter((_, j) => j !== i))} />
        </div>
      ))}
      <div><Button size="sm" variant="outline" icon="plus" onClick={() => setExtra([...extra, { k: Date.now(), en: '', ar: '', file: null }])}>{t('Add another file', 'إضافة ملف آخر')}</Button></div>
    </ACard>
  );
}

function AProductEdit({ app, route }) {
  const { t, tx, compact } = useLocale();
  const isNew = !route.id;
  const p = A_PRODUCTS.find((x) => x.id === route.id) || A_PRODUCTS[0];
  const [tab, setTab] = React.useState('basic');
  const [status, setStatus] = React.useState(isNew ? 'draft' : p.status);
  const [flags, setFlags] = React.useState({ new: false, best: true, smart: false, dim: true, fast: true });
  const leaves = A_CATS.flatMap((m) => m.children.flatMap((g) => g.children.map((l) => ({ value: l.id, label: tx(m.name) + ' › ' + tx(g.name) + ' › ' + tx(l.name) }))));
  const tabs = [['basic', t('Basic info', 'البيانات الأساسية')], ['cat', t('Category & brand', 'القسم والعلامة')], ['var', t('Variants', 'المتغيرات')], ['spec', t('Specifications', 'المواصفات')], ['img', t('Images', 'الصور')], ['files', t('Files', 'الملفات')], ['rel', t('Related', 'ذات صلة')], ['flags', t('Badges & flags', 'الشارات')], ['seo', 'SEO']];
  const save = () => app.toast('success', t('Product saved', 'تم حفظ المنتج'), isNew ? t('Saved as draft', 'تم الحفظ كمسودة') : p.sku);
  const opt = <span className="d-label__opt">· {t('optional', 'اختياري')}</span>;
  return (
    <div className="a-page">
      <AHead crumbs={[{ label: t('Products', 'المنتجات'), onClick: () => app.go('products') }, { label: isNew ? t('New product', 'منتج جديد') : p.sku }]} title={isNew ? t('New product', 'منتج جديد') : tx(p.name)} extra={<APill kind="product" v={status} />}>
        <Button variant="outline" icon="eye" onClick={() => app.toast('info', t('Preview opened in a new tab', 'تم فتح المعاينة'))}>{t('Preview', 'معاينة')}</Button>
        <Button icon="check" onClick={save}>{t('Save changes', 'حفظ التغييرات')}</Button>
      </AHead>
      <div className="a-split" style={{ '--a-aside': '300px' }}>
        <div style={{ display: 'grid', gap: 16, minWidth: 0 }}>
          <div className="a-card" style={{ padding: '0 18px' }}><Tabs value={tab} onChange={setTab} tabs={tabs.map(([id, label]) => ({ id, label }))} /></div>
          {tab === 'basic' && <ACard title={t('Basic info', 'البيانات الأساسية')}>
            <ABi label={t('Product name', 'اسم المنتج')} en={isNew ? '' : p.name.en} ar={isNew ? '' : p.name.ar} />
            <ABi label={t('Overview', 'نظرة عامة')} multiline rows={5} en={isNew ? '' : 'Spun-aluminium dome with an opal diffuser for glare-free light. Integrated 24W LED, CRI ≥ 90, dims smoothly to 1% on phase-cut dimmers.\n\nSupplied with 1.5 m adjustable cable and ceiling canopy.'} ar={isNew ? '' : 'قبة ألومنيوم مع ناشر أوبال لإضاءة بدون انبهار. ليد مدمج 24 وات، CRI ≥ 90، تعتيم سلس حتى 1% مع ديمرات قطع الطور.\n\nمع كابل قابل للضبط 1.5 م وقاعدة سقف.'} />
            <ABi label={<span>{t('Variants summary', 'ملخص المتغيرات')} {opt}</span>} en={isNew ? '' : '3 finishes · 2 CCT'} ar={isNew ? '' : '3 تشطيبات · درجتان للون'} />
            <ABi label={t('Warranty', 'الضمان')} en={isNew ? '' : '3 years'} ar={isNew ? '' : '3 سنوات'} />
            <div style={{ maxWidth: compact ? undefined : 'calc(50% - 8px)' }}><Input label={t('Base SKU', 'الكود الأساسي')} defaultValue={isNew ? '' : p.sku} dir="ltr" /></div>
          </ACard>}
          {tab === 'cat' && <ACard title={t('Category & brand', 'القسم والعلامة')}>
            <AGrid cols={2}>
              <Select label={t('Brand', 'العلامة')} options={A_BRANDS.map((b) => ({ value: b.id, label: b.name }))} defaultValue="philips" />
              <Select label={t('Category', 'القسم')} options={leaves} defaultValue="c111" help={t('Leaf categories only', 'الأقسام الفرعية الأخيرة فقط')} />
            </AGrid>
          </ACard>}
          {tab === 'var' && <APVariants app={app} />}
          {tab === 'spec' && <APSpecs />}
          {tab === 'img' && <APImages />}
          {tab === 'files' && <APFiles />}
          {tab === 'rel' && <>
            <ACard title={t('Often specified together', 'غالباً ما تُطلب معاً')} action={<span className="a-count">{t('Product page row', 'صف في صفحة المنتج')}</span>}><AProductPicker initial={['p2', 'p3']} /></ACard>
            <ACard title={t('Cart suggestions — “Don’t forget”', 'اقتراحات السلة — "لا تنسَ"')} action={<span className="a-count">{t('Shown in the cart', 'تظهر في السلة')}</span>}><AProductPicker initial={['p10']} /></ACard>
          </>}
          {tab === 'flags' && <ACard title={t('Badges & flags', 'الشارات والعلامات')}>
            {[['new', t('New arrival', 'وصل حديثاً'), t('“New” badge; listed in New arrivals', 'شارة "جديد"؛ يظهر في وصل حديثاً')], ['best', t('Best seller', 'الأكثر مبيعاً'), t('Listed in Best sellers', 'يظهر في الأكثر مبيعاً')], ['smart', t('Smart home pick', 'اختيار المنزل الذكي'), t('Listed in the Smart home tab', 'يظهر في تبويب المنزل الذكي')], ['dim', t('Dimmable', 'قابل للتعتيم'), t('Dimmable badge and filter', 'شارة وفلتر قابل للتعتيم')], ['fast', t('Ships in 24h (Cairo & Giza)', 'يُشحن خلال 24 ساعة (القاهرة والجيزة)'), t('Fast-delivery badge on cards', 'شارة التوصيل السريع على البطاقة')]].map(([k, l, h]) => (
              <div key={k} style={{ paddingBottom: 14, borderBottom: '1px solid var(--border-1)' }}><ASwitch checked={flags[k]} onChange={(v) => setFlags({ ...flags, [k]: v })} label={l} help={h} /></div>
            ))}
            <div style={{ maxWidth: 260 }}><Input label={t('Recommended sort weight', 'وزن الترتيب المقترح')} type="number" defaultValue="50" dir="ltr" help={t('Higher shows first in “Recommended”', 'الأعلى يظهر أولاً في "المقترح"')} /></div>
          </ACard>}
          {tab === 'seo' && <ASeo slug={{ en: 'arc-led-pendant-450', ar: 'معلقة-ليد-آرك-450' }} path="/p" title={aM('Arc LED Pendant Ø450, dimmable | Philips | Dots', 'معلقة ليد آرك Ø450 قابلة للتعتيم | فيليبس | دوتس')} desc={aM('Dimmable 24W LED dome pendant, CRI 90, in matte black, white or brass. Official Philips warranty.', 'معلقة قبة ليد 24 وات قابلة للتعتيم، CRI 90، أسود أو أبيض أو نحاسي. ضمان فيليبس الرسمي.')} />}
        </div>
        <div style={{ display: 'grid', gap: 16, position: compact ? 'static' : 'sticky', top: 88 }}>
          <ACard title={t('Visibility', 'الظهور')}>
            <Select label={t('Status', 'الحالة')} value={status} onChange={(e) => setStatus(e.target.value)} options={Object.entries(A_STATUS.product).map(([v, s]) => ({ value: v, label: tx(s[1]) }))} />
            <ImageFrame src={p.image} ratio="1 / 1" style={{ borderRadius: 'var(--radius-md)', border: '1px solid var(--border-1)' }} />
            <div style={{ display: 'grid', gap: 6, font: '600 12px/1.4 var(--font-body)', color: 'var(--fg-3)' }}>
              <span>{t('Created', 'تاريخ الإنشاء')} · 12 Mar 2026</span>
              <span>{t('Last edited by', 'آخر تعديل بواسطة')} Hossam · {tx(p.updated)}</span>
            </div>
          </ACard>
          {!isNew && <Button variant="ghost" icon="trash-2" style={{ color: 'var(--error-600)', justifySelf: 'start' }} onClick={() => app.toast('success', t('Product deleted', 'تم حذف المنتج'), p.sku)}>{t('Delete product', 'حذف المنتج')}</Button>}
        </div>
      </div>
    </div>
  );
}

function acMove(list, pid, from, to) {
  if (pid == null) { const n = [...list]; const [m] = n.splice(from, 1); n.splice(to, 0, m); return n; }
  return list.map((c) => c.id === pid ? { ...c, children: acMove(c.children, null, from, to) } : c.children ? { ...c, children: acMove(c.children, pid, from, to) } : c);
}
function acFind(list, id, parent = null, level = 1) { for (const c of list) { if (c.id === id) return { c, parent, level }; if (c.children) { const r = acFind(c.children, id, c, level + 1); if (r) return r; } } return null; }
const AC_ICONS = ['lamp-ceiling', 'lamp', 'lightbulb', 'trees', 'house', 'plug-zap', 'cable', 'sun', 'shield-check', 'cpu', 'fan', 'zap'];

function ACPanel({ found, tree, setTree, app }) {
  const { t, tx } = useLocale();
  const c = found.c;
  const sub = (title) => <div className="a-side__group" style={{ padding: '12px 0 0', borderTop: '1px solid var(--border-1)' }}>{title}</div>;
  return (
    <div style={{ display: 'grid', gap: 16 }}>
      <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}><Badge tone="neutral">{[t('Main category', 'قسم رئيسي'), t('Group', 'مجموعة'), t('Leaf', 'قسم فرعي')][found.level - 1]}</Badge>{found.parent && <Badge tone="outline">{t('in', 'ضمن')} {tx(found.parent.name)}</Badge>}</div>
      <ABi label={t('Name', 'الاسم')} en={c.name.en} ar={c.name.ar} />
      <ASlugs slug={{ en: c.name.en.toLowerCase().replace(/[^a-z0-9]+/g, '-'), ar: c.name.ar.replace(/\s+/g, '-') }} path="/c" />
      {found.level > 1 && <Select label={t('Parent', 'القسم الأب')} options={(found.level === 2 ? tree : tree.flatMap((x) => x.children)).map((x) => ({ value: x.id, label: tx(x.name) }))} defaultValue={found.parent.id} />}
      {found.level === 1 && <div className="d-field"><span className="d-label">{t('Icon', 'الأيقونة')}</span><div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>{AC_ICONS.map((ic) => <button key={ic} type="button" className="d-opt" aria-pressed={c.icon === ic} style={{ minWidth: 44, height: 44, padding: 0 }} onClick={() => setTree(tree.map((x) => (x.id === c.id ? { ...x, icon: ic } : x)))} aria-label={ic}><Icon name={ic} size={20} /></button>)}</div></div>}
      <ABi label={t('Description', 'الوصف')} multiline rows={3} en={c.name.en + ' from Philips, Delta Light, Flos and more — specified by Dots engineers.'} ar={c.name.ar + ' من فيليبس ودلتا لايت وفلوس وغيرها — باختيار مهندسي دوتس.'} />
      <AImage label={t('Card image', 'صورة البطاقة')} src={kPh(found.level === 1 ? 0 : 13, 600)} ratio="4 / 3" help="card-4x3.webp" />
      <AImage label={t('Hero image', 'صورة الواجهة')} src={kPh(1, 1000)} ratio="16 / 6" help="hero-2400x900.webp" />
      {sub(t('Buying guide', 'دليل الشراء'))}
      <ABi label={t('Buying guide title', 'عنوان دليل الشراء')} en={'How to choose ' + c.name.en.toLowerCase()} ar={'كيف تختار ' + c.name.ar} />
      <ARepeat initial={A_FAQ.slice(0, 2)} blank={{ q: aM('', ''), a: aM('', '') }} addLabel={t('Add question', 'إضافة سؤال')} render={(it) => <><ABi label={t('Question', 'السؤال')} en={it.q.en} ar={it.q.ar} /><ABi label={t('Answer', 'الإجابة')} multiline rows={2} en={it.a.en} ar={it.a.ar} /></>} />
      {sub(t('Top picks', 'أفضل الاختيارات'))}
      <AProductPicker initial={['p1', 'p4']} />
      {sub(t('Visibility', 'الظهور'))}
      <ASwitch checked={c.visible} label={t('Active', 'مفعل')} help={t('Every active category shows in the mega-menu', 'كل قسم مفعل يظهر في القائمة الرئيسية')} onChange={() => {}} />
      <ASwitch checked={found.level === 1} label={t('Show on homepage', 'يظهر في الرئيسية')} onChange={() => {}} />
      <ASwitch checked={found.level === 1} label={t('Show in footer', 'يظهر في الفوتر')} onChange={() => {}} />
      <ASeo title={aM(c.name.en + ' | Dots Egypt', c.name.ar + ' | دوتس مصر')} desc={aM('Shop ' + c.name.en.toLowerCase() + ' with official warranty and delivery across Egypt.', 'تسوق ' + c.name.ar + ' بضمان رسمي وتوصيل لكل مصر.')} />
      <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}><Button icon="check" onClick={() => app.toast('success', t('Category saved', 'تم حفظ القسم'), tx(c.name))}>{t('Save', 'حفظ')}</Button><Button variant="ghost" icon="trash-2" style={{ color: 'var(--error-600)' }}>{t('Delete', 'حذف')}</Button></div>
      <span className="d-help">{c.count.toLocaleString('en-US')} {t('products in this category. Deleting moves them to the parent.', 'منتج في هذا القسم. الحذف ينقلها للقسم الأب.')}</span>
    </div>
  );
}

function ACategories({ app }) {
  const { t, tx, compact } = useLocale();
  const [tree, setTree] = React.useState(A_CATS);
  const [open, setOpen] = React.useState(new Set(['c1', 'c11']));
  const [sel, setSel] = React.useState(compact ? null : 'c1');
  const [drag, setDrag] = React.useState(null);
  const [over, setOver] = React.useState(null);
  const found = sel && acFind(tree, sel);
  const tog = (id) => { const n = new Set(open); n.has(id) ? n.delete(id) : n.add(id); setOpen(n); };
  const rows = [];
  const walk = (list, pid, level) => list.forEach((c, i) => { rows.push({ c, pid, level, i }); if (c.children && open.has(c.id)) walk(c.children, c.id, level + 1); });
  walk(tree, null, 1);
  const dnd = (r) => ({
    draggable: true,
    onDragStart: (e) => { setDrag(r); try { e.dataTransfer.setData('text/plain', r.c.id); } catch (x) {} },
    onDragOver: (e) => { if (drag && drag.pid === r.pid) { e.preventDefault(); setOver(r.c.id); } },
    onDrop: (e) => { e.preventDefault(); if (drag && drag.pid === r.pid && drag.i !== r.i) setTree(acMove(tree, r.pid, drag.i, r.i)); setDrag(null); setOver(null); },
    onDragEnd: () => { setDrag(null); setOver(null); },
  });
  const panel = found && <ACPanel key={found.c.id} found={found} tree={tree} setTree={setTree} app={app} />;
  return (
    <div className="a-page">
      <AHead title={t('Categories', 'الأقسام')} sub={t('3 levels · main category → group → leaf · drag rows to reorder within a level', '3 مستويات · قسم رئيسي ← مجموعة ← فرعي · اسحب لإعادة الترتيب داخل نفس المستوى')}>
        <Button variant="outline" onClick={() => setOpen(new Set(tree.flatMap((c) => [c.id, ...c.children.map((g) => g.id)])))}>{t('Expand all', 'فتح الكل')}</Button>
        <Button icon="plus">{t('Add main category', 'إضافة قسم رئيسي')}</Button>
      </AHead>
      <div className="a-split" style={{ '--a-aside': '520px' }}>
        <section className="a-card" style={{ overflow: 'hidden' }}>
          <div className="a-tree">{rows.map((r) => (
            <div key={r.c.id} {...dnd(r)} className={cx('a-tree__row', r.level > 1 && 'is-l' + r.level, sel === r.c.id && 'is-sel', drag && drag.c.id === r.c.id && 'a-dragging', over === r.c.id && 'a-over')} onClick={() => setSel(r.c.id)}>
              <span className="a-grip"><Icon name="grip-vertical" size={16} /></span>
              {r.c.children ? <button type="button" className="a-tree__tog" onClick={(e) => { e.stopPropagation(); tog(r.c.id); }} aria-expanded={open.has(r.c.id)} aria-label={t('Toggle', 'فتح/إغلاق')}><Icon name={open.has(r.c.id) ? 'chevron-down' : 'chevron-right'} size={16} /></button> : <span style={{ width: 24 }} />}
              {r.level === 1 && <span className="a-kpi__ico" style={{ width: 30, height: 30 }}><Icon name={r.c.icon} size={16} /></span>}
              <span style={{ flex: 1, minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{tx(r.c.name)}</span>
              {!r.c.visible && <Badge tone="outline">{t('Inactive', 'غير مفعل')}</Badge>}
              <span className="a-count">{r.c.count.toLocaleString('en-US')}</span>
              {r.level < 3 && !compact && <IconButton icon="plus" size="sm" label={t('Add child', 'إضافة فرعي')} onClick={(e) => { e.stopPropagation(); app.toast('info', t('New sub-category', 'قسم فرعي جديد'), tx(r.c.name)); }} />}
            </div>
          ))}</div>
        </section>
        {compact
          ? <ADrawer open={!!found} onClose={() => setSel(null)} title={found ? tx(found.c.name) : ''}>{panel}</ADrawer>
          : <ACard title={found ? tx(found.c.name) : t('Select a category', 'اختر قسماً')} icon="pencil">{panel || <span className="d-help">{t('Click a row to edit it.', 'اضغط على صف لتعديله.')}</span>}</ACard>}
      </div>
    </div>
  );
}

;


/* Brands + Attributes (list + edit). */
function ABrands({ app }) {
  const { t, tx } = useLocale();
  const [rows, setRows] = React.useState(A_BRANDS);
  const origins = [...new Set(A_BRANDS.map((b) => b.origin.en))].map((o) => ({ value: o, label: tx(A_BRANDS.find((b) => b.origin.en === o).origin) }));
  const feature = (id) => { setRows(rows.map((x) => ({ ...x, featured: x.id === id }))); app.toast('success', t('Featured brand changed', 'تم تغيير العلامة المميزة'), rows.find((x) => x.id === id).name); };
  return (
    <AList title={t('Brands', 'العلامات التجارية')} sub={t('One featured brand at a time · shown on /brands, product pages, About and the homepage', 'علامة مميزة واحدة فقط · تظهر في صفحة العلامات وصفحات المنتجات و"من نحن" والرئيسية')}
      actions={<Button icon="plus" onClick={() => app.go('brand')}>{t('Add brand', 'إضافة علامة')}</Button>}
      rows={rows} searchText={(b) => b.name + b.origin.en + b.origin.ar} onRow={(b) => app.go('brand', { id: b.id })} emptyIcon="award"
      filters={[
        { id: 'o', label: t('All origins', 'كل الدول'), options: origins, test: (b, v) => b.origin.en === v },
        { id: 's', label: t('Any status', 'كل الحالات'), options: [{ value: 'on', label: t('Active', 'مفعل') }, { value: 'off', label: t('Inactive', 'غير مفعل') }], test: (b, v) => b.active === (v === 'on') },
      ]}
      columns={[
        { id: 'name', label: t('Brand', 'العلامة'), primary: true, sortVal: (b) => b.name, render: (b) => <span style={{ display: 'flex', alignItems: 'center', gap: 12 }}><ImageFrame src={b.logo} style={{ width: 40, height: 40, borderRadius: 6, border: '1px solid var(--border-1)', flex: 'none' }} /><span><b style={{ fontWeight: 700 }}>{b.name}</b>{b.authorised && <span className="a-sub">{t('Authorised distributor', 'موزع معتمد')}</span>}</span></span> },
        { id: 'o', label: t('Origin', 'بلد المنشأ'), sortVal: (b) => tx(b.origin), render: (b) => tx(b.origin) },
        { id: 'c', label: t('Products', 'المنتجات'), num: true, sortVal: (b) => b.count, render: (b) => b.count.toLocaleString('en-US') },
        { id: 'f', label: t('Featured', 'مميزة'), render: (b) => <span onClick={(e) => e.stopPropagation()} style={{ display: 'inline-flex' }}><input type="radio" name="a-feat-brand" checked={b.featured} onChange={() => feature(b.id)} style={{ width: 18, height: 18, accentColor: 'var(--ink-900)' }} aria-label={t('Featured', 'مميزة')} /></span> },
        { id: 'a', label: t('Active', 'مفعل'), render: (b) => <ASwitch checked={b.active} onChange={(v) => setRows(rows.map((x) => (x.id === b.id ? { ...x, active: v } : x)))} /> },
        { id: 'x', label: '', shrink: true, hideMobile: true, render: (b) => <ARowActions onEdit={() => app.go('brand', { id: b.id })} items={[{ icon: 'external-link', label: t('View on store', 'عرض في المتجر') }, { icon: 'package', label: t('View products', 'عرض المنتجات'), onClick: () => app.go('products') }, '-', { icon: 'trash-2', label: t('Delete', 'حذف'), danger: true, onClick: () => { setRows(rows.filter((x) => x.id !== b.id)); app.toast('success', t('Brand deleted', 'تم حذف العلامة'), b.name); } }]} /> },
      ]} />
  );
}

function ABrandEdit({ app, route }) {
  const { t, tx } = useLocale();
  const isNew = !route.id;
  const b = A_BRANDS.find((x) => x.id === route.id) || A_BRANDS[0];
  const cur = A_BRANDS.find((x) => x.featured);
  const [sw, setSw] = React.useState(isNew ? { featured: false, authorised: false, about: false, home: false, active: true } : { featured: b.featured, authorised: b.authorised, about: b.about, home: b.home, active: b.active });
  const s = (k) => (v) => setSw({ ...sw, [k]: v });
  return (
    <AEditor crumbs={[{ label: t('Brands', 'العلامات التجارية'), onClick: () => app.go('brands') }, { label: isNew ? t('New brand', 'علامة جديدة') : b.name }]}
      title={isNew ? t('New brand', 'علامة جديدة') : b.name} extra={<APill kind="active" v={sw.active ? 'on' : 'off'} />}
      actions={<><Button variant="outline" icon="eye">{t('Preview', 'معاينة')}</Button><Button icon="check" onClick={() => app.toast('success', t('Brand saved', 'تم حفظ العلامة'), isNew ? '' : b.name)}>{t('Save changes', 'حفظ التغييرات')}</Button></>}
      aside={<>
        <ACard title={t('Visibility', 'الظهور')}>
          <ASwitch checked={sw.active} onChange={s('active')} label={t('Active', 'مفعل')} />
          <ASwitch checked={sw.featured} onChange={s('featured')} label={t('Featured', 'مميزة')} help={sw.featured && cur && cur.id !== b.id ? t('Replaces ' + cur.name + ' — only one brand can be featured', 'ستحل محل ' + cur.name + ' — علامة مميزة واحدة فقط') : t('Only one brand can be featured', 'علامة مميزة واحدة فقط')} />
          <ASwitch checked={sw.authorised} onChange={s('authorised')} label={t('Authorised distributor badge', 'شارة الموزع المعتمد')} />
          <ASwitch checked={sw.about} onChange={s('about')} label={t('Show in About partners', 'يظهر في شركاء "من نحن"')} />
          <ASwitch checked={sw.home} onChange={s('home')} label={t('Show on homepage', 'يظهر في الرئيسية')} />
          <Input label={t('Sort order', 'الترتيب')} type="number" defaultValue={isNew ? A_BRANDS.length + 1 : b.sort} dir="ltr" />
        </ACard>
        <ACard title={t('Logo', 'الشعار')}><AImage src={isNew ? null : b.logo} ratio="3 / 2" contain help={b.id + '-logo.svg'} /></ACard>
      </>}>
      <ACard title={t('Basic info', 'البيانات الأساسية')}>
        <Input label={t('Name', 'الاسم')} defaultValue={isNew ? '' : b.name} />
        <ASlugs slug={isNew ? { en: '', ar: '' } : { en: b.id, ar: b.id }} path="/brands" />
        <ABi label={t('Origin country', 'بلد المنشأ')} en={isNew ? '' : b.origin.en} ar={isNew ? '' : b.origin.ar} />
        <ABi label={t('Description', 'الوصف')} multiline rows={4} en={isNew ? '' : b.name + ' is represented by Dots as an authorised partner in Egypt — every product ships with the official local warranty.'} ar={isNew ? '' : 'تمثل دوتس ' + b.name + ' كشريك معتمد في مصر — كل منتج بضمان محلي رسمي.'} />
        <ABi label={t('Feature text', 'نص الإبراز')} multiline rows={2} en={isNew ? '' : 'Over ' + b.count.toLocaleString('en-US') + ' products in stock, with datasheets and IES files for every fitting.'} ar={isNew ? '' : 'أكثر من ' + b.count.toLocaleString('en-US') + ' منتج متوفر، مع الداتا شيت وملفات IES لكل وحدة.'} />
      </ACard>
      <ACard title={t('Media', 'الوسائط')}>
        <AImage label={t('Cover image', 'صورة الغلاف')} src={isNew ? null : b.cover} ratio="16 / 6" help={b.id + '-cover.jpg · 2400×900'} />
        <AImage label={t('Feature image', 'صورة الإبراز')} src={isNew ? null : kPh(13, 900)} ratio="4 / 3" help={b.id + '-feature.jpg'} />
      </ACard>
      <ACard title={t('Catalogue', 'الكتالوج')}>
        <AFile label={t('Catalogue PDF', 'كتالوج PDF')} file={isNew ? null : b.catalogue} />
        <ABi label={t('Catalogue button label', 'نص زر الكتالوج')} en={isNew ? '' : 'Download the 2026 catalogue'} ar={isNew ? '' : 'حمّل كتالوج 2026'} />
      </ACard>
      <ASeo title={isNew ? undefined : aM(b.name + ' lighting & electrical in Egypt | Dots', b.name + ' للإضاءة والكهرباء في مصر | دوتس')} desc={isNew ? undefined : aM('Genuine ' + b.name + ' products with official warranty and delivery across Egypt.', 'منتجات ' + b.name + ' أصلية بضمان رسمي وتوصيل لكل مصر.')} />
    </AEditor>
  );
}

const AT_TYPES = { chip: ['tag', aM('Chip', 'شريحة')], swatch: ['palette', aM('Swatch', 'لون')], button: ['rectangle-horizontal', aM('Button', 'زر')] };

function AAttributes({ app }) {
  const { t, tx } = useLocale();
  const [rows, setRows] = React.useState(A_ATTRIBUTES);
  const set = (id, k, v) => setRows(rows.map((x) => (x.id === id ? { ...x, [k]: v } : x)));
  return (
    <AList title={t('Attributes', 'الخصائص')} sub={t('Global attributes used for variant axes and catalogue filters', 'خصائص عامة تُستخدم لمحاور المتغيرات وفلاتر الكتالوج')}
      actions={<Button icon="plus" onClick={() => app.go('attribute')}>{t('Add attribute', 'إضافة خاصية')}</Button>}
      rows={rows} searchText={(a) => a.code + a.name.en + a.name.ar} onRow={(a) => app.go('attribute', { id: a.id })} emptyIcon="sliders-horizontal"
      filters={[
        { id: 'ty', label: t('Any display type', 'كل أنواع العرض'), options: Object.entries(AT_TYPES).map(([v, x]) => ({ value: v, label: tx(x[1]) })), test: (a, v) => a.type === v },
        { id: 'va', label: t('Used for: any', 'الاستخدام: الكل'), options: [{ value: 'y', label: t('Variants', 'المتغيرات') }, { value: 'n', label: t('Filters only', 'الفلاتر فقط') }], test: (a, v) => a.variants === (v === 'y') },
      ]}
      columns={[
        { id: 'n', label: t('Attribute', 'الخاصية'), primary: true, sortVal: (a) => tx(a.name), render: (a) => <span><b style={{ fontWeight: 700 }}>{tx(a.name)}</b><span className="a-sub a-mono">{a.code}</span></span> },
        { id: 't', label: t('Display', 'العرض'), sortVal: (a) => a.type, render: (a) => <span style={{ display: 'inline-flex', gap: 6, alignItems: 'center', fontWeight: 600 }}><Icon name={AT_TYPES[a.type][0]} size={16} style={{ color: 'var(--fg-3)' }} />{tx(AT_TYPES[a.type][1])}</span> },
        { id: 'u', label: t('Unit', 'الوحدة'), render: (a) => a.unit ? <span className="a-mono">{a.unit}</span> : <span className="a-count">—</span> },
        { id: 'v', label: t('Values', 'القيم'), sortVal: (a) => a.values.length, render: (a) => <span style={{ display: 'flex', gap: 4, flexWrap: 'wrap', alignItems: 'center' }}>{a.values.slice(0, 4).map((v) => a.type === 'swatch' ? <span key={v.v} title={tx(v.label)} style={{ width: 16, height: 16, borderRadius: '50%', background: v.swatch, boxShadow: 'inset 0 0 0 1px var(--border-2)' }} /> : <Badge key={v.v} tone="outline">{tx(v.label).split(' ·')[0]}</Badge>)}{a.values.length > 4 && <span className="a-count">+{a.values.length - 4}</span>}</span> },
        { id: 'f', label: t('Filterable', 'قابل للفلترة'), render: (a) => <ASwitch checked={a.filterable} onChange={(v) => set(a.id, 'filterable', v)} /> },
        { id: 'vr', label: t('Variants', 'للمتغيرات'), render: (a) => <ASwitch checked={a.variants} onChange={(v) => set(a.id, 'variants', v)} /> },
        { id: 'x', label: '', shrink: true, hideMobile: true, render: (a) => <ARowActions onEdit={() => app.go('attribute', { id: a.id })} items={[{ icon: 'copy', label: t('Duplicate', 'تكرار') }, '-', { icon: 'trash-2', label: t('Delete', 'حذف'), danger: true, onClick: () => setRows(rows.filter((x) => x.id !== a.id)) }]} /> },
      ]} />
  );
}

function AAttributeEdit({ app, route }) {
  const { t, tx } = useLocale();
  const isNew = !route.id;
  const a = A_ATTRIBUTES.find((x) => x.id === route.id) || A_ATTRIBUTES[3];
  const [type, setType] = React.useState(isNew ? 'chip' : a.type);
  const [sw, setSw] = React.useState({ filterable: isNew ? true : a.filterable, variants: isNew ? false : a.variants });
  const [vals, setVals, props] = useReorder((isNew ? [] : a.values).map((v, i) => ({ k: i, v: v.v, en: v.label.en, ar: v.label.ar, swatch: v.swatch || A_SWATCHES[0][0] })));
  const upd = (i, k, v) => setVals(vals.map((x, j) => (j === i ? { ...x, [k]: v } : x)));
  return (
    <AEditor crumbs={[{ label: t('Attributes', 'الخصائص'), onClick: () => app.go('attributes') }, { label: isNew ? t('New attribute', 'خاصية جديدة') : tx(a.name) }]}
      title={isNew ? t('New attribute', 'خاصية جديدة') : tx(a.name)}
      actions={<Button icon="check" onClick={() => app.toast('success', t('Attribute saved', 'تم حفظ الخاصية'), isNew ? '' : a.code)}>{t('Save changes', 'حفظ التغييرات')}</Button>}
      aside={<ACard title={t('Usage', 'الاستخدام')}>
        <ASwitch checked={sw.filterable} onChange={(v) => setSw({ ...sw, filterable: v })} label={t('Filterable', 'قابل للفلترة')} help={t('Shows as a filter on category pages', 'يظهر كفلتر في صفحات الأقسام')} />
        <ASwitch checked={sw.variants} onChange={(v) => setSw({ ...sw, variants: v })} label={t('Used for variants', 'يُستخدم للمتغيرات')} help={t('Can be picked as an axis in Product → Variants', 'يمكن اختياره كمحور في المنتج ← المتغيرات')} />
        {!isNew && <span className="d-help">{t('Used by 1,284 products', 'مستخدم في 1,284 منتج')}</span>}
      </ACard>}>
      <ACard title={t('Attribute', 'الخاصية')}>
        <AGrid cols={2}>
          <Input label={t('Code', 'الكود')} defaultValue={isNew ? '' : a.code} dir="ltr" help={t('Lowercase, used in URLs and imports', 'أحرف صغيرة، يُستخدم في الروابط والاستيراد')} />
          <Input label={t('Unit', 'الوحدة')} optional defaultValue={isNew ? '' : a.unit} dir="ltr" placeholder="K, W, mm" />
        </AGrid>
        <ABi label={t('Name', 'الاسم')} en={isNew ? '' : a.name.en} ar={isNew ? '' : a.name.ar} />
        <div className="d-field"><span className="d-label">{t('Display type', 'نوع العرض')}</span>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))', gap: 10 }}>
            {Object.entries(AT_TYPES).map(([k, x]) => <RadioCard key={k} name="at-type" value={k} checked={type === k} onChange={setType} icon={x[0]} title={tx(x[1])} />)}
          </div>
        </div>
      </ACard>
      <ACard title={<>{t('Values', 'القيم')} <Badge tone="neutral">{vals.length}</Badge></>} body={false} action={<span className="a-count">{t('Drag to sort · order is used on the store', 'اسحب للترتيب · يُستخدم الترتيب في المتجر')}</span>}>
        {vals.length === 0 ? <AEmpty icon="list-plus" title={t('No values yet', 'لا توجد قيم بعد')} body={t('Add the options customers can pick, e.g. 3000K or Matte black.', 'أضف الخيارات التي يختارها العميل، مثل 3000K أو أسود مطفي.')} /> : (
          <div className="a-scroll">
            <table className="a-table" style={{ minWidth: type === 'swatch' ? 760 : 560 }}>
              <thead><tr><th className="is-shrink" /><th>{t('Value', 'القيمة')}</th><th>{t('Label', 'الاسم')} · EN</th><th>{t('Label', 'الاسم')} · {t('AR', 'عربي')}</th>{type === 'swatch' && <th>{t('Swatch', 'اللون')}</th>}<th className="is-shrink" /></tr></thead>
              <tbody>{vals.map((x, i) => { const p = props(i); return (
                <tr key={x.k} {...p}>
                  <td className="is-shrink"><span className="a-grip"><Icon name="grip-vertical" size={16} /></span></td>
                  <td style={{ padding: 6 }}><input className="d-input d-input--sm" style={{ fontFamily: 'var(--font-mono)', fontSize: 12, width: 100 }} value={x.v} onChange={(e) => upd(i, 'v', e.target.value)} dir="ltr" aria-label={t('Value', 'القيمة')} /></td>
                  <td style={{ padding: 6 }}><input className="d-input d-input--sm" value={x.en} onChange={(e) => upd(i, 'en', e.target.value)} dir="ltr" aria-label="Label EN" /></td>
                  <td style={{ padding: 6 }}><input className="d-input d-input--sm" value={x.ar} onChange={(e) => upd(i, 'ar', e.target.value)} dir="rtl" aria-label="Label AR" /></td>
                  {type === 'swatch' && <td style={{ padding: 6 }}><span style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                    <span style={{ width: 28, height: 28, borderRadius: '50%', background: x.swatch, boxShadow: 'inset 0 0 0 1px var(--border-2)', flex: 'none' }} />
                    <select className="d-input d-input--sm" style={{ width: 170 }} value={A_SWATCHES.some((s) => s[0] === x.swatch) ? x.swatch : 'custom'} onChange={(e) => e.target.value !== 'custom' && upd(i, 'swatch', e.target.value)} aria-label={t('Swatch', 'اللون')}>
                      {A_SWATCHES.map(([v, l]) => <option key={v} value={v}>{tx(l)}{v.startsWith('linear') ? ' · ' + t('gradient', 'تدرج') : ''}</option>)}
                      <option value="custom">{t('Custom…', 'مخصص…')}</option>
                    </select></span></td>}
                  <td className="is-shrink"><IconButton icon="trash-2" size="sm" label={t('Remove', 'حذف')} onClick={() => setVals(vals.filter((_, j) => j !== i))} /></td>
                </tr>); })}</tbody>
            </table>
          </div>
        )}
        <div className="a-card__foot"><Button size="sm" variant="outline" icon="plus" onClick={() => setVals([...vals, { k: Date.now(), v: '', en: '', ar: '', swatch: A_SWATCHES[0][0] }])}>{t('Add value', 'إضافة قيمة')}</Button></div>
      </ACard>
    </AEditor>
  );
}

;


/* Orders list, order detail, WhatsApp leads, consultations. */
function APayMethod({ m }) {
  const { tx } = useLocale();
  const x = A_PAY_METHODS[m];
  return <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, fontWeight: 600, whiteSpace: 'nowrap' }}><Icon name={x.icon} size={16} style={{ color: 'var(--fg-3)' }} />{tx(x.label)}</span>;
}

function AOrders({ app }) {
  const { t, tx, price } = useLocale();
  const [st, setSt] = React.useState('all');
  const [pay, setPay] = React.useState('');
  const [m, setM] = React.useState('');
  const [q, setQ] = React.useState('');
  const [from, setFrom] = React.useState('2026-09-01');
  const [to, setTo] = React.useState('2026-10-01');
  const [page, setPage] = React.useState(1);
  const cnt = (s) => A_ORDERS.filter((o) => s === 'all' || o.status === s).length;
  const rows = A_ORDERS.filter((o) => (st === 'all' || o.status === st) && (!pay || o.pay === pay) && (!m || o.method === m) && (!q || (o.id + tx(o.name) + o.phone).toLowerCase().includes(q.toLowerCase())));
  return (
    <div className="a-page">
      <AHead title={t('Orders', 'الطلبات')} sub={t('4 new orders waiting for confirmation', '4 طلبات جديدة في انتظار التأكيد')}>
        <Button variant="outline" icon="download" onClick={() => app.toast('success', t('Export ready', 'التصدير جاهز'), 'orders-2026-10-01.csv')}>{t('Export CSV', 'تصدير CSV')}</Button>
      </AHead>
      <section className="a-card">
        <div style={{ padding: '0 18px' }}><Tabs value={st} onChange={setSt} tabs={['all', ...Object.keys(A_STATUS.order)].map((s) => ({ id: s, label: s === 'all' ? t('All', 'الكل') : A_STATUS.order[s][1], count: cnt(s) }))} /></div>
        <div className="a-bar">
          <Input className="a-grow" size="sm" icon="search" placeholder={t('Order no., name or mobile', 'رقم الطلب أو الاسم أو الموبايل')} value={q} onChange={(e) => setQ(e.target.value)} aria-label={t('Search orders', 'بحث الطلبات')} />
          <Select size="sm" label={t('Payment status', 'حالة الدفع')} placeholder={t('Any', 'الكل')} options={Object.entries(A_STATUS.pay).map(([v, s]) => ({ value: v, label: tx(s[1]) }))} value={pay} onChange={(e) => setPay(e.target.value)} />
          <Select size="sm" label={t('Payment method', 'طريقة الدفع')} placeholder={t('Any', 'الكل')} options={Object.entries(A_PAY_METHODS).map(([v, s]) => ({ value: v, label: tx(s.label) }))} value={m} onChange={(e) => setM(e.target.value)} />
          <Input size="sm" type="date" label={t('From', 'من')} value={from} onChange={(e) => setFrom(e.target.value)} dir="ltr" />
          <Input size="sm" type="date" label={t('To', 'إلى')} value={to} onChange={(e) => setTo(e.target.value)} dir="ltr" />
        </div>
        <ATable rows={rows} onRow={(r) => app.go('order', { id: r.id })}
          empty={<AEmpty icon="inbox" title={t('No orders match these filters', 'لا توجد طلبات تطابق الفلاتر')} action={<Button variant="outline" size="sm" onClick={() => { setSt('all'); setPay(''); setM(''); setQ(''); }}>{t('Clear filters', 'مسح الفلاتر')}</Button>} />}
          columns={[
            { id: 'id', label: t('Order', 'الطلب'), primary: true, render: (r) => <span><span className="a-mono" style={{ color: 'var(--fg-1)', fontWeight: 500 }}>{r.id}</span><span className="a-sub">{tx(r.date)}</span></span> },
            { id: 'c', label: t('Customer', 'العميل'), render: (r) => <span><b style={{ fontWeight: 700 }}>{tx(r.name)}</b><span className="a-sub" dir="ltr" style={{ textAlign: 'start' }}>{r.phone}</span></span> },
            { id: 'g', label: t('Governorate', 'المحافظة'), hideMobile: true, render: (r) => tx(A_GOVS.find((g) => g.id === r.gov).name) },
            { id: 'n', label: t('Items', 'المنتجات'), num: true, hideMobile: true, render: (r) => r.items },
            { id: 'tot', label: t('Total', 'الإجمالي'), num: true, render: (r) => <b>{price(r.total)}</b> },
            { id: 'm', label: t('Method', 'الطريقة'), render: (r) => <APayMethod m={r.method} /> },
            { id: 'p', label: t('Payment', 'الدفع'), render: (r) => <APill kind="pay" v={r.pay} /> },
            { id: 's', label: t('Status', 'الحالة'), render: (r) => <APill kind="order" v={r.status} /> },
            { id: 'a', label: '', shrink: true, hideMobile: true, render: (r) => <span onClick={(e) => e.stopPropagation()}><IconButton icon="message-circle" size="sm" label={t('WhatsApp customer', 'واتساب العميل')} style={{ color: 'var(--wa-600)' }} onClick={() => app.toast('success', t('Opening WhatsApp…', 'جارٍ فتح واتساب…'), r.phone)} /></span> },
          ]} />
        {rows.length > 0 && <div className="a-card__foot"><Pagination page={page} total={48} onChange={setPage} count={472} perPage={10} /></div>}
      </section>
    </div>
  );
}

const AO_LOG = [
  ['14:31:58', 'payment_key.created', '—', 9276, 'info', aM('Created', 'تم الإنشاء')],
  ['14:32:20', 'transaction.processed', '238410772', 9276, 'error', aM('Declined · 3DS timeout', 'مرفوض · انتهاء مهلة 3DS')],
  ['14:32:41', 'transaction.processed', '238410795', 9276, 'success', aM('Success', 'ناجح')],
  ['14:32:42', 'webhook.received', '238410795', 9276, 'success', aM('HMAC verified', 'تم التحقق HMAC')],
];

function AOrderDetail({ app, route }) {
  const { t, tx, price, compact } = useLocale();
  const o = A_ORDERS.find((x) => x.id === route.id) || A_ORDERS[0];
  const [st, setSt] = React.useState(o.status);
  const [ps, setPs] = React.useState(o.pay);
  const [note, setNote] = React.useState('');
  const [cNote, setCNote] = React.useState({ en: '', ar: '' });
  const [eta, setEta] = React.useState('');
  const [ckey, setCkey] = React.useState(0);
  const guest = A_GUEST_ORDERS.includes(o.id);
  const cust = A_CUSTOMERS.find((c) => c.phone === o.phone);
  const [hist, setHist] = React.useState([
    { at: tx(o.date), title: t('Order placed', 'تم إنشاء الطلب'), by: t('Customer · website', 'العميل · الموقع') },
  ]);
  const cur = { st: o.status, ps: o.pay };
  const [saved, setSaved] = React.useState(cur);
  const items = [K_PRODUCTS[0], K_PRODUCTS[1], K_PRODUCTS[2]].map((p, i) => ({ p, qty: [1, 6, 1][i], v: [aM('Matte black · 3000K', 'أسود مطفي · 3000K'), aM('White · 4000K', 'أبيض · 4000K'), aM('Glass white', 'زجاج أبيض')][i] }));
  const sub = items.reduce((s, i) => s + i.p.price * i.qty, 0);
  const disc = Math.round(sub * 0.1);
  const update = () => {
    const ch = [];
    if (st !== saved.st) ch.push(`${t('Order', 'الطلب')}: ${tx(A_STATUS.order[saved.st][1])} → ${tx(A_STATUS.order[st][1])}`);
    if (ps !== saved.ps) ch.push(`${t('Payment', 'الدفع')}: ${tx(A_STATUS.pay[saved.ps][1])} → ${tx(A_STATUS.pay[ps][1])}`);
    if (eta) ch.push(`${t('Expected delivery', 'التوصيل المتوقع')}: ${eta}`);
    const cn = tx(cNote);
    if (!ch.length && !note && !cn) { app.toast('info', t('Nothing changed', 'لا توجد تغييرات')); return; }
    setHist([{ at: t('Just now', 'الآن'), title: ch.join(' · ') || t('Note added', 'تمت إضافة ملاحظة'), by: 'Mona Adel', cnote: cn, note }, ...hist]);
    setSaved({ st, ps }); setNote(''); setCNote({ en: '', ar: '' }); setCkey(ckey + 1);
    app.toast('success', t('Order updated', 'تم تحديث الطلب'), o.id);
  };
  return (
    <div className="a-page">
      <AHead crumbs={[{ label: t('Orders', 'الطلبات'), onClick: () => app.go('orders') }, { label: o.id }]} title={<span style={{ fontFamily: 'var(--font-mono)', fontWeight: 500 }}>{o.id}</span>} extra={<span style={{ display: 'inline-flex', gap: 6 }}><APill kind="order" v={saved.st} /><APill kind="pay" v={saved.ps} /></span>} sub={`${t('Placed', 'تاريخ الطلب')} ${tx(o.date)} 2026 · ${t('via website', 'من الموقع')}`}>
        <Button variant="whatsapp" onClick={() => app.toast('success', t('Opening WhatsApp…', 'جارٍ فتح واتساب…'), o.phone)}>{t('Contact customer on WhatsApp', 'تواصل مع العميل على واتساب')}</Button>
      </AHead>
      <div className="a-split">
        <div style={{ display: 'grid', gap: 16, minWidth: 0 }}>
          <ACard title={`${t('Items', 'المنتجات')} · ${items.reduce((s, i) => s + i.qty, 0)}`} body={false}>
            {items.map(({ p, qty, v }) => (
              <div key={p.id} style={{ display: 'grid', gridTemplateColumns: '52px minmax(0,1fr) auto', gap: 14, alignItems: 'center', padding: '12px 18px', borderBottom: '1px solid var(--border-1)' }}>
                <ImageFrame src={p.image} style={{ width: 52, height: 52, borderRadius: 8, border: '1px solid var(--border-1)' }} />
                <span style={{ minWidth: 0 }}><button type="button" className="a-link" style={{ textDecoration: 'none' }} onClick={() => app.go('product', { id: p.id })}>{tx(p.name)}</button><span className="a-sub">{tx(v)} · <span className="a-mono">{p.sku}</span></span></span>
                <span style={{ textAlign: 'end', fontVariantNumeric: 'tabular-nums' }}><b style={{ font: '800 14px var(--font-body)' }}>{price(p.price * qty)}</b><span className="a-sub">{qty} × {price(p.price)}</span></span>
              </div>
            ))}
            <div className="a-card__body" style={{ maxWidth: compact ? undefined : 380, marginInlineStart: 'auto', width: '100%' }}>
              <div className="d-totals">
                <div><span>{t('Subtotal', 'الإجمالي الفرعي')}</span><b>{price(sub)}</b></div>
                <div><span style={{ display: 'inline-flex', gap: 8, alignItems: 'center' }}>{t('Coupon', 'الكوبون')} <Badge tone="outline">DOTS10 · −10%</Badge></span><b style={{ color: 'var(--success-600)' }}>−{price(disc)}</b></div>
                <div><span>{t('Delivery · Standard', 'التوصيل · عادي')}</span><b>{price(150)}</b></div>
                <div className="d-totals__grand"><span>{t('Total', 'الإجمالي')}</span><b>{price(sub - disc + 150)}</b></div>
                <span className="a-count">{t('Includes VAT 14%:', 'شامل ضريبة 14%:')} {price(Math.round((sub - disc + 150) * 14 / 114))}</span>
              </div>
            </div>
          </ACard>
          {o.method === 'card' && <ACard title={t('Paymob transaction log', 'سجل معاملات Paymob')} icon="credit-card" body={false} action={<span className="a-mono">order_id 61 220 948</span>}>
            <ATable rowKey="0" rows={AO_LOG.map((r, i) => ({ ...r, 0: i, r }))} columns={[
              { id: 'tm', label: t('Time', 'الوقت'), primary: true, render: ({ r }) => <span className="a-mono">{r[0]}</span> },
              { id: 'ev', label: t('Event', 'الحدث'), render: ({ r }) => <span className="a-mono" style={{ color: 'var(--fg-1)' }}>{r[1]}</span> },
              { id: 'tx', label: t('Txn ID', 'رقم العملية'), render: ({ r }) => <span className="a-mono">{r[2]}</span> },
              { id: 'am', label: t('Amount', 'المبلغ'), num: true, render: ({ r }) => price(r[3]) },
              { id: 're', label: t('Result', 'النتيجة'), render: ({ r }) => <Badge tone={r[4]} dot>{tx(r[5])}</Badge> },
            ]} />
          </ACard>}
          <ACard title={t('Status history', 'سجل الحالة')} icon="history">
            <ol className="a-tl">
              {hist.map((h, i) => <li key={i}><b>{h.title}</b>{h.at} · {h.by}{h.cnote && <span style={{ display: 'flex', gap: 6, alignItems: 'center', marginTop: 4, color: 'var(--fg-1)' }}><Badge tone="info">{t('Customer sees', 'يراه العميل')}</Badge>“{h.cnote}”</span>}{h.note && <span style={{ display: 'flex', gap: 6, alignItems: 'center', marginTop: 4, color: 'var(--fg-1)' }}><Badge tone="neutral">{t('Internal', 'داخلي')}</Badge>“{h.note}”</span>}</li>)}
              {o.method === 'card' && <li><b>{t('Payment captured · Paymob', 'تم تحصيل الدفع · Paymob')}</b>{tx(o.date)} · {t('System', 'النظام')}</li>}
            </ol>
          </ACard>
        </div>
        <div style={{ display: 'grid', gap: 16 }}>
          <ACard title={t('Update status', 'تحديث الحالة')} icon="refresh-cw">
            <Select label={t('Order status', 'حالة الطلب')} value={st} onChange={(e) => setSt(e.target.value)} options={Object.entries(A_STATUS.order).map(([v, s]) => ({ value: v, label: tx(s[1]) }))} />
            <Select label={t('Payment status', 'حالة الدفع')} value={ps} onChange={(e) => setPs(e.target.value)} options={Object.entries(A_STATUS.pay).map(([v, s]) => ({ value: v, label: tx(s[1]) }))} />
            <Input label={t('Expected delivery date', 'تاريخ التوصيل المتوقع')} optional type="date" value={eta} onChange={(e) => setEta(e.target.value)} dir="ltr" />
            <div className="d-field">
              <span className="d-label">{t('Note shown to customer in the tracking timeline', 'ملاحظة تظهر للعميل في تتبع الطلب')} <span className="d-label__opt">· {t('optional', 'اختياري')}</span></span>
              <textarea key={'en' + ckey} className="d-input" rows={2} dir="ltr" placeholder="EN" onChange={(e) => setCNote({ ...cNote, en: e.target.value })} aria-label="Customer note EN" />
              <textarea key={'ar' + ckey} className="d-input" rows={2} dir="rtl" placeholder="عربي" onChange={(e) => setCNote({ ...cNote, ar: e.target.value })} aria-label="Customer note AR" />
            </div>
            <Input label={t('Internal note (admin only)', 'ملاحظة داخلية (للإدارة فقط)')} optional multiline rows={2} value={note} onChange={(e) => setNote(e.target.value)} placeholder={t('e.g. Customer asked for evening delivery', 'مثال: العميل طلب التوصيل مساءً')} />
            <Button block onClick={update}>{t('Update order', 'تحديث الطلب')}</Button>
            <Button block variant="quote-outline" onClick={() => app.toast('success', t('WhatsApp chat opened with a prefilled update', 'تم فتح محادثة واتساب برسالة جاهزة'), `${o.id} · ${tx(A_STATUS.order[saved.st][1])}${tx(cNote) ? ' · ' + tx(cNote) : ''}`)}>{t('Send update on WhatsApp', 'إرسال التحديث على واتساب')}</Button>
            <span className="d-help">{t('Opens a chat with the “Order update” template filled in. Nothing is sent automatically.', 'يفتح محادثة بقالب "تحديث الطلب" جاهز. لا يتم إرسال أي شيء تلقائياً.')}</span>
          </ACard>
          <ACard title={t('Customer', 'العميل')} icon="user">
            <div style={{ display: 'grid', gap: 4, font: '600 14px/1.45 var(--font-body)' }}>
              <span style={{ display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap' }}>{!guest && cust ? <button type="button" className="a-link" onClick={() => app.go('customer', { id: cust.id })}>{tx(o.name)}</button> : <b style={{ fontWeight: 800 }}>{tx(o.name)}</b>}{(guest || !cust) && <Badge tone="neutral">{t('Guest', 'زائر')}</Badge>}</span>
              <span dir="ltr" style={{ textAlign: 'start' }}>{o.phone}</span>
              {cust && !guest && <span style={{ color: 'var(--fg-2)' }}>{cust.email}</span>}
              {cust && !guest && cust.company && <span style={{ display: 'flex', gap: 6, alignItems: 'center', flexWrap: 'wrap', font: '600 13px var(--font-body)' }}><Icon name="building-2" size={14} />{cust.company.name} · {t('Tax ID', 'الرقم الضريبي')} <span className="a-mono">{cust.company.tax}</span></span>}
              {cust && !guest && <span className="a-count">{cust.orders} {t('orders', 'طلبات')} · {price(cust.spent)} {t('lifetime', 'إجمالي')}</span>}
            </div>
          </ACard>
          <ACard title={t('Delivery', 'التوصيل')} icon="truck">
            <div style={{ display: 'grid', gap: 4, font: '600 14px/1.5 var(--font-body)', color: 'var(--fg-2)' }}>
              <b style={{ color: 'var(--fg-1)', fontWeight: 800 }}>{t('Standard delivery · EGP 150', 'توصيل عادي · 150 ج.م')}</b>
              <span>{t('14 El-Nahda St, Building 9, Apt 6, Maadi', '14 شارع النهضة، عمارة 9، شقة 6، المعادي')}</span>
              <span>{tx(A_GOVS.find((g) => g.id === o.gov).name)}</span>
            </div>
          </ACard>
          <ACard title={t('Payment', 'الدفع')} icon="wallet">
            <div style={{ display: 'grid', gap: 8 }}><APayMethod m={o.method} />{o.method === 'card' && <span className="a-count">Visa •••• 4417 · Txn 238410795</span>}<span style={{ display: 'flex', gap: 8, alignItems: 'center', font: '600 13px var(--font-body)' }}>{t('Coupon used', 'الكوبون المستخدم')} <Badge tone="outline">DOTS10</Badge></span></div>
          </ACard>
        </div>
      </div>
    </div>
  );
}

/** Shared pattern for WhatsApp leads and consultation requests. */
function ALeadPage({ app, kind }) {
  const { t, tx } = useLocale();
  const isC = kind === 'consult';
  const SRC = isC ? A_CONSULT_TYPES : A_LEAD_SOURCES;
  const [data, setData] = React.useState(isC ? A_CONSULTS : A_LEADS);
  const [st, setSt] = React.useState('all');
  const [src, setSrc] = React.useState('');
  const [q, setQ] = React.useState('');
  const [openId, setOpenId] = React.useState(null);
  const [draft, setDraft] = React.useState('');
  const lead = data.find((l) => l.id === openId);
  const cnt = (s) => data.filter((l) => s === 'all' || l.status === s).length;
  const rows = data.filter((l) => (st === 'all' || l.status === st) && (!src || l.source === src) && (!q || (tx(l.name) + l.phone + tx(l.ref)).toLowerCase().includes(q.toLowerCase())));
  const patch = (id, fn) => setData(data.map((l) => (l.id === id ? fn(l) : l)));
  const srcLabel = isC ? t('Type', 'النوع') : t('Source', 'المصدر');
  return (
    <div className="a-page">
      <AHead title={isC ? t('Consultation requests', 'طلبات الاستشارة') : t('WhatsApp leads', 'طلبات واتساب')} sub={isC ? t('Booked from service pages and the “Book a consultation” form', 'من صفحات الخدمات ونموذج "احجز استشارة"') : t('Every “Request a quote” and WhatsApp click on the site, with where it came from', 'كل طلب عرض سعر ونقرة واتساب على الموقع، مع مصدرها')}>
        <Button variant="outline" icon="download" onClick={() => app.toast('success', t('Export ready', 'التصدير جاهز'), `${isC ? 'consultations' : 'whatsapp-leads'}-2026-10-01.csv · ${rows.length} ${t('rows', 'صف')}`)}>{t('Export CSV', 'تصدير CSV')}</Button>
      </AHead>
      <section className="a-card">
        <div style={{ padding: '0 18px' }}><Tabs value={st} onChange={setSt} tabs={['all', ...Object.keys(A_STATUS.lead)].map((s) => ({ id: s, label: s === 'all' ? t('All', 'الكل') : A_STATUS.lead[s][1], count: cnt(s) }))} /></div>
        <div className="a-bar">
          <Input className="a-grow" size="sm" icon="search" placeholder={t('Name, mobile or product', 'الاسم أو الموبايل أو المنتج')} value={q} onChange={(e) => setQ(e.target.value)} aria-label={t('Search', 'بحث')} />
          <Select size="sm" aria-label={srcLabel} placeholder={isC ? t('All types', 'كل الأنواع') : t('All sources', 'كل المصادر')} options={Object.entries(SRC).map(([v, s]) => ({ value: v, label: tx(s[1]) }))} value={src} onChange={(e) => setSrc(e.target.value)} />
          <Input size="sm" type="date" defaultValue="2026-09-24" aria-label={t('From', 'من')} dir="ltr" />
          <Input size="sm" type="date" defaultValue="2026-10-01" aria-label={t('To', 'إلى')} dir="ltr" />
        </div>
        <ATable rows={rows} onRow={(r) => setOpenId(r.id)}
          empty={<AEmpty icon="message-circle" title={t('No requests here yet', 'لا توجد طلبات هنا بعد')} body={t('New requests appear the moment a customer taps WhatsApp on the site.', 'تظهر الطلبات الجديدة فور ضغط العميل على واتساب في الموقع.')} />}
          columns={[
            { id: 'who', label: t('Customer', 'العميل'), primary: true, render: (r) => <span><b style={{ fontWeight: 700, color: tx(r.name) ? undefined : 'var(--fg-3)' }}>{tx(r.name) || t('Unknown — fill after chat', 'غير معروف — يُستكمل بعد المحادثة')}</b><span className="a-sub" dir="ltr" style={{ textAlign: 'start' }}>{r.phone || '—'}</span></span> },
            { id: 'src', label: srcLabel, render: (r) => <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, fontWeight: 700, whiteSpace: 'nowrap' }}><Icon name={SRC[r.source][0]} size={16} style={{ color: 'var(--fg-3)' }} />{tx(SRC[r.source][1])}</span> },
            { id: 'ref', label: isC ? t('Details', 'التفاصيل') : t('Product / page', 'المنتج / الصفحة'), wide: true, render: (r) => <span style={{ display: 'block', maxWidth: 340, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={tx(r.ref)}>{tx(r.ref)}</span> },
            { id: 'd', label: t('Date', 'التاريخ'), render: (r) => <span style={{ whiteSpace: 'nowrap', fontVariantNumeric: 'tabular-nums' }}>{tx(r.date)}</span> },
            { id: 's', label: t('Status', 'الحالة'), render: (r) => <APill kind="lead" v={r.status} /> },
            { id: 'n', label: t('Notes', 'ملاحظات'), num: true, hideMobile: true, render: (r) => r.notes.length ? <span style={{ display: 'inline-flex', gap: 4, alignItems: 'center' }}><Icon name="sticky-note" size={14} />{r.notes.length}</span> : <span className="a-count">—</span> },
            { id: 'a', label: '', shrink: true, hideMobile: true, render: (r) => <span onClick={(e) => e.stopPropagation()}><IconButton icon="message-circle" size="sm" label={t('Open WhatsApp chat', 'فتح محادثة واتساب')} style={{ color: 'var(--wa-600)' }} onClick={() => app.toast('success', t('Opening WhatsApp…', 'جارٍ فتح واتساب…'), r.phone)} /></span> },
          ]} />
      </section>
      <ADrawer open={!!lead} onClose={() => setOpenId(null)} title={lead ? (tx(lead.name) || t('Unknown contact', 'جهة اتصال غير معروفة')) : ''} sub={lead && <span className="a-mono">{lead.id} · {tx(lead.date)}</span>}
        foot={lead && <><Button variant="outline" onClick={() => setOpenId(null)}>{t('Close', 'إغلاق')}</Button><Button variant="whatsapp" onClick={() => { patch(lead.id, (l) => ({ ...l, status: l.status === 'new' ? 'contacted' : l.status })); app.toast('success', t('Opening WhatsApp…', 'جارٍ فتح واتساب…'), lead.phone); }}>{t('Open chat', 'فتح المحادثة')}</Button></>}>
        {lead && <>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0,1fr))', gap: 12 }}>
            <Input key={'n' + lead.id} label={t('Name', 'الاسم')} defaultValue={tx(lead.name)} placeholder={t('Fill after the chat', 'يُستكمل بعد المحادثة')} onBlur={(e) => patch(lead.id, (l) => ({ ...l, name: aM(e.target.value, e.target.value) }))} />
            <Input key={'p' + lead.id} label={t('Mobile', 'الموبايل')} defaultValue={lead.phone} placeholder="01X XXXX XXXX" dir="ltr" inputMode="tel" onBlur={(e) => patch(lead.id, (l) => ({ ...l, phone: e.target.value }))} />
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0,1fr))', gap: 12, font: '600 13px/1.4 var(--font-body)' }}>
            <div><span className="a-sub" style={{ marginBottom: 4 }}>{srcLabel}</span>{tx(SRC[lead.source][1])}</div>
            <div><span className="a-sub" style={{ marginBottom: 4 }}>{t('Received', 'تاريخ الطلب')}</span>{tx(lead.date)}</div>
            <div style={{ gridColumn: '1 / -1' }}><span className="a-sub" style={{ marginBottom: 4 }}>{isC ? t('Details', 'التفاصيل') : t('Product / page', 'المنتج / الصفحة')}</span>{tx(lead.ref)}</div>
          </div>
          <Select label={t('Status', 'الحالة')} value={lead.status} onChange={(e) => patch(lead.id, (l) => ({ ...l, status: e.target.value }))} options={Object.entries(A_STATUS.lead).map(([v, s]) => ({ value: v, label: tx(s[1]) }))} />
          <div style={{ display: 'grid', gap: 10 }}>
            <span className="d-label">{t('Notes', 'الملاحظات')}</span>
            {lead.notes.length ? <ol className="a-tl">{lead.notes.map((n, i) => <li key={i}><b>{n.by} · {tx(n.at)}</b><span style={{ color: 'var(--fg-1)' }}>{tx(n.text)}</span></li>)}</ol> : <span className="d-help">{t('No notes yet.', 'لا توجد ملاحظات بعد.')}</span>}
            <Input multiline rows={3} placeholder={t('Add a note for the team…', 'أضف ملاحظة للفريق…')} value={draft} onChange={(e) => setDraft(e.target.value)} aria-label={t('New note', 'ملاحظة جديدة')} />
            <div><Button size="sm" variant="dark" disabled={!draft.trim()} onClick={() => { patch(lead.id, (l) => ({ ...l, notes: [{ by: 'Mona', at: aM('Just now', 'الآن'), text: aM(draft, draft) }, ...l.notes] })); setDraft(''); }}>{t('Add note', 'إضافة ملاحظة')}</Button></div>
          </div>
        </>}
      </ADrawer>
    </div>
  );
}

function ALeads({ app }) { return <ALeadPage app={app} kind="lead" />; }
function AConsultations({ app }) { return <ALeadPage app={app} kind="consult" />; }

;


/* Coupons, delivery methods, payment methods, settings, users. */
function ACoupons({ app }) {
  const { t, price, compact } = useLocale();
  const [list, setList] = React.useState(A_COUPONS);
  const [edit, setEdit] = React.useState(null);
  const [type, setType] = React.useState('pct');
  const open = (c) => { setEdit(c || { id: 'new', code: '', type: 'pct', value: '', min: '', start: '2026-10-01', end: '2026-10-31', limit: '', used: 0, active: true }); setType((c && c.type) || 'pct'); };
  const st = (c) => !c.active ? 'off' : c.limit && c.used >= c.limit ? 'off' : 'on';
  return (
    <div className="a-page">
      <AHead title={t('Coupons', 'الكوبونات')} sub={t('Codes customers enter at checkout', 'أكواد يدخلها العميل عند الدفع')}><Button icon="plus" onClick={() => open()}>{t('New coupon', 'كوبون جديد')}</Button></AHead>
      <section className="a-card">
        <ATable rows={list} onRow={open} columns={[
          { id: 'code', label: t('Code', 'الكود'), primary: true, render: (c) => <span style={{ font: '500 14px var(--font-mono)', color: 'var(--fg-1)' }}>{c.code}</span> },
          { id: 'v', label: t('Discount', 'الخصم'), render: (c) => <b>{c.type === 'pct' ? `${c.value}%` : price(c.value)}</b> },
          { id: 'min', label: t('Min. order', 'الحد الأدنى'), num: true, render: (c) => price(c.min) },
          { id: 'd', label: t('Valid', 'الصلاحية'), render: (c) => <span className="a-mono" style={{ whiteSpace: 'nowrap' }}>{c.start} → {c.end}</span> },
          { id: 'u', label: t('Used', 'الاستخدام'), render: (c) => (
            <span style={{ display: 'grid', gap: 5, minWidth: 110 }}><span style={{ fontVariantNumeric: 'tabular-nums', fontWeight: 700 }}>{c.used.toLocaleString('en-US')}{c.limit ? ` / ${c.limit}` : ` · ${t('no limit', 'بلا حد')}`}</span>
              {c.limit > 0 && <span style={{ height: 4, borderRadius: 2, background: 'var(--ink-150)', position: 'relative', overflow: 'hidden' }}><span style={{ position: 'absolute', insetBlock: 0, insetInlineStart: 0, width: Math.min(100, (c.used / c.limit) * 100) + '%', background: c.used >= c.limit ? 'var(--ink-400)' : 'var(--accent)' }} /></span>}</span>) },
          { id: 's', label: t('Status', 'الحالة'), render: (c) => <APill kind="active" v={st(c)} /> },
          { id: 'a', label: t('Active', 'مفعل'), shrink: true, render: (c) => <ASwitch checked={c.active} onChange={(v) => setList(list.map((x) => (x.id === c.id ? { ...x, active: v } : x)))} /> },
        ]} />
      </section>
      <ADrawer open={!!edit} onClose={() => setEdit(null)} title={edit && edit.id !== 'new' ? edit.code : t('New coupon', 'كوبون جديد')}
        foot={<><Button variant="outline" onClick={() => setEdit(null)}>{t('Cancel', 'إلغاء')}</Button><Button icon="check" onClick={() => { setEdit(null); app.toast('success', t('Coupon saved', 'تم حفظ الكوبون')); }}>{t('Save coupon', 'حفظ الكوبون')}</Button></>}>
        {edit && <>
          <Input label={t('Code', 'الكود')} defaultValue={edit.code} dir="ltr" style={{ textTransform: 'uppercase' }} help={t('Letters and numbers, not case-sensitive', 'حروف وأرقام، بدون حساسية لحالة الأحرف')} trailing={<button type="button" className="a-link" style={{ pointerEvents: 'auto', fontSize: 12 }}>{t('Generate', 'توليد')}</button>} />
          <div className="d-field"><span className="d-label">{t('Type', 'النوع')}</span><div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0,1fr))', gap: 10 }}>
            <RadioCard name="ctype" value="pct" checked={type === 'pct'} onChange={setType} icon="percent" title={t('Percentage', 'نسبة')} />
            <RadioCard name="ctype" value="fixed" checked={type === 'fixed'} onChange={setType} icon="banknote" title={t('Fixed amount', 'مبلغ ثابت')} />
          </div></div>
          <AGrid cols={2}>
            <Input label={t('Value', 'القيمة')} type="number" defaultValue={edit.value} dir="ltr" trailing={<span style={{ font: '700 13px var(--font-body)' }}>{type === 'pct' ? '%' : 'EGP'}</span>} />
            <Input label={t('Minimum order total', 'الحد الأدنى للطلب')} type="number" defaultValue={edit.min} dir="ltr" trailing={<span style={{ font: '700 13px var(--font-body)' }}>EGP</span>} />
            <Input label={t('Starts', 'يبدأ')} type="date" defaultValue={edit.start} dir="ltr" />
            <Input label={t('Ends', 'ينتهي')} type="date" defaultValue={edit.end} dir="ltr" />
            <Input label={t('Usage limit', 'حد الاستخدام')} type="number" defaultValue={edit.limit || ''} placeholder={t('No limit', 'بلا حد')} dir="ltr" />
            <Input label={t('Times used', 'مرات الاستخدام')} value={edit.used.toLocaleString('en-US')} disabled readOnly dir="ltr" />
          </AGrid>
          <ASwitch checked={!!edit.once} onChange={(v) => setEdit({ ...edit, once: v })} label={t('Limit to one use per customer', 'استخدام واحد لكل عميل')} help={t('Checked by mobile number at checkout', 'يتم التحقق برقم الموبايل عند الدفع')} />
          <ASwitch checked={edit.active} onChange={(v) => setEdit({ ...edit, active: v })} label={t('Active', 'مفعل')} help={t('Inactive codes are rejected at checkout', 'الأكواد غير المفعلة تُرفض عند الدفع')} />
        </>}
      </ADrawer>
    </div>
  );
}

function ADelivery({ app }) {
  const { t, tx, price } = useLocale();
  const [list, setList, props] = useReorder(A_DELIVERY);
  const [edit, setEdit] = React.useState(null);
  const [govs, setGovs] = React.useState([]);
  const open = (d) => { setEdit({ icon: 'truck', from: false, freeOver: '', addr: true, ...d }); setGovs(d.govs); };
  const tgl = (id) => setGovs(govs.includes(id) ? govs.filter((x) => x !== id) : [...govs, id]);
  return (
    <div className="a-page">
      <AHead title={t('Delivery methods', 'طرق التوصيل')} sub={t('Shown at checkout in this order · drag to reorder', 'تظهر عند الدفع بهذا الترتيب · اسحب لإعادة الترتيب')}><Button icon="plus" onClick={() => open({ id: 'new', name: aM('', ''), desc: aM('', ''), price: '', govs: [], note: aM('', ''), order: list.length + 1, active: true })}>{t('New method', 'طريقة جديدة')}</Button></AHead>
      <section className="a-card" style={{ overflow: 'hidden' }}>
        {list.map((d, i) => { const p = props(i); return (
          <div key={d.id} {...p} className={cx('a-tree__row', p.className)} style={{ height: 'auto', minHeight: 64, padding: '12px 18px', gap: 14, flexWrap: 'wrap' }} onClick={() => open(d)}>
            <span className="a-grip"><Icon name="grip-vertical" size={16} /></span>
            <span className="a-count" style={{ width: 16 }}>{i + 1}</span>
            <span className="a-kpi__ico" style={{ width: 36, height: 36 }}><Icon name={d.icon} size={18} /></span>
            <span style={{ flex: '1 1 220px', minWidth: 0 }}><b style={{ font: '800 14px/1.3 var(--font-body)' }}>{tx(d.name)}</b><span className="a-sub">{tx(d.desc)}</span></span>
            <span style={{ font: '800 14px var(--font-body)', minWidth: 80 }}>{d.price ? (d.from ? t('From', 'من') + ' ' : '') + price(d.price) : t('Free', 'مجاناً')}{d.freeOver ? <span className="a-sub">{t('Free over', 'مجاني فوق')} {price(d.freeOver)}</span> : null}</span>
            <span className="a-count" style={{ minWidth: 110 }}>{d.govs.length === A_GOVS.length ? t('All governorates', 'كل المحافظات') : `${d.govs.length} ${t('governorates', 'محافظات')}`}</span>
            <ASwitch checked={d.active} onChange={(v) => setList(list.map((x) => (x.id === d.id ? { ...x, active: v } : x)))} />
          </div>); })}
      </section>
      <ADrawer width={560} open={!!edit} onClose={() => setEdit(null)} title={edit && edit.id !== 'new' ? tx(edit.name) : t('New delivery method', 'طريقة توصيل جديدة')}
        foot={<><Button variant="outline" onClick={() => setEdit(null)}>{t('Cancel', 'إلغاء')}</Button><Button icon="check" onClick={() => { setEdit(null); app.toast('success', t('Delivery method saved', 'تم الحفظ')); }}>{t('Save', 'حفظ')}</Button></>}>
        {edit && <>
          <AIconPick label={t('Icon', 'الأيقونة')} value={edit.icon} />
          <ABi label={t('Name', 'الاسم')} en={edit.name.en} ar={edit.name.ar} />
          <ABi label={t('Description', 'الوصف')} en={edit.desc.en} ar={edit.desc.ar} />
          <AGrid cols={2}>
            <Input label={t('Price', 'السعر')} type="number" defaultValue={edit.price} dir="ltr" trailing={<span style={{ font: '700 13px var(--font-body)' }}>EGP</span>} help={t('0 = free', '0 = مجاني')} />
            <Input label={t('Display order', 'الترتيب')} type="number" defaultValue={edit.order} dir="ltr" />
            <Input label={t('Free delivery over', 'توصيل مجاني فوق')} optional type="number" defaultValue={edit.freeOver} dir="ltr" trailing={<span style={{ font: '700 13px var(--font-body)' }}>EGP</span>} />
          </AGrid>
          <ASwitch checked={edit.from} onChange={(v) => setEdit({ ...edit, from: v })} label={t('Show as “From” price', 'عرض السعر كـ "يبدأ من"')} help={t('For methods whose final price depends on the order', 'للطرق التي يعتمد سعرها النهائي على الطلب')} />
          <ASwitch checked={edit.addr} onChange={(v) => setEdit({ ...edit, addr: v })} label={t('Requires an address', 'يتطلب عنواناً')} help={t('Turn off for showroom pickup', 'أوقفه للاستلام من المعرض')} />
          <div className="d-field">
            <span className="d-label" style={{ display: 'flex', justifyContent: 'space-between' }}>{t('Available governorates', 'المحافظات المتاحة')}<span className="a-count">{govs.length} / {A_GOVS.length}</span></span>
            <div style={{ display: 'flex', gap: 8, marginBottom: 4 }}><button type="button" className="a-link" onClick={() => setGovs(A_GOVS.map((g) => g.id))}>{t('Select all', 'تحديد الكل')}</button><button type="button" className="a-link" onClick={() => setGovs([])}>{t('Clear', 'مسح')}</button></div>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>{A_GOVS.map((g) => <Chip key={g.id} active={govs.includes(g.id)} onClick={() => tgl(g.id)} aria-pressed={govs.includes(g.id)}>{govs.includes(g.id) && <Icon name="check" size={14} stroke={2} />}{tx(g.name)}</Chip>)}</div>
            <span className="d-help">{t('+ 15 more governorates in the full list', '+ 15 محافظة أخرى في القائمة الكاملة')}</span>
          </div>
          <ABi label={t('Note', 'ملاحظة')} en={edit.note.en} ar={edit.note.ar} />
          <ASwitch checked={edit.active} onChange={(v) => setEdit({ ...edit, active: v })} label={t('Active', 'مفعل')} help={t('Shown at checkout when the address is in a selected governorate', 'تظهر عند الدفع إذا كان العنوان في محافظة محددة')} />
        </>}
      </ADrawer>
    </div>
  );
}

function AUsers({ app }) {
  const { t, tx } = useLocale();
  const [add, setAdd] = React.useState(false);
  const [invite, setInvite] = React.useState(true);
  const ini = (n) => n.split(' ').map((x) => x[0]).join('').slice(0, 2);
  return (
    <div className="a-page">
      <AHead title={t('Users', 'المستخدمون')} sub={t('Everyone here has full Admin access', 'كل المستخدمين لديهم صلاحية مدير كاملة')}><Button icon="user-plus" onClick={() => setAdd(true)}>{t('Add admin', 'إضافة مدير')}</Button></AHead>
      <section className="a-card">
        <ATable rows={A_USERS} columns={[
          { id: 'n', label: t('Name', 'الاسم'), primary: true, render: (u) => <span style={{ display: 'flex', alignItems: 'center', gap: 10 }}><span className={cx('a-avatar', u.you && 'a-avatar--node')} style={u.status === 'on' ? undefined : { background: 'var(--ink-150)', color: 'var(--fg-2)' }}>{ini(u.name)}</span><span><b style={{ fontWeight: 700 }}>{u.name}</b>{u.you && <span className="a-sub">{t('You', 'أنت')}</span>}</span></span> },
          { id: 'e', label: t('Email', 'البريد'), render: (u) => <span dir="ltr">{u.email}</span> },
          { id: 'r', label: t('Role', 'الدور'), render: () => <Badge tone="dark">{t('Admin', 'مدير')}</Badge> },
          { id: 'l', label: t('Last active', 'آخر نشاط'), render: (u) => tx(u.last) },
          { id: 's', label: t('Status', 'الحالة'), render: (u) => <APill kind="active" v={u.status} /> },
          { id: 'a', label: '', shrink: true, hideMobile: true, render: (u) => !u.you && <AMenu items={[u.status === 'invited' ? { icon: 'send', label: t('Resend invite', 'إعادة إرسال الدعوة'), onClick: () => app.toast('success', t('Invite sent', 'تم إرسال الدعوة'), u.email) } : { icon: 'key-round', label: t('Reset password', 'إعادة تعيين كلمة المرور') }, { icon: u.status === 'off' ? 'user-check' : 'user-x', label: u.status === 'off' ? t('Reactivate', 'إعادة التفعيل') : t('Deactivate', 'إيقاف') }, '-', { icon: 'trash-2', label: t('Remove', 'حذف'), danger: true }]} /> },
        ]} />
      </section>
      <ADrawer open={add} onClose={() => setAdd(false)} title={t('Add admin', 'إضافة مدير')}
        foot={<><Button variant="outline" onClick={() => setAdd(false)}>{t('Cancel', 'إلغاء')}</Button><Button icon={invite ? 'send' : 'check'} onClick={() => { setAdd(false); app.toast('success', invite ? t('Invite sent', 'تم إرسال الدعوة') : t('Admin added', 'تمت الإضافة')); }}>{invite ? t('Send invite', 'إرسال الدعوة') : t('Add admin', 'إضافة')}</Button></>}>
        <Input label={t('Full name', 'الاسم بالكامل')} placeholder={t('e.g. Sara Magdy', 'مثال: سارة مجدي')} />
        <Input label={t('Work email', 'بريد العمل')} type="email" placeholder="name@dots.eg" dir="ltr" />
        <Select label={t('Role', 'الدور')} options={[t('Admin — full access', 'مدير — صلاحية كاملة')]} disabled help={t('Single role for now', 'دور واحد حالياً')} />
        <Checkbox label={t('Email an invite link (expires in 48 h)', 'إرسال رابط دعوة بالبريد (ينتهي خلال 48 ساعة)')} checked={invite} onChange={setInvite} />
        {!invite && <Input label={t('Temporary password', 'كلمة مرور مؤقتة')} type="password" dir="ltr" help={t('They must change it on first sign-in · min. 10 characters', 'يجب تغييرها عند أول دخول · 10 أحرف على الأقل')} />}
      </ADrawer>
    </div>
  );
}

;


/* Payment methods + Settings (Contact, Commerce, WhatsApp templates, Footer, SEO). */
const AP_METHODS = [
  { id: 'cod', icon: 'banknote', name: aM('Cash on delivery', 'الدفع عند الاستلام'), desc: aM('Pay the courier in cash when the order arrives.', 'ادفع نقداً للمندوب عند وصول الطلب.'), ins: aM('Have the exact amount ready. You can check the order before paying.', 'جهز المبلغ بالضبط. يمكنك فحص الطلب قبل الدفع.') },
  { id: 'card', icon: 'credit-card', name: aM('Card via Paymob', 'بطاقة عبر Paymob'), desc: aM('Visa, Mastercard and Meeza on Paymob’s secure page.', 'فيزا وماستركارد وميزة على صفحة Paymob الآمنة.'), ins: aM('You will be redirected to Paymob to complete a secure payment.', 'سيتم تحويلك إلى Paymob لإتمام الدفع بأمان.') },
  { id: 'instapay', icon: 'smartphone', name: aM('InstaPay via WhatsApp', 'إنستاباي عبر واتساب'), desc: aM('We reserve the order and send transfer details on WhatsApp.', 'نحجز الطلب ونرسل بيانات التحويل على واتساب.'), ins: aM('After placing the order, tap “Continue on WhatsApp”. We send the InstaPay address and amount.', 'بعد الطلب اضغط "تابع على واتساب". نرسل عنوان إنستاباي والمبلغ.') },
];

function APayments({ app }) {
  const { t, tx } = useLocale();
  const [list, setList, props] = useReorder(AP_METHODS.map((m) => ({ ...m, active: m.id !== 'card' })));
  const [configured, setConfigured] = React.useState(false);
  const [edit, setEdit] = React.useState(null);
  const m = edit && list.find((x) => x.id === edit);
  const toggle = (id, v) => { if (id === 'card' && v && !configured) { app.toast('warning', t('Paymob is not configured', 'Paymob غير مُعد'), t('Add the keys to the server .env first.', 'أضف المفاتيح إلى ملف ‎.env على الخادم أولاً.')); return; } setList(list.map((x) => (x.id === id ? { ...x, active: v } : x))); };
  const cardStatus = configured
    ? <AAlert tone="success" title={t('Configured on the server', 'مُعد على الخادم')}>{t('Paymob keys were found in the server environment.', 'تم العثور على مفاتيح Paymob في بيئة الخادم.')}</AAlert>
    : <AAlert tone="warning" title={t('Not configured — add keys to the server .env', 'غير مُعد — أضف المفاتيح إلى ملف ‎.env على الخادم')}>{t('Card payment stays hidden at checkout until the keys are added by a developer. Keys are never entered here.', 'يبقى الدفع بالبطاقة مخفياً عند الدفع حتى يضيف المطور المفاتيح. لا يتم إدخال المفاتيح هنا.')}</AAlert>;
  return (
    <div className="a-page">
      <AHead title={t('Payment methods', 'طرق الدفع')} sub={t('Active methods appear at checkout in this order · drag to reorder', 'الطرق المفعلة تظهر عند الدفع بهذا الترتيب · اسحب لإعادة الترتيب')} />
      <div style={{ display: 'grid', gap: 12 }}>
        {list.map((x, i) => { const p = props(i); return (
          <section key={x.id} {...p} className={cx('a-card', p.className)} style={{ padding: 18, display: 'grid', gap: 14 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 14, flexWrap: 'wrap' }}>
              <span className="a-grip" style={{ cursor: 'grab' }}><Icon name="grip-vertical" size={16} /></span>
              <span className="a-count" style={{ width: 14 }}>{i + 1}</span>
              <span className="a-kpi__ico" style={{ width: 44, height: 44 }}><Icon name={x.icon} size={20} /></span>
              <span style={{ flex: '1 1 220px', minWidth: 0 }}><b style={{ font: '800 15px/1.3 var(--font-body)', display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap' }}>{tx(x.name)}{x.id === 'card' && <Badge tone={configured ? 'success' : 'warning'} dot>{configured ? t('Configured', 'مُعد') : t('Not configured', 'غير مُعد')}</Badge>}</b><span className="a-sub">{tx(x.desc)}</span></span>
              <Button variant="outline" size="sm" icon="pencil" onClick={() => setEdit(x.id)}>{t('Edit', 'تعديل')}</Button>
              <ASwitch checked={x.active} onChange={(v) => toggle(x.id, v)} label={x.active ? t('Active', 'مفعل') : t('Off', 'متوقف')} />
            </div>
            {x.id === 'card' && !configured && cardStatus}
          </section>); })}
      </div>
      <button type="button" className="a-link" style={{ justifySelf: 'start', fontSize: 12 }} onClick={() => setConfigured(!configured)}>{t('Kit demo: toggle server configuration', 'للتجربة: تبديل حالة إعداد الخادم')}</button>
      <ADrawer width={600} open={!!m} onClose={() => setEdit(null)} title={m ? tx(m.name) : ''}
        foot={<><Button variant="outline" onClick={() => setEdit(null)}>{t('Cancel', 'إلغاء')}</Button><Button icon="check" onClick={() => { setEdit(null); app.toast('success', t('Payment method saved', 'تم الحفظ')); }}>{t('Save', 'حفظ')}</Button></>}>
        {m && <>
          <ABi label={t('Name', 'الاسم')} en={m.name.en} ar={m.name.ar} />
          <ABi label={t('Description', 'الوصف')} en={m.desc.en} ar={m.desc.ar} />
          <ABi label={t('Customer instructions', 'تعليمات العميل')} multiline rows={3} en={m.ins.en} ar={m.ins.ar} />
          <AGrid cols={2}>
            <Input label={t('Display order', 'الترتيب')} type="number" defaultValue={list.indexOf(m) + 1} dir="ltr" />
            <div style={{ alignSelf: 'end', paddingBottom: 8 }}><ASwitch checked={m.active} onChange={(v) => toggle(m.id, v)} label={t('Active', 'مفعل')} /></div>
          </AGrid>
          {m.id === 'card' && <ACard title={t('Paymob status', 'حالة Paymob')} icon="shield-check">
            {cardStatus}
            <Input label={t('Webhook URL', 'رابط Webhook')} value="https://dots.eg/api/paymob/webhook" readOnly dir="ltr" help={t('Read-only · paste it in Paymob → Developers → Payment integrations', 'للقراءة فقط · الصقه في Paymob ← Developers ← Payment integrations')} />
          </ACard>}
          {m.id === 'instapay' && <AGrid cols={2}><Input label={t('InstaPay address', 'عنوان إنستاباي')} defaultValue="dots@instapay" dir="ltr" /><Input label={t('Hold order for', 'حجز الطلب لمدة')} type="number" defaultValue="24" dir="ltr" trailing={<span style={{ font: '700 13px var(--font-body)' }}>{t('hours', 'ساعة')}</span>} /></AGrid>}
        </>}
      </ADrawer>
    </div>
  );
}

function ASettingsContact() {
  const { t } = useLocale();
  const days = [[t('Sun–Thu', 'الأحد–الخميس'), '09:00', '18:00', true], [t('Saturday', 'السبت'), '11:00', '17:00', true], [t('Friday', 'الجمعة'), '', '', false]];
  return (
    <div style={{ display: 'grid', gap: 16 }}>
      <ACard title={t('Contact', 'التواصل')}>
        <AGrid cols={2}>
          <Input label={t('WhatsApp number', 'رقم واتساب')} icon="message-circle" defaultValue="+20 100 123 4567" dir="ltr" help={t('Used by every WhatsApp button on the site', 'يستخدمه كل زر واتساب في الموقع')} />
          <Input label={t('Hotline', 'الخط الساخن')} icon="phone" defaultValue="19 345" dir="ltr" />
          <Input label={t('Email', 'البريد')} icon="mail" defaultValue="hello@dots.eg" dir="ltr" />
          <Input label={t('Google Maps link', 'رابط خرائط جوجل')} icon="map-pin" defaultValue="https://maps.app.goo.gl/dots-zayed" dir="ltr" />
        </AGrid>
        <ABi label={t('Showroom address', 'عنوان المعرض')} en="Arkan Plaza, Building 3, 26th of July Corridor, Sheikh Zayed, Giza" ar="أركان بلازا، مبنى 3، محور 26 يوليو، الشيخ زايد، الجيزة" />
      </ACard>
      <ACard title={t('Working hours', 'مواعيد العمل')}>
        {days.map(([d, f, to, on]) => (
          <div key={d} style={{ display: 'grid', gridTemplateColumns: 'minmax(110px, 160px) auto minmax(0,1fr) minmax(0,1fr)', gap: 10, alignItems: 'center' }}>
            <b style={{ font: '700 14px var(--font-body)' }}>{d}</b><ASwitch checked={on} onChange={() => {}} />
            <input className="d-input d-input--sm" type="time" defaultValue={f} disabled={!on} dir="ltr" aria-label={t('Opens', 'يفتح')} />
            <input className="d-input d-input--sm" type="time" defaultValue={to} disabled={!on} dir="ltr" aria-label={t('Closes', 'يغلق')} />
          </div>
        ))}
      </ACard>
      <ACard title={t('Social links', 'روابط التواصل الاجتماعي')}>
        <AGrid cols={2}>{[['instagram', 'Instagram', 'instagram.com/dots.eg'], ['facebook', 'Facebook', 'facebook.com/dots.eg'], ['linkedin', 'LinkedIn', 'linkedin.com/company/dots-eg'], ['youtube', 'YouTube', '']].map(([ic, l, v]) => <Input key={l} label={l} icon={ic} defaultValue={v} placeholder="https://" dir="ltr" />)}</AGrid>
      </ACard>
    </div>
  );
}

function ASettingsCommerce() {
  const { t } = useLocale();
  return (
    <div style={{ display: 'grid', gap: 16 }}>
      <ACard title={t('Tax & pricing', 'الضريبة والتسعير')}>
        <AGrid cols={2}>
          <Input label={t('VAT rate', 'نسبة الضريبة')} type="number" defaultValue="14" dir="ltr" trailing={<span style={{ font: '700 13px var(--font-body)' }}>%</span>} />
          <Input label={t('Default low-stock threshold', 'حد التنبيه الافتراضي للمخزون')} type="number" defaultValue="5" dir="ltr" help={t('Used when a variant has no threshold of its own', 'يُستخدم عندما لا يكون للمتغير حد خاص')} />
        </AGrid>
        <ABi label={t('VAT note', 'ملاحظة الضريبة')} en="Prices include 14% VAT" ar="الأسعار شاملة ضريبة القيمة المضافة 14%" />
        <ABi label={t('Project pricing note', 'ملاحظة أسعار المشروعات')} multiline rows={2} en="Ordering for a project? Send your BOQ on WhatsApp for trade pricing." ar="تطلب لمشروع؟ أرسل المقايسة على واتساب لأسعار المشروعات." />
      </ACard>
      <ACard title={t('Product page trust items', 'عناصر الثقة في صفحة المنتج')} action={<span className="a-count">{t('3 rows under the add-to-cart button', '3 صفوف أسفل زر الإضافة للسلة')}</span>}>
        {[['shield-check', 'Official local warranty', 'ضمان محلي رسمي'], ['truck', 'Next-day delivery in Cairo & Giza', 'توصيل اليوم التالي في القاهرة والجيزة'], ['message-circle', 'Engineers on WhatsApp', 'مهندسون على واتساب']].map(([ic, en, ar], i) => (
          <div key={i} className="a-card" style={{ padding: 12, display: 'grid', gap: 10 }}>
            <span className="a-count">#{i + 1}</span>
            <AIconPick label={t('Icon', 'الأيقونة')} value={ic} />
            <ABi label={t('Text', 'النص')} en={en} ar={ar} />
          </div>
        ))}
      </ACard>
      <ACard title={t('Product page tabs', 'تبويبات صفحة المنتج')}>
        <ABi label={t('Delivery text', 'نص التوصيل')} multiline rows={3} en="Next working day in Cairo & Giza, 2–5 working days elsewhere. Free over EGP 5,000." ar="يوم العمل التالي في القاهرة والجيزة، و2–5 أيام عمل لباقي المحافظات. مجاني فوق 5,000 ج.م." />
        <ABi label={t('Returns text', 'نص الاسترجاع')} multiline rows={3} en="14 days to return unused items in their original box. Cut-to-length items can’t be returned." ar="14 يوماً لاسترجاع المنتجات غير المستخدمة بعلبتها الأصلية. المنتجات المقطوعة بالطول لا تُسترجع." />
      </ACard>
    </div>
  );
}

function ASettingsTemplates() {
  const { t, tx, lang, compact } = useLocale();
  const [cur, setCur] = React.useState(A_TEMPLATES[0].id);
  const [vals, setVals] = React.useState(() => Object.fromEntries(A_TEMPLATES.map((x) => [x.id, { en: x.en, ar: x.ar }])));
  const tp = A_TEMPLATES.find((x) => x.id === cur);
  const v = vals[cur];
  const ins = (l, name) => setVals({ ...vals, [cur]: { ...v, [l]: v[l] + '{' + name + '}' } });
  const sample = { product_name: 'Arc LED Pendant Ø450', sku: 'PH-ARC-450-30', product_url: 'dots.eg/p/arc-450', cart_count: '3', cart_total: 'EGP 10,140', cart_lines: '• PH-ARC-450-30 × 1\n• DL-TRM-12-40 × 6', order_no: 'DT-10482', total: 'EGP 9,276', customer_name: 'Ahmed', project_type: 'Villa', location: 'New Cairo', page_title: 'Smart home', page_url: 'dots.eg/services/smart', order_status: lang === 'ar' ? 'قيد التجهيز' : 'Preparing', customer_note: lang === 'ar' ? 'سيصلك الطلب يوم الخميس.' : 'It will reach you on Thursday.', expected_date: '2 Oct', tracking_url: 'dots.eg/track' };
  const fill = (s) => s.replace(/\{(\w+)\}/g, (m, k) => sample[k] || m);
  return (
    <div style={{ display: 'grid', gridTemplateColumns: compact ? 'minmax(0,1fr)' : '240px minmax(0,1fr)', gap: 16, alignItems: 'start' }}>
      <section className="a-card" style={{ padding: 8, display: 'grid', gap: 2 }}>{A_TEMPLATES.map((x) => <button key={x.id} type="button" className="a-side__link" aria-current={x.id === cur ? 'page' : undefined} style={{ height: 'auto', minHeight: 38, paddingBlock: 8, lineHeight: 1.3 }} onClick={() => setCur(x.id)}><Icon name="message-square-text" size={16} />{tx(x.name)}</button>)}</section>
      <ACard title={tx(tp.name)}>
        {['en', 'ar'].map((l) => (
          <div key={l} className="d-field">
            <span className="d-label">{l === 'en' ? 'English' : 'العربية'}</span>
            <textarea className="d-input" rows={4} dir={l === 'ar' ? 'rtl' : 'ltr'} value={v[l]} onChange={(e) => setVals({ ...vals, [cur]: { ...v, [l]: e.target.value } })} />
            <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap', alignItems: 'center' }}><span className="a-count">{t('Insert', 'إدراج')}</span>{tp.vars.map((n) => <button key={n} type="button" className="a-var" onClick={() => ins(l, n)}>{'{' + n + '}'}</button>)}</div>
          </div>
        ))}
        <div className="d-field"><span className="d-label">{t('Preview with sample data', 'معاينة ببيانات تجريبية')}</span><div style={{ background: 'var(--ink-50)', borderRadius: 'var(--radius-md)', padding: 16 }}><div className="a-bubble" dir={lang === 'ar' ? 'rtl' : 'ltr'}>{fill(v[lang])}</div></div></div>
      </ACard>
    </div>
  );
}

function ASettingsFooter() {
  const { t } = useLocale();
  return (
    <ACard title={t('Footer', 'الفوتر')} action={<span className="a-count">{t('Link columns are edited in Page content → Global blocks', 'أعمدة الروابط تُعدل من محتوى الصفحات ← العناصر العامة')}</span>}>
      <ABi label={t('About text', 'نبذة')} multiline rows={3} en="Dots designs, supplies and installs lighting, smart home and electrical systems across Egypt." ar="دوتس تصمم وتورد وتركب أنظمة الإضاءة والمنزل الذكي والكهرباء في كل مصر." />
      <ABi label={t('Payment methods line', 'سطر طرق الدفع')} en="Cash on delivery · Visa · Mastercard · Meeza · InstaPay" ar="الدفع عند الاستلام · فيزا · ماستركارد · ميزة · إنستاباي" />
      <ABi label={t('Copyright', 'حقوق النشر')} en="© 2026 Dots for Lighting & Electrical. All rights reserved." ar="© 2026 دوتس للإضاءة والكهرباء. جميع الحقوق محفوظة." />
    </ACard>
  );
}

function ASettingsSeo() {
  const { t } = useLocale();
  return (
    <ACard title={t('Default SEO', 'إعدادات SEO الافتراضية')} action={<span className="a-count">{t('Used when a page has no SEO of its own', 'تُستخدم عندما لا يكون للصفحة إعدادات خاصة')}</span>}>
      <ABi label={t('Default meta title', 'عنوان الصفحة الافتراضي')} counter={60} en="Dots — Lighting, Smart Home & Electrical in Egypt" ar="دوتس — إضاءة ومنزل ذكي وكهرباء في مصر" />
      <ABi label={t('Default meta description', 'وصف الصفحة الافتراضي')} counter={160} multiline rows={3} en="10,000+ lighting, smart home and electrical products from 8 global brands, designed and installed by Dots engineers." ar="أكثر من 10,000 منتج إضاءة ومنزل ذكي وكهرباء من 8 علامات عالمية، بتصميم وتركيب مهندسي دوتس." />
      <AImage label={t('Default share image', 'صورة المشاركة الافتراضية')} src={kPh(0, 1200)} ratio="1200 / 630" help="og-default-1200x630.jpg" />
    </ACard>
  );
}

function ASettings({ app }) {
  const { t } = useLocale();
  const [tab, setTab] = React.useState('contact');
  return (
    <div className="a-page">
      <AHead title={t('Settings', 'الإعدادات')}><Button icon="check" onClick={() => app.toast('success', t('Settings saved', 'تم حفظ الإعدادات'))}>{t('Save changes', 'حفظ التغييرات')}</Button></AHead>
      <div className="a-card" style={{ padding: '0 18px' }}><Tabs value={tab} onChange={setTab} tabs={[{ id: 'contact', label: t('Contact', 'التواصل') }, { id: 'commerce', label: t('Commerce', 'التجارة') }, { id: 'wa', label: t('WhatsApp templates', 'قوالب واتساب') }, { id: 'footer', label: t('Footer', 'الفوتر') }, { id: 'seo', label: 'SEO' }]} /></div>
      {tab === 'contact' && <ASettingsContact />}
      {tab === 'commerce' && <ASettingsCommerce />}
      {tab === 'wa' && <ASettingsTemplates />}
      {tab === 'footer' && <ASettingsFooter />}
      {tab === 'seo' && <ASettingsSeo />}
    </div>
  );
}

;


/* Services + Projects (list + edit). */
function AServices({ app }) {
  const { t, tx } = useLocale();
  const [rows, setRows] = React.useState(A_SERVICES);
  const set = (id, k, v) => setRows(rows.map((x) => (x.id === id ? { ...x, [k]: v } : x)));
  return (
    <AList title={t('Services', 'الخدمات')} sub={t('Shown on /services, the homepage and the footer', 'تظهر في صفحة الخدمات والرئيسية والفوتر')}
      actions={<Button icon="plus" onClick={() => app.go('service')}>{t('Add service', 'إضافة خدمة')}</Button>}
      rows={rows} searchText={(s) => s.num + s.title.en + s.title.ar} onRow={(s) => app.go('service', { id: s.id })} emptyIcon="wrench"
      filters={[
        { id: 'a', label: t('Any status', 'كل الحالات'), options: [{ value: 'on', label: t('Active', 'مفعل') }, { value: 'off', label: t('Inactive', 'غير مفعل') }], test: (s, v) => s.active === (v === 'on') },
        { id: 'f', label: t('Footer: any', 'الفوتر: الكل'), options: [{ value: 'y', label: t('In footer', 'في الفوتر') }, { value: 'n', label: t('Not in footer', 'ليس في الفوتر') }], test: (s, v) => s.footer === (v === 'y') },
      ]}
      columns={[
        { id: 'num', label: '#', shrink: true, sortVal: (s) => s.num, render: (s) => <span style={{ font: '700 18px var(--font-display)' }}>{s.num}</span> },
        { id: 'n', label: t('Service', 'الخدمة'), primary: true, sortVal: (s) => tx(s.title), render: (s) => <span style={{ display: 'flex', alignItems: 'center', gap: 12 }}><ImageFrame src={kPh(s.img, 200)} style={{ width: 56, height: 40, borderRadius: 6, flex: 'none' }} /><span style={{ minWidth: 0 }}><b style={{ fontWeight: 700 }}>{tx(s.title)}</b><span className="a-sub" style={{ maxWidth: 360, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{tx(s.excerpt)}</span></span></span> },
        { id: 'i', label: t('Included items', 'البنود المشمولة'), num: true, sortVal: (s) => s.included, render: (s) => s.included },
        { id: 'f', label: t('Footer', 'الفوتر'), render: (s) => <ASwitch checked={s.footer} onChange={(v) => set(s.id, 'footer', v)} /> },
        { id: 'a', label: t('Status', 'الحالة'), render: (s) => <APill kind="active" v={s.active ? 'on' : 'off'} /> },
        { id: 'x', label: '', shrink: true, hideMobile: true, render: (s) => <ARowActions onEdit={() => app.go('service', { id: s.id })} items={[{ icon: 'external-link', label: t('View on site', 'عرض في الموقع') }, { icon: s.active ? 'eye-off' : 'eye', label: s.active ? t('Deactivate', 'إيقاف') : t('Activate', 'تفعيل'), onClick: () => set(s.id, 'active', !s.active) }, '-', { icon: 'trash-2', label: t('Delete', 'حذف'), danger: true, onClick: () => setRows(rows.filter((x) => x.id !== s.id)) }]} /> },
      ]} />
  );
}

function AServiceEdit({ app, route }) {
  const { t, tx } = useLocale();
  const isNew = !route.id;
  const s = A_SERVICES.find((x) => x.id === route.id) || A_SERVICES[0];
  const [sw, setSw] = React.useState({ active: isNew ? true : s.active, footer: isNew ? false : s.footer });
  return (
    <AEditor crumbs={[{ label: t('Services', 'الخدمات'), onClick: () => app.go('services') }, { label: isNew ? t('New service', 'خدمة جديدة') : tx(s.title) }]}
      title={isNew ? t('New service', 'خدمة جديدة') : tx(s.title)} extra={<APill kind="active" v={sw.active ? 'on' : 'off'} />}
      actions={<><Button variant="outline" icon="eye">{t('Preview', 'معاينة')}</Button><Button icon="check" onClick={() => app.toast('success', t('Service saved', 'تم حفظ الخدمة'))}>{t('Save changes', 'حفظ التغييرات')}</Button></>}
      aside={<>
        <ACard title={t('Visibility', 'الظهور')}>
          <ASwitch checked={sw.active} onChange={(v) => setSw({ ...sw, active: v })} label={t('Active', 'مفعل')} />
          <ASwitch checked={sw.footer} onChange={(v) => setSw({ ...sw, footer: v })} label={t('Show in footer', 'يظهر في الفوتر')} />
          <Input label={t('Sort order', 'الترتيب')} type="number" defaultValue={isNew ? 5 : +s.num} dir="ltr" />
        </ACard>
        <ACard title={t('Main image', 'الصورة الرئيسية')}><AImage src={isNew ? null : kPh(s.img, 900)} ratio="4 / 3" help={s.id + '-main.jpg'} /></ACard>
      </>}>
      <ACard title={t('Basic info', 'البيانات الأساسية')}>
        <div style={{ maxWidth: 160 }}><Input label={t('Number', 'الرقم')} defaultValue={isNew ? '05' : s.num} dir="ltr" /></div>
        <ABi label={t('Title', 'العنوان')} en={isNew ? '' : s.title.en} ar={isNew ? '' : s.title.ar} />
        <ASlugs slug={isNew ? { en: '', ar: '' } : { en: s.id, ar: s.title.ar.replace(/\s+/g, '-') }} path="/services" />
        <ABi label={t('Card excerpt', 'نص البطاقة')} multiline rows={2} en={isNew ? '' : s.excerpt.en} ar={isNew ? '' : s.excerpt.ar} />
        <ABi label={t('Header description', 'وصف الواجهة')} multiline rows={3} en={isNew ? '' : 'We plan every room around how it’s used — then hand you a drawing your electrician can follow and a schedule you can buy from.'} ar={isNew ? '' : 'نخطط كل غرفة حسب استخدامها — ثم نسلمك رسماً يتبعه الكهربائي وجدولاً تشتري منه.'} />
        <ABi label={t('Duration text', 'نص المدة')} en={isNew ? '' : 'From brief to handover in 3–6 weeks'} ar={isNew ? '' : 'من الطلب للتسليم في 3–6 أسابيع'} />
      </ACard>
      <ACard title={t('What’s included', 'ما الذي تشمله الخدمة')}>
        <ABi label={t('Section title', 'عنوان القسم')} en="What’s included" ar="ما الذي تشمله الخدمة" />
        <ARepeat initial={(isNew ? [] : A_SERVICE_INCLUDED.slice(0, s.included)).map((x) => ({ t: x }))} blank={{ t: aM('', '') }} addLabel={t('Add item', 'إضافة بند')} render={(it) => <ABi label={t('Item', 'البند')} en={it.t.en} ar={it.t.ar} />} />
      </ACard>
      <ACard title={t('Gallery', 'معرض الصور')}><AGallery photos={isNew ? [] : [9, 13, 28]} help={t('3 images · drag to reorder', '3 صور · اسحب لإعادة الترتيب')} /></ACard>
      <ACard title={t('Service FAQ', 'الأسئلة الشائعة للخدمة')}>
        <ARepeat initial={isNew ? [] : A_FAQ} blank={{ q: aM('', ''), a: aM('', '') }} addLabel={t('Add question', 'إضافة سؤال')} render={(it) => <><ABi label={t('Question', 'السؤال')} en={it.q.en} ar={it.q.ar} /><ABi label={t('Answer', 'الإجابة')} multiline rows={2} en={it.a.en} ar={it.a.ar} /></>} />
      </ACard>
      <ASeo title={isNew ? undefined : aM(s.title.en + ' in Egypt | Dots', s.title.ar + ' في مصر | دوتس')} desc={isNew ? undefined : s.excerpt} />
    </AEditor>
  );
}

function AProjects({ app }) {
  const { t, tx } = useLocale();
  const [rows, setRows] = React.useState(A_PROJECTS);
  return (
    <AList title={t('Projects', 'المشروعات')} sub={t('Current projects show live progress; completed ones show the case study', 'المشروعات الجارية تعرض التقدم؛ والمكتملة تعرض دراسة الحالة')}
      actions={<Button icon="plus" onClick={() => app.go('project')}>{t('Add project', 'إضافة مشروع')}</Button>}
      tabs={[{ id: 'current', label: t('Current', 'الجارية'), test: (p) => p.st === 'current' }, { id: 'completed', label: t('Completed', 'المكتملة'), test: (p) => p.st === 'completed' }]}
      rows={rows} searchText={(p) => p.title.en + p.title.ar + p.loc.en + p.loc.ar + p.client} onRow={(p) => app.go('project', { id: p.id })} emptyIcon="building-2"
      filters={[
        { id: 'ty', label: t('All types', 'كل الأنواع'), options: Object.entries(A_PROJECT_TYPES).map(([v, l]) => ({ value: v, label: tx(l) })), test: (p, v) => p.type === v },
        { id: 'f', label: t('Featured: any', 'المميزة: الكل'), options: [{ value: 'y', label: t('Featured', 'مميز') }, { value: 'n', label: t('Not featured', 'غير مميز') }], test: (p, v) => p.featured === (v === 'y') },
      ]}
      columns={[
        { id: 'n', label: t('Project', 'المشروع'), primary: true, sortVal: (p) => tx(p.title), render: (p) => <span style={{ display: 'flex', alignItems: 'center', gap: 12 }}><ImageFrame src={kPh(p.img, 200)} style={{ width: 56, height: 40, borderRadius: 6, flex: 'none' }} /><span><b style={{ fontWeight: 700 }}>{tx(p.title)}</b><span className="a-sub">{tx(p.loc)}{p.client ? ' · ' + p.client : ''}</span></span></span> },
        { id: 'ty', label: t('Type', 'النوع'), sortVal: (p) => tx(A_PROJECT_TYPES[p.type]), render: (p) => tx(A_PROJECT_TYPES[p.type]) },
        { id: 'pr', label: t('Progress', 'التقدم'), sortVal: (p) => p.progress, render: (p) => p.st === 'completed' ? <span style={{ fontWeight: 700 }}>{p.year}</span> : <span style={{ display: 'grid', gap: 4, minWidth: 130 }}><span style={{ display: 'flex', justifyContent: 'space-between', gap: 8, font: '700 12px var(--font-body)' }}><span>{tx(A_PHASES[p.stage])}</span><span>{p.progress}%</span></span><span style={{ height: 4, borderRadius: 2, background: 'var(--ink-150)', position: 'relative', overflow: 'hidden' }}><span style={{ position: 'absolute', insetBlock: 0, insetInlineStart: 0, width: p.progress + '%', background: 'var(--accent)' }} /></span></span> },
        { id: 'f', label: t('Featured', 'مميز'), render: (p) => <ASwitch checked={p.featured} onChange={(v) => setRows(rows.map((x) => (x.id === p.id ? { ...x, featured: v } : x)))} /> },
        { id: 'a', label: t('Status', 'الحالة'), render: (p) => <APill kind="active" v={p.active ? 'on' : 'off'} /> },
        { id: 'x', label: '', shrink: true, hideMobile: true, render: (p) => <ARowActions onEdit={() => app.go('project', { id: p.id })} items={[{ icon: 'external-link', label: t('View on site', 'عرض في الموقع') }, p.st === 'current' && { icon: 'circle-check', label: t('Mark completed', 'تحديد كمكتمل'), onClick: () => setRows(rows.map((x) => (x.id === p.id ? { ...x, st: 'completed', progress: 100, stage: 4, year: 2026 } : x))) }, '-', { icon: 'trash-2', label: t('Delete', 'حذف'), danger: true, onClick: () => setRows(rows.filter((x) => x.id !== p.id)) }].filter(Boolean)} /> },
      ]} />
  );
}

function AProjectEdit({ app, route }) {
  const { t, tx } = useLocale();
  const isNew = !route.id;
  const p = A_PROJECTS.find((x) => x.id === route.id) || A_PROJECTS[0];
  const [st, setSt] = React.useState(isNew ? 'current' : p.st);
  const [stage, setStage] = React.useState(isNew ? 0 : p.stage);
  const [prog, setProg] = React.useState(isNew ? 0 : p.progress);
  const [sw, setSw] = React.useState({ featured: isNew ? false : p.featured, active: isNew ? true : p.active });
  const done = st === 'completed';
  return (
    <AEditor crumbs={[{ label: t('Projects', 'المشروعات'), onClick: () => app.go('projects') }, { label: isNew ? t('New project', 'مشروع جديد') : tx(p.title) }]}
      title={isNew ? t('New project', 'مشروع جديد') : tx(p.title)} extra={<APill kind="project" v={st} />}
      actions={<><Button variant="outline" icon="eye">{t('Preview', 'معاينة')}</Button><Button icon="check" onClick={() => app.toast('success', t('Project saved', 'تم حفظ المشروع'))}>{t('Save changes', 'حفظ التغييرات')}</Button></>}
      aside={<>
        <ACard title={t('Status', 'الحالة')}>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0,1fr))', gap: 8 }}>
            <RadioCard name="pj-st" value="current" checked={st === 'current'} onChange={setSt} title={t('Current', 'جاري')} />
            <RadioCard name="pj-st" value="completed" checked={done} onChange={(v) => { setSt(v); setStage(4); setProg(100); }} title={t('Completed', 'مكتمل')} />
          </div>
          <ASwitch checked={sw.featured} onChange={(v) => setSw({ ...sw, featured: v })} label={t('Featured', 'مميز')} />
          <ASwitch checked={sw.active} onChange={(v) => setSw({ ...sw, active: v })} label={t('Active', 'مفعل')} />
          <Input label={t('Sort order', 'الترتيب')} type="number" defaultValue="1" dir="ltr" />
        </ACard>
        <ACard title={t('Cover image', 'صورة الغلاف')}><AImage src={isNew ? null : kPh(p.img, 900)} ratio="4 / 3" help={p.id + '-cover.jpg'} /></ACard>
      </>}>
      <ACard title={t('Project', 'المشروع')}>
        <ABi label={t('Title', 'العنوان')} en={isNew ? '' : p.title.en} ar={isNew ? '' : p.title.ar} />
        <ASlugs slug={isNew ? { en: '', ar: '' } : { en: p.title.en.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''), ar: p.title.ar.replace(/\s+/g, '-') }} path="/projects" />
        <AGrid cols={2}>
          <Select label={t('Type', 'النوع')} options={Object.entries(A_PROJECT_TYPES).map(([v, l]) => ({ value: v, label: tx(l) }))} defaultValue={isNew ? 'res' : p.type} />
          <Input label={t('Client', 'العميل')} optional defaultValue={isNew ? '' : p.client} />
        </AGrid>
        <ABi label={t('Location', 'الموقع')} en={isNew ? '' : p.loc.en} ar={isNew ? '' : p.loc.ar} />
        <ABi label={t('Scope summary', 'ملخص النطاق')} multiline rows={2} en={isNew ? '' : 'Lighting design, KNX control and full supply for 14 rooms and the garden.'} ar={isNew ? '' : 'تصميم إضاءة وتحكم KNX وتوريد كامل لـ 14 غرفة والحديقة.'} />
      </ACard>
      <ACard title={t('Timeline & progress', 'الجدول الزمني والتقدم')}>
        <AGrid cols={3}>
          <Input label={t('Start date', 'تاريخ البدء')} type="date" defaultValue={isNew ? '' : p.start || '2025-03-01'} dir="ltr" />
          <Input label={t('Expected completion', 'الانتهاء المتوقع')} type="date" defaultValue={isNew ? '' : p.expected || ''} disabled={done} dir="ltr" />
          <Input label={t('Completion year', 'سنة الانتهاء')} type="number" defaultValue={done ? p.year || 2026 : ''} disabled={!done} dir="ltr" />
        </AGrid>
        {!done && <>
          <div className="d-field">
            <span className="d-label">{t('Stage', 'المرحلة')}</span>
            <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>{A_PHASES.map((ph, i) => <Chip key={i} active={stage === i} aria-pressed={stage === i} onClick={() => setStage(i)}>{i + 1}. {tx(ph)}</Chip>)}</div>
          </div>
          <Progress value={prog} steps={A_PHASES} current={stage} />
          <AGrid cols={2}>
            <Input label={t('Progress', 'نسبة التقدم')} type="number" value={prog} onChange={(e) => setProg(Math.max(0, Math.min(100, +e.target.value)))} dir="ltr" trailing={<span style={{ font: '700 13px var(--font-body)' }}>%</span>} />
            <div />
          </AGrid>
          <ABi label={t('Phase label', 'وصف المرحلة')} en={isNew ? '' : 'Second-floor fixtures going in this week'} ar={isNew ? '' : 'تركيب وحدات الدور الثاني هذا الأسبوع'} />
        </>}
      </ACard>
      <ACard title={t('About', 'عن المشروع')}>
        <ARich en="<p>The owners wanted the villa to feel calm at night without a wall of switches. We designed three lighting layers per room and tied them to KNX scenes.</p><h3>Key choices</h3><ul><li>2700K throughout living spaces</li><li>Trimless downlights in all ceilings</li></ul>" ar="<p>أراد الملاك أن تبدو الفيلا هادئة ليلاً بدون حائط من المفاتيح. صممنا ثلاث طبقات إضاءة لكل غرفة وربطناها بمشاهد KNX.</p><h3>أهم الاختيارات</h3><ul><li>2700K في كل مساحات المعيشة</li><li>سبوتات بدون إطار في كل الأسقف</li></ul>" minH={180} />
      </ACard>
      {done && <ACard title={t('Case study', 'دراسة الحالة')}>
        <ABi label={t('Challenge', 'التحدي')} multiline rows={3} en="Low ceilings and a tight handover date." ar="أسقف منخفضة وموعد تسليم ضيق." />
        <ABi label={t('Solution', 'الحل')} multiline rows={3} en="Shallow 35 mm downlights and pre-programmed KNX scenes tested off-site." ar="سبوتات بعمق 35 مم ومشاهد KNX مبرمجة ومختبرة مسبقاً." />
        <ABi label={t('Result', 'النتيجة')} multiline rows={3} en="Handed over two weeks early with 38% lower lighting load." ar="تم التسليم قبل الموعد بأسبوعين مع خفض أحمال الإضاءة 38%." />
      </ACard>}
      <ACard title={t('Gallery', 'معرض الصور')}><AGallery photos={isNew ? [] : [p.img, 9, 13, 28, 22]} /></ACard>
      <ACard title={t('Products & services used', 'المنتجات والخدمات المستخدمة')}>
        <AProductPicker label={t('Products used', 'المنتجات المستخدمة')} initial={isNew ? [] : ['p1', 'p2', 'p3']} />
        <AChips label={t('Services used', 'الخدمات المستخدمة')} options={A_SERVICES.map((s) => ({ value: s.id, label: s.title }))} initial={isNew ? [] : ['design', 'smart']} />
      </ACard>
      <ASeo title={isNew ? undefined : aM(p.title.en + ' | Dots projects', p.title.ar + ' | مشروعات دوتس')} desc={isNew ? undefined : aM('Lighting and smart home by Dots in ' + p.loc.en + '.', 'إضاءة ومنزل ذكي من دوتس في ' + p.loc.ar + '.')} />
    </AEditor>
  );
}

;


/* Blog posts, post editor, blog categories & tags (inline edit). */
function ABlog({ app }) {
  const { t, tx } = useLocale();
  const [rows, setRows] = React.useState(A_POSTS);
  const cat = (id) => A_BLOG_CATS.find((c) => c.id === id);
  return (
    <AList title={t('Blog', 'المدونة')} sub={t('Articles, guides and project stories', 'مقالات وأدلة وقصص مشروعات')}
      actions={<><Button variant="outline" icon="tags" onClick={() => app.go('blogtax')}>{t('Categories & tags', 'الأقسام والوسوم')}</Button><Button icon="plus" onClick={() => app.go('post')}>{t('New post', 'مقال جديد')}</Button></>}
      tabs={[{ id: 'all', label: t('All', 'الكل') }, ...Object.entries(A_STATUS.post).map(([k, s]) => ({ id: k, label: tx(s[1]), test: (p) => p.status === k }))]}
      rows={rows} searchText={(p) => p.title.en + p.title.ar} onRow={(p) => app.go('post', { id: p.id })} emptyIcon="newspaper"
      filters={[
        { id: 'c', label: t('All categories', 'كل الأقسام'), options: A_BLOG_CATS.map((c) => ({ value: c.id, label: tx(c.name) })), test: (p, v) => p.cat === v },
        { id: 'f', label: t('Featured: any', 'المميزة: الكل'), options: [{ value: 'y', label: t('Featured', 'مميز') }, { value: 'n', label: t('Not featured', 'غير مميز') }], test: (p, v) => p.featured === (v === 'y') },
      ]}
      selectable={[{ icon: 'eye', label: t('Publish', 'نشر'), onClick: (ids) => setRows(rows.map((x) => (ids.includes(x.id) ? { ...x, status: 'published' } : x))) }, { icon: 'file', label: t('Move to draft', 'نقل للمسودة'), onClick: (ids) => setRows(rows.map((x) => (ids.includes(x.id) ? { ...x, status: 'draft' } : x))) }]}
      columns={[
        { id: 'n', label: t('Post', 'المقال'), primary: true, sortVal: (p) => tx(p.title), render: (p) => <span style={{ display: 'flex', alignItems: 'center', gap: 12 }}><ImageFrame src={kPh(p.img, 200)} style={{ width: 64, height: 44, borderRadius: 6, flex: 'none' }} /><span style={{ minWidth: 0 }}><b style={{ fontWeight: 700 }}>{tx(p.title)}</b><span className="a-sub">{p.read} {t('min read', 'دقائق قراءة')}</span></span></span> },
        { id: 'c', label: t('Category', 'القسم'), sortVal: (p) => tx(cat(p.cat).name), render: (p) => tx(cat(p.cat).name) },
        { id: 's', label: t('Status', 'الحالة'), render: (p) => <APill kind="post" v={p.status} /> },
        { id: 'f', label: t('Featured', 'مميز'), render: (p) => <ASwitch checked={p.featured} onChange={(v) => setRows(rows.map((x) => (x.id === p.id ? { ...x, featured: v } : x)))} /> },
        { id: 'd', label: t('Publish date', 'تاريخ النشر'), sortVal: (p) => p.date || 'z', render: (p) => p.date ? <span className="a-mono">{p.date}</span> : <span className="a-count">—</span> },
        { id: 'x', label: '', shrink: true, hideMobile: true, render: (p) => <ARowActions onEdit={() => app.go('post', { id: p.id })} items={[{ icon: 'external-link', label: t('View on site', 'عرض في الموقع') }, { icon: 'copy', label: t('Duplicate', 'تكرار') }, '-', { icon: 'trash-2', label: t('Delete', 'حذف'), danger: true, onClick: () => setRows(rows.filter((x) => x.id !== p.id)) }]} /> },
      ]} />
  );
}

function APostEdit({ app, route }) {
  const { t, tx } = useLocale();
  const isNew = !route.id;
  const p = A_POSTS.find((x) => x.id === route.id) || A_POSTS[0];
  const [status, setStatus] = React.useState(isNew ? 'draft' : p.status);
  const [featured, setFeatured] = React.useState(isNew ? false : p.featured);
  return (
    <AEditor crumbs={[{ label: t('Blog', 'المدونة'), onClick: () => app.go('blog') }, { label: isNew ? t('New post', 'مقال جديد') : tx(p.title) }]}
      title={isNew ? t('New post', 'مقال جديد') : tx(p.title)} extra={<APill kind="post" v={status} />}
      actions={<><Button variant="outline" icon="eye">{t('Preview', 'معاينة')}</Button><Button icon="check" onClick={() => app.toast('success', status === 'scheduled' ? t('Post scheduled', 'تمت جدولة المقال') : t('Post saved', 'تم حفظ المقال'))}>{status === 'published' ? t('Update', 'تحديث') : t('Save', 'حفظ')}</Button></>}
      aside={<>
        <ACard title={t('Publishing', 'النشر')}>
          <Select label={t('Status', 'الحالة')} value={status} onChange={(e) => setStatus(e.target.value)} options={Object.entries(A_STATUS.post).map(([v, s]) => ({ value: v, label: tx(s[1]) }))} />
          <Input label={t('Publish date', 'تاريخ النشر')} type="datetime-local" defaultValue={isNew || !p.date ? '' : p.date + 'T10:00'} dir="ltr" help={status === 'scheduled' ? t('Goes live automatically at this time', 'يُنشر تلقائياً في هذا الوقت') : undefined} />
          <ASwitch checked={featured} onChange={setFeatured} label={t('Featured', 'مميز')} help={t('Shown first on /blog and on the homepage', 'يظهر أولاً في المدونة والرئيسية')} />
        </ACard>
        <ACard title={t('Organise', 'التصنيف')}>
          <Select label={t('Category', 'القسم')} options={A_BLOG_CATS.map((c) => ({ value: c.id, label: tx(c.name) }))} defaultValue={isNew ? 'guides' : p.cat} />
          <AChips label={t('Tags', 'الوسوم')} options={A_TAGS.map((x) => ({ value: x.id, label: x.name }))} initial={isNew ? [] : p.tags} />
          <Input label={t('Read time', 'مدة القراءة')} type="number" defaultValue={isNew ? '' : p.read} dir="ltr" trailing={<span style={{ font: '700 13px var(--font-body)' }}>{t('min', 'دقيقة')}</span>} />
        </ACard>
        <ACard title={t('Author', 'الكاتب')} action={<span className="a-count">{t('Optional', 'اختياري')}</span>}>
          <ABi label={t('Name', 'الاسم')} en={isNew ? '' : 'Eng. Walid Sherif'} ar={isNew ? '' : 'م. وليد شريف'} />
          <ABi label={t('Role', 'الوظيفة')} en={isNew ? '' : 'Lighting design lead'} ar={isNew ? '' : 'رئيس تصميم الإضاءة'} />
          <AImage label={t('Photo', 'الصورة')} src={isNew ? null : kPh(31, 300)} ratio="1 / 1" help="walid.jpg" />
        </ACard>
      </>}>
      <ACard title={t('Post', 'المقال')}>
        <ABi label={t('Title', 'العنوان')} en={isNew ? '' : p.title.en} ar={isNew ? '' : p.title.ar} />
        <ASlugs slug={isNew ? { en: '', ar: '' } : { en: p.title.en.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''), ar: p.title.ar.replace(/[\s؟]+/g, '-') }} path="/blog" />
        <ABi label={t('Excerpt', 'المقتطف')} counter={180} multiline rows={2} en={isNew ? '' : 'Ambient, task and accent: how to layer kitchen light so it works for cooking and for dinner.'} ar={isNew ? '' : 'عامة ووظيفية وتجميلية: كيف توزع إضاءة المطبخ لتناسب الطبخ والعشاء.'} />
        <AImage label={t('Cover image', 'صورة الغلاف')} src={isNew ? null : kPh(p.img, 1200)} ratio="16 / 9" help={p.id + '-cover.jpg · 1600×900'} />
      </ACard>
      <ACard title={t('Body', 'المحتوى')}>
        <ARich minH={320} en={isNew ? '' : '<p>A kitchen needs three kinds of light. Get the layers right and you’ll never need the “big light” again.</p><h2>1. Ambient</h2><p>Trimless downlights on a 1.2 m grid give even, glare-free light.</p><h2>2. Task</h2><ul><li>LED strip under every wall cabinet</li><li>2700K–3000K, CRI 90+</li></ul><blockquote>Put task lighting on its own circuit — you’ll use it more than you think.</blockquote><div class="d-callout">Send us your kitchen plan on WhatsApp and we’ll mark the fixture positions for free.</div>'} ar={isNew ? '' : '<p>يحتاج المطبخ ثلاثة أنواع من الإضاءة. وزّعها صح ولن تحتاج "النور الكبير" مرة أخرى.</p><h2>1. الإضاءة العامة</h2><p>سبوتات بدون إطار على شبكة 1.2 م تعطي إضاءة متساوية بدون انبهار.</p><h2>2. الإضاءة الوظيفية</h2><ul><li>شريط ليد أسفل كل دولاب علوي</li><li>2700K–3000K، CRI 90+</li></ul><blockquote>ضع الإضاءة الوظيفية على دائرة منفصلة — ستستخدمها أكثر مما تتوقع.</blockquote><div class="d-callout">أرسل لنا مخطط مطبخك على واتساب وسنحدد أماكن الوحدات مجاناً.</div>'} />
      </ACard>
      <ACard title={t('Products in this article', 'منتجات في هذا المقال')}><AProductPicker initial={isNew ? [] : ['p2', 'p7']} /></ACard>
      <ASeo title={isNew ? undefined : aM(p.title.en + ' | Dots blog', p.title.ar + ' | مدونة دوتس')} desc={isNew ? undefined : aM('Ambient, task and accent: how to layer kitchen light.', 'عامة ووظيفية وتجميلية: كيف توزع إضاءة المطبخ.')} />
    </AEditor>
  );
}

function ATaxTable({ initial, kind, app }) {
  const { t } = useLocale();
  const [rows, setRows] = React.useState(initial.map((x) => ({ ...x, en: x.name.en, ar: x.name.ar })));
  const [q, setQ] = React.useState('');
  const [sort, setSort] = React.useState({ id: 'o', dir: 1 });
  const [page, setPage] = React.useState(1);
  const per = 6;
  const upd = (id, k, v) => setRows(rows.map((x) => (x.id === id ? { ...x, [k]: v } : x)));
  const key = { o: (x) => +x.order, n: (x) => x.en.toLowerCase(), c: (x) => x.count };
  const list = rows.filter((x) => !q || (x.en + x.ar + x.slug).toLowerCase().includes(q.toLowerCase())).sort((a, b) => { const x = key[sort.id](a), y = key[sort.id](b); return (x > y ? 1 : x < y ? -1 : 0) * sort.dir; });
  const shown = list.slice((page - 1) * per, page * per);
  const inp = (x, k, dir, w) => <input className="d-input d-input--sm" style={{ width: w, minWidth: 0, fontFamily: k === 'slug' ? 'var(--font-mono)' : undefined, fontSize: k === 'slug' ? 12 : undefined }} type={k === 'order' ? 'number' : 'text'} value={x[k]} dir={dir} onChange={(e) => upd(x.id, k, e.target.value)} onClick={(e) => e.stopPropagation()} aria-label={k} />;
  return (
    <section className="a-card">
      <div className="a-bar">
        <Input className="a-grow" size="sm" icon="search" placeholder={t('Search…', 'بحث…')} value={q} onChange={(e) => { setQ(e.target.value); setPage(1); }} aria-label={t('Search', 'بحث')} />
        <Button size="sm" icon="plus" onClick={() => { setRows([{ id: 'new' + Date.now(), en: '', ar: '', slug: '', order: rows.length + 1, count: 0, name: aM('', '') }, ...rows]); setQ(''); setSort({ id: 'c', dir: 1 }); setPage(1); }}>{kind === 'cat' ? t('Add category', 'إضافة قسم') : t('Add tag', 'إضافة وسم')}</Button>
      </div>
      <ATable rows={shown} sort={sort} onSort={setSort}
        empty={<AEmpty icon="tags" title={t('Nothing matches this search', 'لا توجد نتائج')} action={<Button size="sm" variant="outline" onClick={() => setQ('')}>{t('Clear search', 'مسح البحث')}</Button>} />}
        columns={[
          { id: 'n', label: t('Name', 'الاسم') + ' · EN', sort: true, primary: true, render: (x) => inp(x, 'en', 'ltr', '100%') },
          { id: 'ar', label: t('Name', 'الاسم') + ' · ' + t('AR', 'عربي'), render: (x) => inp(x, 'ar', 'rtl', '100%') },
          { id: 's', label: t('Slug', 'الرابط'), render: (x) => inp(x, 'slug', 'ltr', 150) },
          { id: 'o', label: t('Order', 'الترتيب'), sort: true, render: (x) => inp(x, 'order', 'ltr', 70) },
          { id: 'c', label: t('Posts', 'المقالات'), sort: true, num: true, render: (x) => x.count },
          { id: 'x', label: '', shrink: true, render: (x) => <span style={{ display: 'inline-flex' }}><IconButton icon="check" size="sm" label={t('Save', 'حفظ')} onClick={() => app.toast('success', t('Saved', 'تم الحفظ'), x.en)} /><IconButton icon="trash-2" size="sm" label={t('Delete', 'حذف')} onClick={() => setRows(rows.filter((y) => y.id !== x.id))} /></span> },
        ]} />
      {list.length > 0 && <div className="a-card__foot"><Pagination page={page} total={Math.max(1, Math.ceil(list.length / per))} onChange={setPage} count={list.length} perPage={per} /></div>}
    </section>
  );
}

function ABlogTax({ app }) {
  const { t } = useLocale();
  const [tab, setTab] = React.useState('cat');
  return (
    <div className="a-page">
      <AHead crumbs={[{ label: t('Blog', 'المدونة'), onClick: () => app.go('blog') }, { label: t('Categories & tags', 'الأقسام والوسوم') }]} title={t('Categories & tags', 'الأقسام والوسوم')} sub={t('Edit inline · changes save per row', 'تعديل مباشر · الحفظ لكل صف')} />
      <div className="a-card" style={{ padding: '0 18px' }}><Tabs value={tab} onChange={setTab} tabs={[{ id: 'cat', label: t('Categories', 'الأقسام'), count: A_BLOG_CATS.length }, { id: 'tag', label: t('Tags', 'الوسوم'), count: A_TAGS.length }]} /></div>
      {tab === 'cat' ? <ATaxTable key="cat" kind="cat" initial={A_BLOG_CATS} app={app} /> : <ATaxTable key="tag" kind="tag" initial={A_TAGS} app={app} />}
    </div>
  );
}

;


/* Page content (per-page collapsible sections) + Content pages (list + editor). */
function APField({ f }) {
  const { t } = useLocale();
  const [k, a, b, c, d] = f;
  if (k === 'bi') return <ABi label={a} en={b} ar={c} />;
  if (k === 'ml') return <ABi label={a} en={b} ar={c} multiline rows={3} />;
  if (k === 'img') return <AImage label={a} src={kPh(b, 900)} ratio={c || '16 / 9'} help={d} />;
  if (k === 'video') return <AVideo label={a} file={b} poster={kPh(c, 900)} />;
  if (k === 'btn') return <ABtn label={a} en={b} ar={c} link={d} />;
  if (k === 'icon') return <AIconPick label={a} value={b} />;
  if (k === 'text') return <Input label={a} defaultValue={b} dir="ltr" />;
  if (k === 'row') return <AGrid cols={a.length}>{a.map((x, i) => <APField key={i} f={x} />)}</AGrid>;
  if (k === 'items') return (
    <div className="d-field">
      <span className="d-label">{a}</span>
      <div style={{ display: 'grid', gap: 10 }}>{b.map((fs, i) => (
        <div key={i} className="a-card" style={{ padding: 12, display: 'grid', gap: 10, background: 'var(--ink-50)' }}>
          <span className="a-count">#{i + 1}</span>
          {fs.map((x, j) => <APField key={j} f={x} />)}
        </div>))}</div>
    </div>
  );
  if (k === 'faq') return (
    <div className="d-field"><span className="d-label">{a}</span>
      <ARepeat initial={b} blank={{ q: aM('', ''), a: aM('', '') }} addLabel={t('Add question', 'إضافة سؤال')} render={(it) => <><ABi label={t('Question', 'السؤال')} en={it.q.en} ar={it.q.ar} /><ABi label={t('Answer', 'الإجابة')} multiline rows={2} en={it.a.en} ar={it.a.ar} /></>} />
    </div>
  );
  if (k === 'links') return (
    <div className="d-field"><span className="d-label">{a}</span>
      <ARepeat initial={b.map(([en, ar, l]) => ({ en, ar, l }))} blank={{ en: '', ar: '', l: '' }} addLabel={t('Add link', 'إضافة رابط')} render={(it) => <><ABi label={t('Label', 'النص')} en={it.en} ar={it.ar} /><Input label={t('Link', 'الرابط')} defaultValue={it.l} dir="ltr" icon="link" /></>} />
    </div>
  );
  return null;
}

function APSection({ s, i }) {
  const { t } = useLocale();
  const [open, setOpen] = React.useState(i === 0);
  const [on, setOn] = React.useState(true);
  return (
    <section className="a-card" style={{ opacity: on ? 1 : 0.7 }}>
      <header className="a-card__head" style={{ borderBottom: open ? undefined : 0, cursor: 'pointer' }} onClick={() => setOpen(!open)}>
        <span className="a-grip"><Icon name="grip-vertical" size={16} /></span>
        <h3 style={{ flex: 1, minWidth: 0 }}>{s.title}{!on && <Badge tone="outline">{t('Hidden', 'مخفي')}</Badge>}</h3>
        <span onClick={(e) => e.stopPropagation()} style={{ display: 'inline-flex' }}><ASwitch checked={on} onChange={setOn} label={on ? t('Active', 'مفعل') : t('Off', 'متوقف')} /></span>
        <button type="button" className="a-tree__tog" aria-expanded={open} aria-label={t('Toggle section', 'فتح/إغلاق القسم')}><Icon name={open ? 'chevron-up' : 'chevron-down'} size={18} /></button>
      </header>
      {open && <div className="a-card__body">{s.fields.map((f, j) => <APField key={j} f={f} />)}</div>}
    </section>
  );
}

const apItems = (n, make) => Array.from({ length: n }, (_, i) => make(i));

function apDefs(t) {
  const eyebrow = (en, ar) => ['bi', t('Eyebrow', 'العنوان الصغير'), en, ar];
  const title = (en, ar) => ['bi', t('Title', 'العنوان'), en, ar];
  const lede = (en, ar) => ['ml', t('Lede', 'المقدمة'), en, ar];
  const btn = (n, en, ar, l) => ['btn', t('Button', 'زر') + (n ? ' ' + n : ''), en, ar, l];
  const heading = (id, name, e, ti, extra = []) => ({ id, title: name, fields: [eyebrow(e[0], e[1]), title(ti[0], ti[1]), ...extra] });
  const feats = [['truck', 'Next-day delivery in Cairo & Giza', 'توصيل اليوم التالي في القاهرة والجيزة'], ['shield-check', 'Official local warranty', 'ضمان محلي رسمي'], ['pencil-ruler', 'Free lighting advice', 'استشارة إضاءة مجانية'], ['banknote', 'Cash on delivery', 'الدفع عند الاستلام']];
  const why = [['badge-check', 'Authorised for 8 brands', 'وكيل معتمد لـ 8 علامات'], ['pencil-ruler', 'Engineers, not salespeople', 'مهندسون لا مندوبو مبيعات'], ['package', '10,000+ products in stock', 'أكثر من 10,000 منتج متوفر'], ['hammer', 'Our own installers', 'فنيو تركيب تابعون لنا'], ['shield-check', 'Warranty we handle', 'ضمان نتابعه بأنفسنا'], ['message-circle', 'Answers on WhatsApp', 'رد سريع على واتساب']];
  const steps = [['Brief', 'الطلب', 'Tell us about the space on WhatsApp or in the showroom.', 'أخبرنا عن المساحة على واتساب أو في المعرض.'], ['Design', 'التصميم', 'Layouts, renders and a priced schedule in two budgets.', 'توزيع وتصميمات وجدول أسعار بميزانيتين.'], ['Supply', 'التوريد', 'Everything delivered together, checked and labelled.', 'كل شيء يُورَّد معاً، مفحوص ومُرقم.'], ['Install', 'التركيب', 'Our team installs, programs and hands over.', 'فريقنا يركب ويبرمج ويسلم.']];
  return {
    home: [
      { id: 'hero', title: t('Hero', 'الواجهة'), fields: [eyebrow('Lighting · Smart home · Electrical', 'إضاءة · منزل ذكي · كهرباء'), ['bi', t('Headline', 'العنوان الرئيسي'), 'Light, done right — from first sketch to final switch.', 'إضاءة صح — من أول رسمة لآخر مفتاح.'], ['ml', t('Sub-headline', 'العنوان الفرعي'), '10,000+ products from 8 global brands, designed and installed by engineers.', 'أكثر من 10,000 منتج من 8 علامات عالمية، بتصميم وتركيب مهندسين.'], ['row', [btn(1, 'Shop products', 'تسوق المنتجات', '/products'), btn(2, 'Book a consultation', 'احجز استشارة', '/services')]], ['row', [['video', t('Background video', 'فيديو الخلفية'), 'hero-loop.mp4', 1], ['img', t('Poster image', 'صورة الغلاف'), 1, '16 / 9', 'hero-poster.jpg']]]] },
      { id: 'features', title: t('Features strip', 'شريط المزايا'), fields: [['items', t('4 items', '4 عناصر'), feats.map(([ic, en, ar]) => [['icon', t('Icon', 'الأيقونة'), ic], ['bi', t('Text', 'النص'), en, ar]])]] },
      heading('cats', t('Categories heading', 'عنوان الأقسام'), ['Shop by category', 'تسوق حسب القسم'], ['Everything for light and power', 'كل ما تحتاجه للإضاءة والكهرباء'], [btn(0, 'All categories', 'كل الأقسام', '/categories')]),
      heading('featured', t('Featured products heading', 'عنوان المنتجات المميزة'), ['Featured', 'مختارات'], ['Picked by our engineers', 'اختيار مهندسينا'], [['row', [['bi', t('Tab 1', 'تبويب 1'), 'New arrivals', 'وصل حديثاً'], ['bi', t('Tab 2', 'تبويب 2'), 'Best sellers', 'الأكثر مبيعاً'], ['bi', t('Tab 3', 'تبويب 3'), 'Smart home', 'المنزل الذكي']]]]),
      heading('services', t('Services heading', 'عنوان الخدمات'), ['Services', 'الخدمات'], ['Design, supply and install — one team', 'تصميم وتوريد وتركيب — فريق واحد']),
      { id: 'smart', title: t('Smart home banner', 'بانر المنزل الذكي'), fields: [eyebrow('Smart home', 'المنزل الذكي'), title('One app for every light, blind and AC', 'تطبيق واحد لكل إضاءة وستارة وتكييف'), ['ml', t('Text', 'النص'), 'KNX and Zigbee systems designed around how you live — installed and programmed by Dots.', 'أنظمة KNX وZigbee مصممة حسب أسلوب حياتك — تركيب وبرمجة دوتس.'], ['row', [btn(1, 'Explore smart home', 'اكتشف المنزل الذكي', '/services/smart'), btn(2, 'Talk to an engineer', 'كلم مهندس', 'whatsapp')]], ['img', t('Image', 'الصورة'), 18, '16 / 9', 'smart-banner.jpg']] },
      heading('brands', t('Brands heading', 'عنوان العلامات'), ['Authorised partner', 'شريك معتمد'], ['Brands we represent', 'العلامات التي نمثلها']),
    ],
    about: [
      { id: 'hero', title: t('Hero', 'الواجهة'), fields: [eyebrow('About Dots', 'عن دوتس'), title('We make light work', 'نجعل الإضاءة تعمل'), lede('Since 2012 we’ve lit 1,400+ homes, offices and hotels across Egypt.', 'منذ 2012 أضأنا أكثر من 1,400 منزل ومكتب وفندق في مصر.'), ['row', [['img', t('Image', 'الصورة'), 27, '16 / 9', 'about-hero.jpg'], ['video', t('Video', 'الفيديو'), 'about.mp4', 27]]], ['row', [btn(1, 'Our projects', 'مشروعاتنا', '/projects'), btn(2, 'Visit the showroom', 'زر المعرض', '/contact')]]] },
      { id: 'story', title: t('Our story', 'قصتنا'), fields: [eyebrow('Our story', 'قصتنا'), title('Started by two engineers and a showroom', 'بدأنا بمهندسَين ومعرض'), ['ml', t('Text', 'النص'), 'Dots began as a lighting design studio. Clients kept asking us to supply and install, so we built the team to do it properly.', 'بدأت دوتس كاستوديو لتصميم الإضاءة. طلب العملاء التوريد والتركيب، فبنينا الفريق لنقوم بذلك بشكل صحيح.'], ['row', [['img', t('Image 1', 'صورة 1'), 9, '4 / 5'], ['img', t('Image 2', 'صورة 2'), 13, '4 / 5'], ['img', t('Image 3', 'صورة 3'), 28, '4 / 5']]]] },
      { id: 'stats', title: t('Stats', 'الأرقام'), fields: [['items', t('4 items', '4 عناصر'), [['1400', '+', 'Projects delivered', 'مشروع منفذ'], ['8', '', 'Global brands', 'علامات عالمية'], ['10', 'k+', 'Products in stock', 'منتج متوفر'], ['14', '', 'Years in Egypt', 'عاماً في مصر']].map(([v, sfx, en, ar]) => [['row', [['text', t('Value', 'القيمة'), v], ['text', t('Suffix', 'اللاحقة'), sfx]]], ['bi', t('Label', 'الوصف'), en, ar]])]] },
      { id: 'mv', title: t('Mission & vision', 'الرسالة والرؤية'), fields: [['ml', t('Mission', 'الرسالة'), 'Make good lighting easy to choose, buy and live with.', 'أن نجعل الإضاءة الجيدة سهلة الاختيار والشراء والاستخدام.'], ['img', t('Mission image', 'صورة الرسالة'), 4, '4 / 3'], ['ml', t('Vision', 'الرؤية'), 'Every Egyptian space lit with intent.', 'كل مساحة في مصر مضاءة بعناية.'], ['img', t('Vision image', 'صورة الرؤية'), 22, '4 / 3']] },
      { id: 'disc', title: t('Disciplines', 'التخصصات'), fields: [eyebrow('What we do', 'ما نقدمه'), title('Three disciplines, one team', 'ثلاثة تخصصات، فريق واحد'), ['items', t('3 items', '3 عناصر'), [['lamp-ceiling', 'Lighting', 'الإضاءة', 13, '/services/design'], ['house', 'Smart home', 'المنزل الذكي', 18, '/services/smart'], ['plug-zap', 'Electrical', 'الكهرباء', 17, '/services/electrical']].map(([ic, en, ar, ph, l]) => [['bi', t('Title', 'العنوان'), en, ar], ['ml', t('Text', 'النص'), 'Design, supply and installation for ' + en.toLowerCase() + '.', 'تصميم وتوريد وتركيب ' + ar + '.'], ['row', [['icon', t('Icon', 'الأيقونة'), ic], ['text', t('Link', 'الرابط'), l]]], ['img', t('Image', 'الصورة'), ph, '4 / 3']])]] },
      { id: 'why', title: t('Why Dots', 'لماذا دوتس'), fields: [eyebrow('Why Dots', 'لماذا دوتس'), title('Six reasons clients come back', 'ستة أسباب تجعل العملاء يعودون'), ['items', t('6 items', '6 عناصر'), why.map(([ic, en, ar]) => [['icon', t('Icon', 'الأيقونة'), ic], ['bi', t('Title', 'العنوان'), en, ar], ['ml', t('Text', 'النص'), '', '']])]] },
      heading('partners', t('Partners heading', 'عنوان الشركاء'), ['Partners', 'الشركاء'], ['Brands we’re authorised for', 'العلامات المعتمدين لها']),
      { id: 'cta', title: t('Closing CTA', 'دعوة ختامية'), fields: [title('Let’s light your next space', 'لنُضئ مساحتك القادمة'), ['ml', t('Text', 'النص'), 'Send a plan or a photo — an engineer replies within 2 working hours.', 'أرسل مخططاً أو صورة — يرد مهندس خلال ساعتي عمل.'], ['img', t('Background image', 'صورة الخلفية'), 30, '16 / 6']] },
    ],
    services: [
      { id: 'header', title: t('Header', 'الواجهة'), fields: [eyebrow('Services', 'الخدمات'), title('From first sketch to final switch', 'من أول رسمة لآخر مفتاح'), ['ml', t('Intro', 'المقدمة'), 'Design, supply and installation by one accountable team.', 'تصميم وتوريد وتركيب بفريق واحد مسؤول.'], btn(0, 'Book a consultation', 'احجز استشارة', '/consultation')] },
      { id: 'process', title: t('Process', 'خطوات العمل'), fields: [eyebrow('How it works', 'كيف نعمل'), title('Four steps, one point of contact', 'أربع خطوات وجهة تواصل واحدة'), ['items', t('4 steps', '4 خطوات'), steps.map(([en, ar, ten, tar]) => [['bi', t('Title', 'العنوان'), en, ar], ['ml', t('Text', 'النص'), ten, tar]])]] },
      { id: 'faq', title: t('FAQ', 'الأسئلة الشائعة'), fields: [title('Questions we get every week', 'أسئلة تصلنا كل أسبوع'), ['faq', t('General FAQ', 'الأسئلة العامة'), A_FAQ]] },
    ],
    brands: [{ id: 'header', title: t('Header', 'الواجهة'), fields: [eyebrow('Brands', 'العلامات'), title('Eight global brands, one local warranty', 'ثماني علامات عالمية وضمان محلي واحد'), lede('Authorised stock with datasheets, IES files and official support.', 'مخزون معتمد مع الداتا شيت وملفات IES ودعم رسمي.')] }],
    projects: [
      { id: 'current', title: t('Current projects hero', 'واجهة المشروعات الجارية'), fields: [eyebrow('On site now', 'في الموقع الآن'), title('Projects in progress', 'مشروعات قيد التنفيذ'), lede('Follow live progress from design to handover.', 'تابع التقدم من التصميم حتى التسليم.'), ['img', t('Image', 'الصورة'), 9, '16 / 6'], btn(0, 'Start a project', 'ابدأ مشروعك', 'whatsapp')] },
      { id: 'previous', title: t('Previous projects header', 'واجهة المشروعات السابقة'), fields: [eyebrow('Portfolio', 'أعمالنا'), title('Completed projects', 'مشروعات منفذة'), lede('Homes, offices and hotels we’ve lit across Egypt.', 'منازل ومكاتب وفنادق أضأناها في مصر.')] },
    ],
    blog: [{ id: 'header', title: t('Header', 'الواجهة'), fields: [eyebrow('Journal', 'المدونة'), title('Guides from our engineers', 'أدلة من مهندسينا'), lede('Practical advice on lighting, smart home and electrical work.', 'نصائح عملية عن الإضاءة والمنزل الذكي والكهرباء.')] }],
    global: [
      { id: 'quote', title: t('Quote band', 'شريط عرض السعر'), fields: [eyebrow('Projects & trade', 'المشروعات والتجار'), title('Get a quote in 24 hours', 'احصل على عرض سعر خلال 24 ساعة'), ['items', t('4 bullet points', '4 نقاط'), [['Send a BOQ or drawings', 'أرسل مقايسة أو رسومات'], ['Trade pricing for contractors', 'أسعار خاصة للمقاولين'], ['Alternatives in two budgets', 'بدائل بميزانيتين'], ['Delivery to site', 'توصيل للموقع']].map(([en, ar]) => [['bi', t('Bullet', 'نقطة'), en, ar]])], ['bi', t('Hours text', 'نص المواعيد'), 'Sun–Thu 9:00–18:00 · Sat 11:00–17:00', 'الأحد–الخميس 9–6 · السبت 11–5'], btn(0, 'Send on WhatsApp', 'أرسل على واتساب', 'whatsapp')] },
      { id: 'mega', title: t('Mega menu promo', 'بانر القائمة الرئيسية'), fields: [['img', t('Image', 'الصورة'), 13, '4 / 3'], title('Free lighting layout with every order over EGP 20,000', 'توزيع إضاءة مجاني مع كل طلب فوق 20,000 ج.م'), btn(0, 'Learn more', 'اعرف المزيد', '/services/design')] },
      { id: 'news', title: t('Newsletter band', 'شريط النشرة البريدية'), fields: [eyebrow('Newsletter', 'النشرة البريدية'), title('New products and guides, once a month', 'منتجات وأدلة جديدة، مرة شهرياً')] },
    ],
  };
}

function APageContent({ app }) {
  const { t } = useLocale();
  const [pg, setPg] = React.useState('home');
  const defs = apDefs(t);
  const pages = [['home', t('Home', 'الرئيسية')], ['about', t('About', 'من نحن')], ['services', t('Services', 'الخدمات')], ['brands', t('Brands', 'العلامات')], ['projects', t('Projects', 'المشروعات')], ['blog', t('Blog', 'المدونة')], ['global', t('Global blocks', 'العناصر العامة')]];
  return (
    <div className="a-page">
      <AHead title={t('Page content', 'محتوى الصفحات')} sub={t('Every section on the public site · toggle a section off to hide it', 'كل أقسام الموقع العام · أوقف القسم لإخفائه')}>
        <Button variant="outline" icon="external-link" onClick={() => app.toast('info', t('Opening page preview…', 'جارٍ فتح المعاينة…'))}>{t('Preview page', 'معاينة الصفحة')}</Button>
        <Button icon="check" onClick={() => app.toast('success', t('Page content saved', 'تم حفظ المحتوى'))}>{t('Save changes', 'حفظ التغييرات')}</Button>
      </AHead>
      <div className="a-card" style={{ padding: '0 18px' }}><Tabs value={pg} onChange={setPg} tabs={pages.map(([id, label]) => ({ id, label, count: defs[id].length }))} /></div>
      <div key={pg} style={{ display: 'grid', gap: 12 }}>{defs[pg].map((s, i) => <APSection key={s.id} s={s} i={i} />)}</div>
    </div>
  );
}

function AContentPages({ app }) {
  const { t, tx } = useLocale();
  const [rows, setRows] = React.useState(A_CONTENT_PAGES);
  const set = (id, k, v) => setRows(rows.map((x) => (x.id === id ? { ...x, [k]: v } : x)));
  return (
    <AList title={t('Content pages', 'صفحات المحتوى')} sub={t('Delivery, returns, warranty, careers and legal pages', 'صفحات التوصيل والاسترجاع والضمان والوظائف والصفحات القانونية')}
      actions={<Button icon="plus" onClick={() => app.go('contentpage')}>{t('New page', 'صفحة جديدة')}</Button>}
      rows={rows} searchText={(p) => p.title.en + p.title.ar + p.slug.en} onRow={(p) => app.go('contentpage', { id: p.id })} emptyIcon="file-text"
      filters={[
        { id: 'a', label: t('Any status', 'كل الحالات'), options: [{ value: 'on', label: t('Active', 'مفعل') }, { value: 'off', label: t('Inactive', 'غير مفعل') }], test: (p, v) => p.active === (v === 'on') },
        { id: 'f', label: t('Footer: any', 'الفوتر: الكل'), options: [{ value: 'y', label: t('In footer', 'في الفوتر') }, { value: 'n', label: t('Not in footer', 'ليس في الفوتر') }], test: (p, v) => p.footer === (v === 'y') },
      ]}
      columns={[
        { id: 'o', label: '#', shrink: true, sortVal: (p) => p.sort, render: (p) => <span className="a-count">{p.sort}</span> },
        { id: 'n', label: t('Page', 'الصفحة'), primary: true, sortVal: (p) => tx(p.title), render: (p) => <b style={{ fontWeight: 700 }}>{tx(p.title)}</b> },
        { id: 'u', label: 'URL', render: (p) => <span className="a-mono">/{tx(p.slug)}</span> },
        { id: 'd', label: t('Last updated', 'آخر تحديث'), sortVal: (p) => p.updated || '0', render: (p) => p.updated ? <span className="a-mono">{p.updated}</span> : <span className="a-count">—</span> },
        { id: 'f', label: t('Footer', 'الفوتر'), render: (p) => <ASwitch checked={p.footer} onChange={(v) => set(p.id, 'footer', v)} /> },
        { id: 'a', label: t('Status', 'الحالة'), render: (p) => <APill kind="active" v={p.active ? 'on' : 'off'} /> },
        { id: 'x', label: '', shrink: true, hideMobile: true, render: (p) => <ARowActions onEdit={() => app.go('contentpage', { id: p.id })} items={[{ icon: 'external-link', label: t('View on site', 'عرض في الموقع') }, '-', { icon: 'trash-2', label: t('Delete', 'حذف'), danger: true, onClick: () => setRows(rows.filter((x) => x.id !== p.id)) }]} /> },
      ]} />
  );
}

function AContentPageEdit({ app, route }) {
  const { t, tx } = useLocale();
  const isNew = !route.id;
  const p = A_CONTENT_PAGES.find((x) => x.id === route.id) || A_CONTENT_PAGES[0];
  const [sw, setSw] = React.useState({ footer: isNew ? false : p.footer, active: isNew ? true : p.active });
  return (
    <AEditor crumbs={[{ label: t('Content pages', 'صفحات المحتوى'), onClick: () => app.go('contentpages') }, { label: isNew ? t('New page', 'صفحة جديدة') : tx(p.title) }]}
      title={isNew ? t('New page', 'صفحة جديدة') : tx(p.title)} extra={<APill kind="active" v={sw.active ? 'on' : 'off'} />}
      actions={<><Button variant="outline" icon="eye">{t('Preview', 'معاينة')}</Button><Button icon="check" onClick={() => app.toast('success', t('Page saved', 'تم حفظ الصفحة'), t('Last updated set to today', 'تم تحديث التاريخ لليوم'))}>{t('Save changes', 'حفظ التغييرات')}</Button></>}
      aside={<ACard title={t('Settings', 'الإعدادات')}>
        <ASwitch checked={sw.active} onChange={(v) => setSw({ ...sw, active: v })} label={t('Active', 'مفعل')} />
        <ASwitch checked={sw.footer} onChange={(v) => setSw({ ...sw, footer: v })} label={t('Show in footer', 'يظهر في الفوتر')} />
        <Input label={t('Sort order', 'الترتيب')} type="number" defaultValue={isNew ? A_CONTENT_PAGES.length + 1 : p.sort} dir="ltr" />
        <Input label={t('Last updated', 'آخر تحديث')} value={isNew ? '—' : p.updated || '—'} readOnly disabled dir="ltr" help={t('Set automatically on save', 'يُحدث تلقائياً عند الحفظ')} />
      </ACard>}>
      <ACard title={t('Page', 'الصفحة')}>
        <ABi label={t('Title', 'العنوان')} en={isNew ? '' : p.title.en} ar={isNew ? '' : p.title.ar} />
        <ASlugs slug={isNew ? { en: '', ar: '' } : p.slug} />
        <ABi label={t('Lede', 'المقدمة')} multiline rows={2} en={isNew ? '' : 'Where we deliver, how long it takes and what it costs.'} ar={isNew ? '' : 'أين نوصّل، وكم يستغرق، وكم يكلف.'} />
      </ACard>
      <ACard title={t('Body', 'المحتوى')}>
        <ARich minH={300} en={isNew ? '' : '<h2>Delivery times</h2><p>Orders confirmed before 15:00 on a working day leave our warehouse the same day.</p><ul><li>Cairo &amp; Giza — next working day</li><li>Alexandria &amp; Delta — 2–3 working days</li></ul><h3>Fees</h3><p>Standard delivery is EGP 150 and free over EGP 5,000.</p><div class="d-callout">Project deliveries are scheduled with our project team.</div>'} ar={isNew ? '' : '<h2>مواعيد التوصيل</h2><p>الطلبات المؤكدة قبل 3 عصراً في يوم عمل تخرج من المخزن في نفس اليوم.</p><ul><li>القاهرة والجيزة — يوم العمل التالي</li><li>الإسكندرية والدلتا — 2–3 أيام عمل</li></ul><h3>الرسوم</h3><p>التوصيل العادي 150 ج.م ومجاني فوق 5,000 ج.م.</p><div class="d-callout">توصيل المشروعات يُجدول مع فريق المشروعات.</div>'} />
      </ACard>
      <ASeo title={isNew ? undefined : aM(p.title.en + ' | Dots', p.title.ar + ' | دوتس')} desc={isNew ? undefined : aM('Where we deliver, how long it takes and what it costs.', 'أين نوصّل، وكم يستغرق، وكم يكلف.')} />
    </AEditor>
  );
}

;


/* Customers (list + detail) and Newsletter subscribers. */
function ACustomers({ app }) {
  const { t, tx, price } = useLocale();
  return (
    <AList title={t('Customers', 'العملاء')} sub={t('Accounts created at checkout or sign-up', 'حسابات تم إنشاؤها عند الدفع أو التسجيل')}
      actions={<Button variant="outline" icon="download" onClick={() => app.toast('success', t('Export ready', 'التصدير جاهز'), 'customers-2026-10-06.csv')}>{t('Export CSV', 'تصدير CSV')}</Button>}
      rows={A_CUSTOMERS} searchText={(c) => c.name.en + c.name.ar + c.phone + c.email} onRow={(c) => app.go('customer', { id: c.id })} emptyIcon="users-round"
      filters={[
        { id: 's', label: t('Any status', 'كل الحالات'), options: Object.entries(A_STATUS.customer).map(([v, s]) => ({ value: v, label: tx(s[1]) })), test: (c, v) => c.status === v },
        { id: 'g', label: t('All governorates', 'كل المحافظات'), options: A_GOVS.map((g) => ({ value: g.id, label: tx(g.name) })), test: (c, v) => c.gov === v },
        { id: 'o', label: t('Orders: any', 'الطلبات: الكل'), options: [{ value: 'y', label: t('Has orders', 'لديه طلبات') }, { value: 'n', label: t('No orders yet', 'بدون طلبات') }], test: (c, v) => (c.orders > 0) === (v === 'y') },
      ]}
      columns={[
        { id: 'n', label: t('Customer', 'العميل'), primary: true, sortVal: (c) => tx(c.name), render: (c) => <span style={{ display: 'flex', alignItems: 'center', gap: 10 }}><span className="a-avatar" style={c.status === 'active' ? undefined : { background: 'var(--ink-150)', color: 'var(--fg-2)' }}>{c.name.en.split(' ').map((x) => x[0]).join('')}</span><span><b style={{ fontWeight: 700 }}>{tx(c.name)}</b>{c.company && <span className="a-sub">{c.company.name}</span>}</span></span> },
        { id: 'p', label: t('Mobile', 'الموبايل'), render: (c) => <span dir="ltr">{c.phone}</span> },
        { id: 'e', label: t('Email', 'البريد'), hideMobile: true, render: (c) => <span dir="ltr" style={{ color: 'var(--fg-2)' }}>{c.email}</span> },
        { id: 'o', label: t('Orders', 'الطلبات'), num: true, sortVal: (c) => c.orders, render: (c) => c.orders },
        { id: 't', label: t('Total spent', 'إجمالي الإنفاق'), num: true, sortVal: (c) => c.spent, render: (c) => <b>{price(c.spent)}</b> },
        { id: 'j', label: t('Joined', 'تاريخ الانضمام'), sortVal: (c) => c.joined, render: (c) => <span className="a-mono">{c.joined}</span> },
        { id: 's', label: t('Status', 'الحالة'), render: (c) => <APill kind="customer" v={c.status} /> },
        { id: 'x', label: '', shrink: true, hideMobile: true, render: (c) => <span onClick={(e) => e.stopPropagation()} style={{ display: 'inline-flex' }}><IconButton icon="message-circle" size="sm" label={t('WhatsApp customer', 'واتساب العميل')} style={{ color: 'var(--wa-600)' }} onClick={() => app.toast('success', t('Opening WhatsApp…', 'جارٍ فتح واتساب…'), c.phone)} /><AMenu items={[{ icon: 'eye', label: t('View profile', 'عرض الملف'), onClick: () => app.go('customer', { id: c.id }) }, '-', { icon: c.status === 'active' ? 'user-x' : 'user-check', label: c.status === 'active' ? t('Disable account', 'تعطيل الحساب') : t('Enable account', 'تفعيل الحساب'), danger: c.status === 'active' }]} /></span> },
      ]} />
  );
}

function ACustomerDetail({ app, route }) {
  const { t, tx, price } = useLocale();
  const c = A_CUSTOMERS.find((x) => x.id === route.id) || A_CUSTOMERS[0];
  const [status, setStatus] = React.useState(c.status);
  const [notes, setNotes] = React.useState([{ by: 'Hossam', at: t('12 Sep 2026', '12 سبتمبر 2026'), text: t('Prefers WhatsApp over calls. Architect on the Sodic villa project.', 'يفضل واتساب على المكالمات. مهندس مشروع فيلا سوديك.') }]);
  const [draft, setDraft] = React.useState('');
  const orders = A_ORDERS.filter((o) => o.phone === c.phone);
  const shown = orders.length ? orders : A_ORDERS.slice(6, 9).map((o) => ({ ...o, name: c.name, phone: c.phone }));
  const gov = tx(A_GOVS.find((g) => g.id === c.gov).name);
  const addrs = [{ label: t('Home', 'المنزل'), def: true, lines: [t('14 El-Nahda St, Building 9, Apt 6', '14 شارع النهضة، عمارة 9، شقة 6'), t('Maadi', 'المعادي') + ' · ' + gov] }, { label: t('Office', 'المكتب'), def: false, lines: [t('Arkan Plaza, Building 3, Office 204', 'أركان بلازا، مبنى 3، مكتب 204'), t('Sheikh Zayed · Giza', 'الشيخ زايد · الجيزة')] }];
  const toggle = () => { const n = status === 'active' ? 'disabled' : 'active'; setStatus(n); app.toast(n === 'active' ? 'success' : 'warning', n === 'active' ? t('Account enabled', 'تم تفعيل الحساب') : t('Account disabled', 'تم تعطيل الحساب'), n === 'active' ? '' : t('The customer can no longer sign in. Orders are kept.', 'لا يمكن للعميل تسجيل الدخول. الطلبات محفوظة.')); };
  return (
    <div className="a-page">
      <AHead crumbs={[{ label: t('Customers', 'العملاء'), onClick: () => app.go('customers') }, { label: tx(c.name) }]} title={tx(c.name)} extra={<APill kind="customer" v={status} />} sub={t('Customer since', 'عميل منذ') + ' ' + c.joined}>
        <Button variant={status === 'active' ? 'outline' : 'primary'} icon={status === 'active' ? 'user-x' : 'user-check'} onClick={toggle} style={status === 'active' ? { color: 'var(--error-600)' } : undefined}>{status === 'active' ? t('Disable account', 'تعطيل الحساب') : t('Enable account', 'تفعيل الحساب')}</Button>
        <Button variant="whatsapp" onClick={() => app.toast('success', t('Opening WhatsApp…', 'جارٍ فتح واتساب…'), c.phone)}>{t('WhatsApp', 'واتساب')}</Button>
      </AHead>
      {status === 'disabled' && <AAlert tone="warning" title={t('This account is disabled', 'هذا الحساب معطل')}>{t('The customer can’t sign in or place orders with this account.', 'لا يمكن للعميل تسجيل الدخول أو الطلب بهذا الحساب.')}</AAlert>}
      <div className="a-split">
        <div style={{ display: 'grid', gap: 16, minWidth: 0 }}>
          <div className="a-kpis">
            {[[t('Orders', 'الطلبات'), c.orders], [t('Total spent', 'إجمالي الإنفاق'), price(c.spent)], [t('Average order', 'متوسط الطلب'), c.orders ? price(Math.round(c.spent / c.orders)) : '—'], [t('Wishlist', 'المفضلة'), 4]].map(([l, v]) => <div key={l} className="a-card a-kpi"><div className="a-kpi__l">{l}</div><div className="a-kpi__v" style={{ fontSize: 24 }}>{v}</div></div>)}
          </div>
          <ACard title={t('Orders', 'الطلبات')} body={false}>
            <ATable rows={shown} onRow={(r) => app.go('order', { id: r.id })} empty={<AEmpty icon="shopping-bag" title={t('No orders yet', 'لا توجد طلبات بعد')} />} columns={[
              { id: 'id', label: t('Order', 'الطلب'), primary: true, render: (r) => <span><span className="a-mono" style={{ color: 'var(--fg-1)' }}>{r.id}</span><span className="a-sub">{tx(r.date)}</span></span> },
              { id: 'n', label: t('Items', 'المنتجات'), num: true, render: (r) => r.items },
              { id: 'tot', label: t('Total', 'الإجمالي'), num: true, render: (r) => <b>{price(r.total)}</b> },
              { id: 'p', label: t('Payment', 'الدفع'), render: (r) => <APill kind="pay" v={r.pay} /> },
              { id: 's', label: t('Status', 'الحالة'), render: (r) => <APill kind="order" v={r.status} /> },
            ]} />
          </ACard>
          <ACard title={t('Wishlist', 'المفضلة')} body={false}>
            {A_PRODUCTS.slice(0, 4).map((p) => (
              <button key={p.id} type="button" onClick={() => app.go('product', { id: p.id })} style={{ display: 'grid', gridTemplateColumns: '44px minmax(0,1fr) auto', gap: 12, alignItems: 'center', width: '100%', padding: '10px 18px', border: 0, borderBottom: '1px solid var(--border-1)', background: 'none', textAlign: 'start', cursor: 'pointer' }}>
                <ImageFrame src={p.image} style={{ width: 44, height: 44, borderRadius: 6, border: '1px solid var(--border-1)' }} />
                <span style={{ minWidth: 0, font: '700 13px/1.3 var(--font-body)' }}>{tx(p.name)}<span className="a-sub a-mono">{p.sku}</span></span>
                <span style={{ textAlign: 'end' }}><b style={{ font: '800 13px var(--font-body)' }}>{p.price ? price(p.price) : t('On request', 'عند الطلب')}</b><span className="a-sub"><APill kind="stock" v={p.stockState} /></span></span>
              </button>
            ))}
          </ACard>
        </div>
        <div style={{ display: 'grid', gap: 16 }}>
          <ACard title={t('Profile', 'الملف الشخصي')} icon="user">
            <div style={{ display: 'grid', gap: 8, font: '600 14px/1.45 var(--font-body)' }}>
              <span style={{ display: 'flex', gap: 8, alignItems: 'center' }}><Icon name="phone" size={16} style={{ color: 'var(--fg-3)' }} /><span dir="ltr">{c.phone}</span></span>
              <span style={{ display: 'flex', gap: 8, alignItems: 'center' }}><Icon name="mail" size={16} style={{ color: 'var(--fg-3)' }} /><span dir="ltr">{c.email}</span></span>
              <span style={{ display: 'flex', gap: 8, alignItems: 'center' }}><Icon name="globe" size={16} style={{ color: 'var(--fg-3)' }} />{t('Prefers Arabic', 'يفضل العربية')}</span>
              {c.company && <span style={{ display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap' }}><Icon name="building-2" size={16} style={{ color: 'var(--fg-3)' }} />{c.company.name} · {t('Tax ID', 'الرقم الضريبي')} <span className="a-mono">{c.company.tax}</span></span>}
            </div>
          </ACard>
          <ACard title={t('Addresses', 'العناوين')} icon="map-pin">
            {addrs.map((a, i) => <div key={i} className="d-file" style={{ alignItems: 'flex-start' }}><span style={{ flex: 1, minWidth: 0, display: 'grid', gap: 2, font: '600 13px/1.45 var(--font-body)' }}><b style={{ display: 'flex', gap: 6, alignItems: 'center' }}>{a.label}{a.def && <Badge tone="dark">{t('Default', 'افتراضي')}</Badge>}</b>{a.lines.map((l, j) => <span key={j} style={{ color: 'var(--fg-2)' }}>{l}</span>)}</span></div>)}
          </ACard>
          <ACard title={t('Internal notes', 'ملاحظات داخلية')} icon="sticky-note">
            {notes.length ? <ol className="a-tl">{notes.map((n, i) => <li key={i}><b>{n.by} · {n.at}</b><span style={{ color: 'var(--fg-1)' }}>{n.text}</span></li>)}</ol> : <span className="d-help">{t('No notes yet.', 'لا توجد ملاحظات بعد.')}</span>}
            <Input multiline rows={2} placeholder={t('Add a note for the team…', 'أضف ملاحظة للفريق…')} value={draft} onChange={(e) => setDraft(e.target.value)} aria-label={t('New note', 'ملاحظة جديدة')} />
            <div><Button size="sm" variant="dark" disabled={!draft.trim()} onClick={() => { setNotes([{ by: 'Mona', at: t('Just now', 'الآن'), text: draft }, ...notes]); setDraft(''); }}>{t('Add note', 'إضافة ملاحظة')}</Button></div>
          </ACard>
        </div>
      </div>
    </div>
  );
}

function ANewsletter({ app }) {
  const { t, tx } = useLocale();
  return (
    <AList title={t('Newsletter', 'النشرة البريدية')} sub={t('Sign-ups from the footer and blog articles', 'الاشتراكات من الفوتر ومقالات المدونة')}
      actions={<Button variant="outline" icon="download" onClick={() => app.toast('success', t('Export ready', 'التصدير جاهز'), 'newsletter-subscribers-2026-10-06.csv')}>{t('Export CSV', 'تصدير CSV')}</Button>}
      tabs={[{ id: 'all', label: t('All', 'الكل') }, { id: 'sub', label: t('Subscribed', 'مشترك'), test: (s) => s.status === 'subscribed' }, { id: 'uns', label: t('Unsubscribed', 'ألغى الاشتراك'), test: (s) => s.status === 'unsubscribed' }]}
      rows={A_SUBSCRIBERS} searchText={(s) => s.email} emptyIcon="mail" perPage={8}
      filters={[
        { id: 'src', label: t('All sources', 'كل المصادر'), options: Object.entries(A_SUB_SOURCES).map(([v, l]) => ({ value: v, label: tx(l) })), test: (s, v) => s.source === v },
        { id: 'l', label: t('Any language', 'كل اللغات'), options: [{ value: 'ar', label: 'العربية' }, { value: 'en', label: 'English' }], test: (s, v) => s.lang === v },
      ]}
      columns={[
        { id: 'e', label: t('Email', 'البريد'), primary: true, sortVal: (s) => s.email, render: (s) => <b dir="ltr" style={{ fontWeight: 700 }}>{s.email}</b> },
        { id: 'l', label: t('Language', 'اللغة'), sortVal: (s) => s.lang, render: (s) => <Badge tone="outline">{s.lang === 'ar' ? 'العربية' : 'English'}</Badge> },
        { id: 'src', label: t('Source', 'المصدر'), sortVal: (s) => s.source, render: (s) => tx(A_SUB_SOURCES[s.source]) },
        { id: 'd', label: t('Date', 'التاريخ'), sortVal: (s) => s.date, render: (s) => <span className="a-mono">{s.date}</span> },
        { id: 's', label: t('Status', 'الحالة'), render: (s) => <APill kind="sub" v={s.status} /> },
        { id: 'x', label: '', shrink: true, hideMobile: true, render: (s) => <AMenu items={[{ icon: 'copy', label: t('Copy email', 'نسخ البريد'), onClick: () => app.toast('success', t('Copied', 'تم النسخ'), s.email) }, s.status === 'subscribed' && { icon: 'mail-x', label: t('Unsubscribe', 'إلغاء الاشتراك') }, '-', { icon: 'trash-2', label: t('Delete', 'حذف'), danger: true }].filter(Boolean)} /> },
      ]} />
  );
}

;


const A_PAGES = [
  ['login', 'Login'], ['dashboard', 'Dashboard'],
  ['products', 'Products'], ['product', 'Product edit'], ['categories', 'Categories'], ['brands', 'Brands'], ['brand', 'Brand edit'], ['attributes', 'Attributes'], ['attribute', 'Attribute edit'],
  ['pagecontent', 'Page content'], ['services', 'Services'], ['service', 'Service edit'], ['projects', 'Projects'], ['project', 'Project edit'], ['blog', 'Blog'], ['post', 'Post edit'], ['blogtax', 'Blog categories & tags'], ['contentpages', 'Content pages'], ['contentpage', 'Content page edit'],
  ['orders', 'Orders'], ['order', 'Order detail'], ['customers', 'Customers'], ['customer', 'Customer detail'], ['coupons', 'Coupons'],
  ['leads', 'WhatsApp leads'], ['consultations', 'Consultations'], ['newsletter', 'Newsletter'],
  ['delivery', 'Delivery methods'], ['payments', 'Payment methods'], ['settings', 'Settings'], ['users', 'Users'],
];
const A_DEFAULT_ID = { product: 'p1', order: 'DT-10478', brand: 'philips', attribute: 'finish', service: 'design', project: 'pr1', post: 'b1', contentpage: 'delivery', customer: 'cu4' };
const A_SCREENS = { login: ALogin, dashboard: ADashboard, products: AProducts, product: AProductEdit, categories: ACategories, brands: ABrands, brand: ABrandEdit, attributes: AAttributes, attribute: AAttributeEdit, pagecontent: APageContent, services: AServices, service: AServiceEdit, projects: AProjects, project: AProjectEdit, blog: ABlog, post: APostEdit, blogtax: ABlogTax, contentpages: AContentPages, contentpage: AContentPageEdit, orders: AOrders, order: AOrderDetail, customers: ACustomers, customer: ACustomerDetail, coupons: ACoupons, leads: ALeads, consultations: AConsultations, newsletter: ANewsletter, delivery: ADelivery, payments: APayments, settings: ASettings, users: AUsers };

function AToolbar({ route, go, lang, setLang, mobile, setMobile }) {
  const seg = (items, val, set) => <div className="d-tabs d-tabs--pill" style={{ flex: 'none' }}>{items.map(([v, l]) => <button key={String(v)} type="button" className="d-tab" aria-selected={v === val} onClick={() => set(v)}>{l}</button>)}</div>;
  return (
    <div style={{ flex: 'none', zIndex: 200, background: 'var(--white)', borderBottom: '1px solid var(--border-1)', display: 'flex', alignItems: 'center', gap: 16, padding: '8px 16px', overflowX: 'auto' }} dir="ltr">
      <span style={{ font: '800 12px var(--font-body)', letterSpacing: '.12em', textTransform: 'uppercase', color: 'var(--fg-3)', whiteSpace: 'nowrap' }}>Admin kit</span>
      <div className="d-tabs d-tabs--pill" style={{ flex: 'none' }}>{A_PAGES.map(([k, l]) => <button key={k} type="button" className="d-tab" aria-selected={route.page === k} onClick={() => go(k, A_DEFAULT_ID[k] ? { id: A_DEFAULT_ID[k] } : {})}>{l}</button>)}</div>
      <span style={{ marginInlineStart: 'auto' }} />
      {seg([[false, 'Desktop'], [true, 'Mobile']], mobile, setMobile)}
      {seg([['en', 'EN · LTR'], ['ar', 'AR · RTL']], lang, setLang)}
    </div>
  );
}

function AApp() {
  const saved = React.useMemo(() => { try { return JSON.parse(localStorage.getItem('dots-admin-kit') || '{}'); } catch (e) { return {}; } }, []);
  const [route, setRoute] = React.useState(saved.route || { page: 'dashboard' });
  const [lang, setLang] = React.useState(saved.lang || 'ar');
  const [mobile, setMobile] = React.useState(!!saved.mobile);
  const [side, setSide] = React.useState(false);
  const [toasts, setToasts] = React.useState([]);
  const main = React.useRef(null);
  React.useEffect(() => { localStorage.setItem('dots-admin-kit', JSON.stringify({ route, lang, mobile })); }, [route, lang, mobile]);
  const go = (page, params = {}) => { setRoute({ page, ...params }); setSide(false); if (main.current) main.current.scrollTo(0, 0); };
  const toast = (tone, title, message) => { const id = Math.random(); setToasts((x) => [...x.slice(-2), { id, tone, title, message }]); setTimeout(() => setToasts((x) => x.filter((y) => y.id !== id)), 4200); };
  const app = { go, toast, setLang };
  const Screen = A_SCREENS[route.page] || ADashboard;
  const body = (
    <LocaleProvider lang={lang} compact={mobile} style={{ height: '100%' }}>
      {route.page === 'login' ? <div style={{ height: '100%', overflowY: 'auto' }}><ALogin app={app} /></div> : (
        <div className="a-shell">
          {(!mobile || side) && <ASidebar page={route.page} go={go} onClose={mobile ? () => setSide(false) : null} />}
          {mobile && side && <div className="d-overlay" onClick={() => setSide(false)} />}
          <div className="a-main" ref={main}>
            <ATopbar onMenu={() => setSide(true)} onLang={setLang} go={go} toast={toast} />
            <Screen key={route.page + (route.id || '')} app={app} route={route} />
          </div>
        </div>
      )}
      <div className="d-toasts">{toasts.map((x) => <Toast key={x.id} {...x} onClose={() => setToasts((y) => y.filter((z) => z.id !== x.id))} />)}</div>
    </LocaleProvider>
  );
  return (
    <div style={{ height: '100vh', display: 'flex', flexDirection: 'column' }}>
      <AToolbar route={route} go={go} lang={lang} setLang={setLang} mobile={mobile} setMobile={setMobile} />
      {mobile ? (
        <div style={{ flex: 1, minHeight: 0, overflowY: 'auto', background: '#E9E8E3', padding: '28px 0 40px', display: 'grid', placeItems: 'start center' }}>
          <div style={{ width: 390, height: 844, borderRadius: 44, border: '10px solid #181816', overflow: 'hidden', transform: 'translateZ(0)', background: '#fff', boxShadow: 'var(--shadow-lg)' }}>{body}</div>
        </div>
      ) : <div style={{ flex: 1, minHeight: 0, transform: 'translateZ(0)' }}>{body}</div>}
    </div>
  );
}

return {cx,formatPrice,LocaleProvider,useLocale,Icon,ImageFrame,Button,IconButton,Badge,Chip,Tagline,Eyebrow,NodeDivider,Input,Select,SearchBar,Checkbox,Radio,RadioCard,QuantityStepper,VariantPicker,SwatchPicker,LangSwitch,DOTS_MEGA_DEFAULT,MegaMenu,Navbar,Footer,Breadcrumbs,Pagination,Tabs,Accordion,WishlistPrompt,ProductCard,BrandCard,CategoryCard,ServiceCard,ArticleCard,ProjectCard,Gallery,BeforeAfter,StatStrip,LogoStrip,ShareBar,FilterSidebar,ActiveFilters,SpecTable,FileDownloads,Toast,Progress,CartLine,CartDrawer,CheckoutForm,OrderSummary,K_PRODUCTS,K_BRANDS,K_CATEGORIES,K_SUBCATS,K_SERVICES,K_FACETS,K_TEMPS,K_FINISHES,K_CART_START,kPh,K_BLOG_CATS,K_ARTICLES,K_PROJECT_TYPES,K_PROJECTS,K_DONE,aT,A_STATUS,A_PAY_METHODS,A_PRODUCTS,A_GOVS,A_ORDERS,A_LEAD_SOURCES,A_LEADS,A_CONSULT_TYPES,A_CONSULTS,A_COUPONS,A_DELIVERY,A_USERS,A_CATS,A_TEMPLATES,A_SWATCHES,A_ATTRIBUTES,A_BRANDS,A_SERVICES,A_SERVICE_INCLUDED,A_FAQ,A_PHASES,A_PROJECT_TYPES,A_PROJECTS,A_BLOG_CATS,A_TAGS,A_POSTS,A_CONTENT_PAGES,A_CUSTOMERS,A_GUEST_ORDERS,A_SUB_SOURCES,A_SUBSCRIBERS,APill,ASwitch,ACard,AHead,AGrid,ABi,AAlert,AEmpty,AMenu,ADrawer,ATable,useReorder,AFieldLabel,ASidebar,ATopbar,AImage,AFile,AGallery,ARich,ARepeat,ACollapse,AProductPicker,AChips,ASlugs,ASeo,AVideo,A_ICON_SET,AIconPick,ABtn,AList,ARowActions,AEditor,ALogin,ADashboard,AProducts,AProductEdit,ACategories,ABrands,ABrandEdit,AAttributes,AAttributeEdit,AOrders,AOrderDetail,ALeads,AConsultations,ACoupons,ADelivery,AUsers,APayments,ASettings,AServices,AServiceEdit,AProjects,AProjectEdit,ABlog,APostEdit,ABlogTax,APageContent,AContentPages,AContentPageEdit,ACustomers,ACustomerDetail,ANewsletter,AApp};