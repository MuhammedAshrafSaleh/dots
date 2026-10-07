
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';
import { Input } from '../forms/Input.jsx';
import { Select } from '../forms/Select.jsx';
import { Checkbox } from '../forms/Checkbox.jsx';
import { RadioCard } from '../forms/RadioCard.jsx';

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
export function CheckoutForm({ delivery, onDelivery, payment, onPayment, contact, addresses }) {
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

