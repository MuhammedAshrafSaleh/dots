# Phase 7 Report — Cart, checkout, payments, orders

**Branch:** `phase-7` (local only, branched from `main` after phase-6 merge)
**Date:** 2026-10-16
**Gate result:** 492 tests passed / 2 skipped · PHPStan 0 errors · Pint clean · Vite build OK

---

## Commits (main..phase-7)

| Hash | Title |
|------|-------|
| `ecf0198` | T7.1: Cart — service, drawer, page, coupon rules |
| `7c70a09` | T7.2: Checkout — CheckoutForm, PlaceOrder transaction, confirmation |
| `2dc2ad6` | T7.3: Confirmation (3 variants), payment-failed, track order |
| `b3bff3a` | T7.1-7.3: fix double Alpine on public site + silent overselling guard |
| `0de82a6` | fix: products filter drawer visible on desktop, checkout summary fixed on mobile, cart delivery estimate uses project method |
| `c354d98` | T7.4: InstaPay via WhatsApp — release expired reservations + order message |
| `6cbc949` | T7.5: Paymob driver (Intention/Unified Checkout) — PaymentGateway + webhook/return HMAC + idempotency |
| `a3b297d` | T7.6: Admin orders — list (tabs/filters/CSV) + detail (status/payment update, notes, cancel restores stock) |
| `b5a5899` | T7.7: Admin coupons, delivery, payments, leads, consultations, newsletter + dashboard KPIs |
| `3ea86db` | T7.8: New-order email notification to admin contact email (queued) |

---

## Task summaries

### T7.1 — Cart
`ecf0198`

Cart service (session for guests, DB for authenticated customers). `CartDrawer` and `/cart` page: qty stepper, remove, coupon apply/remove with inline errors, delivery estimate, totals with VAT note, "Quote this cart" WhatsApp link, cross-sell suggestions. Tests cover coupon rules: minimum total, date window, usage limit, once-per-customer.

### T7.2 — Checkout
`7c70a09`

`CheckoutForm` Livewire: contact fields (Egyptian mobile validation), optional company + tax ID, governorate-filtered delivery methods (free-over threshold, requires-address flag), saved addresses for logged-in customers, payment method selection (card hidden when Paymob not configured). `PlaceOrder` action runs in a DB transaction: stock decrement (never negative), coupon redemption, product/variant/price/address snapshots, first history row, sequential order number. Tests per payment method; overselling guard enforced silently.

### T7.3 — Confirmation, payment-failed, track order
`2dc2ad6`

Three confirmation variants (COD, InstaPay, card). `KPaymentFailed` offers retry / switch to COD / switch to InstaPay. `KTrack` looks up by order number + mobile (no cross-order data leak); renders timeline from order histories and expected delivery date. SMS and courier copy removed throughout.

### T7.4 — InstaPay via WhatsApp
`c354d98`

`awaiting_transfer` status; `reserved_until` read from settings. Redirect to WhatsApp with pre-filled instapay message and click logged. `ReleaseExpiredReservations` scheduled job cancels timed-out reservations, restores stock, appends history note. Tested with time-travel.

### T7.5 — Paymob driver
`6cbc949`

`PaymentGateway` contract + `PaymobGateway` implementation using the Intention / Unified Checkout API. Creates payment intention, redirects to hosted checkout, handles return URL, verifies webhook HMAC, stores `webhook_events` rows for idempotency, writes `payments` rows, updates order payment status + history. All config from `.env` only. Tests cover valid HMAC, invalid HMAC (rejected), duplicate event (idempotent), failed payment.

### T7.6 — Admin: orders
`a3b297d`

`AOrders`: status tabs, filters (payment status, payment method, date range, search), CSV export. `AOrderDetail`: items table with coupon + VAT breakdown, Paymob log panel, status update with customer-visible note, internal note, expected delivery date, history timeline, customer card (guest / profile link + tax ID), "Send update on WhatsApp" button. Cancel action restores stock and writes history.

### T7.7 — Admin: coupons, delivery, payments, leads, consultations, newsletter, dashboard
`b5a5899`

- **ACoupons** — CRUD: code, type (flat/percent), value, min total, dates, usage limit, once-per-customer.
- **ADelivery** — drag-reorder, per-method governorate assignments.
- **APayments** — read-only Paymob config status + webhook URL hint; InstaPay address + hold hours.
- **ALeads / AConsultations** — status workflow, notes, editable name/mobile, CSV export.
- **ANewsletter** — subscriber list with CSV export.
- **ADashboard KPIs** — today's orders, month revenue, WhatsApp clicks, new consultations, recent orders table, low-stock alerts, latest leads.

### T7.8 — Notifications
`3ea86db`

`NewOrderMail` queued mailable: subject "New order {number}", Markdown view with customer name/mobile, payment method/status, total, items table, "View order in admin" button. `PlaceOrder::dispatchNewOrderMail()` reads `contact.email` from Settings, skips silently if blank. Tests: `Mail::assertQueued` with correct order id + recipient; no mail queued when contact email empty. Published `resources/views/vendor/mail/` scaffolding so the `mail::` namespace resolves in tests.

---

## Decisions logged

- **Paymob API:** Intention / Unified Checkout API (owner confirmed during T7.5).
- **Cart storage:** guest = Laravel session; authenticated = `carts` table (already migrated in Phase 2).
- **Browser tests paused:** All new behavior covered by HTTP + Livewire component feature tests per the CLAUDE.md rule.

## Known pre-existing flakiness

- `tests/Feature/Admin/Attributes/AttributesCrudTest.php:27` — intermittently flaky; unrelated to Phase 7, unchanged.

## Phase checklist

- [x] Full purchase works for COD and InstaPay with empty Paymob env
- [ ] Card flow works with sandbox keys — requires owner to supply sandbox credentials for manual verification
- [x] Admin order management complete
- [x] Tests green (492 passed / 2 skipped)

---

## How to review

1. Open `http://dots.test` and run a complete COD purchase end-to-end (add to cart → apply coupon → checkout → confirmation).
2. Repeat with InstaPay: verify the WhatsApp redirect URL contains the correct message and the order lands in `awaiting_transfer`.
3. Admin: `/admin/orders` — check tabs, filters, CSV download; open an order, change status, add a note, verify timeline.
4. Admin: `/admin/coupons`, `/admin/delivery`, `/admin/payments`, `/admin/leads`, `/admin/newsletter`, `/admin/dashboard`.
5. For Paymob card flow: set sandbox keys in `.env`, place a test order, complete on Paymob hosted page, verify webhook updates order.
6. To test the new-order email: set `contact.email` in `/admin/settings`, place an order, check `storage/logs/laravel.log` for the queued mail (with `MAIL_MAILER=log`).

```
composer gates   # must stay green
git log --oneline main..phase-7
```
