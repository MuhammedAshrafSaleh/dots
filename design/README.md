# Design source (read before opening any file here)

This folder is the final Claude Design export, already unpacked. **Never look for or open the original `.html` bundles** (21 MB / 5 MB). Open only the file a task points to, and prefer `grep` over reading whole files.

```
design/
  website/
    src/ui_kits/website/*.jsx   one file per page group (see map below)
    src/components/**/*.jsx     shared components (Navbar, ProductCard, CheckoutForm…)
    styles/styles.css           design tokens + every d-* class. Port this, do not rewrite it.
    assets/images/              logo.png, mark.png, heroVid.mp4, ph0.jpg … ph39.jpg (stock placeholders)
  admin/
    src/admin-kit.jsx           ALL admin screens in one file (search by function name, see map below)
    styles/styles.css           tokens + d-* + a-* admin classes
    assets/images/logo.png
```

## How to read the JSX
- It is a React prototype, not production code. Use it for **layout, markup structure, class names, copy and states**. Rebuild it as Blade + Livewire + Alpine.
- `t('English', 'عربي')` and `{ en, ar }` objects = bilingual text. Decide per string: content from the DB (see `docs/CONTENT_MAP.md`) or a UI label (goes to `lang/en|ar/*.php`).
- `kPh(n)` = placeholder photo `assets/images/ph{n}.jpg`. Real images come from the database (Media Library). Use the placeholders only in seeders.
- `data.jsx`, `data2.jsx`, and the `KA_*`, `KO_*` constants in Account.jsx and Orders.jsx = sample data. Use it for seeders.
- Ignore everything that is only for previewing the kit: `KToolbar` / `AToolbar`, `KAKit` "Kit preview · state" tabs, every "Kit demo:" hint, `localStorage` usage, the fake `app.toast` flows that stand in for real requests.

## Website page map (design/website/src/ui_kits/website)
| Page | File · component |
|---|---|
| Home | Home.jsx · KHome, KHero |
| Products list | Products.jsx · KProducts |
| Product detail | Products.jsx · KProductDetail (+ Account.jsx · KFavButton) |
| Category | Brands.jsx · KCategory |
| Brands / Brand detail | Brands.jsx · KBrands, KBrandDetail |
| Services / Service detail | Services.jsx · KServices, KServiceDetail |
| Projects current / previous / detail | Projects.jsx · KProjectsCurrent, KProjectsPrevious, KProjectDetail |
| Blog / Article | Blog.jsx · KBlog, KArticle |
| About | About.jsx · KAbout, KMediaHero |
| Cart / Checkout | Cart.jsx · KCart, KCheckout + components/commerce/CheckoutForm.jsx |
| Order confirmation / Payment failed / Track order | Orders.jsx · KOrderConfirm, KPaymentFailed, KTrack |
| Content pages (Delivery, Returns, Warranty, Careers, Terms, Privacy) | Content.jsx · KContentPage |
| Login / Register / Forgot / Reset | Account.jsx · KLogin, KRegister, KForgot, KReset |
| Account area (overview, orders, order, wishlist, addresses, profile) | Account.jsx · KAccount and KA* |
| Shared sections | Shared.jsx · KPageHead, KHead, KSection, KQuoteBand, KProductGrid, KReveal |
| Header / mega menu / footer / cart drawer | components/navigation/*, components/commerce/CartDrawer.jsx |
| Motion (preloader, page curtain, cursor) | Motion.jsx |

## Admin screen map (design/admin/src/admin-kit.jsx, search `function <Name>(`)
Login `ALogin` · Dashboard `ADashboard` · Products `AProducts` · Product edit `AProductEdit` (+ `APVariants`, `APSpecs`, `APImages`, `APFiles`) · Categories `ACategories`/`ACPanel` · Brands `ABrands`/`ABrandEdit` · Attributes `AAttributes`/`AAttributeEdit` · Page content `APageContent`/`APSection` · Services `AServices`/`AServiceEdit` · Projects `AProjects`/`AProjectEdit` · Blog `ABlog`/`APostEdit`/`ABlogTax` · Content pages `AContentPages`/`AContentPageEdit` · Orders `AOrders`/`AOrderDetail` · Customers `ACustomers`/`ACustomerDetail` · Coupons `ACoupons` · WhatsApp leads `ALeads` (`ALeadPage`) · Consultations `AConsultations` · Newsletter `ANewsletter` · Delivery `ADelivery` · Payments `APayments` · Settings `ASettings` (+ Contact/Commerce/Templates/Footer/Seo) · Users `AUsers` · Layout `ASidebar`, `ATopbar` · Building blocks `ATable`, `ACard`, `ADrawer`, `ABi`, `ASlugs`, `ASeo`, `AImage`, `AGallery`, `ARich`, `ARepeat`, `AProductPicker`, `AIconPick`.

## In the design but OUT of scope (do not build)
- Before / After slider on the completed project page (`BeforeAfter.jsx`).
- "Instalments" and "InstaPay / Fawry · reference by SMS" options in `CheckoutForm.jsx`: payment options are rendered from the `payment_methods` table (3 methods).
- Anything that says SMS (order confirmation, track order, profile mobile change). No SMS anywhere.
- Courier / Bosta / AWB text in the tracking timeline.
- "Trade & project accounts" link.
- Spec groups (Optical / Electrical / Physical): specs are a flat name + value list.
- Products list bulk action "Hide": use "Archive".
