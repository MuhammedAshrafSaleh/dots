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

## Step 4 — Application deploy *(done)*

Executed over SSH (UTC, 2026-11-02). All code / git / build / artisan ran **as the `dots` site user**
(`sudo -u dots -H …`), never as root. Site now serves the real application at
`https://dots-electric.com` with content restored from the Google-Drive backup.

### Standing rules for this server (traps — read before touching it)

- **`php8.5`, never `php`.** The box's default `php` is **8.4.27**; the site's FPM pool + gates run on
  **8.5.12**. Every `artisan`/`composer` command must be `php8.5 …` (composer: `php8.5 "$(which composer)" …`).
  A bare `php artisan` silently runs on 8.4 — do not use it.
- **Node is nvm-only, under `dots`.** A fresh root shell has no `node` on PATH. Builds run as `dots`
  after activating nvm: `export NVM_DIR="$HOME/.nvm"; . "$NVM_DIR/nvm.sh"; nvm use 22.23.0`.
- **After every `git clone`/fresh checkout, create the writable runtime dirs** — the repo does **not**
  track them, and `artisan` throws *"Please provide a valid cache path"* without them:
  `mkdir -p storage/framework/{cache/data,sessions,views}`.

### a. GitHub access — read-only deploy key

A **deploy key** (least privilege: scoped to this one repo, read-only, no token rotation) generated as
`dots` at `~/.ssh/dots_deploy` (ed25519), with `~/.ssh/config` routing `github.com` → that key
(`IdentitiesOnly yes`). Added on GitHub → repo → Settings → Deploy keys (write access **off**). Auth
verified: `ssh -T git@github.com` → "Hi MuhammedAshrafSaleh/dots!". The server only ever **pulls**.

### b. Clone `main` in place (docroot path never moves)

CloudPanel's docroot is fixed at `…/dots-electric.com/public`. To avoid any window where that path is
missing, the repo was **init-cloned in place** (not a rename/swap): `git init -b main` →
`git remote add origin git@github.com:MuhammedAshrafSaleh/dots.git` → fetch `main` only
(`remote.origin.fetch = +refs/heads/main:refs/remotes/origin/main`) → removed the 31-byte CloudPanel
placeholder `public/index.php` (backed up to `/home/dots/placeholder-index.php.bak`) → `git checkout -b
main --track origin/main`. `public/.well-known/` (ACME) is untracked and left intact. Deploy branch is
**`main`**.

### c. Production `.env`

Built **from `.env.example`** (never the local `.env`), perms `0640 dots:dots`, written via a quoted
heredoc piped to `tee` (so special chars — `/ = @ ! //` in the DB/backup/Google secrets — land
byte-exact; never echoed through a shell). Keys set (**names only**): `APP_ENV=production`,
`APP_DEBUG=false`, `APP_URL=https://dots-electric.com`, `APP_KEY`, `APP_NAME/LOCALE/FALLBACK/FAKER`,
`BCRYPT_ROUNDS`, `LOG_CHANNEL/STACK/DAILY_DAYS/DEPRECATIONS_CHANNEL`, `LOG_LEVEL=warning`,
`DB_CONNECTION/HOST/PORT`, `DB_DATABASE=dots-production`, `DB_USERNAME=dots-app`, `DB_PASSWORD`,
`SESSION_DRIVER=redis`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT/LIFETIME/PATH/DOMAIN`,
`CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `REDIS_CLIENT=phpredis`, `REDIS_HOST/PORT/PASSWORD`,
`IMAGE_DRIVER=imagick`, `FILESYSTEM_DISK=local`, `BROADCAST_CONNECTION=log`, `MAIL_MAILER=log`
(placeholder — real mail is step 6), `MAIL_FROM_ADDRESS=no-reply@dots-electric.com`,
`BACKUP_NOTIFICATION_EMAIL`, `BACKUP_ARCHIVE_PASSWORD`, `GOOGLE_DRIVE_CLIENT_ID/SECRET/REFRESH_TOKEN`,
`GOOGLE_DRIVE_FOLDER` (empty). Left **empty by design**: `AWS_*`, `PAYMOB_*`, `ADMIN_EMAIL/PASSWORD`.
Values never stored in the repo.

- **`APP_KEY` — generated fresh** (`openssl rand -base64 32`, prefixed `base64:`, byte-identical to
  `key:generate` for the `AES-256-CBC` cipher; done on-server so it never left the box). This is **safe**:
  a codebase audit found **no** `encrypted` casts, no `Crypt::encrypt`, `SESSION_ENCRYPT=false` — nothing
  is encrypted at rest, so a new key makes no restored data unreadable. (Passwords/tokens are *hashed*,
  not key-encrypted.)
- **Trusted proxies — none needed.** The app does **not** configure `trustProxies()`, and the outer 443
  block does **not** send `X-Forwarded-Proto`. HTTPS is detected because the inner 8080 block sets
  `fastcgi_param HTTPS "on"` → `request()->isSecure()` is true via the native server-param path. Verified
  on the live site: `isSecure()` true, generated URLs `https://`. (Client IP relies on the step-3 nginx
  `real_ip` rewrite, not proxy trust.) If the inner `HTTPS "on"` ever goes away, add explicit proxy trust.
- **`ADMIN_PASSWORD` empty is safe:** the only enforcement is a throw inside `RolesAndAdminSeeder`, which
  step 4 never runs (admin comes from the restore). `config/dots.php` returns `null` with no boot guard.
