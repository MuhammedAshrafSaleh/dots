# Payments hardening — PAY.1 → PAY.8

**Goal (one line):** Make card payments safe and correct so that going live needs only real Paymob credentials in `.env` — no code changes — and so no failure path can silently hold stock or mislead a customer.

**Execution order (identifiers unchanged):** **PAY.3 → PAY.2 → PAY.1 → PAY.4 → PAY.5 → PAY.6 → PAY.7 → PAY.8.** The dead-key cleanup (PAY.3) and the config gate (PAY.2) are small and land first so PAY.1 builds on a clean, fully-gated base. Task numbers are kept as-is; only the order in which they are worked changes.

**Branch:** `payments-hardening`, cut from an up-to-date `main` **after** `bulk-import` is merged and tagged (`bulk-import-done`). Do **not** create the branch now. Release works like a phase: on the owner's "approved, push payments-hardening" → push branch, `git merge --no-ff` into main, tag `payments-hardening-done`, push `main --tags`.

**Rules (review-gated, same as phases):** one task per session, in order. After each task `composer gates` must pass (unit+feature+Larastan+Pint+build) → tick it here → **commit locally** `PAY.<n>: <title>` (one commit per task, no push). Browser tests stay paused — write feature/Livewire tests instead. When a task touches UI/Livewire, list exactly what the owner clicks on dots.test. Update PROGRESS.md "Current state" before stopping. STOP after each task for owner review. Credentials only from `.env`; never in DB, code, or fixtures. Never weaken the dots_testing guard.

**Reuse anchors (do not invent parallel mechanisms):**
- `App\Actions\ReleaseExpiredReservations` — the one and only stock-release sweeper. PAY.1 extends it; it does not get a sibling.
- `App\Actions\PlaceOrder` (stock decrement + `reserved_until`), `App\Actions\UpdateOrderStatus` (admin cancel + `restoreStock`).
- `App\Services\Payments\PaymobGateway` (`isConfigured()`, `createCheckout()`, `verifyWebhook()`, `verifyCallback()`, `recordTransaction()`), `App\Services\Payments\PaymentGateway` contract, `App\Services\Payments\PaymentException`.
- `App\Services\Settings` (`commerce.instapay_hold_hours`, `commerce.instapay_address`), `config/services.php` `paymob.*`.
- Enums `OrderPaymentStatus`, `OrderStatus`, `PaymentStatus`, `PaymentMethodCode`.
- Lang `lang/ar/ui.php` + `lang/en/ui.php` (`pf_*`, `confirm_*`, `instapay_step*`, `pay_status_*`).
- `App\Console\Commands\ReleaseExpiredReservations` (`orders:release-reservations`, scheduled every 15 min in `routes/console.php`).

---

## PAY.1 — Auto-release stock for failed & abandoned card payments  _(Opus)_
- [x] Done

**Goal:** Extend the existing reservation sweeper so unpaid **card** orders return their stock, exactly as InstaPay orders already do — without ever sweeping legitimate COD holds, and without a stock/webhook race.

**Design decisions (documented, binding):**
- **`reserved_until` becomes the universal "pay-by deadline."** It is the single discriminator the sweeper reads. COD orders keep `reserved_until = NULL` → never swept (COD stock is admin-managed until fulfilled/cancelled). InstaPay keeps today's behaviour (`now + instapay_hold_hours`). Card orders now also carry a deadline.
- **Who sets the card deadline:** `PlaceOrder` sets an initial `reserved_until = now + commerce.card_payment_window_minutes` (new setting, default **60**) for card orders, so even an order whose `createCheckout()` throws still gets swept. `createCheckout()` **refreshes** `reserved_until = now + window` on every call (initial attempt and every retry from the payment-failed page), so an in-flight retry can never be swept underneath the customer.
- **Window floor:** the admin field must be validated `min:30` — never below 30 minutes (and an integer), so the window can never be shortened enough to sweep a customer mid-payment. Default 60.
- **Eligible for release:** `reserved_until IS NOT NULL AND reserved_until <= now AND status != cancelled` AND `payment_status IN (awaiting_transfer, pending, failed)`. COD is excluded automatically (null deadline). `paid`/`refunded` are excluded.
- **Grace window:** one card window (default 60 min, configurable) applies to both `pending` (abandoned browser, webhook never arrived) and `failed` (declined, customer did not switch/retry). We deliberately do **not** split these — both represent unpaid stock that must return within ~1h; a single tunable window is simpler and therefore safer. InstaPay keeps its own `instapay_hold_hours`.
- **Race vs. in-flight webhook:** the sweep loop wraps each order in a DB transaction and `lockForUpdate()`s the order row, then **re-reads** eligibility inside the lock before cancelling. `recordTransaction()` already runs in its own transaction; on **success** it now also sets `reserved_until = NULL` so a paid order immediately drops out of the sweep. Whichever transaction commits first wins; the loser re-reads under the lock and no-ops.
- **Late success after a wrongful cancel (money taken, order already cancelled):** `recordTransaction()` must **not** silently un-cancel or re-decrement stock. It records the `Payment` row + `hmac_verified`, sets `payment_status = paid`, leaves `status = cancelled`, and writes a history row flagged for manual review (`en`: "Card payment received AFTER auto-cancellation — review and refund or re-fulfil.", `ar`: "تم استلام الدفع بالبطاقة بعد الإلغاء التلقائي — يُرجى المراجعة والاسترجاع أو إعادة التجهيز."). A human decides. The 60-min window makes this essentially impossible outside pathological late retries (Paymob webhooks fire within seconds), but the path is handled, not assumed away.
- **History on release:** the Cancelled history note is reason-aware — card: "Card payment not completed in time — order cancelled and reserved stock released." / InstaPay: today's existing transfer-window note. Bilingual, inline on the history row (same shape as existing notes).

