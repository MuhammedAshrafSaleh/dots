# Phase 7 — Cart, checkout, payments, orders

**Goal:** Customers can buy with COD, card (when configured) or InstaPay via WhatsApp; staff manage orders.

Rules: read only this file + PROGRESS.md at session start; open refs only when a task needs them; one commit per task; STOP at the end of the phase.

## T7.1 — Cart
- Cart service (session for guests, carts table for customers), CartDrawer + cart page Livewire: qty stepper, remove, coupon apply/remove with clear errors, delivery estimate, totals, VAT note, 'Quote this cart' WhatsApp, suggestions (cart_suggestion relations).
- **Design/docs:** Cart.jsx, CartDrawer.jsx, CartLine.jsx, OrderSummary.jsx
- **Done when:** Tests: coupon rules (min total, dates, limit, once per customer).

## T7.2 — Checkout
- CheckoutForm Livewire: contact, company + tax ID, governorate-filtered delivery methods (free-over, requires-address), saved addresses for customers, payment methods (card hidden if Paymob not configured), validation (Egyptian mobile), place order in a DB transaction (stock decrement, coupon redemption, snapshots, history row, order number).
- **Design/docs:** Cart.jsx: KCheckout; CheckoutForm.jsx
- **Done when:** Tests per payment method; stock never negative.

## T7.3 — Confirmation, payment failed, track order
- KOrderConfirm (3 payment variants; guest 'save your details' card wired in Phase 8), KPaymentFailed (retry card / switch to COD / InstaPay), KTrack (number + mobile, timeline from histories, expected delivery). Remove every SMS/courier text from copy.
- **Design/docs:** Orders.jsx
- **Done when:** Tests: track requires matching mobile; no data leak between orders.

## T7.4 — InstaPay via WhatsApp
- awaiting_transfer status, reserved_until from settings, redirect to WhatsApp with instapay_order template + logging, scheduled job releasing expired reservations (cancel + restore stock + history note).
- **Design/docs:** PRD §6
- **Done when:** Test with time travel.

## T7.5 — Paymob driver
- ASK the owner: Intention API or legacy? Then implement PaymentGateway + PaymobGateway: create payment, redirect/iframe, return URL handling, webhook with HMAC verify, webhook_events idempotency, payments rows, order payment status + history. Config only from env.
- **Design/docs:** PRD §8
- **Done when:** Tests with recorded fake payloads: valid HMAC, invalid HMAC, duplicate event, failed payment.

## T7.6 — Admin: orders
- AOrders (tabs by status, filters: payment status/method, dates, search, CSV export) and AOrderDetail (items, totals with coupon + VAT, Paymob log, status + payment update with customer-visible note, internal note, expected delivery, history timeline, customer card with Guest/profile link + tax ID, 'Send update on WhatsApp').
- **Design/docs:** admin-kit.jsx: AOrders, AOrderDetail
- **Done when:** Tests: status change writes history; cancel restores stock.

## T7.7 — Admin: coupons, delivery, payments, leads, consultations, newsletter, dashboard
- ACoupons, ADelivery (drag order, governorates), APayments (read-only Paymob status + webhook URL, InstaPay address + hold hours), ALeads/AConsultations (status, notes, editable name/mobile, CSV), ANewsletter (CSV), ADashboard KPIs (today's orders, month revenue, WhatsApp clicks, new consultations, recent orders, low stock, latest leads).
- **Design/docs:** admin-kit.jsx
- **Done when:** Tests for each screen's main action.

## T7.8 — Notifications
- New order email to settings contact email (queued). No customer messages.
- **Design/docs:** PRD §7
- **Done when:** Mail fake test.

## Phase checklist
- [ ] Full purchase works for COD and InstaPay with empty Paymob env
- [ ] Card flow works with sandbox keys (owner provides)
- [ ] Admin order management complete
- [ ] Tests green
