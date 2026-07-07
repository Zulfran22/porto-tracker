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
`dailyAt('00:05')` in `routes/console.php`) — this requires the scheduler to actually be running in
production; see the deploy commands below. The "Terapkan Bulan Ini" button in Keuangan is a manual
on-demand trigger for the same idempotent logic (`app/Actions/ApplyRecurringTransactions.php`).

## Deployment

This repo carries **four different deploy configs** left over from evaluating different hosts —
they all now background `php artisan schedule:work` (there's no system cron on these platforms) before
starting the web server, so the daily recurring-transactions job actually runs wherever this is deployed:

| File | Target |
|---|---|
| `railway.json` | Railway (current) |
| `Dockerfile` | Any Docker host, incl. Railway if it prefers the Dockerfile over `railway.json` |
| `Procfile` + `nixpacks.toml` | Northflank (earlier attempt) |

Pick one platform and delete the other configs once you've settled — keeping all four risks them
drifting out of sync.

For a production `.env`, start from `.env.production.example` (already sets `APP_DEBUG=false`,
`SESSION_SECURE_COOKIE=true`, Postgres, etc.) rather than `.env.example`. `AppServiceProvider` also
force-disables debug mode and forces a secure session cookie whenever `APP_ENV=production`, as a
safety net against a misconfigured environment.

Every deploy runs `php artisan migrate --force` — there is no automated backup step; verify your
host's database backup story independently before relying on this in production.

## Architecture notes

- Ownership checks on mutable resources (`Portofolio`, `Transaction`, `KontrakCicilanEmas`,
  `RecurringTransaction`, `CustomCategory`) go through Policies (`app/Policies`) via
  `$this->authorize()`, not ad-hoc `abort_if` checks.
- Validation lives in `FormRequest` classes (`app/Http/Requests`), shared between store/update where
  the rules are identical.
- `portofolios` and `transactions` use `SoftDeletes`; `portofolios` also has a
  `unique(user_id, bulan)` constraint — re-`catat`-ing a soft-deleted month restores the original row
  rather than creating a duplicate (see `PortofolioController::store()`).