**Gap A — order may be cancelled while the customer sits on the payment-failed page.** The sweeper can cancel an order in the seconds/minutes the customer spends choosing on `order.payment-failed`. `PaymentFailed::proceed()` must, for **all three branches** (retry card / switch to COD / switch to InstaPay), re-load the order `lockForUpdate()` inside a transaction and re-check it is **not** cancelled before mutating anything. If the order is already cancelled:
  - do **not** mutate the order, create a payment, or move stock;
  - show a bilingual message (`pf_expired_*`) that the reservation expired and the order was released;
  - offer a **"re-add items to cart"** action. **What that path does with the cart:** it re-adds each `OrderItem` (by `product_variant_id` + `qty`) to the current cart via `CartManager`, clamping each line to the variant's **current** `stock_qty` (skipping variants now inactive/out of stock, with a bilingual note listing anything dropped), then redirects to the cart page. It does **not** resurrect the cancelled order, re-apply the old coupon, or touch stock directly — the customer checks out fresh. The cancelled order stays cancelled for the record.

**Gap B — coupon redemption on an auto-cancelled order (investigated).** Confirmed in code: `PlaceOrder` both inserts a `CouponRedemption` row and `increment`s `coupon.used_count` (`app/Actions/PlaceOrder.php:97-106`). **Neither** `ReleaseExpiredReservations.release()` **nor** `UpdateOrderStatus.restoreStock()` rolls either back — so an auto-cancelled order **permanently consumes a coupon use** (and, for limited coupons, can exhaust them). This is a real bug and is **in scope for PAY.1**.

**Two gates consume a coupon — both must be handled (the original plan named only the first):**
1. **Usage-limit gate — reads the `coupon.used_count` *counter*.** `app/Services/Coupons.php:35` (`used_count >= usage_limit`) and `Coupon::scopeValid` (`app/Models/Coupon.php:53`, `used_count < usage_limit`). Fixed by decrementing `used_count`.
2. **Once-per-customer gate — reads redemption *rows* via `exists()`.** `app/Services/Coupons.php:44-51` does `CouponRedemption::where('coupon_id',…)->where('customer_id',…)->exists()` with no status filter — so a kept row would permanently block that customer even after the order is cancelled. Fixed by making the gate release-aware.

**Resolution = soft-release, not delete (owner-approved 2026-10-27).** A new nullable `released_at` timestamp is added to `coupon_redemptions` (ERD updated; **not** Laravel `SoftDeletes`/`deleted_at`, which would hide the row globally — `released_at` keeps the row visible and labelled "released", preserving the history of a coupon used on a later-cancelled order). On cancel, inside the same locked transaction: stamp `released_at = now()` on the order's redemption row(s) that are still active (`whereNull('released_at')`), count how many that touched, and `decrement('used_count')` by exactly that count (never below 0). Then add `->whereNull('released_at')` to the once-per-customer `exists()` check in `Coupons.php`. Idempotent: a re-run finds no active rows, releases nothing, and does not double-decrement. Apply the same rollback to the admin cancel path (`UpdateOrderStatus`) so both routes behave identically. If an order carries no coupon, this is a no-op.

**Guest observation (behaviour NOT changed by PAY.1).** The once-per-customer gate is wrapped in `$customerId !== null` (`Coupons.php:44`), so a **guest** order (null `customer_id`) is exempt from the once-per-customer rule today. The new `whereNull('released_at')` clause lives inside that same block, so it runs only for non-null customers and does not alter guest behaviour either way. Changing the guest exemption is out of scope for PAY.1.

**Files to touch:**
- New migration — add nullable `released_at` timestamp to `coupon_redemptions` (Gap B soft-release).
- `app/Models/CouponRedemption.php` — cast/allow `released_at` (model is `$guarded = ['id']`, so writable; add a `datetime` cast).
- `app/Services/Coupons.php` — add `->whereNull('released_at')` to the once-per-customer `exists()` gate (Gap B).
- `app/Actions/ReleaseExpiredReservations.php` — broaden query; `lockForUpdate()` + in-lock re-check; reason-aware note; coupon soft-release + `used_count` decrement (Gap B).
- `app/Actions/UpdateOrderStatus.php` — add the same coupon soft-release rollback to `restoreStock()`/cancel path (Gap B).
- `app/Actions/PlaceOrder.php` — set initial `reserved_until` for card orders from the new setting.
- `app/Services/Payments/PaymobGateway.php` — `recordTransaction()`: clear `reserved_until` on success; late-success-after-cancel path (record + flag, no un-cancel). **Money + signature code → Opus.**
- `app/Livewire/Shop/PaymentFailed.php` — lock + re-check all three branches (Gap A); cancelled → expired message + re-add-to-cart; switching to COD sets `reserved_until = NULL`; InstaPay resets it; retry relies on `createCheckout()` refreshing it.
- `resources/views/livewire/shop/payment-failed.blade.php` — expired-order state + "re-add items to cart" action.
- `app/Services/CartManager.php` — reuse existing add/clamp methods for the re-add path (no new mechanism).
- Settings surface for `commerce.card_payment_window_minutes` (default 60, `min:30`): `app/Livewire/Admin/Payments/Index.php` + its view — reuse the existing `instapay_hold_hours` field pattern and validation.
- `lang/ar/ui.php` + `lang/en/ui.php` — `pf_expired_*`, re-add-to-cart labels/notes.

**Acceptance criteria**
- A `failed` card order past its window is cancelled and its stock restored exactly once; a second sweep no-ops.
- An abandoned `pending` card order (no webhook) past its window is cancelled + stock restored.
- A COD `pending` order is **never** swept, regardless of age.
- A card order settled `paid` has `reserved_until` cleared and is never swept.
- A late success webhook on an already-cancelled order records the payment, sets `paid`, keeps `cancelled`, writes the review note, and does **not** change stock.
- Retrying from the payment-failed page refreshes the deadline (no mid-retry cancel).
- Card window field rejects values below 30 and non-integers.
- Cancelling an order (auto or admin) soft-releases its coupon redemption (`released_at` stamped, row kept) and decrements `used_count`, idempotently; the once-per-customer gate ignores released rows so the customer may reuse the coupon; an order with no coupon is unaffected.
- Choosing any option on the payment-failed page for an already-cancelled order mutates nothing and shows the expired message; the re-add action repopulates the cart from the order's items clamped to current stock.

