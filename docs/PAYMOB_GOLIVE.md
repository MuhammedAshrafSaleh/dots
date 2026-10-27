# Paymob Go-Live Checklist

Follow this document top-to-bottom. No code changes are required — only `.env` values and Paymob dashboard settings.

---

## 1 — Fill in `.env`

Add the five keys below to your production `.env`. All five must be non-empty or the card payment option will be hidden automatically.

```dotenv
# Base URL — keep the default unless Paymob changes it
PAYMOB_BASE_URL=https://accept.paymob.com

# From Paymob Dashboard → Developers → API Keys
PAYMOB_SECRET_KEY=sk-...
PAYMOB_PUBLIC_KEY=pk-...

# From Paymob Dashboard → Developers → Payment Integrations
# Open the card integration you created → copy the "Integration ID"
PAYMOB_INTEGRATION_ID_CARD=12345678

# From Paymob Dashboard → Developers → Webhooks (or Account Settings → HMAC)
# The single HMAC secret that Paymob uses to sign both webhooks and browser callbacks
PAYMOB_HMAC_SECRET=...
```

`APP_URL` must also be set to the real public HTTPS origin of your production site (e.g. `https://dots.example.com`). The `notification_url` (webhook) and `redirection_url` (browser return) sent to Paymob are built from `APP_URL` via `route()` — an incorrect value means Paymob will POST to the wrong address or redirect customers to a dead URL.

---

## 2 — Paymob Dashboard: callback URLs

In the Paymob Dashboard, open the card integration whose ID you placed in `PAYMOB_INTEGRATION_ID_CARD`.

| Setting | Value |
|---|---|
| **Transaction processed callback** (server → server) | `POST {APP_URL}/payments/paymob/webhook` |
| **Transaction response callback** (browser redirect) | `{APP_URL}/{locale}/payments/paymob/return` |

Replace `{APP_URL}` with your production origin. Replace `{locale}` with the locale prefix your customers use — for Arabic: `ar`, for English: `en`. You may register both if needed; the route accepts both.

The HMAC secret in the dashboard is the value you set as `PAYMOB_HMAC_SECRET`. Make sure you are reading from the correct integration — different integrations can have different HMAC secrets.

---

## 3 — Local webhook testing via a public tunnel (Herd Share / ngrok)

**Happy path (no tunnel needed):** After a customer completes or fails the card form on Paymob Unified Checkout, the browser is redirected back to `{APP_URL}/{locale}/payments/paymob/return`. The return controller verifies the callback HMAC and records the transaction itself. This means the customer sees the correct confirmation or payment-failed page regardless of whether a webhook arrives. You can test this flow fully locally without a tunnel.

**Abandoned-browser / server-to-server webhook (tunnel required):** If the customer closes the tab before the browser redirect fires, Paymob delivers the transaction result to `notification_url` (the webhook endpoint) instead. To test this path locally, Paymob must be able to POST to your machine over the internet. Use a tunnel:

1. Start Herd Share (`herd share`) or ngrok and copy the HTTPS tunnel URL (e.g. `https://abc123.share.laravel.dev`).
2. Set `APP_URL=https://abc123.share.laravel.dev` in your local `.env` for the duration of the test.
3. Update the Paymob dashboard **Transaction processed callback** to the same tunnel URL + `/payments/paymob/webhook`.
4. Place a card order in the sandbox, complete the Paymob form, then immediately kill or ignore the browser tab before the redirect completes — Paymob will POST to the webhook and the order will settle server-side.
5. Restore `APP_URL` and the dashboard callback to your real origin when done.

---

## 4 — Verify the webhook is reachable in production

After deploying, send a test POST to the webhook (e.g. with `curl` or Paymob's dashboard "Test" feature). The endpoint **always** returns HTTP 200 so Paymob does not retry payloads that were legitimately rejected:

- `{"ok": true}` — HMAC valid, transaction recorded.
- `{"ok": false}` — HMAC invalid or unknown order; nothing was written.

To confirm a real event arrived and was processed, check:

1. **`webhook_events` table** — one row per unique Paymob transaction ID; the `created_at` timestamp shows when it arrived.
2. **`payments` table** — `hmac_verified = true` on the payment row for the order.
3. **Order history** — the order detail in the admin shows a "Card payment received" history note with the transaction ID.
4. **Order `payment_status`** — should be `paid`; `reserved_until` should be `NULL` (cleared on success).

---

## 5 — One real low-value test transaction

With production keys and a real (low-value) card:

1. Place a card order on the live site for a minimal amount.
2. Complete the Paymob Unified Checkout form with a real card.
3. Confirm the browser redirects to the order confirmation page.
4. Check the admin order detail:
   - `payment_status = paid`
   - `reserved_until` is null
   - A payment row exists with `hmac_verified = true`
   - A `webhook_events` row exists for the order
   - The order history shows a "Card payment received" note
5. Refund the charge from the Paymob Dashboard → Transactions → find the order → Refund. Paymob does not send a refund webhook in all configurations; the order `payment_status` stays `paid` in the database and must be updated manually by an admin if you need to track it there.

---

## 6 — Queue reminder

`NewOrderMail` is queued (`ShouldQueue`). In production with `QUEUE_CONNECTION=database` or `redis`, no order confirmation email will be sent unless a worker is running:

```bash
php artisan queue:work
```

Run this as a persistent background process (e.g. via Supervisor). Without it, emails queue up but are never delivered.