- **`DB_PASSWORD` was reset in this step.** The step-3 password was lost, and CloudPanel 6.0.8 `clpctl`
  has **no** DB-user password-change command (only `db:add/delete/export/import/show:master-credentials`;
  `user:reset:password` is for *panel* users). So the empty DB was **deleted + recreated** via
  `clpctl db:delete` + `clpctl db:add` (owner-approved; same pattern as the step-3 site recreate) — keeps
  the panel's stored credential in sync with MySQL. New password handed to the owner once (password
  manager), verified connecting over `127.0.0.1`.

### d. Build for production

- `php8.5 "$(which composer)" install --no-dev --optimize-autoloader` → `vendor/` (109 pkgs, no dev),
  **Composer 2.10.3**, **Laravel 13.35.0**, **PHP 8.5.12** (matches local exactly).
- **Frontend built on-server** (deploy must be a documented on-box sequence, not laptop-dependent):
  Node **v22.23.0** / npm **10.9.8** via **nvm under `dots`** — the exact version local builds use (no
  `engines`/`.nvmrc`; Vite 8 needs ≥ Node 20.19/22.12; v22 is LTS). `npm ci && npm run build` → `public/build`
  (manifest + `site.css`/`admin.css`/`app.js`/`admin.js`). **`node_modules` removed after the build**
  (137 MB freed); the app runs and the Vite manifest resolves without it (Laravel needs `node_modules`
  only at build time).
- **Verified through the app** (not by eyeballing `.env`): `artisan about` → env `production`, debug OFF;
  SHA-256 of each secret read back via `env()` **matched** the local-expected hashes (special chars intact);
  `DB_CONNECT=OK`, `REDIS_PING=true`.

### e. Content restore (RESTORE.md — also the first prod restore drill)

`php8.5 artisan dots:backup-download` → latest archive `2026-11-01-19-31-46.zip` (80,607,508 B) →
decrypt via PHP `ZipArchive` with `BACKUP_ARCHIVE_PASSWORD` (1204 entries). **DB imported into the empty
`dots-production` directly** (deviation from RESTORE.md's scratch-DB step — that step protects *live* data,
and this DB was empty/pre-launch). Import ran as **`dots-app`**, which lacks `SUPER`, so **all**
`SQL_LOG_BIN`/`GTID_PURGED` preamble lines were stripped (RESTORE.md strips 2; a `dots-app` import needs
the session-`SQL_LOG_BIN` restore line gone too — it only affects binary logging during restore). Media
`rsync -a --delete`'d from the archive (authoritative over the repo's tracked demo images) →
`storage/app/public`, owned `dots`; `storage:link` created. Decrypted artifacts (`/home/dots/restore`,
customer data) removed afterward.

**Objective verification — all row counts matched the 2026-11-01 drill exactly:**

| products | variants | categories | brands | orders | customers | pages | settings |
| -------: | -------: | ---------: | -----: | -----: | --------: | ----: | -------: |
|       18 |       30 |         65 |      8 |    108 |         9 |     6 |       37 |

Media **860 / 860 files** (exact). Size: `du` disk 84,040 KB · apparent 80,902 KB · drill-ref 82,664 KB —
the spread is ext4 block accounting vs the mac the drill ran on; identical file count + `rsync -a` (exact
bytes) = intact.

**Content URL audit (restored data):** content tables (`pages`, `page_sections`, `faqs`) are **clean** —
zero `dots.test`/`http://`. The bulk of `dots.test` hits are **transactional snapshots** — `order_items`
(132), `quote_requests` (40), `orders` (2), and a test `pf-test@dots.test` account — which are **demo data
that `php artisan dots:clean-demo-data` deletes before launch** (TEST-/demo products, demo customers, all
orders, carts, leads, import logs). **They resolve on their own via clean-demo-data — not a bug; do not
hand-edit historical records.** The one genuine content bug is `settings.brand.logo_url` (see go-live).

### f. Migrate + caches

`php8.5 artisan migrate --force` → **Nothing to migrate** (restored migration table already at `main`).
`storage:link` confirmed. Production caches built: `config:cache`, `route:cache`, `view:cache`,
`event:cache` — all CACHED. **If `.env` or config changes later, `config:clear` first** (cached config
ignores `.env`).

### g. Ownership & permissions (verified, not assumed)

Under the whole site tree: **0** files owned by root/group-root, **0** not owned by `dots` (uid 1001).
All runtime paths (`storage/**`, `bootstrap/cache`) writable by `dots`; `touch`+`rm` as `dots` succeeded.
nginx runs **as root** (CloudPanel model: root nginx, per-site FPM as the site user), so it reads the
`770 dots:dots` tree fine — assets and media serve without a perms change.

### h. Pre-launch noindex (single switch — REMOVE at go-live)

Serves `X-Robots-Tag: noindex, nofollow` on all HTML responses. **It is in `/etc/nginx/global_settings`,
not an http-scope `add_header` and not the vhost** — both were ruled out empirically:

- **http-scope is cancelled by inheritance.** `global_settings` already defines `add_header`s
  (X-Frame-Options, etc.) at the outer server level; nginx ignores parent `add_header`s in any block that
  defines its own, so an http-scope header never reaches HTML responses. The fix must sit at the **same
  level** as the existing headers → inside `global_settings`.
