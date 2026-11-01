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

## Step 2 — Site, PHP, MySQL, Redis *(done)*

All executed over SSH on the temporary hostname `srv2107659.hstgr.cloud` (real domain not yet
pointed — that is step 3). No app code, `.env`, git clone or migrations in this step (step 4).

### PHP version — 8.5

Server runs **PHP 8.5.12**, matching the local Herd dev environment **exactly** (also 8.5.12).
`composer.json` requires `^8.3`, but that is a *floor*, not a target: every test / Larastan /
Pint / build gate has only ever run against 8.5 locally, so production must match or the gates
stop testing what the client runs. PHP 8.5.0 went GA 2025-11-20 (now 8.5.x), active support to
2027-12-31, security to 2029-12-31 — a mature, recommended release, not an early one. CloudPanel
had 7.1–8.5 installed; 8.5 chosen deliberately, not "newest by default".

### Site (CloudPanel, `clpctl site:add:php`)

- **vhost template:** `Laravel 13` (CloudPanel 6.0.8 ships `Laravel 12` + `Laravel 13`; project is
  Laravel 13.35.0). The template sets docroot to `public/` and the correct Laravel `try_files`
  automatically — no hand-patching. `Generic` would have left the root at the site base and 404'd.
- **Site user:** `dots` (uid 1001, gid 1003)
- **Document root:** `/home/dots/htdocs/srv2107659.hstgr.cloud/public`
- **PHP-FPM pool:** `/etc/php/8.5/fpm/pool.d/srv2107659.hstgr.cloud.conf` (listens `127.0.0.1:20001`)
- **Re-point in step 3 without rebuild:** a `clpctl` domain change renames the vhost + docroot to
  `dots-electric.com`; the `dots` user, FPM pool, and deployed code stay in place, then issue LE cert.

### PHP-FPM pool tuning

CloudPanel's 8.5 FPM `php.ini` ships generous size/time defaults (not stock PHP). The real risk was
**not** per-request limits but `pm.max_children = 250` on an 8 GB / 2 vCPU box (worst case
250 × 768M ≈ 192 GB → OOM kills the box under load). Final values (`php_admin_value`, so not
per-request overridable; backup at `/root/pool-srv2107659-*.conf.bak`):

| Setting | Before | After | Reason |
|---|---|---|---|
| `pm.max_children` | 250 | **30** | Real OOM protection. ~4 GB PHP budget ÷ ~150 MB/worker ≈ 30; also 2 vCPUs can't usefully serve more than 30 concurrent — higher buys queueing, not throughput. |
| `pm.max_requests` | 100 | **500** | Recycle workers to bound PhpSpreadsheet / image-processing memory growth. |
| `memory_limit` | 768M | **512M** | Ample for request-side parse/preview; heavy import runs in the queue worker (step 5), not the web request. |
| `upload_max_filesize` | 2G | **256M** | Covers ~120 MB image ZIP + large Excel; caps abuse vs. wide-open 2G. |
| `post_max_size` | 3G | **512M** | Must exceed upload limit + field headroom. |
| `max_input_time` | 90 | **180** | Receiving a 256 MB upload on a slow client. |
| `max_execution_time` | 90 | **180** | Headroom for synchronous parse/preview; frees stuck workers. |

**Carry-forwards:**
- **Step 9 (pre-launch):** time the real Excel parse/preview with a realistically-sized catalogue
  sheet; raise `max_execution_time` only if measurement shows 180s is insufficient — do not pad to
  300 on a guess.
- **Step 5 (queue worker sizing):** the heavy import (PhpSpreadsheet + image derivatives) runs in
  the worker, not the web request, so the real memory/timeout pressure lands there — size it
  deliberately. Baseline RAM (8 GB total, ~6 GB free): `mysqld` ~960 MB, idle PHP-FPM masters
  (7.1–8.5) ~300 MB, nginx ~160 MB. **Varnish** (~93 MB) and **Memcached** (≤64 MB cap, ~5 MB used)
  are running CloudPanel defaults but **NOT in this site's request path** — nginx proxies
  `443 → 127.0.0.1:8080` (nginx PHP backend), not Varnish's `6081`, and the vhost doesn't reference
  Memcached. Left running (not disabled); revisit only if the worker budget gets tight.