**Required tests (feature, with time travel)**
- Card `failed` past window → cancelled + stock restored once; idempotent on re-run.
- Card `pending` (abandoned) past window → released.
- COD `pending` well past any window → untouched.
- Card `paid` → `reserved_until` null, sweeper ignores it.
- Late success webhook after auto-cancel → payment recorded, `paid`+`cancelled`, review note present, stock unchanged.
- `orders:release-reservations` command still releases an expired InstaPay order (no regression).
- Card window below 30 rejected by admin validation.
- Auto-cancel of a coupon order → redemption row `released_at` stamped (row kept), `used_count` decremented; re-run does not double-decrement; the coupon is usable again within its limit (once-per-customer gate skips released rows).
- **Gap A:** order auto-cancelled while the customer is on the payment-failed page, then the customer picks **COD** → order untouched (still cancelled, no new payment), correct expired message shown, **no stock movement**; re-add action repopulates the cart.

**Owner clicks (dots.test):** Admin → Payments → confirm the "card payment window (minutes)" field saves and rejects a value under 30. (Release itself is time-based/scheduled — verify via the sweep test output, not a click.)

---

## PAY.2 — Card option can never show with partial credentials  _(Sonnet)_
- [x] Done

**Goal:** `CheckoutForm` must gate the card method on full gateway configuration, not just the secret key.

**Files to touch:** `app/Livewire/Shop/CheckoutForm.php` — `cardConfigured()` delegates to `app(PaymobGateway::class)->isConfigured()` (secret + public + integration id).

**Acceptance criteria**
- Card hidden when any of secret/public/integration-id is blank; shown only when all three are present.
- No behaviour change when fully configured.

**Required tests (Livewire)**
- Partial config (secret set, **public blank**) → card method absent.
- Partial config (secret + public set, **integration id blank**) → card method absent.
- Full config → card method present. (Existing "hides until configured" test stays green.)

**Owner clicks (dots.test):** with only `PAYMOB_SECRET_KEY` filled in `.env`, open checkout → card option must **not** appear. Fill all three → it appears.

---

## PAY.3 — Remove dead Paymob env keys  _(Sonnet)_
- [x] Done

**Goal:** Delete `PAYMOB_API_KEY` and `PAYMOB_IFRAME_ID` — declared but read nowhere — so the go-live `.env` has only keys that matter.

**Files to touch:** `config/services.php` (drop `api_key`, `iframe_id`), `.env.example` (drop both lines).

**Acceptance criteria**
- `grep -rn "api_key\|iframe_id\|PAYMOB_API_KEY\|PAYMOB_IFRAME_ID" app config` returns nothing.
- `PaymobGateway::isConfigured()` and `createCheckout()` unchanged (never used them).
- Gates green (no reference breaks).

**Required tests:** none new; covered by existing `PaymobGatewayTest` + the grep in acceptance. (State in the task report that the grep was run.)

**Owner clicks:** none (config only).

---

## PAY.4 — Safe, bilingual failure reason on the payment-failed page  _(Sonnet)_
- [x] Done

**Goal:** Tell the customer *why* the card failed when we can do so safely — never echo raw gateway text.

**Design decision:** map a small set of known Paymob `txn_response_code`/`data.message` outcomes (e.g. declined, insufficient funds, expired card, 3-D-Secure failed, auth error) to curated `pf_reason_*` lang keys; everything else falls back to a generic `pf_reason_generic` line. Raw `payments.failure_message` is **never** rendered. Reason is read from the order's latest failed `Payment`.

**Wording rules for every `pf_reason_*` line (both locales):**
- Stay neutral; never blame the customer's bank by name (no "your bank declined…").
- Every line ends by pointing at a next action available on this page — try again, cash on delivery, or InstaPay.
- Arabic must read as natural, human Arabic — not a literal translation of the English line.

**Files to touch:** `app/Livewire/Shop/PaymentFailed.php` (resolve a reason key from the latest payment), `resources/views/livewire/shop/payment-failed.blade.php` (render it under the heading), `lang/ar/ui.php` + `lang/en/ui.php` (`pf_reason_*`). Reuse the existing `pf_*` styling.

**Acceptance criteria**
- A known decline shows its curated bilingual reason; an unknown/empty code shows the generic line.
- Raw gateway message never appears in the DOM.
- No reason shown when there is no failed payment (e.g. arrived here from a gateway-unreachable throw).

**Required tests (Livewire)**
- Order whose latest payment has a known failure code → curated reason string present.
- Unknown code → generic reason present, raw text absent.
- No failed payment → neither reason line nor raw text rendered.

**Owner clicks (dots.test):** with sandbox keys, force a decline → payment-failed page shows a human reason (AR + EN), no technical codes.

---

## PAY.5 — Show InstaPay address + deadline on confirmation  _(Sonnet)_
- [ ] Not started

**Goal:** An InstaPay customer sees the transfer address, the exact amount, the order number, and the hold deadline on the confirmation page — not only after a staff WhatsApp reply.

**Files to touch:** `resources/views/site/order-confirmation.blade.php` (InstaPay variant block), `app/Http/Controllers/OrderConfirmationController.php` (pass `commerce.instapay_address` + formatted `reserved_until`), `lang/ar/ui.php` + `lang/en/ui.php` (labels + "address not yet available" fallback). Also covers the confirmation page reached after switching to InstaPay from payment-failed (PAY handled in PaymentFailed already sets `reserved_until`).