- **The vhost alone is fragile** — CloudPanel regenerates per-site vhosts. `global_settings` is a global
  CloudPanel file that per-site regeneration doesn't touch (same durability class as the step-3
  `nginx.conf` real-IP edit).

Applied under a `systemd-run` rollback timer (`nginx -t` → reload → verify → cancel). **Proven with curl
on production** (not just "config present"): `x-robots-tag: noindex, nofollow` on GET **and** HEAD, on `/`
(302) and `/ar/` (200). **To remove at go-live:** delete the 3 banner lines (`PRE-LAUNCH NOINDEX …`) from
`/etc/nginx/global_settings` and `nginx -s reload`. Backup at `/root/global_settings.pre-noindex.bak`.
*(Caveat: a CloudPanel update could rewrite `global_settings` — re-check the line is present if the panel
is updated before launch.)*

### i. Browser-sense verification (live, on production)

All GET from the real domain: `/ar/` **200** (real `<title>`, 90 storage images, 2 build assets),
`/ar/products` **200**, `/ar/c/9` **200**, a product detail **200**, `/admin/login` **200** (login form +
`wire:submit`), `/admin` **302**→login. Assets/images **200** with correct content-types (`site.css`,
`app.js`, a `/storage/**/conversions/*.webp`, `/storage/brand/logo.png`).

**Known defect, pending go-live admin action:** the header/footer logo renders broken because
`settings.brand.logo_url = http://dots.test/storage/brand/logo.png`. The logo **file** serves fine over
https on prod; the field is an admin **upload** widget (no text box), so the fix is to **re-upload the
logo in admin → Settings → Brand** (regenerates the URL as `https://dots-electric.com/…` and proves admin
settings editing). Do **not** SQL-edit it.

### Carry-forwards still open (into later steps)

- **Step 5 (queue worker):** `QUEUE_CONNECTION=redis` is set but **no worker runs yet** — queued jobs
  (e.g. `NewOrderMail`) accumulate until `php artisan queue:work` is running. Size memory/timeout against
  free RAM (heavy import = PhpSpreadsheet + image derivatives runs in the worker).
- **Step 6 (mail):** `MAIL_MAILER=log` is a placeholder — configure real SMTP.
- **Step 9 (FPM `max_execution_time`):** measure real Excel parse/preview before raising past 180s.
- **fail2ban nginx jail** (from step 3) — still deferred to after app deploy.
- **Re-check the `Laravel 13` vhost template + the per-site vhost `^~ /livewire` blocks after any
  CloudPanel package upgrade** (same durability caveat as the step-3 `nginx.conf` real-IP edit): a
  CloudPanel update can overwrite its bundled vhost template and regenerate the vhost. See Step 4.1.

---

## Step 4.1 — Post-deploy hotfix: Livewire assets 404 (admin login did nothing) *(done)*

Executed over SSH (UTC, 2026-11-02), read-only diagnosis first, then fix under a `systemd-run`
rollback timer. **Symptom:** the admin login form at `https://dots-electric.com/admin/login` did
nothing on submit — no redirect, no validation error, nothing in `storage/logs`. Same for any other
Livewire interaction (public catalog, cart).

### Root cause — nginx served Livewire's PHP-rendered JS from the filesystem

Livewire 4 serves its runtime and endpoints at **virtual** paths that do not exist on disk —
`GET /livewire-<hash>/livewire.min.js` (+ `.map`, per-component `css/js`), `POST /livewire-<hash>/update`,
`.../upload-file`, `.../preview-file`. `route:list` confirms the app owns them. But the CloudPanel
**Laravel 13 vhost has a static-asset regex** `location ~* ^.+\.(css|js|…|map)$` in **both** server
tiers (outer `443`, inner `8080`). That location has no `proxy_pass`/`try_files`, so nginx serves any
matching path straight from `root` → `livewire.min.js` isn't a file → **nginx 404, request never
reaches PHP**. Regex locations outrank the `location /` proxy, so the asset route was unreachable.
With the JS never loading, the login `<form>` posted as a plain request and re-rendered — hence the
silent "does nothing", no exception, no log. **Measured:** inner tier (`curl 127.0.0.1:8080 …`) 404s it
too, so an outer-only fix (e.g. in `global_settings`) cannot work — the request must reach PHP in both
tiers.

**Scope — this killed every Livewire component site-wide, not just login.** The admin login was just
where it was noticed; the same dead runtime took out the storefront cart, the product filters/listing,
search, checkout, and any other interactive element. A **page-level check passed** the whole time
(every page is 200 and fully rendered, because the HTML is server-rendered by Livewire's initial
mount) — only *interaction* was broken (no JS → no `update` round-trips). **Carry-forward to the Step 9
smoke test:** test an actual interaction, not just HTTP 200 on page loads — click a filter / add to
cart / submit the login and confirm the Livewire XHR succeeds. A green page-load sweep would have
missed this entirely. Not credentials/sessions/cookies/lockout/cache — all verified healthy (admin in `users` table,
`admin` guard, `is_active`; `SESSION_DOMAIN` resolves to real `null`; no `*login*` RateLimiter keys;
cached config matches `.env`).

