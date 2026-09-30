# Going live

Everything runs with Docker on one small Ubuntu server:

```
Phone app / browser ──https──▶ web (Caddy: automatic HTTPS, React app, security headers)
                                 ├─ /api, /storage, /privacy, /terms, /delete-account, /up ──▶ api (Laravel on FrankenPHP)
                                 │                                                              └─▶ db (PostgreSQL 16)
                                 └─ backup: daily database dump at 02:30, kept 14 days, in /opt/adupangarai/backups
```

## You need
- **A server:** Ubuntu 24.04 with 2 GB+ RAM (for example Hostinger KVM 1, or DigitalOcean in the Bangalore region).
- **A domain:** add **A records** for `@` and `www` that point to the server's IP.
- **An email-sending account (free):** [Brevo](https://www.brevo.com). Go to SMTP & API and create an SMTP key, then verify your domain under Senders & domains.
- **Optional:** a free [Sentry](https://sentry.io) project for error alerts, and a free [UptimeRobot](https://uptimerobot.com) monitor on `https://DOMAIN/up`.

## First time (about 15 minutes)

**1. Set up the server.** Log in as root and run:
```bash
curl -fsSL https://raw.githubusercontent.com/sindhujatamilarasan/Adupangarai/main/deploy/setup-server.sh | bash
```
This installs:
- updates and automatic security updates
- a firewall that only opens ports 22, 80 and 443
- fail2ban, Docker and swap

It also clones the app to `/opt/adupangarai`. If you add an SSH key first, it switches SSH to key-only login.

**2. Fill in the settings.**
```bash
cd /opt/adupangarai
cp .env.example .env
nano .env                  # DOMAIN, and a long random DB_PASSWORD (e.g. from: openssl rand -hex 24)
cp api/.env.production.example api/.env
nano api/.env              # the domain in APP_URL, FRONTEND_URL and CORS_ALLOWED_ORIGINS; mail; AI key; Google client ID
docker compose -f docker-compose.prod.yml run --rm --no-deps --entrypoint php api artisan key:generate --show
nano api/.env              # paste the key into APP_KEY=
```

**3. Start the app.**
```bash
./deploy/deploy.sh --first     # builds, starts, migrates, loads ingredients, recipes and the demo account
curl https://YOUR-DOMAIN/up    # should answer 200
```

**4. Google sign-in.** In Google Cloud → Credentials:
- Open the **Web client** and add `https://YOUR-DOMAIN` under *Authorized JavaScript origins*.
- Add Android clients for the Play signing SHA-1s. See [docs/play-store](../docs/play-store/README.md).

**5. Build the phone app** against the live server, on your laptop:
```bash
cd web && VITE_API_URL=https://YOUR-DOMAIN npm run android:aab   # for the Play Store
```

## Every update
```bash
cd /opt/adupangarai && ./deploy/deploy.sh
```
This pulls the latest code, rebuilds, restarts and runs any new migrations. Add `--seed` to refresh the built-in recipes and the demo account.

## Everyday commands
| What | Command (in `/opt/adupangarai`) |
|---|---|
| Status | `docker compose -f docker-compose.prod.yml ps` |
| Live logs | `docker compose -f docker-compose.prod.yml logs -f api web` |
| App error log | `docker compose -f docker-compose.prod.yml exec api tail -n 100 storage/logs/laravel-$(date +%F).log` |
| Backups | `ls -lh backups/` |
| Restore a backup | `gunzip -c backups/adupangarai-2026-10-01.sql.gz \| docker compose -f docker-compose.prod.yml exec -T db psql -U adupangarai adupangarai` (into an empty database) |
| Laravel console | `docker compose -f docker-compose.prod.yml exec api php artisan tinker` |

**Copy backups off the server.** A dead server takes its backups with it, so also download `backups/` now and then. For example, once a week:
```bash
scp root@SERVER:/opt/adupangarai/backups/*.sql.gz .
```
You can also set up `rclone` to copy them to Google Drive.

## Security that's already in place
- **HTTPS:** automatic certificates, HSTS, security headers (`nosniff`, `DENY` framing, a referrer policy and a permissions policy), and HTTP → HTTPS redirects.
- **Error pages:** `APP_DEBUG=false`, so errors show a plain message and no code or stack traces.
- **Network:**
  - Only the web container is exposed; the API and database are internal.
  - The firewall allows only SSH, HTTP and HTTPS, and fail2ban blocks SSH brute force.
- **Accounts and data:**
  - Passwords are hashed, and login tokens expire after 30 days.
  - A password reset signs out every device.
  - Rate limits apply to login, register, forgot password, account deletion and AI.
  - CORS allows only your domain and the Android app.
- **Uploads:** photos only, up to 5 MB.
