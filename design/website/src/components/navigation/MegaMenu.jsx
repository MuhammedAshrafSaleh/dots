
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';
import { ImageFrame } from '../core/ImageFrame.jsx';
import { Button } from '../buttons/Button.jsx';

export const DOTS_MEGA_DEFAULT = [
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
export function MegaMenu({ categories = DOTS_MEGA_DEFAULT, onNavigate, promo, style }) {
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

