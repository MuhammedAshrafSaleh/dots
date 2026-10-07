
import React from 'react';

const kImg = (en, ar) => ({ en, ar: ar || en });

export const K_PRODUCTS = [
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

export const K_BRANDS = [
  { id: 'philips', name: 'Philips', origin: { en: 'Netherlands', ar: 'هولندا' }, count: 1412 },
  { id: 'legrand', name: 'Legrand', origin: { en: 'France', ar: 'فرنسا' }, count: 1240 },
  { id: 'schneider', name: 'Schneider Electric', origin: { en: 'France', ar: 'فرنسا' }, count: 1106 },
  { id: 'delta', name: 'Delta Light', origin: { en: 'Belgium', ar: 'بلجيكا' }, count: 688 },
  { id: 'osram', name: 'Osram', origin: { en: 'Germany', ar: 'ألمانيا' }, count: 802 },
  { id: 'abb', name: 'ABB', origin: { en: 'Switzerland', ar: 'سويسرا' }, count: 954 },
  { id: 'flos', name: 'Flos', origin: { en: 'Italy', ar: 'إيطاليا' }, count: 196 },
  { id: 'artemide', name: 'Artemide', origin: { en: 'Italy', ar: 'إيطاليا' }, count: 174 },
];

export const K_CATEGORIES = [
  { id: 'pend', name: { en: 'Pendants & chandeliers', ar: 'معلقات ونجف' }, count: 1148, imageLabel: kImg('Lifestyle: pendants over dining table, 2700K') },
  { id: 'down', name: { en: 'Downlights & spots', ar: 'سبوتات' }, count: 960, imageLabel: kImg('Lifestyle: kitchen ceiling, downlights on') },
  { id: 'out', name: { en: 'Outdoor lighting', ar: 'إضاءة خارجية' }, count: 1320, imageLabel: kImg('Lifestyle: villa facade at dusk') },
  { id: 'smart', name: { en: 'Smart home', ar: 'المنزل الذكي' }, count: 720, imageLabel: kImg('Lifestyle: hand on smart panel') },
  { id: 'elec', name: { en: 'Switches & sockets', ar: 'مفاتيح وبرايز' }, count: 2140, imageLabel: kImg('Detail: switch plate on plaster wall') },
  { id: 'strip', name: { en: 'LED strips & profiles', ar: 'شرائط ليد وبروفايلات' }, count: 540, imageLabel: kImg('Lifestyle: cove lighting in living room') },
];

export const K_SUBCATS = [
  { id: 's1', name: { en: 'Pendants', ar: 'معلقات' }, count: 612, imageLabel: kImg('Cut-out: pendant') },
  { id: 's2', name: { en: 'Chandeliers', ar: 'نجف' }, count: 188, imageLabel: kImg('Cut-out: chandelier') },
  { id: 's3', name: { en: 'Linear suspensions', ar: 'معلقات خطية' }, count: 164, imageLabel: kImg('Cut-out: linear') },
  { id: 's4', name: { en: 'Cluster pendants', ar: 'معلقات عنقودية' }, count: 92, imageLabel: kImg('Cut-out: cluster') },
  { id: 's5', name: { en: 'Ceiling roses & kits', ar: 'قواعد وملحقات' }, count: 92, imageLabel: kImg('Cut-out: canopy') },
];

export const K_SERVICES = [
  { id: 'design', num: '01', title: { en: 'Lighting design', ar: 'تصميم الإضاءة' }, desc: { en: 'Lux calculations, DIALux renders and a fixture schedule matched to your budget.', ar: 'حسابات شدة الإضاءة وتصميمات DIALux وجدول وحدات يناسب ميزانيتك.' }, imageLabel: kImg('Designer reviewing DIALux render with client') },
  { id: 'smart', num: '02', title: { en: 'Smart home installation', ar: 'تركيب المنزل الذكي' }, desc: { en: 'Lighting scenes, curtains, climate and security on one app — installed and programmed.', ar: 'مشاهد الإضاءة والستائر والتكييف والأمان في تطبيق واحد — تركيب وبرمجة.' }, imageLabel: kImg('Technician programming smart panel') },
  { id: 'contract', num: '03', title: { en: 'Electrical contracting', ar: 'مقاولات كهربائية' }, desc: { en: 'Wiring, distribution boards and certified testing for villas, offices and retail.', ar: 'تمديدات ولوحات توزيع واختبارات معتمدة للفيلات والمكاتب والمحلات.' }, imageLabel: kImg('Electrician at distribution board') },
  { id: 'maint', num: '04', title: { en: 'Maintenance & support', ar: 'الصيانة والدعم' }, desc: { en: 'Annual contracts, emergency call-outs and warranty handling for everything we supply.', ar: 'عقود سنوية وزيارات طارئة ومتابعة الضمان لكل ما نوفره.' }, imageLabel: kImg('Technician replacing driver on ladder') },
];

export const K_FACETS = {
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

export const K_TEMPS = [
  { value: '2700', label: '2700K', sub: { en: 'Warm', ar: 'دافئ' }, chip: '#FFD08A' },
  { value: '3000', label: '3000K', sub: { en: 'Warm white', ar: 'أبيض دافئ' }, chip: '#FFE2B0' },
  { value: '4000', label: '4000K', sub: { en: 'Neutral', ar: 'محايد' }, chip: '#FFF6E6', disabled: true },
];
export const K_FINISHES = [
  { value: 'black', label: { en: 'Matte black', ar: 'أسود مطفي' }, color: '#1E1E1C' },
  { value: 'white', label: { en: 'Satin white', ar: 'أبيض ساتان' }, color: '#F4F3EF' },
  { value: 'brass', label: { en: 'Brushed brass', ar: 'نحاسي مصقول' }, color: 'linear-gradient(135deg,#C9A45C,#E8CF96 50%,#B08A45)' },
];

export const K_CART_START = [
  { id: 'c1', name: { en: 'Arc LED Pendant Ø450', ar: 'معلقة ليد آرك Ø450' }, variant: { en: 'Matte black · 3000K', ar: 'أسود مطفي · 3000K' }, price: 4850, qty: 1 },
  { id: 'c2', name: { en: 'Trimless Downlight 12W', ar: 'سبوت غاطس 12 وات' }, variant: { en: 'White · 4000K · 36°', ar: 'أبيض · 4000K · 36°' }, price: 690, qty: 6 },
];