**Acceptance criteria**
- InstaPay confirmation shows: InstaPay address (from settings), amount, order number, and the deadline (`reserved_until`, localized).
- When `commerce.instapay_address` is empty, a bilingual "we'll send it on WhatsApp" fallback shows instead of a blank — never a PHP error.
- COD and card variants are visually unchanged.
- The address is confirmed **not secret** (a merchant receiving handle), but is still only rendered behind the existing order-view gate (`viewableInSession()` / owning customer). It must **never** appear on any page reachable without that gate — add an explicit assertion that an unauthorised viewer of the order gets a 404 and the address string is absent.

**Required tests (feature/Livewire)**
- InstaPay order with address set → page contains address, amount, deadline.
- InstaPay order with address blank → fallback copy present, address absent, no error.
- Switch-to-InstaPay from payment-failed then land on confirmation → same address+deadline shown.
- Unauthorised viewer (no session, not the owning customer) → 404, address string absent from the response.

**Owner clicks (dots.test):** set an InstaPay address in Admin → Payments, place an InstaPay order → confirmation shows address + amount + deadline. Blank the address → fallback copy.

---

## PAY.6 — Verify HMAC before any order lookup on return  _(Opus)_
- [x] Done

**Goal:** `PaymobReturnController` must verify the callback HMAC **before** it queries for an order, so a tampered/forged callback can't drive a lookup or leak order existence.

**Files to touch:** `app/Http/Controllers/PaymobReturnController.php` — reorder: `verifyCallback()` first; only on a verified `obj` derive the merchant ref and load the order; unverified → redirect to cart (no lookup).

**Acceptance criteria**
- Unverified/missing HMAC → no order query runs, customer redirected to cart.
- Verified paid callback → records transaction, redirects to confirmation (unchanged happy path).
- Verified failed callback → redirects to payment-failed (unchanged).

**Required tests (feature)** — **signature code → Opus:**
- Valid HMAC paid → confirmation redirect, order settled (existing test stays green).
- **Invalid HMAC** with a real `merchant_order_id` present → redirect to cart, order untouched, no payment row.
- **Missing HMAC** → redirect to cart, order untouched.

**Owner clicks:** none (covered by tests; manual verification folded into PAY.8 real-transaction run).

---

## PAY.7 — Close the remaining test gaps  _(Opus for the webhook/HMAC & release assertions; Sonnet for the checkout-throw UI test)_
- [x] Done

**Goal:** Cover the critical paths this audit found untested.

**Files to touch:** test files only — extend `tests/Feature/Commerce/PaymobGatewayTest.php`, `tests/Feature/Commerce/CheckoutTest.php`, `tests/Feature/Commerce/ReleaseExpiredReservationsTest.php` (and a new focused file if cleaner).

**Required tests**
- **Browser-return with invalid HMAC** and with **missing HMAC** (overlaps PAY.6; keep here if not already added there).
- **Checkout end-to-end when the gateway throws:** `Http::fake` the intention as failing → `CheckoutForm::place()` with card → asserts redirect to `order.payment-failed`, order exists, stock already decremented, no paid payment. _(Sonnet.)_
- **Card-failed order reaches automatic release** (full chain: place card order → failed webhook → time-travel past window → sweep cancels + restores stock). _(Opus.)_
- **`instapay_hold_hours` actually drives `reserved_until`:** set the setting to a non-default, place an InstaPay order, assert `reserved_until ≈ now + setting`. _(Sonnet.)_

**Acceptance criteria**
- All four scenarios have explicit assertions; `composer gates` green.

**Owner clicks:** none.

---

## PAY.8 — Go-live checklist `docs/PAYMOB_GOLIVE.md`  _(Sonnet)_
- [x] Done

**Goal:** A single document the owner follows to go live with zero code changes.

**File to create:** `docs/PAYMOB_GOLIVE.md`. Must contain:
- **Exact `.env` keys to fill** (post-PAY.3): `PAYMOB_BASE_URL`, `PAYMOB_SECRET_KEY`, `PAYMOB_PUBLIC_KEY`, `PAYMOB_INTEGRATION_ID_CARD`, `PAYMOB_HMAC_SECRET` — what each is and where to find it in the Paymob dashboard.
- **Paymob dashboard config:** set the **Transaction processed callback** to `POST {APP_URL}/payments/paymob/webhook` and the **Transaction response callback** to `{APP_URL}/{locale}/payments/paymob/return`; which integration the `PAYMOB_INTEGRATION_ID_CARD` must match; where the HMAC secret is read from.
- **`APP_URL` note:** `notification_url`/`redirection_url` are built by `route()` from `APP_URL` — it must be the real public HTTPS origin in production (and the tunnel origin when testing locally).
- **Local webhook testing via a public tunnel (Herd Share / ngrok):** start the tunnel, set `APP_URL` to the tunnel URL so `notification_url` resolves publicly, point the Paymob sandbox callbacks at it. **State clearly:** the browser-return route settles the order on its own (verified HMAC), so the happy path is fully testable locally **without** a tunnel; the tunnel is only needed to exercise the **abandoned-browser / server-to-server webhook** case.
- **Verify the webhook is reachable in production:** how to confirm Paymob can reach the webhook (expect HTTP 200; a bad-HMAC payload returns `{"ok":false}` with 200 and writes nothing), and where to look (`webhook_events`, `payments.hmac_verified`, order history).
- **One real low-value test transaction:** place a minimal real card order, complete it, and confirm end to end: redirect to Unified Checkout → return to confirmation → `payments` row `paid` + `hmac_verified=true` → `webhook_events` has the event → order `payment_status=paid`, `reserved_until` cleared → admin order detail shows the Paymob log. Then refund it from the Paymob dashboard and note how a refund is reflected.
- **Queue reminder:** `NewOrderMail` is queued; production needs `php artisan queue:work` (per CLAUDE.md).

**Acceptance criteria**
- A reader can go live by filling `.env` and dashboard settings only — no code step.
- Local-test section distinguishes "happy path, no tunnel" from "abandoned/webhook, tunnel required."

**Required tests:** none (docs). Note in the report that every referenced route name/key was grep-verified against the code.

