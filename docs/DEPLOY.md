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

## Step 3 — Domain re-point, TLS, Cloudflare decision, real-IP *(done)*

Executed over SSH (UTC, 2026-11-01→02). Same discipline as steps 1–2: each sub-step announced;
`systemd-run --on-active` rollback timer armed before anything that could take the site/access
down, cancelled on verified success.

### Current serving state (summary)

- The site serves **directly from the origin** (187.126.20.146) at **https://dots-electric.com**
  and **https://www.dots-electric.com** over a **Let's Encrypt** certificate. `www` 301→apex;
  `http` 301→`https`. Verified end-to-end (trusted chain, `ssl_verify_result=0`) from both an
  external client and the origin.
- **Cloudflare is used for DNS only** — both records grey-cloud ("DNS only"). The Cloudflare
  HTTP/HTTPS **proxy is deliberately OFF** (see the d/e/f decision below).
- No app code deployed yet (placeholder `index.php`). App deploy is step 4.

### a. Domain re-point — CloudPanel has no rename; delete + recreate

CloudPanel 6.0.8 (`clpctl`) has **no** command to change a site's primary domain, and the schema
models one primary domain per site (`site.domain_name` UNIQUE, no addon-domain table). The step-2
note that assumed "a clpctl domain change renames the vhost + docroot" was **wrong**.

Owner-approved path: **delete the temp site and recreate on the real domain** (cheapest while
nothing is deployed — DB empty, no code):

- `clpctl site:delete --domainName=srv2107659.hstgr.cloud` — CloudPanel's `database.site_id` FK is
  `ON DELETE CASCADE`, so this **also dropped** the (empty) MySQL DB `dots-production`, the
  `dots-app` user, the `dots` system user, `/home/dots`, the vhost and the FPM pool. Verified at the
  MySQL level that db + user were gone. **No data loss** (nothing had been migrated).
- `clpctl site:add:php --domainName=dots-electric.com --phpVersion=8.5 --vhostTemplate='Laravel 13'
  --siteUser=dots` — recreated with the **same `dots` user** (uid 1001 / gid 1003).
- **Docroot path changed:** `/home/dots/htdocs/srv2107659.hstgr.cloud/public` →
  **`/home/dots/htdocs/dots-electric.com/public`**.
- FPM pool tuning re-applied from the step-2 **tuned** snapshot (`/root/pool-TUNED-preT103*.conf.bak`);
  values identical to step 2 (`pm.max_children 30`, `pm.max_requests 500`, `memory_limit 512M`,
  `upload_max_filesize 256M`, `post_max_size 512M`, `max_execution_time`/`max_input_time` 180).
- DB recreated via `clpctl db:add` (`dots-production` / `dots-app`, connects over `127.0.0.1`).
  **New MySQL + new site-user passwords** handed to the owner in-chat (password manager), **not** in
  the repo. **Redis password unchanged.**

### b. Let's Encrypt certificate + renewal

- Issued: `clpctl lets-encrypt:install:certificate --domainName=dots-electric.com
  --subjectAlternativeName=www.dots-electric.com`. Worked first try because the proxy was off and the
  HTTP-01 challenge reached the origin directly (the vhost `.well-known` location is open:
  `allow all`, `auth_basic off`).
- SANs cover **apex + www**; issuer Let's Encrypt; **expires 2027-01-30 22:35 GMT** (~90 days).
- **Auto-renewal (verified, not assumed):** CloudPanel cron `/etc/cron.d/clp` →
  `15 5 * * * clp … clpctl lets-encrypt:renew:certificates` (daily 05:15 UTC, as user `clp`). The
  command renews any LE cert with **≤7 days to expiry** (`RENEW_DAYS_BEFORE_EXPIRATION = 7`),
  reinstalls + reloads nginx, and raises a **CRITICAL CloudPanel notification** on failure. Running it
  as `clp` returns exit 0 (nothing due) → cert auto-renews ~2027-01-23. *(Caveat: run as `root` the
  command throws a namespace error; it only resolves as the `clp` user — which is exactly how the cron
  runs, so renewal is fine. Do not "fix" the cron to run as root.)*

