# Payments — manual QA smoke script

Hand-run this on **dots.test** before merging `payments-hardening` and again before go-live.
Every case is derived from what the code in these files actually does, not from the plan:
`PlaceOrder`, `PaymobGateway`, `ReleaseExpiredReservations` (action + command),
`ReleaseOrderCoupon`, `CheckoutForm`, `PaymentFailed`, `OrderConfirmationController`,
`PaymobReturnController`, `PaymobWebhookController`, `UpdateOrderStatus`, `CartManager`,
`Coupons`, `Order::viewableInSession`, `TrackOrder`.

> **Do not change application code while running this.** If a case fails, note the ID and the
> observed behaviour; fixes are a separate session.

---

## 0. Prerequisites & conventions

### 0.1 Environment

- `php artisan schedule:work` **must not** be running unless a case says so. The stock sweeper
  (`orders:release-reservations`, scheduled every 15 min) will otherwise fire mid-test and
  invalidate timing cases. For the timing cases you run the sweep **manually**:
  `php artisan orders:release-reservations`.
- `QUEUE_CONNECTION=sync` locally, so `NewOrderMail` dispatches inline. Mailpit UI at
  http://localhost:8025 — use it to confirm the admin new-order email.
- Card cases need Paymob **sandbox** keys in `.env`:
  `PAYMOB_SECRET_KEY`, `PAYMOB_PUBLIC_KEY`, `PAYMOB_INTEGRATION_ID_CARD`, `PAYMOB_HMAC_SECRET`,
  `PAYMOB_BASE_URL`. The card method is hidden on checkout unless **all three** of
  secret/public/integration-id are filled (`PaymobGateway::isConfigured()`).
- `APP_URL` must be the dots.test origin. The **browser-return** route settles a card order on
  its own (verified HMAC), so the happy card path works **without** a tunnel. Only the
  abandoned-browser / server-to-server **webhook** case needs a public tunnel (Herd Share /
  ngrok) with the Paymob sandbox callbacks pointed at it.
- Set `commerce.card_payment_window_minutes` to its default (60) in Admin → Payments unless a
  case changes it. For timing cases it is far faster to **time-travel the DB clock is not
  available in the browser**, so timing cases instead temporarily lower the window to its floor
  (30) *or* edit `reserved_until` to the past via tinker — each case says which.

### 0.2 Paymob sandbox test cards

Paymob rotates these; **confirm the current values in your Paymob sandbox dashboard** before you
rely on them. As documented at time of writing:

| Purpose | Card | Exp | CVV | OTP (3-D Secure) |
|---|---|---|---|---|
| **Success** | `4987654321098769` (Visa) or `5123456789012346` (Mastercard) | any future, e.g. `12/30` | `123` | if prompted, `123456` |
| **3-D Secure challenge** | same success card | future | `123` | **complete** the OTP step with `123456` |
| **Decline** | use the sandbox "declined" card from your dashboard, **or** the reliable method: enter a **wrong OTP** (`000000`) on the 3-D Secure page to force a failed transaction |

Which case uses which is named per case (CARD-01 success, CARD-02 3-DS, CARD-03 decline).

### 0.3 Fixture data to create once (via the real app / admin, not raw SQL)

- **Product L** — one product, one variant, **stock_qty = 1**, has a price. (last-unit cases)
- **Product N** — one product, one variant, **stock_qty = 5**, has a price. (general cases)
- **Coupon ONCE10** — 10% percent, `once_per_customer = true`, no usage limit.
- **Coupon LAST1** — fixed amount, `usage_limit = 1`, `used_count = 0`.
- **Coupon FULL100** — percent 100 (so discount ≥ subtotal), no limit. (zero-total case)
- **Coupon SOON** — valid, `ends_at` a few minutes out (editable). (expiry-mid-flow case)
- A **registered customer** C1 (known mobile) and a second customer C2 (different mobile).
- An **InstaPay address** set in Admin → Payments, and a separate run with it blank.

---

## A. Happy paths (baseline — run these first)