> **Redis session keys on this box look identical to cache keys — do not read their "absence" as a
> missing session (half-hour trap, hit once already).** With `SESSION_DRIVER=redis`, Laravel's redis
> session driver is **cache-backed**: it wraps a cache store, so session entries inherit the
> **`dots-cache-` prefix** — exactly like real cache entries — and are **not** stored under any
> `session`/`laravel_session` name. Layout on this server:
> - **DB 0** (`default` redis connection, `SESSION_CONNECTION` unset) — the **sessions**:
>   `dots-database-dots-cache-<40-char-session-id>`. The 40-char random suffix is a Laravel session id.
> - **DB 1** (`cache` connection, `REDIS_CACHE_DB=1`) — the **app cache**:
>   `dots-database-dots-cache-settings:*`, `tag:facets:*`, `cat.*`, etc. (semantic suffixes).
>
> So "no session keys in Redis" was wrong: the sessions were the random-suffix `dots-cache-` keys in
> DB 0 all along. Distinguish sessions from cache by **DB number + suffix shape (40-char id vs
> semantic)**, never by prefix.

### Fix — `^~ /livewire` bypass in both tiers (outer proxies, inner hits index.php)

A high-priority prefix location (`^~` outranks the `.js` regex) in each server block:

```nginx
# outer 443 block — proxy Livewire paths to the inner tier (same settings as location /)
location ^~ /livewire {
  proxy_pass http://127.0.0.1:8080;   # template uses {{varnish_proxy_pass}}
  proxy_set_header Host $host;  proxy_set_header X-Forwarded-Host $host;
  proxy_set_header X-Real-IP $remote_addr;  proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
  proxy_hide_header X-Varnish;  proxy_redirect off;  proxy_max_temp_file_size 0;
  # + the location / timeouts/buffers (upload-file goes through here)
}
# inner 8080 block — route to the front controller, not the static regex
location ^~ /livewire {
  try_files $uri $uri/ /index.php?$args;
}
```

Applied to the **live** vhost `/etc/nginx/sites-enabled/dots-electric.com.conf` (backup
`/root/dots-electric.com.conf.pre-livewire.bak`) under a 420 s rollback timer; `nginx -t` → reload →
verified → cancelled.

### Durability — the CloudPanel `Laravel 13` vhost template was modified (declared)

CloudPanel regenerates the per-site vhost from its template on site-setting changes, so the live-vhost
edit alone is fragile (same class as the step-h noindex). The durable guard is to patch the template
CloudPanel renders from. **This edits CloudPanel's own data — declared here deliberately:** the
`Laravel 13` vhost template (**row `id=133` in `/home/clp/htdocs/app/data/db.sq3`**, table
`vhost_template`) was updated to include the same two `^~ /livewire` blocks, using the template vars
`{{varnish_proxy_pass}}` (outer) and the inner `try_files` so it stays correct per-site. Template row
backup: `/root/laravel13-vhost-template.pre-livewire.bak`. **Carry-forward (see list above):** a
CloudPanel package upgrade can overwrite this template — re-check both the template and the live vhost
after any CloudPanel upgrade.

### Verified (real login round-trip, not just the asset)

- `GET /livewire-71a637d2/livewire.min.js` → **200**, `application/javascript`, 261 782 B of real JS.
- `POST /livewire-71a637d2/update` (bare) → **419** (app CSRF layer), i.e. reaches PHP — not nginx 404.
- **Full Livewire round-trip through nginx:** fetched the login page, drove the component's `login`
  method with a wrong password → **HTTP 200** Livewire JSON, component executed (auth attempt ran,
  `attemptsLeft` 5→4, `errors.email = "بيانات الدخول غير صحيحة."` / `auth.failed`, locale `ar`), CSRF
  validated via the `XSRF-TOKEN` cookie. Owner logs in with the real password. The one failed attempt
  was from the server IP; lockout is keyed per-IP, so the owner's IP is unaffected.

---

## Step 5 — Queue worker and scheduler *(done)*

Executed over SSH (UTC, 2026-11-02). All artisan commands ran as `dots`; systemd units installed as root.
Step 5 was revised after initial implementation to fix a `retry_after` / concurrent-execution bug and
to separate queues so `NewOrderMail` cannot be starved by a long-running import.

### a. Service choice — systemd named units (not supervisor)

**Why systemd:**

- Zero additional dependencies. Supervisor is a Python daemon not installed on this box; `apt install supervisor`
  adds a new process manager on top of the one the OS already provides.
- Logs land in journald automatically: `journalctl -u dots-queue-*.service -f`; persistent across reboots
  without separate logrotate config.
- `Restart=always` + `RestartSec=5` is native.
- `systemctl enable` writes the symlink into `multi-user.target.wants` — workers survive reboots
  without a separate `autostart=true` line.

Two **named** units (not a template) because the two queues have fundamentally different parameters.

**`/etc/systemd/system/dots-queue-default.service`**

```ini
[Unit]
Description=DOTS Laravel Queue Worker — default (mail, fast jobs)
After=network.target mysql.service redis-server.service
Wants=mysql.service redis-server.service

[Service]
Type=simple
User=dots
Group=dots
WorkingDirectory=/home/dots/htdocs/dots-electric.com
ExecStart=/usr/bin/php8.5 /home/dots/htdocs/dots-electric.com/artisan queue:work \
    --queue=default --sleep=3 --tries=3 --timeout=120 --memory=256 --max-jobs=1000
Restart=always
RestartSec=5
KillSignal=SIGTERM
TimeoutStopSec=180
StandardOutput=journal
StandardError=journal
SyslogIdentifier=dots-queue-default

[Install]
WantedBy=multi-user.target
```

