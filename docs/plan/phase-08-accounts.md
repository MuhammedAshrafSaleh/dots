# Phase 8 — Customer accounts

**Goal:** Optional accounts with orders, wishlist, addresses and faster checkout.

Rules: read only this file + PROGRESS.md at session start; open refs only when a task needs them; one commit per task; STOP at the end of the phase.

## T8.1 — Auth screens
- KLogin (mobile or email), KRegister (validation rules from PRD §9, terms/privacy links), KForgot (email, resend cooldown 60 s), KReset (30-min single-use token, logs out other devices). Throttling 5/15 min. 'Continue as guest' when coming from checkout.
- **Design/docs:** Account.jsx: KLogin, KRegister, KForgot, KReset
- **Done when:** Tests incl. lockout and disabled customer.

## T8.2 — Linking and merging
- On register and on 'save your details': attach guest orders where mobile AND email match. On login: merge session cart into customer cart.
- **Design/docs:** PRD §9
- **Done when:** Tests: mobile-only match does not link.

## T8.3 — Account area
- KAccount layout (side nav desktop, tabs mobile), overview, orders list (empty state), order detail (timeline, items, payment summary, address, payment method, Reorder, WhatsApp help), wishlist (remove, add all to cart), addresses (drawer form, default, delete confirm), profile (edit details, change password).
- **Design/docs:** Account.jsx: KAccount, KA*
- **Done when:** Tests: customers only see their own orders.

## T8.4 — Wiring
- Navbar account dropdown, wishlist hearts (cards + product page) with logged-out prompt, checkout logged-in state (prefill + address cards), confirmation 'save your details' card, coupon once-per-customer.
- **Design/docs:** Navbar.jsx, ProductCard.jsx, CheckoutForm.jsx, Orders.jsx: KOSaveAccount
- **Done when:** End-to-end test guest → order → save account → sees order.

## T8.5 — Admin: customers
- ACustomers (filters, totals) and ACustomerDetail (profile, addresses, orders, wishlist, notes, disable/enable).
- **Design/docs:** admin-kit.jsx: ACustomers, ACustomerDetail
- **Done when:** Disable logs the customer out.

## Phase checklist
- [ ] Accounts complete
- [ ] Guard separation verified
- [ ] Tests green