### c. Nginx real-IP restoration for Cloudflare — KEPT even though the proxy is off

It is kept for two reasons: (1) it closed a **genuine spoofing hole that existed regardless of
Cloudflare** (proven by measurement below); (2) it is correct for the day the proxy is ever enabled.

**CloudPanel native is lockdown-only, not real-IP.** `clpctl cloudflare:update:ips` fetches the
official `https://www.cloudflare.com/ips-v4` + `ips-v6` daily (cron `20 6 * * *`) into
`/etc/nginx/cloudflare/ips`, but that file is an **`allow …; deny all;`** list — the
`allow_traffic_from_cloudflare_only` traffic lockdown (step f), **not** real-IP. An app-wide search of
CloudPanel's source found **zero** `real_ip_header` / `CF-Connecting-IP` / `set_real_ip_from`. So
real-IP is hand-built, **reusing CloudPanel's official-source IP fetch** (not a remembered list).

**Two-tier topology:** the outer 443 server block (writes the access log) `proxy_pass`es to an inner
8080 block that `fastcgi_pass`es to FPM — **Laravel's `request()->ip()` comes from the inner block's
`$remote_addr`.**

**Spoofing hole found + measured.** A naive http-scope `real_ip_header CF-Connecting-IP;` trusting
`127.0.0.1` is **spoofable**: a direct (non-Cloudflare) client sending a forged `CF-Connecting-IP`
has the outer block correctly ignore it (access log stays honest), but the outer forwards the forged
header to the inner block over the trusted 127.0.0.1 hop, so the inner block — and Laravel — take the
forged value. **Measured:** forged `CF-Connecting-IP: 1.2.3.4` from a non-CF source → access log
`187.126.20.146` (honest) but `REMOTE_ADDR=1.2.3.4` (spoofed). That would let an attacker forge the
client IP used for rate-limiting, customer login lockout and audit — **independent of Cloudflare**.

**Fix (applied, measured non-spoofable).** The inner block must restore from a header the outer
**overwrites**, trusting only the 127.0.0.1 hop. `X-Forwarded-For` + `real_ip_recursive on` does this
with **no vhost edit**, because the outer block always emits (template-generated)
`proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;` — recursion stops at the outer's
appended real source, so a client-supplied header can never reach past it:

```nginx
# /etc/nginx/cloudflare/real-ip.conf  (http scope; generated — do not hand-edit)
real_ip_header X-Forwarded-For;
real_ip_recursive on;
set_real_ip_from 127.0.0.1;        # trusted inner <- outer nginx hop
set_real_ip_from <each Cloudflare v4/v6 range>;
```

Re-tested under the fix: forged `CF-Connecting-IP` → `REMOTE_ADDR=187.126.20.146` (real source);
forged `X-Forwarded-For` (even with a trailing CF-looking IP) → real source; a real client delivered
through a **trusted** hop (XFF) → correctly extracted. Access log honest in all cases.

**Where it lives and why it survives vhost regeneration:**

- `/etc/nginx/cloudflare/real-ip.conf` — a file CloudPanel never generates.
- Included at **http scope in `/etc/nginx/nginx.conf`** — a **dpkg conffile** (`nginx-common`),
  untouched by CloudPanel's per-site vhost regeneration (CloudPanel only rewrites
  `/etc/nginx/sites-enabled/<domain>.conf`). The old unsafe CloudPanel scaffold
  (`set_real_ip_from 0.0.0.0/0;` with the header commented) is commented out.