### MySQL — database + user

- **Database:** `dots-production` · **User:** `dots-app` (hyphens — CloudPanel's validator rejects
  underscores in db/user names).
- Created via **`clpctl db:add`** (not raw SQL). Deliberate choice:
  1. CloudPanel-registered + tied to the site, so it stays visible/manageable in the panel (a db
     created behind CloudPanel's back would not be).
  2. MySQL `root@localhost` requires a password held internally by `clpctl`; the sanctioned path
     avoids moving the root password around.
- **Grant is db-scoped `ALL PRIVILEGES ON \`dots-production\`.*` + `USAGE ON *.*`** — not root, not
  global. This is a **deliberate choice over a curated grant list**, not an oversight: the only
  privileges db-scoped ALL adds over a hand-picked DML/DDL list are routines, triggers, views and
  events *on this one database* — which Laravel migrations and packages legitimately use. Curating
  the grant would need the MySQL root password for `REVOKE`, trading a real cost for near-zero gain.
- CloudPanel creates the user as `dots-app`@**`%`** (fixed CloudPanel behaviour, not `@localhost`).
  Not externally reachable: MySQL is bound to `127.0.0.1` (below) and UFW blocks 3306. The app
  connects over `127.0.0.1`.

### MySQL — bind-address restricted to 127.0.0.1 *(carry-forward from step 1, done)*

Baseline had MySQL on all interfaces (`*:3306`, `*:33060`). No `bind-address` was set anywhere.
`my.cnf` includes `conf.d/` then `mysql.conf.d/` (last wins), so the change went in a dedicated
drop-in **`/etc/mysql/mysql.conf.d/z-dots-bind-address.cnf`** (`bind-address = 127.0.0.1`,
`mysqlx-bind-address = 127.0.0.1`) — sorts after CloudPanel's `mysqld.cnf` so it wins, and a
separate file survives CloudPanel regenerating its own config. Restart was done under a
`systemd-run --on-active=600` rollback timer (`rm` the drop-in + restart); cancelled only after
verifying `ss` shows `127.0.0.1:3306`/`:33060`, the app user connects, and CloudPanel still responds.
**Rollback if ever needed:** `rm -f /etc/mysql/mysql.conf.d/z-dots-bind-address.cnf && systemctl restart mysql`.

### Redis — requirepass set

Baseline: bound to `127.0.0.1`/`::1` only, no password, empty keyspace, no persistent clients, and
CloudPanel's `.env` has **no** Redis/cache/session/queue config (CloudPanel's own app runs on
SQLite). So Redis has no CloudPanel dependency and setting a password breaks nothing. `requirepass`
set and persisted via `config rewrite` (in `/etc/redis/redis.conf`). Rationale: Redis will hold the
Laravel session/cache/queue in step 4 — a password blocks a local compromised-FPM-worker
`EVAL`/`FLUSHALL` at negligible cost on an already-localhost-bound instance. Password stored in the
owner's password manager, not the repo. (The app's `REDIS_PASSWORD` is set in step 4's `.env`.)

### Verification results

- PHP-FPM 8.5 active, listening `127.0.0.1:20001`; throwaway `_healthcheck.php` via nginx returned
  `FPM_OK 8.5.12` then removed → full `nginx → FPM → PHP` path works.
- Site over temp hostname: public `http` 301 → `https` 200.
- MySQL: `dots-app` connects over `127.0.0.1` to `dots-production` (`SELECT 1` ok); 3306/33060 bound
  to `127.0.0.1` only.
- Redis: no-auth ping → `NOAUTH`; authed ping → `PONG`.
- All services active: `php8.5-fpm`, `nginx`, `mysql`, `redis-server`. CloudPanel panel healthy (302).

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