**Owner clicks:** review the doc.

---

## PAY.13 — Make Livewire toasts visible on the shop frontend  _(Sonnet)_
- [x] Done

**Goal:** Shop Livewire components dispatch `toast` events that currently fire into nothing. The shop JS bundle has no listener and the shop layout's `.d-toasts` container is static (session-flash only). Fix both so every `$this->dispatch('toast', …)` on the shop/account frontend is visible to the customer.

**Root cause:**
1. `app.js` has no `Alpine.store('toasts', …)` and no `Livewire.on('toast', …)` — unlike `admin.js` which has both.
2. The shop layout's `.d-toasts` div renders only server-side `<x-toast>` session-flash components; there is no `x-for` loop driven by the store.
3. Two dispatches in `PaymentFailed.php` use `type:` instead of `tone:` (the key the store reads).

**Non-admin toast dispatches (all must be confirmed working after this task):**
- `app/Livewire/Shop/PaymentFailed.php:72` — gateway error on card retry (was `type:`)
- `app/Livewire/Shop/PaymentFailed.php:86` — rate-limit message on retry (was `type:`)
- `app/Livewire/Account/Auth/ForgotPassword.php:54` — password-reset resent (correct `tone:`)

**Files to touch:**
- `resources/js/app.js` — add `alpine:init` block registering `Alpine.store('toasts', {…})` (same shape as admin), and `livewire:init` block calling `window.Livewire.on('toast', …)` (same as admin).
- `resources/views/components/layouts/app.blade.php` — replace the static `.d-toasts` div with an Alpine-store-driven `x-for` loop; session-flash values pushed into the store via `x-init` so no second rendering path is needed.
- `app/Livewire/Shop/PaymentFailed.php` — change `type:` → `tone:` on both dispatch calls.

**Acceptance criteria:**
- Navigating to the payment-failed page and triggering "retry" too soon shows an error toast on screen.
- The forgot-password "resend" button shows a success toast.
- Session-flashed `toast_success` and `toast_info` still appear (not broken by the layout change).
- `composer gates` green.