- **Not** placed in the vhost — that is regenerated on every site-setting change.
- **Auto-refresh:** `/usr/local/sbin/dots-cf-realip.sh` regenerates `real-ip.conf` from
  `/etc/nginx/cloudflare/ips` (official source, kept fresh daily by CloudPanel's own
  `cloudflare:update:ips`), runs `nginx -t`, reloads only if valid + changed. Cron
  `/etc/cron.d/dots-cf-realip` at `35 6` (after CloudPanel's 06:20 refresh). So Cloudflare range
  changes propagate automatically.
- **Carry-forward:** re-verify this http-scope edit after an nginx **package** upgrade (dpkg saves a
  `.dpkg-dist` rather than overwrite — confirm the `include` line is still present).

### d / e / f. Cloudflare proxy — DECISION: DNS only, proxy NOT used

**Decision (2026-11-02):** Cloudflare is used for **DNS only**. The HTTP/HTTPS proxy (orange cloud) is
**not** enabled, and the origin firewall lockdown (f) is **not** applied.

**Evidence.** From **two separate Egyptian ISP networks**, the Cloudflare edge IPs assigned to this
zone (`188.114.96.6` / `188.114.97.6`) **reset the connection during the TLS handshake**, on both HTTP
and HTTPS. The same request through a different Cloudflare anycast IP (`104.21.80.1`) returns
`HTTP/2 200` with `server: cloudflare`, and the origin itself serves fine. So Cloudflare, the
certificate and the origin are all healthy — but the **specific IP range Cloudflare assigned to this
zone is being interfered with by Egyptian ISPs**. On the **free plan the assigned edge IPs cannot be
chosen**, so this is not fixable by configuration. For an Egyptian e-commerce site, that is
**disqualifying**.

**Consequences:**

- **(e) real-IP verification with live Cloudflare traffic — skipped.** There is no Cloudflare proxy
  traffic to observe. The real-IP config (c) is verified against the spoofing attack and the
  trusted-hop positive case; the live-CF path is untested by definition.
- **(f) origin firewall lockdown — must NOT be applied.** Restricting inbound 80/443 to Cloudflare
  ranges would make the origin **unreachable entirely**, since visitors reach it directly, not via
  Cloudflare.
- The real-IP config (c) is **kept as-is** — correct, closes a real hole, and ready if the proxy is
  ever enabled.

**Re-enabling the proxy in future requires, in order:** (1) **re-test reachability of the zone's
assigned Cloudflare edge IPs from Egyptian networks — this is the blocker, do it first;** (2) set
SSL/TLS mode to **Full (strict)** *before* switching DNS to Proxied (origin has a valid LE cert, so
strict validates; Flexible would cause a redirect loop); (3) flip both records to Proxied; (4) run (e)
to confirm real visitor IPs in the access log; (5) only then consider the (f) lockdown (stale-range
risk already mitigated by the daily `cloudflare:update:ips` refresh).

### fail2ban nginx jail — still deferred

Deliberately skipped in step 1 (app + log paths didn't exist). Still deferred: add after app deploy,
targeting the Laravel login / auth endpoints for credential-stuffing rate-limiting. **Ordering rule
that still holds if the Cloudflare proxy is ever enabled:** real-IP restoration must be confirmed
first, or the first blocked request bans a Cloudflare edge IP and takes the site down for everyone
behind it. With the proxy off (current state) the jail would see real client IPs directly.

---

## Step 4 — Application deploy

*(to be filled in)*

### Carry-forward tasks into step 4 (explicit, not reminders)

- **T4-TASK: block search-engine indexing until launch.** Because Cloudflare is DNS-only (step 3),
  the site is **publicly reachable at the real domain `https://dots-electric.com` before launch** —
  nothing gatekeeps it. The app **must** be prevented from being indexed until go-live. Implement as a
  deliberate deploy task: serve `X-Robots-Tag: noindex, nofollow` (and/or a `robots.txt` disallow-all)
  for the whole site while in pre-launch, and **remove it as part of the go-live checklist**. Do not
  rely on "search engines probably won't find it".
- **App `.env` (from step 2):** `DB_HOST=127.0.0.1`, DB `dots-production` / user `dots-app` (new
  password from step 3, in the owner's password manager), `REDIS_PASSWORD` (step-2 value), queue/cache/
  session on Redis. `QUEUE_CONNECTION=database`/`redis` in prod needs a running `php artisan queue:work`
  (NewOrderMail is queued).
- **FPM `max_execution_time` (step 2 carry-forward):** measure real Excel parse/preview before raising
  past 180s.
- **Queue worker sizing (step 2 carry-forward):** heavy import (PhpSpreadsheet + image derivatives)
  runs in the worker — size memory/timeout against free RAM.