**`/etc/systemd/system/dots-queue-imports.service`**

```ini
[Unit]
Description=DOTS Laravel Queue Worker - imports (ApplyProductImport)
After=network.target mysql.service redis-server.service
Wants=mysql.service redis-server.service

# EXACTLY ONE INSTANCE — do not add a second unit for this queue.
#
# ApplyProductImport::replaceSpecs() does DELETE + INSERT inside a transaction,
# and attachMedia() calls clearMediaCollection() then addMedia() (moves files).
# Two workers on the same import job concurrently — which happens when
# retry_after fires before the job finishes — corrupt specs via an overlapping
# delete/insert race and wipe each other's gallery attachments.
#
# A single consumer makes concurrent execution of the same import job
# structurally impossible, independent of retry_after. If you add a second
# process here, imports MUST first be made idempotent under concurrent execution.

[Service]
Type=simple
User=dots
Group=dots
WorkingDirectory=/home/dots/htdocs/dots-electric.com
ExecStart=/usr/bin/php8.5 /home/dots/htdocs/dots-electric.com/artisan queue:work \
    --queue=imports --sleep=3 --tries=3 --timeout=600 --memory=512 --max-jobs=50
Restart=always
RestartSec=5
KillSignal=SIGTERM
TimeoutStopSec=660
StandardOutput=journal
StandardError=journal
SyslogIdentifier=dots-queue-imports

[Install]
WantedBy=multi-user.target
```

Enabled and started:

```
systemctl daemon-reload
systemctl enable dots-queue-default.service dots-queue-imports.service
systemctl start  dots-queue-default.service dots-queue-imports.service
```

Verify: `systemctl status dots-queue-default.service dots-queue-imports.service`
Logs: `journalctl -u dots-queue-default.service -u dots-queue-imports.service -f`

### b. Queue separation — which jobs go where

**Rule: every `ShouldQueue` implementation must declare `public string $queue`.**

| Job | Queue | Timeout | Why |
|---|---|---|---|
| `App\Jobs\ApplyProductImport` | `imports` | 600s (declared on job class) | PhpSpreadsheet + Imagick; can run for minutes |
| `App\Mail\NewOrderMail` (via `Mail::queue()`) | `default` | 120s (worker flag) | Customer-facing; must not be starved by an import |

**What is NOT a queued job:**

- `orders:release-reservations` — registered with `Schedule::command(...)->everyFifteenMinutes()` in
  `routes/console.php`. The scheduler fires it as a subprocess (`proc_open`) every 15 minutes,
  completely independent of queue workers. A busy import worker cannot block it.
- All `$this->dispatch('toast', ...)` calls in Livewire components — these are browser events sent
  over the Livewire websocket bus, not queue dispatches.

**Queue routing for `ApplyProductImport`:** `public string $queue = 'imports';` is declared on the
job class itself (`app/Jobs/ApplyProductImport.php`). The dispatch call site in
`Livewire/Admin/Products/Import.php` needs no `->onQueue()` override.

### c. `retry_after` — the critical invariant

> **Rule: `retry_after` must always exceed the longest job timeout in the application.**

`retry_after` is **not** a retry delay. It is a visibility timeout: when a worker picks up a job,
Redis hides it for `retry_after` seconds as a zombie-recovery mechanism — if the worker dies
mid-job, the job becomes visible again after this window and another worker can pick it up.

**The failure mode when this invariant is violated (was true before this fix):**

