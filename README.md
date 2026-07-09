# WealthID

A personal finance tracker for gold-installment ("cicilan emas Pegadaian") investors — tracks monthly
portfolio snapshots (gold, emergency fund, mutual funds, government bonds), daily cashflow, installment
contracts, and progress toward savings targets.

Stack: Laravel 12 (PHP 8.2) + Inertia 2 + Vue 3 (Composition API) + Tailwind + shadcn-vue.

## Features

- **Dashboard** — monthly net worth, breakeven-price progress on the active gold contract, cashflow summary, saving simulator.
- **Catat** — record/edit a month's portfolio snapshot (also available as a quick bottom sheet from any page via the FAB).
- **Grafik** — trend charts across all recorded months.
- **Keuangan** — daily income/expense ledger, per-category budgets, recurring transactions, custom categories.
- **Kontrak Cicilan** — gold installment contract management (with optional supporting document upload).
- **Target** — savings goals vs. current progress.
- **Info** — static reference info (due dates, BEP formula, etc).

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build   # or `npm run dev` for hot-reload during development
php artisan serve
```

`.env.example` is the **local development** template (debug on, sqlite). For a production
deploy, start from `.env.production.example` instead — see below.

### Required environment variables beyond the Laravel defaults

| Variable | Purpose | Default |
|---|---|---|
| `GOLD_PEGADAIAN_MARKUP` | Markup applied to live spot gold price to estimate the Pegadaian retail price | `1.04` |
| `GOLD_FALLBACK_XAU_PRICE` | Fallback USD/oz gold price used if the live price API is unavailable | `3280` |
| `SENTRY_LARAVEL_DSN` | Error monitoring — leave blank to disable (no-op with no DSN) | _(empty)_ |

`GOLD_*` also has a JS-side counterpart in `resources/js/Composables/useFinanceConstants.js`
that must be kept in sync manually with `config/gold.php` — there is no single source of truth
for these yet.

Cicilan/BEP figures are never estimated from a fallback constant — they only ever come from
a user's own active `KontrakCicilanEmas` record (`aktifKontrak`). If a user has no contract,
those sections are hidden rather than guessing.

## Running tests

```bash
php artisan test        # feature/unit tests (sqlite in-memory, see phpunit.xml)
vendor/bin/pint --test  # code style check
vendor/bin/pint         # auto-fix code style
```

CI (`.github/workflows/ci.yml`) runs both on every push/PR to `main`.

## The daily "recurring transactions" job

Active recurring transactions are applied automatically once a day (`recurring:apply`, scheduled
`dailyAt('00:05')` in `routes/console.php`). The "Terapkan Bulan Ini" button in Keuangan is a manual
on-demand trigger for the same idempotent logic (`app/Actions/ApplyRecurringTransactions.php`).

In production the app runs on Render's free web service, which **sleeps after 15 minutes idle** —
`schedule:work` sleeps with the container, so the 00:05 run can be missed. The reliable trigger is
the tokenized webhook `GET /cron/recurring-apply?token=...` (`CRON_TOKEN` env), called daily by an
external cron service (cron-job.org). The command is idempotent, so the webhook and the internal
scheduler can safely fire on the same day.

## Deployment (Render + Neon + Backblaze B2 — all free tiers, no credit card)

The app deploys to [Render](https://render.com) from GitHub via `render.yaml`
(dashboard → New → Blueprint). The free web service sleeps after 15 minutes idle (cold start
30–60s on wake; an optional cron-job.org ping to `/up` every 10 minutes keeps it awake —
Render's 750 free instance hours/month cover one always-on service). The container filesystem
is ephemeral and Render's own free Postgres is deleted after 30 days, so state lives elsewhere:

| Concern | Where | Why |
|---|---|---|
| Database | [Neon](https://neon.tech) free Postgres (`DB_URL`) | Permanent free tier (unlike Render's 30-day expiry); auto-suspends when idle, wakes in ~1s |
| Uploaded contract files | [Backblaze B2](https://www.backblaze.com/cloud-storage) via the `s3` disk (`UPLOADS_DISK=s3`) | Container disk is wiped on every deploy/sleep cycle; B2 gives 10GB free with no card |
| Daily scheduler | GitHub Actions (`.github/workflows/keepalive.yml` + `daily-recurring.yml`) | Keep-alive pings keep `schedule:work` running; a tokenized webhook call is the backup |

For the production env vars, start from `.env.production.example` (already sets `APP_DEBUG=false`,
`SESSION_SECURE_COOKIE=true`, Neon/B2/cron placeholders, etc.) rather than `.env.example`.
`AppServiceProvider` also force-disables debug mode and forces a secure session cookie whenever
`APP_ENV=production`, as a safety net against a misconfigured environment.

### Pre-publish checklist

- **Create the B2 bucket and set `UPLOADS_DISK=s3` + `AWS_*`.** Without it, uploaded contract
  documents silently vanish on the next redeploy or sleep-wake cycle.
- **Add the `CRON_TOKEN` repo secret** (Settings → Secrets and variables → Actions) so the
  `daily-recurring.yml` backup webhook works. Without it, the daily job relies solely on the
  in-container scheduler staying awake via `keepalive.yml`. An external pinger
  ([cron-job.org](https://cron-job.org)) is a sturdier alternative if Actions cron proves flaky.
- **Mail must point at a real SMTP provider.** With `MAIL_MAILER=log` the password-reset flow
  looks like it works but no email is ever sent — see the commented SMTP block in
  `.env.production.example`.
- **Set `SENTRY_LARAVEL_DSN`.** The integration is wired up but no-ops without a DSN, leaving
  production exceptions visible only to someone tailing logs.
- **Verify database backups.** Every deploy runs `php artisan migrate --force` and there is no
  automated backup step — Neon's free tier keeps a limited point-in-time restore window; export
  a manual dump (`pg_dump`) before risky migrations.

## Architecture notes

- Ownership checks on mutable resources (`Portofolio`, `Transaction`, `KontrakCicilanEmas`,
  `RecurringTransaction`, `CustomCategory`) go through Policies (`app/Policies`) via
  `$this->authorize()`, not ad-hoc `abort_if` checks.
- Validation lives in `FormRequest` classes (`app/Http/Requests`), shared between store/update where
  the rules are identical.
- `portofolios` and `transactions` use `SoftDeletes`; `portofolios` also has a
  `unique(user_id, bulan)` constraint — re-`catat`-ing a soft-deleted month restores the original row
  rather than creating a duplicate (see `PortofolioController::store()`).