### HP-01 — COD order end to end · blocker
- **Pre:** guest or C1, cart has Product N ×1, no coupon, an address-requiring delivery method exists.
- **Steps:** cart → checkout → fill contact + address → choose a delivery method → payment = Cash on delivery → place.
- **Expected:** lands on `order.confirmation`; payment badge = "Cash on delivery" tone neutral; `reserved_until` is **null** (COD never swept); stock of N went 5→4; Mailpit shows the new-order mail. Confirmation shows order number, placed date, correct total.
- **Why blocker:** COD must work with zero Paymob config.

### HP-02 — InstaPay-via-WhatsApp order · blocker
- **Pre:** InstaPay address set in Admin → Payments; cart has Product N ×1.
- **Steps:** checkout → payment = InstaPay via WhatsApp → place.
- **Expected:** confirmation shows the **"awaiting transfer"** variant: eyebrow "reserved", the InstaPay address (`data-instapay-address`), the **exact amount**, the order number, and a **deadline** (`reserved_until` ≈ now + `instapay_hold_hours`, default 24h), localized. Stock decremented. No code error.

### HP-03 — Card order, successful payment · blocker
- **Pre:** sandbox keys filled; cart has Product N ×1.
- **Steps:** checkout → payment = Card → place → on Unified Checkout pay with the **success card** (CARD-01) → return.
- **Expected:** redirected to Unified Checkout, then back to `order.confirmation` showing paid. A `payments` row exists with `status=paid`, `hmac_verified=true`, correct `card_last4`; `webhook_events` has the event (if webhook reachable); order `payment_status=paid` and `reserved_until` **cleared (null)**; stock decremented. History has "Card payment received via Paymob."

### HP-04 — Card order, 3-D Secure challenge · major
- **Pre:** as HP-03.
- **Steps:** place card order → on Unified Checkout use CARD-02 and **complete** the OTP (`123456`).
- **Expected:** same successful end state as HP-03.

---

## B. Card race conditions & timing

> Blockers first.

### RC-01 — Paid webhook clears the deadline so the sweeper can't cancel a paid order · blocker
- **Pre:** card order just paid (HP-03 end state), `reserved_until` null.
- **Steps:** run `php artisan orders:release-reservations`.
- **Expected:** output "Released 0"; the paid order is untouched (still paid, not cancelled, stock unchanged). A paid order must never be swept.

### RC-02 — Abandoned card order (no payment) released after its window · blocker
- **Pre:** place a card order (HP-03 flow) but **do not pay** — close the Unified Checkout tab. Order is `pending`, stock decremented, `reserved_until` ≈ now+60m.
- **Setup the expiry:** via tinker, set this order's `reserved_until` to a past time (e.g. `now()->subMinute()`). (Real-app alternative: set the card window to 30 in admin, wait 30 min — slower.)
- **Steps:** run `php artisan orders:release-reservations`.
- **Expected:** order → **cancelled**, stock **restored exactly once** (back to original), `reserved_until` null, history note "Card payment not completed in time — order cancelled and reserved stock released." (bilingual). Run the command **again** → "Released 0", stock not double-restored.

### RC-03 — Declined card order released after its window · blocker
- **Pre:** place a card order, on Unified Checkout **force a decline** (CARD-03 / wrong OTP), land on payment-failed, then **leave it** (do not retry/switch). Order is `failed`, stock still decremented.
- **Setup:** set its `reserved_until` to the past via tinker.
- **Steps:** run the sweep.
- **Expected:** order cancelled, stock restored once, card note written. Re-run → no-op.