**Required tests:** no new tests (toast rendering is a JS/Alpine concern; the dispatch calls were already exercised by existing Livewire component tests — they just weren't visible in the browser).

**Owner clicks (dots.test):** Go to `/ar/account/forgot-password`, enter any email, click "إعادة الإرسال" (resend) — a success toast must appear at the corner of the screen. (Alternatively: add an item to cart and proceed to checkout with a payment error to see the gateway-error toast from PaymentFailed, but the forgot-password path is the easiest click.)

---

## PAY.14 — Card-payment retry fails: duplicate `special_reference` + orphaned payment rows  _(Opus)_
- [x] Done

**Problem reported:** Retrying a card payment from the payment-failed page always fails. Every `createCheckout()` for an order that already has an intention returns **HTTP 400** from Paymob. Order `DT-10119` accumulated eight orphaned `pending` payment rows (one per click), none with a `gateway_ref`. Log showed `Paymob intention failed {order: DT-10119, status: 400}` repeatedly.

### Diagnosis (done 2026-11-01)

**Exact 400 body (captured from the live sandbox with a raw duplicate-reference call — no order/payment mutated, temp script removed afterward, so no change to the production log was needed):**
```
Attempt 1 (ref=DIAG-DUP-…) → 201 Created
Attempt 2 (SAME ref)       → 400 Bad Request
   {"merchant_order_id":"An Order with ref: DIAG-DUP-… already exists"}
```

**Confirmed cause:** `special_reference` **must be unique per intention.** We send `special_reference = $order->number` (`PaymobGateway::createCheckout()`), so the *first* intention for an order succeeds and *every retry* reuses the same reference → Paymob rejects it 400 with the message above. The rejected field is exactly `special_reference` (Paymob echoes the error under the `merchant_order_id` key).

**The hard constraint on any fix:** Paymob's docs state `special_reference` is "returned in the transaction callback under `merchant_order_id`." Our webhook/return handler matches the order on exactly that field — `recordTransaction()` does `Order::where('number', $obj['order']['merchant_order_id'])`. So whatever we send as `special_reference` is what we must be able to resolve back to an order. We cannot simply replace the order number with a random reference or we lose the webhook→order link.

**Second defect (orphan rows):** `createCheckout()` writes the `payments` row *before* the HTTP call (`PaymobGateway.php:60`). When the call 400s it throws *after* the row is already committed, so every failed attempt leaves a `pending` row with no `gateway_ref`. Verified on `DT-10119`: 8 such rows (`gateway_ref` NULL, `transaction_id` NULL, `status=pending`) from the retries, plus pre-existing legit rows (two `failed` webhook rows with txn ids, one accepted-but-abandoned `pending` row with a `pi_test_…` ref).

### Fix as implemented (owner-approved 2026-11-01 — stored-reference column, no parsing)

The owner rejected parsing the order number out of `special_reference` (the `order_prefix` setting is admin-editable, so a prefix with a hyphen+digits could misroute money). Research into the two shortcuts:
- **`extras` / metadata echo (Q1):** Paymob's docs confirm an `extras` field whose values "are returned in callbacks under the payment key claims object." Usable for the *server webhook*, but unverified for the *browser-redirect* flat callback (our primary local settle path) and unsigned — not worth the risk.
- **Reuse the existing intention (Q2):** docs are silent on intention expiry and explicitly leave reuse "open"; sources conflict ("one intention can carry several transactions" vs "client_secret is one-time"). Can't verify without driving a live sandbox decline+retry. Too risky for the money path.

→ Took the migration. A verified fact backs it: the full string we send round-trips verbatim as `merchant_order_id` on **both** inbound paths (webhook + browser return), which already read that field today.

1. **New column `payments.gateway_reference`** (nullable, indexed; ERD updated). Stores the exact `special_reference` we sent.
2. **Unique reference, order number embedded.** `special_reference = "{$order->number}-".Str::upper(Str::random(12))` — unique per intention; order number stays human-visible in the Paymob dashboard. Stored on the payment row.
3. **Resolve by exact lookup, no parsing.** New `PaymobGateway::orderForReference($merchantRef)`: exact `Payment::where('gateway_reference', $merchantRef)` → its order (the ONLY supported path). A commented *transitional* fallback to `Order::where('number', …)` covers intentions already in flight with a bare reference; it is explicitly not a supported path for new intentions and is to be removed once none can be in flight. Both `recordTransaction()` and `PaymobReturnController` use this one method.
4. **Row only after acceptance.** `payments()->create([...])` moved below the `failed() || blank(client_secret)` guard (sets `gateway_ref` + `gateway_reference`). A rejected intention throws before any row exists → zero orphans.
5. **Cleaned up `DT-10119`** — deleted the 8 orphan rows (`pending`, `gateway_ref` NULL, `transaction_id` NULL); kept the two `failed`-with-txn rows (#16/#17) and the accepted `pi_test_` row (#18).

**Files touched:** `database/migrations/2026_11_01_132146_add_gateway_reference_to_payments_table.php` (new), `app/Services/Payments/PaymobGateway.php` (unique reference, row-after-acceptance, `orderForReference()`), `app/Http/Controllers/PaymobReturnController.php` (resolve via `orderForReference()`), `docs/ERD.md`, `tests/Feature/Commerce/PaymobGatewayTest.php`.

**Acceptance criteria**
- A retry from the payment-failed page for an order with an existing intention succeeds (no 400); each attempt sends a distinct `special_reference`.
- A webhook/return carrying the suffixed `merchant_order_id` still resolves to the correct order (and a bare legacy reference still resolves).
- A rejected intention leaves **no** payment row behind.
- `DT-10119`'s 8 orphan rows removed; its real payment rows untouched.

**Required tests (feature)**
- `createCheckout` sends a `special_reference` that starts with the order number and is unique across two calls for the same order.
- Rejected intention (`Http::fake` 400) → `PaymentException` thrown **and** `Payment::where('order_id', …)->count()` unchanged (no orphan).
- `recordTransaction` resolves the order from a suffixed `merchant_order_id` (and still from a bare one).

**Owner clicks (dots.test):** force a card decline, then on the payment-failed page click "retry" → it must reach Unified Checkout again (no gateway-error toast, no 400 in the log), and no new `pending` orphan row appears.

---

## PAY.9 — A settled payment is terminal on the payment-failed page  _(Opus)_
- [x] Done

**Problem reported (confirmed by hand on `DT-10130`, 2026-11-01):** the order was **paid by card**, the customer reopened its payment-failed URL, chose **cash on delivery**, and the order flipped from `paid`→`pending` with method `cod`. Real money had been taken; the system then believed the order was unpaid. Root cause: `PaymentFailed` guards only on `status === Cancelled`. A page opened (or re-opened) before settlement could still mutate the order after settlement — the paid state was never treated as terminal, and the COD/InstaPay branch re-checked only for `Cancelled` under its lock, not for a settled payment.

**Terminal payment statuses (which `payment_status` values block every action — decided here):**
- **`paid` — IN (terminal).** Money has been taken and the order is settled; nothing on this page may start a new intention or change the method.
- **`refunded` — IN (terminal).** The money lifecycle is closed (taken, then returned). Letting a customer "retry"/switch on a refunded order would re-open a settled financial record; treat it as done.
- **`pending` — OUT.** Not settled — awaiting webhook or an abandoned attempt. Retrying/switching here is exactly the page's job.
- **`awaiting_transfer` — OUT.** InstaPay not yet reconciled; the customer may still switch method.
- **`failed` — OUT.** Declined; retry/switch is the whole point of the page.

So **terminal = {`paid`, `refunded`}**; everything else remains actionable. (`Cancelled` order `status` stays a separate terminal signal — the "expired/released" state — and `paid`+`cancelled`, i.e. a late success after auto-cancel, is shown as **settled**, since money was taken.)

**Guard applied in two places, for all three branches:**
- `mount()` — classify the loaded order as `settled` (terminal payment) in addition to the existing `expired` (cancelled). A page that loads already-settled renders no mutating options.
- `proceed()` — a locked re-read (`terminalState()`: `lockForUpdate()` inside a transaction) runs **before** any branch acts, covering the **retry** branch (which starts a checkout intention) and short-circuiting it before `createCheckout()`. The existing COD/InstaPay locked transaction **additionally** re-checks the settled state inside its own lock. Either way, if the order became terminal between page load and the click, nothing is mutated, no payment row is written, and **no checkout intention is started**.

**Settled view:** a clear bilingual message (`pf_settled_*`) that the order is already settled, a **link to the order** (`order.confirmation`, behind the same session gate the page already passed), and **no mutating controls at all** — no retry, no COD, no InstaPay. Icon is success (check), not the error alert.

**DT-10130 data fix (owner-authorised, this task):** restored to its correct state — `payment_status=paid`, `payment_method_code=card` (`payment_method_id=3`), `reserved_until` cleared (already null), `status` left `new` (the card payment never changed order status). The erroneous "method changed to cod" history row is **kept** (true record of what happened); a new bilingual history row records the manual correction. The real `paid` payment row (#30, txn `564944576`, hmac-verified) was untouched.

**Files touched:** `app/Livewire/Shop/PaymentFailed.php` (terminal-payment guard in `mount()` + `proceed()` + the COD/InstaPay locked re-check), `resources/views/livewire/shop/payment-failed.blade.php` (settled state), `lang/ar/ui.php` + `lang/en/ui.php` (`pf_settled_*`), `tests/Feature/Commerce/PaymentFailedTest.php`.

**Acceptance criteria**
- A `paid` order: each of retry / COD / InstaPay mutates nothing, creates no payment row, and starts no checkout intention; the settled message + order link are shown.
- A `refunded` order is treated identically (terminal).
- The race — order becomes `paid` after the page loaded but before the click — is caught by the locked re-read: nothing is mutated and no intention starts.
- Existing failed/pending/cancelled behaviour is unchanged.

**Required tests (feature/Livewire)**
- Paid order → `settled` true on mount.
- Paid order, each branch (retry / COD / InstaPay) → no mutation, `Payment` count unchanged, no intention sent (`Http::assertNothingSent`), method still `card`, status still paid.
- Race: load with a `failed` order, settle it to `paid`, then click retry → `settled` true, nothing mutated, no intention.
- Refunded order → terminal on mount.

**Owner clicks (dots.test):** open `DT-10130` payment-failed URL (`/ar/order/DT-10130/payment-failed`) — it must show the "already settled" message with a link to the order and **no** retry/COD/InstaPay options. Then confirm `DT-10130` in admin reads `paid` / `card`.

---

## PAY.10 — Re-reserve stock (and re-consume coupon) when an order is reinstated out of Cancelled  _(Opus)_
- [x] Done

**Decisions (owner-approved 2026-11-01):** Decision 1 = **(B) allow + warn + record**, clamped at zero. Decision 2 = **yes, re-consume the coupon** symmetrically with PAY.1's release.

**Problem reported:** `UpdateOrderStatus` restores stock when an order moves **into** `Cancelled` (`restoreStock()` + `ReleaseOrderCoupon`), but moving it **back out** of `Cancelled` (e.g. `Cancelled → Confirmed`) does nothing. The stock was handed back on cancel and may have been sold to someone else in the meantime; an admin reinstating the order then fulfils it against stock that no longer exists. The coupon is also left released (PAY.1 soft-releases it on cancel), so a reinstated live order no longer counts against its coupon.

**Scope:** a new mirror path in `UpdateOrderStatus.handle()`:
`$reinstating = $order->status === Cancelled && $status !== Cancelled`. Everything below runs inside the existing single transaction, symmetric to the `$cancelling` block.

### Decision 1 (owner picked **B**) — what to do when current stock can't cover the order
The order needed 3 of a variant; only 1 is in stock now; the admin is reinstating anyway. Options:
- **(A) Block** the status change with a clear message naming the short line(s); order stays `Cancelled`.
- **(B) Allow + warn** — reinstate, decrement each line clamped at 0 (never negative), and tell the admin exactly which lines are short and by how much, both as a toast **and** as a permanent bilingual history row + internal flag. _(Claude's recommendation.)_
- **(C) Allow + go negative** — decrement raw; `stock_qty` can drop below 0 as an honest "we owe N" signal.

### Decision 2 (owner picked **yes**) — re-consume the coupon on reinstatement?
PAY.1 releases the coupon on cancel (`ReleaseOrderCoupon`: stamps `released_at`, decrements `used_count`, once-per-customer gate ignores released rows). Proposed: **yes, re-consume** — mirror the release so a reinstated live order counts again. A sibling `ConsumeOrderCoupon` action re-activates this order's released redemption row(s) (`released_at = null`), `increment`s `used_count` by the count re-activated, idempotent (re-run finds none). Known edge: if the customer used the same once-per-customer / limited coupon on a *new* order while this one was cancelled, re-consuming pushes them over the limit — surfaced in the history row, not blocked (admin-driven, rare).

### Design (as implemented)
- **One lock, both directions (reverse race).** `UpdateOrderStatus` had **no** order-row lock before this task — only the sweeper (`ReleaseExpiredReservations`, PAY.1) did. Rather than add a second mechanism, `handle()` now opens its transaction with a single `Order::whereKey(...)->lockForUpdate()` re-read of the **authoritative** current status, and computes **both** `$cancelling` and `$reinstating` from it. Two admins saving at once therefore can't both cross the same boundary (no double restore, no double decrement). This also hardens the pre-existing cancel path.
- **`reserveStock(Order): list<shortage>`** mirrors `restoreStock()` — for each line it `lockForUpdate()`s the variant row (as `PlaceOrder` does), decrements `stock_qty` by `min(available, needed)` (**clamped at zero, never negative**), re-runs `UpdateProductCacheColumns`, and returns `{label, needed, available}` for any line it couldn't fully cover.
- **`reserved_until` stays `null`** on reinstatement (admin manages this order's stock from here, like COD) — no new payment window.
- **Customer not auto-messaged.** The shortage surfaces two admin-only ways: a `warning` toast at save time, and a persisted **internal note** appended to the status-history row (`detail.blade.php:99-100`), so it stays visible after the toast disappears — the cue to phone the customer. The customer-visible `note` is never touched.
- **`ConsumeOrderCoupon`** (new, exact reverse of `ReleaseOrderCoupon`): re-activates this order's `released_at`-stamped redemption row(s) (`released_at = null`) and bumps `used_count` back by the count re-activated; idempotent. Known edge (documented, not blocked): if the customer reused the same once-per-customer/limited coupon on a new order while this one was cancelled, re-consuming pushes them over the limit — surfaced for the admin, not prevented (rare, admin-driven).

**Files touched:** `app/Actions/UpdateOrderStatus.php` (lock + `reinstating` branch + `reserveStock()` + shortage note; returns `list<shortage>`), `app/Actions/ConsumeOrderCoupon.php` (new), `app/Livewire/Admin/Orders/Detail.php` (warning toast from the returned shortages), `lang/en/admin.php` + `lang/ar/admin.php` (`reinstate_short_*`), `tests/Feature/Admin/Orders/OrdersAdminTest.php`.

**Acceptance criteria (met)**
- Reinstating `Cancelled → <live>` re-decrements every line's stock exactly once; a no-boundary move (e.g. `Confirmed → Shipped`) is unaffected; moving `→ Cancelled` is unchanged.
- Insufficient stock → reinstated anyway, decremented clamped at zero, admin told exactly which lines and by how much (toast + persisted internal note).
- Coupon re-consumed symmetrically and idempotently; `reserved_until` stays null.
- Re-saving the same already-live status does not double-decrement or double-consume.

**Required tests (all added to `OrdersAdminTest.php`, green)**
- `Cancelled → Confirmed` with enough stock → every line re-decremented once; `Confirmed → Shipped` after → no further decrement.
- Reinstating with short stock → clamped at zero, `warning` toast dispatched, shortage persisted in the history internal note.
- `Cancelled → Confirmed` with a released coupon → row re-activated, `used_count` restored; re-saving the live status → no double-consume.

**Owner clicks (dots.test):** below.

---

## PAY.16 — Cart stepper stock cap + honest oversell messaging  _(Opus)_

Three fixes from manual testing, plus a confirmation of the server-side backstop.

1. **Cart stepper not capped.** PAY.12 capped the stepper on the PDP but the cart/drawer line let the customer raise qty past stock. The shared `_cart-line.blade.php` now computes `$atStockCap = stock > 0 && qty >= stock`: the increment button is `disabled` at the cap and the same short line (`ui.pdp_stock_cap` → "Only :n available" / ":n قطعة فقط متاحة") shows under the stepper. `CartManager::updateQty()` also clamps qty to current stock server-side (defensive mirror of the disabled button). `add()` is intentionally left ungated (used by re-add etc.; the backstop below covers it).
2. **Submit-time error was wrong.** It said the product "is no longer available" even when it was available in a smaller qty. `PlaceOrder::createItems()` now splits the locked re-read into two cases and `CheckoutException` carries `reason` + `requested` + `available`: completely unavailable (`outOfStock`, stock < 1) → `ui.checkout_item_out_of_stock_named` ("…has sold out… remove it to continue"); some-but-not-enough (`insufficientStock`) → `ui.checkout_item_insufficient_named` naming **both** numbers ("You asked for :requested … only :available in stock now — lower the quantity"). `CheckoutForm::place()` matches on `$e->reason`.
3. **Pre-flight banner.** `detectCartProblems()` already split the two reasons; now also carries `requested`, and the insufficient line shows both numbers (`ui.checkout_cart_insufficient_qty` → "you asked for :requested, only :available in stock").

**Is the stepper the only guard?** No. Cart qty is re-validated twice after the cart page: (a) `CheckoutForm::detectCartProblems()` on checkout mount (the pre-flight banner), and (b) the authoritative backstop — `PlaceOrder::createItems()` re-reads each variant with `lockForUpdate()` inside the order transaction and rejects (full rollback) if locked stock can't cover the line. The stepper cap and `updateQty` clamp are UX/defensive only; the locked re-read in PlaceOrder is the real oversell backstop and is unchanged in intent.

**Required tests (CheckoutTest.php, green):** insufficient-at-submit names both numbers and not "no longer available"; fully-out-of-stock-at-submit uses the distinct "sold out" message; `updateQty` clamps a line to available stock server-side.

**Owner clicks (dots.test):** below.

---

## Checklist  _(execution order: PAY.3 → PAY.2 → PAY.1 → PAY.4 → PAY.5 → PAY.6 → PAY.7 → PAY.8 → PAY.13)_
- [x] PAY.3 Remove dead Paymob env keys (Sonnet)
- [x] PAY.2 Card option gated on full `isConfigured()` (Sonnet)
- [x] PAY.1 Auto-release stock for failed & abandoned card payments; + Gap A payment-failed re-check; + Gap B coupon rollback (Opus)
- [x] PAY.4 Safe bilingual failure reason (Sonnet)
- [x] PAY.5 InstaPay address + deadline on confirmation (Sonnet)
- [x] PAY.6 HMAC verified before order lookup on return (Opus)
- [x] PAY.7 Close test gaps (Opus/Sonnet split per scenario)
- [x] PAY.8 Go-live checklist doc (Sonnet)
- [x] PAY.13 Make Livewire toasts visible on the shop frontend (Sonnet)
- [x] PAY.14 Fix card-retry duplicate `special_reference` 400 + orphaned payment rows; stored-reference column resolved by lookup (Opus)
- [x] PAY.9 A settled payment (paid/refunded) is terminal on the payment-failed page; guard in mount() + locked re-check in proceed() for all three branches; DT-10130 data fix (Opus)
- [x] PAY.10 Re-reserve stock (+ re-consume coupon) when an order is reinstated out of Cancelled; one lockForUpdate serves both boundaries; short stock allowed+warned+recorded, clamped at zero (Opus)
- [x] PAY.16 Cart stepper capped at stock (disabled + "Only :n available") + updateQty server clamp; submit/pre-flight errors split out-of-stock vs insufficient and name both numbers; PlaceOrder locked re-read confirmed as the real backstop (Opus)

---

## Resolved (owner decisions, folded into the tasks above)
1. **Card payment window** — 60 min default, settings field, validated `min:30` (never lower). (PAY.1)
2. **Late success after auto-cancel** — record payment, set `paid`, keep `cancelled`, flag for manual review; never auto-reinstate, never re-decrement stock. (PAY.1)
3. **InstaPay address** — not secret (merchant receiving handle); render on confirmation behind the existing order-view gate; must never appear on any ungated page. (PAY.5)
4. **Failure reasons** — curated bilingual lines + generic fallback, raw text never rendered; neutral tone, never name/blame the bank, every line ends pointing at a next action; natural (non-literal) Arabic. (PAY.4)

## Open questions (decide before work starts)
1. **Auto- vs admin-cancelled in the admin orders list.** Should the admin orders list distinguish auto-cancelled (reservation-expired) orders from admin-cancelled ones — e.g. a filter or a visible marker — given the Cancelled history note already records the reason? Not implemented in this plan; decide whether to add a task.
2. **PAY.8 real transaction.** This plan documents the live-test procedure only (I can't run a real charge). Do you want to run the single low-value live test yourself following the doc, or should PAY.8 also include a sandbox dry-run walkthrough?