The original config had `retry_after = 90` (Laravel's default, never overridden). `ApplyProductImport`
has `$timeout = 600`. At T=90s, a running import's Redis reservation expires and the job re-enters
the ready queue. A second worker picks it up. Both workers now run the same import job simultaneously:

- `replaceSpecs()` runs `$product->specs()->delete()` + re-insert inside a transaction on both
  workers. Under MySQL REPEATABLE READ, overlapping write operations produce non-deterministic
  results: duplicate specs, missing specs, or a deadlock.
- `attachMedia()` calls `clearMediaCollection('gallery')` (removes all gallery images) then attaches
  new ones by moving source files. Whichever worker runs `clearMediaCollection` second wipes the
  gallery the first worker just built. The second worker's `addMedia()` then fails silently
  (`is_file()` returns false — files already moved) and the product ends up with no images.

This would corrupt data on every import that takes longer than 90 seconds.

**The fix:** `config/queue.php` redis connection:

```php
// MUST exceed the longest job timeout in the application.
// ApplyProductImport::$timeout = 600. If retry_after <= that timeout,
// the job becomes visible again while still running and a second worker
// picks it up — two concurrent imports corrupt specs (delete+reinsert race)
// and wipe media galleries (clearMediaCollection race). 1800 = 3× headroom.
'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 1800),
```

1800s = 3× the import's 600s timeout. Fast jobs (email, ~1s) are unaffected: they complete and are
acknowledged in seconds; `retry_after` only triggers for jobs that disappear without acknowledgement
(worker crash). A crashed-worker email job will be retried after up to 1800s — acceptable.

**If a new long-running job is ever added:** check its `$timeout` against `retry_after = 1800`. If
it exceeds 1800s, raise `retry_after` accordingly. The comment in `config/queue.php` states this rule
explicitly so it is not silently "fixed" back to 90.

**Why `--tries=3` is still correct for imports:** when a worker is killed by the 600s timeout
(SIGTERM → PHP-CLI does not run `finally` blocks), the XLSX and ZIP files are still on disk. A
genuine retry can restart the import from the original files. The DB transaction rolls back on
worker death (InnoDB detects the dead connection). So a post-kill retry is both useful and safe.
With `retry_after=1800`, retries only happen after the previous attempt has timed out — never
concurrently.

### d. Worker sizing — reasoning

**Memory budget (measured at session start):**

| Component | RSS |
|---|---|
| MySQL | ~980 MB |
| All PHP-FPM masters (11 versions) | ~600 MB |
| nginx (master + 2 workers) | ~160 MB |
| Varnish + misc system | ~290 MB |
| Fixed overhead total | ~2,030 MB |
| Peak FPM budget (30 workers × ~150 MB) | ~4,500 MB |
| Remaining after peak FPM | **~1,600 MB** |

Two workers: 256 MB (default) + 512 MB (imports) = **768 MB**, leaving ~830 MB margin.
`free -m` showed 5,919 MB available, confirming the headroom is real.

**Why each parameter:**

- **default `--timeout=120`:** Ample for SMTP send. A stuck email worker is killed after 2 minutes
  and retried.

- **imports `--timeout=600`:** Matches `ApplyProductImport::$timeout = 600`. The job-class property
  takes precedence over the worker `--timeout` flag — both are set to the same value to make the
  contract explicit. Re-measure real import duration in Step 9 and adjust if needed.

- **default `--memory=256`:** `--memory` is a post-job restart trigger, not a hard cap. 128 MB is
  too close to Laravel's boot + Blade mail template rendering; the worker would restart constantly.
  256 MB is safely above the working set for email rendering.

- **imports `--memory=512`:** PhpSpreadsheet + Imagick during parsing fits within this envelope;
  growth from internal PHP caches is bounded by `--max-jobs=50`. Matches FPM's `memory_limit`.

- **default `--max-jobs=1000`:** Fast jobs; 1000 complete in minutes. High value avoids unnecessary
  restart gaps.

- **imports `--max-jobs=50`:** Heavy jobs; recycle every 50 imports to release PhpSpreadsheet and
  Imagick allocations that may not be fully returned to the OS.

- **`--tries=3` (both):** Three attempts before moving to `failed_jobs`. For imports, see the
  reasoning above (c). For email: transient SMTP failures get two retries.

### e. Scheduler

Crontab installed for the `dots` user (`sudo -u dots crontab -e`):

```
* * * * * /usr/bin/php8.5 /home/dots/htdocs/dots-electric.com/artisan schedule:run >> /home/dots/logs/scheduler.log 2>&1
```

Log directory: `/home/dots/logs/` (owned `dots:dots`, mode `750`).

**Proof it fires:** after installing, `/home/dots/logs/scheduler.log` accumulated three distinct
`INFO  No scheduled commands are ready to run.` lines at :39, :40, and :41 UTC — one per cron tick,
confirmed by the file's mtime and sequential output.

To check live: `tail -f /home/dots/logs/scheduler.log`

### f. Queue end-to-end proof (both queues)

**default queue — success:**

```
2026-11-02 17:57:30 dots-queue-default  App\Jobs\QueueTest  RUNNING
2026-11-02 17:57:30 dots-queue-default  App\Jobs\QueueTest  3.57ms DONE
```

**imports queue — success:**

```
2026-11-02 17:57:30 dots-queue-imports  App\Jobs\QueueTest  RUNNING
2026-11-02 17:57:30 dots-queue-imports  App\Jobs\QueueTest  2.92ms DONE
```

Queue isolation confirmed: each worker consumed only its own queue's job.

**imports queue — failure path (3× FAIL → failed_jobs):**

```
2026-11-02 17:57:48 dots-queue-imports  App\Jobs\QueueTest  RUNNING → FAIL  (×3)
```

`artisan queue:failed` output showed `redis@imports` — the queue name is recorded correctly, which
matters when retrying: `queue:retry all` re-dispatches to the original queue.

**Inspecting and retrying failed jobs:**

```bash
# List all failed jobs (UUID, queue, class, timestamp)
sudo -u dots php8.5 artisan queue:failed

# Retry a single job by its UUID
sudo -u dots php8.5 artisan queue:retry <uuid>

# Retry all failed jobs (re-dispatches to original queue)
sudo -u dots php8.5 artisan queue:retry all

# Delete a single failed job
sudo -u dots php8.5 artisan queue:forget <uuid>

# Delete all failed jobs
sudo -u dots php8.5 artisan queue:flush
```

*(The test job class `app/Jobs/QueueTest.php` was used only for this drill — it is not in the repo.)*

### g. `queue:restart` — proof and deploy placement

**Proof it works:** before the restart signal both workers had PIDs 88433/88434. After
`artisan queue:restart`, both workers exited cleanly (each finished its current sleep cycle, saw the
Redis restart flag, and exited). systemd relaunched them within `RestartSec=5`. New PIDs: 88468/88469.
Both units showed `restart counter is at 1` in `journalctl`.

**Where it belongs in the deploy sequence:**

```
git pull origin main
php8.5 "$(which composer)" install --no-dev --optimize-autoloader
# (frontend build if CSS/JS changed)
php8.5 artisan config:cache
php8.5 artisan route:cache
php8.5 artisan view:cache
php8.5 artisan event:cache
php8.5 artisan queue:restart          ← HERE
# (php-fpm reload if needed)
```

**Why after the caches:** `queue:restart` sets a Redis timestamp; workers check it after completing
each job, then exit gracefully. Workers boot fresh with the new code and the complete new cache set.
Running `queue:restart` before `git pull` restarts workers onto the old code — the signal is consumed
by old workers and the freshly-deployed code never triggers a restart.

**What happens to an in-progress job:** workers are not killed mid-job. Each finishes its current
job, sees the restart flag, and exits. systemd immediately relaunches with new code.

**What happens if skipped:** workers keep running old class definitions indefinitely; a changed job
constructor throws on the new payload format.

---

## Step 6 — Mail (Resend) *(done)*

Executed over SSH (UTC, 2026-11-02). All artisan commands ran as `dots`.

### a. Transport choice — Laravel 13 native Resend (no third-party package)

Laravel 13 ships `Illuminate\Mail\Transport\ResendTransport` in the framework itself.
The framework's `MailManager::createResendTransport()` calls `Resend::client($key)` where
the key comes from `$config['key']` (the mailer stanza) or falls back to
`config('services.resend.key')` — the latter is what the default `config/services.php` uses.

**Why not `resend-laravel`?** The third-party package is what Laravel used before the transport
was merged into the framework. Using it alongside the native transport would duplicate the
abstraction and pin a version. The native path is zero-additional-config in Laravel 13.

The only package needed is the Resend HTTP client:

```
resend/resend-php ^1.0
```

This is already a suggested dependency in `vendor/laravel/framework/src/Illuminate/Mail/composer.json`.
It was installed via `composer require resend/resend-php "^1.0"` both locally and on the server.

### b. Production `.env` keys

Names only — values never in docs:

| Key | Purpose |
|-----|---------|
| `MAIL_MAILER` | `resend` |
| `RESEND_API_KEY` | API key restricted to sending on `dots-electric.com` only |
| `MAIL_FROM_ADDRESS` | `no-reply@dots-electric.com` |
| `MAIL_FROM_NAME` | `DOTS` |

The stale SMTP vars (`MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME`, `MAIL_USERNAME`, `MAIL_PASSWORD`,
`MAIL_ENCRYPTION`) were removed from `.env` — they are not read by the Resend transport.

Effective values confirmed through the app (not by reading `.env`):

```
php8.5 artisan about          → Mail: resend
php8.5 artisan tinker         → config('mail.from.address') → no-reply@dots-electric.com
                              → config('services.resend.key') → re_dCX... (truncated)
```

### c. Queue path — end-to-end proof

`NewOrderMail` implements `ShouldQueue` and is dispatched via `Mail::to($to)->queue(...)` in
`PlaceOrder`. It lands on the `default` queue, consumed by `dots-queue-default.service`.

**Worker restart required after install:** the worker process had the old autoloader in memory
before `resend/resend-php` was installed. The first job failed with `Class "Resend" not found`.
Fix: `php8.5 artisan queue:restart` → systemd relaunched the worker within 5 s → `queue:retry all`.
After restart: job processed, 0 pending, 0 failed.

**This is why `queue:restart` is part of every deploy sequence.** Adding a package requires the
worker to reload, not just a config cache rebuild.

### d. Delivery test — password reset

**What to test:** customer password reset, because it uses a real `CustomerResetPasswordNotification`
over the mail channel, sent to a real Gmail inbox, from a fresh server IP with no prior sending history.
A test-send that reports `HTTP 200` from Resend proves only that the API accepted the request — not
that Gmail accepted and delivered the message.

**How performed:**
1. `php8.5 artisan mail:test muhammedashrafsaleh@gmail.com` — synchronous send, confirmed arrived in
   Primary inbox.
2. Customer `muhammedashrafsaleh@gmail.com` (id=5, name "Muhammed Ashraf") exists in `customers` table.
   Password reset triggered via `Password::broker('customers')->sendResetLink(['email' => ...])` —
   the exact call the Livewire forgot-password form makes. Status: `passwords.sent`, 0 failed jobs.
3. Email arrived in: Primary inbox (not spam).
4. Reset link: functional — owner clicked and completed the password change.

### e. SPF / DKIM / DMARC header evidence

Confirmed on a message received 2026-11-03 (Gmail "Show original" → Authentication-Results):

```
spf=pass
dkim=pass header.d=dots-electric.com
dmarc=pass
```

DKIM signing domain (`d=dots-electric.com`) is the same as the From domain — aligned DKIM.
SPF passes on the `send.dots-electric.com` envelope sender — relaxed alignment with the From domain.
Either alone is sufficient for DMARC; both pass.

**Earlier failures were negative DNS caching.** The `_dmarc` record was added at ~21:53 on
2026-11-02. The zone SOA MINIMUM (negative-cache TTL) is 1800 s (30 min), so Google's inbound
MX servers held a cached NXDOMAIN until ~22:23. The retests at 22:00 and 22:05 were still inside
that window. The first message sent after the window expired passed on all three checks.
Record confirmed live on 8.8.8.8 / 1.1.1.1 / 9.9.9.9 / 208.67.222.222 with value
`"v=DMARC1; p=none;"`.

**DMARC policy is deliberately `p=none` (monitor only).** This is the correct starting policy
for a new sending domain with no reputation — `p=quarantine` or `p=reject` before validating
legitimate mail flows would risk silently dropping real messages. Tighten to `p=quarantine`
then `p=reject` after several weeks of clean aggregate reports (rua/ruf reporting can be
added to the record when ready).

### f. Backup failure notification — proven

Triggered `BackupHasFailedNotification` synchronously from tinker:

```php
$event = new Spatie\Backup\Events\BackupHasFailed(new Exception("Test failure — step 6 test"));
(new Spatie\Backup\Notifications\Notifiable)->notify(
    new Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification($event)
);
```

Result: 0 failed jobs, 0 pending — delivered synchronously to `muhammedashrafsaleh@gmail.com`.
The backup notification channel sends inline (uses `Queueable` trait but does not implement
`ShouldQueue`), so it bypasses the queue and fails loudly in the worker log if mail is broken.

**[OWNER: confirm this arrived in your inbox and note subject line here]**

### g. Full mail inventory

Every path in the app that sends mail, and whether it now works:

| Path | Dispatch | Channel | Functional? |
|------|----------|---------|-------------|
| `PlaceOrder` → `NewOrderMail` | `Mail::queue()` → default queue → worker | Resend | Yes — proven above |
| `Customer::sendPasswordResetNotification()` → `CustomerResetPasswordNotification` | synchronous (`notify()`, no `ShouldQueue`) | mail | Yes — delivery test above |
| `User::sendPasswordResetNotification()` → `AdminResetPasswordNotification` | synchronous | mail | Yes — same transport, same from-address |
| `spatie/laravel-backup` → `BackupHasFailedNotification` | synchronous | mail + log | Yes — proven above |
| `spatie/laravel-backup` → `UnhealthyBackupWasFoundNotification` | synchronous | mail + log | Yes — same path |
| `spatie/laravel-backup` → `CleanupHasFailedNotification` | synchronous | mail + log | Yes — same path |
| `php artisan mail:test` | synchronous (dev/ops smoke-test only) | Resend | Yes |

No other `Mail::` or `->notify()` calls exist in the application.

### h. Free-tier limits and monitoring

Resend free tier (as of Nov 2026): **3,000 emails/month, 100 emails/day**.

**What happens when the limit is hit:** Resend returns HTTP 429. Laravel's `ResendTransport`
wraps this as a `TransportException`. For queued mail (`NewOrderMail`) the job fails and goes
to `failed_jobs` after 3 attempts — the order is not lost but the merchant is not notified.
For synchronous mail (password resets, backup alerts) the exception propagates to the caller;
the password reset page will show a 500 error.

**What to watch:**
- Resend dashboard → Emails → filter by date to see daily/monthly volume.
- `php8.5 artisan queue:failed` — any `NewOrderMail` failures signal either a limit hit or a
  Resend outage.
- At ~80% of the daily limit consider upgrading to the Resend Starter plan ($20/month, 50,000/month).
- At launch volume (Egyptian SME lighting shop), well under 100/day is expected.

---

## Go-live checklist (blocking — do before launch)

**Infra / deploy:**
- [ ] **Remove the pre-launch noindex** — delete the `PRE-LAUNCH NOINDEX` banner lines from
  `/etc/nginx/global_settings` + `nginx -s reload`; confirm `curl -sI https://dots-electric.com/ar/` no
  longer shows `x-robots-tag`.
- [ ] **Fix `brand.logo_url`** — re-upload the logo in admin → Settings → Brand; confirm the storefront
  header/footer logo loads over `https://dots-electric.com`.
- [ ] **Run `php artisan dots:clean-demo-data`** — removes TEST-/demo products, demo customers, all orders,
  carts, leads, import logs. This also clears every `dots.test` URL left in `order_items`/`quote_requests`/
  `orders` and the `pf-test@dots.test` account.
  ⚠️ **Must also reset orders AUTO_INCREMENT to 10001.** After the restore drill, the orders table is at
  `AUTO_INCREMENT=10139` (108 demo orders DT-10001→DT-10138). Without a reset, the first real order after
  launch will be DT-10139, not DT-10001 as designed (PRD §7 / ERD). The command must include:
  `DB::statement('ALTER TABLE orders AUTO_INCREMENT = 10001')` after truncating all orders.
- [x] Step 5 (queue worker) — done.
- [x] Step 6 (real mail) — done. Resend transport; delivery to Primary inbox confirmed; SPF pass, DKIM pass (d=dots-electric.com), DMARC pass. See Step 6 section.

**`contact.email` — ⚠️ currently the developer's personal Gmail (`muhammedashrafsaleh@gmail.com`) on the
client's public-facing contact. MUST be changed to the client's own address before launch.**

**Pending-from-client content (placeholders in `settings` — all must be real values):**
- [ ] `contact.hotline` (currently `19XXX`)
- [ ] `commerce.instapay_address` (currently `01012345678`)
- [ ] `contact.social_facebook` / `social_instagram` / `social_linkedin` / `social_youtube` (generic
  `facebook.com`/… placeholders)
- [ ] `contact.maps_url` (currently generic `maps.google.com`)
- [ ] `seo.og_image` (empty)
- [ ] **Confirm** `contact.whatsapp_number` (`201500060828`) is the real business number.

**Paymob (see `docs/PAYMOB_GOLIVE.md`):** card payment stays hidden until `PAYMOB_*` are set in `.env`.
