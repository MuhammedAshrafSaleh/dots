
import React from 'react';
import { useLocale } from '../core/LocaleProvider.jsx';
import { IconButton } from '../buttons/IconButton.jsx';
import { Button } from '../buttons/Button.jsx';
import { Tagline } from '../badges/Tagline.jsx';

/** Site footer on soft warm gray: brand + newsletter column, four link columns, legal/payment strip. */
export function Footer({ logoSrc, onNavigate }) {
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