### RC-04 — Webhook arrives while the sweeper is eligible to cancel (late success after cancel) · blocker
- **Pre:** take the cancelled-after-abandon order from RC-02 (now cancelled, stock already restored).
- **Steps:** simulate / allow a **late successful** webhook for that order (in a tunnel run: the real webhook arriving after cancel; otherwise replay the sandbox's processed-callback). 
- **Expected:** a `payments` row recorded `paid`, order `payment_status` becomes `paid` **but** `status` stays **cancelled**, **stock is NOT re-decremented**, and a history note flags it: "Card payment received AFTER auto-cancellation — review and refund or re-fulfil." (bilingual). A human must act; the app must not silently un-cancel.

### RC-05 — Retrying from payment-failed refreshes the deadline (no mid-retry sweep) · major
- **Pre:** a `failed` card order sitting on payment-failed with `reserved_until` close to or just past now.
- **Steps:** on payment-failed choose "retry card" → observe redirect to Unified Checkout. Before paying, check the order's `reserved_until`.
- **Expected:** `createCheckout` **pushed `reserved_until` forward** to now+window, so a concurrent sweep will no-op. (A new `payments` pending row is created per retry — expected.)

### RC-06 — Return redirect and webhook both settle the same transaction · major
- **Pre:** tunnel run so both the browser return and the server webhook fire for one successful payment.
- **Steps:** pay a card order successfully; let both callbacks land.
- **Expected:** exactly **one** effective settlement — `webhook_events` has one row for the transaction id; the payment/order are `paid` once; no duplicate history spam from double-processing (the second call finds the existing `webhook_events` row and no-ops).

---

## C. Backwards / stale state the customer can reach

> **Blockers first.** BK-01 and BK-02 are the two I am most worried about.

### BK-01 — Re-opening an old payment-failed link for an order that is NOW PAID · blocker — see GAP-01
- **Pre:** a card order that **failed once**, then was **paid** on retry (so it is now `paid`, not cancelled). Keep the payment-failed URL (`/{locale}/order/DT-xxxx/payment-failed`).
- **Steps:** while still in the session that can view it, open that payment-failed URL again. Try "Cash on delivery", then separately try "retry card".
- **Expected (correct behaviour):** the page should refuse to mutate a **paid** order — it should not downgrade `payment_status` to pending/COD and must not start a **second** card charge.
- **Reality to verify:** `PaymentFailed` only guards on `status === Cancelled`, never on `payment_status === Paid`. See **GAP-01** — likely lets you both re-charge and downgrade a paid order. **Investigate and record exactly what happens.**

### BK-02 — Back button to a used/expired Unified Checkout URL · major
- **Pre:** completed card payment (HP-03).
- **Steps:** press browser Back until you reach the Paymob Unified Checkout page; try to pay again.
- **Expected:** Paymob should reject a reused/expired `clientSecret`. Confirm our side does **not** create a second paid payment for the order. (This depends on Paymob; mark as investigate if it lets you re-submit.)

### BK-03 — Refreshing / revisiting the confirmation page · minor
- **Pre:** any confirmation page in the current session.
- **Steps:** refresh (F5) several times; press Back to it after navigating away.
- **Expected:** page re-renders identically (it is a GET gated by `viewableInSession`); no duplicate orders, no state change.

### BK-04 — Same order open in two tabs, switch method in one · major
- **Pre:** a `failed` card order; open its payment-failed page in **two** tabs.
- **Steps:** in tab 1 switch to COD (redirects to confirmation). In tab 2 (still showing options) choose InstaPay → proceed.
- **Expected:** each `proceed()` re-loads the order under a row lock; the order ends in whichever method committed last, with a consistent `payment_status`/`reserved_until` for that method (COD→null, InstaPay→now+hold). No stock movement either way; no crash. Record the final method.

### BK-05 — Payment-failed link after the sweeper already cancelled the order · major
- **Pre:** a `failed`/`pending` card order whose `reserved_until` you set to the past; run the sweep so it is **cancelled**.
- **Steps:** open its payment-failed URL; try each option (retry / COD / InstaPay).
- **Expected:** page shows the **expired** state (eyebrow "Reservation expired", heading "This order was released"); every option mutates **nothing**; the only action is **"Re-add items to cart"**, which repopulates the cart from the order's items clamped to current stock and redirects to the cart. The order stays cancelled; its old coupon is **not** re-applied.

### BK-06 — Guest returns to confirmation after the session ends · major
- **Pre:** place an order as a **guest**; note the confirmation URL.
- **Steps:** clear cookies / open the URL in a fresh private window (no session, not signed in).
- **Expected:** **404** (`viewableInSession` false). No order detail leaks.

---

## D. Stock boundaries

### ST-01 — Last unit, two customers racing · blocker
- **Pre:** Product L (stock 1). Customer A and Customer B each have L ×1 in cart (two browsers/sessions).
- **Steps:** both reach checkout; submit **place** as close to simultaneous as you can.
- **Expected:** exactly **one** order succeeds (stock 1→0); the other gets a validation error from `CheckoutException::unavailable()` → `ui.checkout_item_unavailable`, **no** order created for the loser, stock never negative.

### ST-02 — Admin drops stock below cart qty mid-checkout · blocker
- **Pre:** Product N in cart ×3; in admin reduce N's stock to 1 before placing.
- **Steps:** place the order.
- **Expected:** whole order **rejected** with `ui.checkout_item_unavailable`; nothing partially placed; stock stays 1.

### ST-03 — Zero-stock variant can't be bought · major
- **Pre:** Product L reduced to stock 0 while L ×1 is in a cart.
- **Steps:** place.
- **Expected:** rejected (`unavailable`), no order.

### ST-04 — Variant deactivated between placing (card) and paying · major
- **Pre:** place a **card** order for Product N, go to Unified Checkout but don't pay yet; in admin **deactivate** N's variant.
- **Steps:** complete the card payment.
- **Expected:** payment still settles `paid` against the snapshot (order already captured product/price); order is honoured. Stock stays decremented. (Deactivation affects only future carts and the re-add path, not a placed order.)

### ST-05 — Re-add to cart after cancel, stock partly gone · major
- **Pre:** BK-05 cancelled order (had N ×3); meanwhile reduce N's stock to 2 and fully deactivate another line if the order had one.
- **Steps:** on the expired page click "Re-add items to cart".
- **Expected:** cart repopulated with N clamped to **2** (a bilingual "we lowered some quantities" notice naming it); any inactive/0-stock line **dropped** with a bilingual "no longer available" notice; redirect to cart. If nothing could be re-added, the "none available" notice shows.

---

## E. Coupons

### CP-01 — Coupon consumed on placement is refunded on auto-cancel · blocker
- **Pre:** C1, cart qualifies; apply **LAST1** (`usage_limit 1`). Place a **card** or InstaPay order (so it has a deadline), do not pay.
- **Steps:** confirm `coupons.used_count` for LAST1 is now 1 and a `coupon_redemptions` row exists (`released_at` null). Set `reserved_until` to the past; run the sweep.
- **Expected:** order cancelled; the redemption row is **kept** but `released_at` stamped; `used_count` decremented back to **0**. Re-run sweep → no double-decrement. LAST1 is usable again.

### CP-02 — once_per_customer coupon reusable after auto-cancel · blocker
- **Pre:** C1 applies **ONCE10**, places a card/InstaPay order, it auto-cancels (as CP-01).
- **Steps:** C1 starts a new cart and applies ONCE10 again.
- **Expected:** coupon **accepted** (the once-per-customer gate ignores rows with `released_at` set). Contrast: if the first order had been **paid/fulfilled** (not cancelled), re-applying ONCE10 must be **rejected** with `ui.coupon_used`.

### CP-03 — Coupon at its last remaining use · major
- **Pre:** LAST1 `used_count` one below `usage_limit` (i.e. 0 of 1 used, or set a 2-limit coupon to 1).
- **Steps:** apply it and place an order.
- **Expected:** accepted; `used_count` hits the limit. A **second** customer applying it now gets `ui.coupon_limit`.

### CP-04 — Coupon expires between placing and paying (card) · major
- **Pre:** apply **SOON** (ends in ~2 min) to a card order; place it (discount snapshotted on the order, `used_count` incremented). Let SOON expire.
- **Steps:** pay the card order.
- **Expected:** payment settles against the **order's snapshotted total** (already discounted) — coupon expiry does **not** change an already-placed order. No recalculation. Order paid at the discounted amount.

### CP-05 — Re-add to cart does not restore the old coupon · minor
- **Pre:** BK-05 cancelled order that had a coupon.
- **Steps:** "Re-add items to cart".
- **Expected:** cart has the items but **no** coupon (cart coupon was cleared at placement). Customer must re-enter a code; totals reflect no discount until they do.

### CP-06 — Coupon dropped when cart falls below its minimum · minor
- **Pre:** apply a coupon with a min-order-total to a qualifying cart.
- **Steps:** remove items until subtotal is below the minimum; view cart/checkout totals.
- **Expected:** coupon silently dropped from the session (`sessionDiscount` forgets it); no discount line; no error.

---

## F. Money correctness

### MN-01 — Amount sent to Paymob equals the order total · blocker
- **Pre:** card order with a non-round total (e.g. after a percent coupon and a delivery fee).
- **Steps:** place → inspect the created `payments` row `amount` and, on Unified Checkout, the amount shown.
- **Expected:** `amount_cents` = `round(order.total * 100)` exactly; the Paymob page shows the same EGP amount as the confirmation/summary total. No divergence from rounding.

### MN-02 — VAT is 14% **included**, not added · major
- **Pre:** any order.
- **Steps:** read the summary: subtotal, discount, delivery, VAT, total.
- **Expected:** `total = round(subtotal - discount + deliveryFee, 2)`; VAT = `round(total * 14 / 114, 2)` shown as **included within** the total (total is not subtotal+VAT). Changing nothing else, VAT tracks the total.

### MN-03 — Free-delivery threshold zeroes the fee · major
- **Pre:** a delivery method with `free_over_amount`; cart subtotal above it.
- **Steps:** at checkout pick that method.
- **Expected:** delivery fee shows 0 / "free"; total excludes the fee; VAT recomputed on the new total.

### MN-04 — Discounted total and its VAT · major
- **Pre:** apply a percent coupon.
- **Steps:** read totals.
- **Expected:** discount line present; `total = subtotal - discount + fee`; VAT on the discounted total. Place the order and confirm the order's stored `subtotal/discount/total/vat_amount` match what the summary showed.

### MN-05 — Zero-total order after a 100% coupon · major — see GAP-02
- **Pre:** apply **FULL100** so discount ≥ subtotal and pick a free/zero delivery so total = 0.
- **Steps (COD):** place COD → expected to succeed, confirmation shows total 0.
- **Steps (card):** try to place the **same** with **Card**.
- **Expected/Reality:** card `createCheckout` POSTs `amount = 0` to Paymob, which rejects a zero-amount intention → `PaymentException` → redirect to payment-failed, **but the order is already placed** (stock decremented, coupon consumed) and can never be card-paid. See **GAP-02**; record what the customer sees and whether the card option should even be offered at total 0.

### MN-06 — Rounding on odd quantities/prices · minor
- **Pre:** a variant with a price like 33.33, qty 3.
- **Steps:** read line total and subtotal.
- **Expected:** `line_total = round(price * qty, 2)`; subtotal is the rounded sum; no floating-point tail (e.g. 99.99, not 99.99000001).

---

## G. Payment method switching (same order, from payment-failed)

### SW-01 — Card → COD · blocker
- **Pre:** a `failed` card order on payment-failed (not yet swept).
- **Steps:** choose Cash on delivery → proceed.
- **Expected:** order `payment_method_code=cod`, `payment_status=pending`, **`reserved_until` set to null** (so the sweeper never cancels the now-COD order); stock **unchanged** (stays decremented); redirect to confirmation showing the COD variant; history note "Payment method changed to cod".

### SW-02 — Card → InstaPay · major
- **Pre:** as SW-01.
- **Steps:** choose InstaPay via WhatsApp → proceed.
- **Expected:** `payment_method_code=instapay_whatsapp`, `payment_status=awaiting_transfer`, `reserved_until` reset to now + `instapay_hold_hours`; stock unchanged; confirmation shows the InstaPay variant (address + amount + new deadline).

### SW-03 — Full cycle card → COD → InstaPay → card · major
- **Pre:** a `failed` card order; keep its payment-failed URL.
- **Steps:** switch to COD; then **revisit** the payment-failed URL and switch to InstaPay; then revisit and choose retry **card**.
- **Expected:** each switch updates method/status/`reserved_until` per SW-01/SW-02; the final retry-card call runs `createCheckout`, refreshes `reserved_until` to now+window, and redirects to Unified Checkout. Stock never moves across any switch. Each switch writes its own history row.

### SW-04 — Switching an order the sweeper just cancelled · major
- **Pre:** a `failed` card order whose `reserved_until` you set to past; run the sweep so it is cancelled; **then** open payment-failed.
- **Steps:** try COD / InstaPay / retry.
- **Expected:** expired state only; **no** mutation, **no** new payment, **no** stock movement; re-add-to-cart offered (same as BK-05).

---

## H. Locale (run each customer-facing screen in AR and EN)

### LO-01 — No raw JSON for any translatable value · blocker
- **Pre:** place orders in both `ar` and `en`; include a product and a governorate whose names are translatable.
- **Steps:** view confirmation, payment-failed, track-order, and admin order detail in both locales. Look specifically at: product name, variant label, delivery method name, governorate name, status/history notes.
- **Expected:** every value renders as localized text — **never** a raw `{"ar":"…","en":"…"}` JSON blob. (These are stored as translation arrays on the order/order items/address; a missing `getTranslation` call would leak JSON.)

### LO-02 — Every payments message exists in AR and EN · major
- **Steps:** trigger and read in both locales: `checkout_item_unavailable`, `checkout_cart_empty`, `too_many_attempts`, `mobile_invalid`; payment-failed `pf_*` (heading, body, the three option labels/descriptions, CTAs); expired `pf_expired_*` (eyebrow, heading, body, re-add CTA, dropped/reduced/none notices); failure reasons `pf_reason_*` (declined, insufficient funds, expired card, 3-DS failed, auth error, generic); confirmation `pay_status_*`, `confirm_*`, InstaPay address label + **fallback** copy + deadline label.
- **Expected:** all present, human-readable, no `ui.xxx` key echoed, Arabic reads naturally (not literal).

### LO-03 — RTL layout · major
- **Steps:** in AR, eyeball confirmation, payment-failed, and the checkout summary.
- **Expected:** RTL layout correct (alignment, icons, the money/meta columns), `prefers-reduced-motion` respected; then confirm LTR (EN) is also intact.

### LO-04 — Failure reason mapping, both locales · major
- **Pre:** force a decline (CARD-03). 
- **Steps:** read the reason line on payment-failed in AR and EN.
- **Expected:** a curated, neutral `pf_reason_*` line (never the raw gateway `failure_message`), ending by pointing at a next action; unknown codes fall back to `pf_reason_generic`. When there is no failed payment (e.g. gateway-unreachable throw), **no** reason line shows.

---

## I. Permissions & visibility

### PV-01 — Cannot view another customer's order · blocker
- **Pre:** C1 places an order; sign in as C2 (or be a guest without that order in session).
- **Steps:** open C1's confirmation and payment-failed URLs.
- **Expected:** **404** for both (`viewableInSession` requires session-placed or owning customer). No leak of contact, address, or amount.

### PV-02 — internal_note never reaches the customer · blocker
- **Pre:** as admin, add an **internal note** when updating an order's status.
- **Steps:** view that order's confirmation and the public **track-order** timeline as the customer.
- **Expected:** the internal note appears **only** in admin order detail; the public timeline shows `note` (customer-visible) / fallback copy, **never** `internal_note`.

### PV-03 — Track order requires exact number **and** mobile · major
- **Pre:** a known order (number + the mobile it was placed with).
- **Steps:** on /track-order try: correct number + **wrong** mobile; then correct number + correct mobile.
- **Expected:** wrong mobile → no result (empty state), order details not shown; correct pair → timeline shown. Neither value alone authenticates.

### PV-04 — Track-order lookup is rate-limited · minor
- **Steps:** submit 11 lookups within a minute from one IP.
- **Expected:** the 11th is blocked with `ui.too_many_attempts` (10/min/IP). (Checkout `place` is separately capped at 10/10min/IP — PV-05.)

### PV-05 — Checkout placement rate limit · minor
- **Steps:** attempt to place 11 orders within 10 minutes from one IP.
- **Expected:** the 11th blocked with `ui.too_many_attempts`.

### PV-06 — Forged/tampered Paymob return does not leak or mutate · blocker
- **Pre:** a real order number.
- **Steps:** hit `/{locale}/payments/paymob/return` with a bogus/missing `hmac` and that `merchant_order_id`.
- **Expected:** HMAC is verified **before** any order lookup → unverified → redirect to **cart**; the order is untouched, **no** payment row written, no signal of whether the order exists.

### PV-07 — Bad-HMAC / unknown-order webhook is a silent 200 · major
- **Steps:** POST to `/payments/paymob/webhook` with a bad HMAC, then with a valid-looking but unknown order.
- **Expected:** HTTP **200** with `{"ok":false}` (so Paymob doesn't retry), **nothing** written (`webhook_events`, `payments`, order all unchanged).

---

## J. Admin side

### AD-01 — Admin cancels a **paid** card order · blocker
- **Pre:** a `paid` card order (HP-03).
- **Steps:** Admin → order → set status **Cancelled**; add a customer note + internal note.
- **Expected:** stock **restored once**, coupon (if any) soft-released + `used_count` decremented, `reserved_until` null, one history row. **No automatic Paymob refund** happens — the money is still captured; refund is a **manual** dashboard step. Confirm the app does not claim a refund occurred.

### AD-02 — Admin moves an order **out of** Cancelled (cancelled → paid) · major — see GAP-03
- **Pre:** a cancelled order (stock already restored).
- **Steps:** Admin → set status back to **New/Processing/Paid**.
- **Expected (correct):** re-entering a live state should **re-reserve** stock (re-decrement) to avoid overselling.
- **Reality:** `UpdateOrderStatus` only restores stock when moving **into** Cancelled; moving **out** does **not** re-decrement. See **GAP-03** — verify stock is **not** re-decremented and record the oversell risk.

### AD-03 — Admin double-submits a cancel · minor
- **Pre:** a live (non-cancelled) order.
- **Steps:** click Save with status=Cancelled twice quickly.
- **Expected:** stock restored **once** (second pass sees it already cancelled → `$cancelling` false → no restore); coupon not double-released. A second history row may be written — confirm it does not move stock or `used_count` again.

### AD-04 — Admin cancels an InstaPay/COD order · major
- **Pre:** an `awaiting_transfer` InstaPay order and a `pending` COD order.
- **Steps:** cancel each.
- **Expected:** both restore stock once, release coupon if any, set `reserved_until` null, write history. The previously-null COD `reserved_until` stays null.

### AD-05 — Status change shows correct bilingual history · minor
- **Steps:** change status with a customer note in one locale.
- **Expected:** the customer-visible note renders in both the admin detail and the public timeline localized; empty notes are stored null (no blank "" row).

---

## K. "Looks like it worked but changed nothing"

### SN-01 — Place with an empty cart · major
- **Pre:** empty the cart (or let another tab clear it), then submit checkout.
- **Expected:** `CheckoutException::emptyCart` → `ui.checkout_cart_empty` validation error; **no** order created. The button must not appear to succeed.

### SN-02 — Switch method on payment-failed for an already-cancelled order · major
- **Covered by BK-05/SW-04** — the key assertion: the option buttons look active but **proceed() mutates nothing**; confirm via DB that method/status/`reserved_until`/stock are unchanged.

### SN-03 — Paymob return with no HMAC · minor
- **Covered by PV-06** — the redirect to cart must not look like a confirmation; confirm the customer isn't told the order is paid.

### SN-04 — Re-add to cart when nothing is available · minor
- **Pre:** cancelled order whose every variant is now inactive/0-stock.
- **Steps:** click re-add.
- **Expected:** redirect to cart with the `pf_expired_readd_none` notice; cart unchanged (nothing added) — not a silent no-op with no message.

---

## L. Gaps — cases I believe are NOT (fully) handled

These are honest findings from the code, not expected-results massaged to match. Treat each as
**investigate / likely fix** rather than pass/fail.

- **GAP-01 (blocker) — payment-failed page does not guard a PAID order.**
  `PaymentFailed::proceed()` and `mount()` only check `status === Cancelled`. There is **no**
  `payment_status === Paid` check. So re-opening an old payment-failed link for an order that was
  later paid appears to let the customer (a) choose "retry card" → `createCheckout` runs again →
  **second charge**, and/or (b) choose COD/InstaPay → `payment_status` overwritten from `paid` to
  `pending`/`awaiting_transfer`, **downgrading a paid order**. Verify in BK-01. If confirmed, this
  needs a guard (treat paid like cancelled: show a terminal state, no options).

- **GAP-02 (major) — zero-total card order.** Nothing stops a customer selecting **Card** when the
  total is 0 (e.g. a 100% coupon + free delivery). `createCheckout` sends `amount = 0`, Paymob
  rejects it, and the customer is bounced to payment-failed **after** the order was already placed
  (stock decremented, coupon consumed). See MN-05. Likely needs either hiding card at total 0 or
  short-circuiting to confirmation. Investigate what Paymob actually returns for amount 0.

- **GAP-03 (major) — moving an order out of Cancelled does not re-reserve stock.**
  `UpdateOrderStatus` restores stock only when transitioning **into** Cancelled. An admin setting a
  cancelled order back to a live status (or paid) does **not** re-decrement stock, so the order is
  fulfilled against stock that was handed back — **oversell risk**. See AD-02. Confirm and decide
  whether to re-decrement (and how to handle insufficient current stock).

- **GAP-04 (investigate) — no amount reconciliation on settlement.** `recordTransaction` records
  the paid amount from the webhook/callback but never asserts it equals `order.total`. The HMAC
  covers the amount so tampering is caught, but a genuine partial/mismatched settlement would still
  mark the order `paid`. Low risk (we set the intention amount), but there is no explicit guard —
  confirm whether a mismatch is possible in the sandbox and whether it should flag for review.

- **GAP-05 (investigate) — concurrent double checkout from two tabs.** `place()` reads cart items,
  then clears the cart inside the transaction. Two near-simultaneous submits (two tabs) could both
  read the items before either clears, producing **two orders** from one cart (stock permitting).
  The per-IP rate limiter (10/10min) and the cart-clear reduce but may not eliminate this. Try a
  genuine double-submit and watch for two orders.

- **GAP-06 (investigate) — reused Unified Checkout URL / back button.** Whether a used or expired
  Paymob `clientSecret` can be re-submitted is Paymob's behaviour, not ours; our side has no
  explicit "this order already has a paid payment, refuse a new intention" guard beyond GAP-01.
  Verify in BK-02 whether a second charge is possible.

---

## Timing & the 20-minute subset

**Full script:** roughly **2.5–3 hours** if Paymob sandbox is configured and you exercise both
locales. The card race/timing cases (section B) and the tunnel-only webhook case (RC-04) are the
slowest; the locale sweep (section H) across every screen is the next biggest chunk.

**If you only have 20 minutes**, run these — they are the highest blast-radius and all three
go-live-critical payment methods plus the two scary gaps:

1. **HP-01** COD happy path (zero-config safety net).
2. **HP-03** Card happy path (success card) — redirect, return, `paid`, `reserved_until` cleared.
3. **HP-02** InstaPay confirmation shows address + amount + deadline.
4. **BK-01 / GAP-01** re-open a paid order's payment-failed link — the double-charge / downgrade risk.
5. **RC-02** abandoned card order released by a manual sweep, stock restored once (set `reserved_until` to the past, run `php artisan orders:release-reservations`).
6. **PV-01** another customer's order → 404.
7. **PV-06** forged Paymob return → redirect to cart, nothing written.
8. **CP-01** auto-cancel refunds the coupon use.

That covers all three payment methods, the stock sweeper, order-access security, the HMAC gate,
coupon rollback, and the single most dangerous backward-navigation gap.
