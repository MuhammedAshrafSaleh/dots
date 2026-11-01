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
- [ ] Not started

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

## Checklist  _(execution order: PAY.3 → PAY.2 → PAY.1 → PAY.4 → PAY.5 → PAY.6 → PAY.7 → PAY.8 → PAY.13)_
- [x] PAY.3 Remove dead Paymob env keys (Sonnet)
- [x] PAY.2 Card option gated on full `isConfigured()` (Sonnet)
- [x] PAY.1 Auto-release stock for failed & abandoned card payments; + Gap A payment-failed re-check; + Gap B coupon rollback (Opus)
- [x] PAY.4 Safe bilingual failure reason (Sonnet)
- [x] PAY.5 InstaPay address + deadline on confirmation (Sonnet)
- [x] PAY.6 HMAC verified before order lookup on return (Opus)
- [x] PAY.7 Close test gaps (Opus/Sonnet split per scenario)
- [x] PAY.8 Go-live checklist doc (Sonnet)
- [ ] PAY.13 Make Livewire toasts visible on the shop frontend (Sonnet)

---

## Resolved (owner decisions, folded into the tasks above)
1. **Card payment window** — 60 min default, settings field, validated `min:30` (never lower). (PAY.1)
2. **Late success after auto-cancel** — record payment, set `paid`, keep `cancelled`, flag for manual review; never auto-reinstate, never re-decrement stock. (PAY.1)
3. **InstaPay address** — not secret (merchant receiving handle); render on confirmation behind the existing order-view gate; must never appear on any ungated page. (PAY.5)
4. **Failure reasons** — curated bilingual lines + generic fallback, raw text never rendered; neutral tone, never name/blame the bank, every line ends pointing at a next action; natural (non-literal) Arabic. (PAY.4)

## Open questions (decide before work starts)
1. **Auto- vs admin-cancelled in the admin orders list.** Should the admin orders list distinguish auto-cancelled (reservation-expired) orders from admin-cancelled ones — e.g. a filter or a visible marker — given the Cancelled history note already records the reason? Not implemented in this plan; decide whether to add a task.
2. **PAY.8 real transaction.** This plan documents the live-test procedure only (I can't run a real charge). Do you want to run the single low-value live test yourself following the doc, or should PAY.8 also include a sandbox dry-run walkthrough?
