# Deployment runbook

Server: `srv2107659.hstgr.cloud` · 187.126.20.146 · Hostinger KVM 2 · Ubuntu 24.04 · CloudPanel 6.0.8

Steps mirror the phase-10 task breakdown. Each step is a separate session.

---

## Step 1 — Server hardening *(done)*

SSH key-only auth, UFW review, fail2ban, UTC timezone. See phase-10 git log.

### UFW rule set — reviewed, no changes made

Active rules at handoff: `22/tcp`, `80/tcp`, `443/tcp+udp`, `8433–8443/tcp`.

The `8433–8443/tcp` range was deliberately left as the CloudPanel installer default.
Nothing listens on 8433–8442 today, and an open port with no service behind it is not
an attack surface — the kernel refuses the connection. Tightening to `8443/tcp` only
would trade a real risk (CloudPanel silently moving to a port in that range after an
update) for zero measurable security gain. Do not "fix" this range without confirming
CloudPanel's current port usage first.

---

## Step 2 — MySQL, Redis, PHP configuration

### ⚠️ MySQL bind-address must be restricted to 127.0.0.1

At baseline (step 1), MySQL (Percona 8) is bound to all interfaces (`*:3306`, `*:33060`).
UFW blocks those ports externally today, but one accidental `ufw allow 3306` would expose the database.

Before any other MySQL work in step 2, add `bind-address = 127.0.0.1` to the CloudPanel-managed
MySQL config (typically `/etc/mysql/conf.d/` — confirm the exact file on the server at that time)
and restart `mysql.service`. Verify with `ss -tlnp | grep 3306` that it shows `127.0.0.1:3306`.

---

## Step 3 — Site, queue worker, scheduler

### ⚠️ Cloudflare real-IP restoration must come before any fail2ban nginx jail

Cloudflare will sit in front of the site. Once it does, Nginx sees Cloudflare's edge IPs
for every request, not the real visitor IP. This affects two things:

1. **Real-IP restoration** — configure `set_real_ip_from` with Cloudflare's published IP
   ranges and `real_ip_header CF-Connecting-IP` in Nginx so that access logs, Laravel's
   `request()->ip()`, and rate-limiting all see the actual client address.

2. **Ordering constraint — do not create the fail2ban nginx jail until real-IP is verified.**
   If the jail is active before real-IP restoration is in place, the first batch of blocked
   requests will ban a Cloudflare edge IP, taking the site down for all visitors routed
   through that edge node. Real-IP restoration first, jail second — no exceptions.

### fail2ban nginx jail (deferred from step 1)

The nginx jail was deliberately skipped in step 1 because the app and its Nginx log paths
do not exist yet. Add it in step 3 after the site is deployed and real-IP restoration is
confirmed working. The jail should target the Laravel login route and any other
authentication endpoints for rate-limiting credential-stuffing attacks.

---

## Step 4 — DNS, SSL, go-live

*(to be filled in)*
