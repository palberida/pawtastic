# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

Laravel 8 (PHP ^7.3|^8.0) order/inventory/accounting back-office app. Blade views + Tailwind, Alpine.js. Asset pipeline has both Laravel Mix (`webpack.mix.js`) and Vite (`vite.config.js`) wired up — `npm run dev`/`build` uses Vite, `npm run watch`/`prod` uses Mix. Database is MySQL (the migrations directory only contains the Laravel default tables; the real schema lives in the production DB and is referenced directly from the models — do not assume migrations are the source of truth).

## Commands

```bash
# PHP deps & app
composer install
php artisan serve                       # local dev server
php artisan migrate                     # only the default Laravel tables; see note above
php artisan tinker

# Frontend (pick one pipeline; both are present)
npm run dev                             # Vite dev server
npm run build                           # Vite production build
npm run watch                           # Laravel Mix watch
npm run prod                            # Laravel Mix production

# Tests (PHPUnit, configured via phpunit.xml)
./vendor/bin/phpunit                            # all suites
./vendor/bin/phpunit --testsuite Unit           # unit only
./vendor/bin/phpunit --testsuite Feature        # feature only
./vendor/bin/phpunit --filter SomeTest          # single test/class

# Per-deploy setup (rewrites APP_CODE into .htaccess + MySessionGuard cookie name)
cd scripts && ./setup.sh

# Operational scripts (run from project root with php)
php scripts/monthly_inventory.php       # cron: snapshots variants → inventory_history
php scripts/fetch_ads.php
scripts/db_backup.sh                    # mysqldump using .env creds → /home/pablo.merida
```

## Architecture

### Auth & roles

Authentication uses a **custom guard** (`App\Guards\MySessionGuard`) registered in `AppServiceProvider::boot()` as the `my_session_guard` driver, and selected as the `web` guard in `config/auth.php`. Its only deviation from `SessionGuard` is `getRecallerName()` returning a per-deploy cookie name (`remember_ossu` / `remember_${APP_CODE}`) so multiple instances of this app can coexist on the same host. **`scripts/setup.sh` rewrites this cookie name from `.env`'s `APP_CODE` at deploy time** — if you rename or refactor `MySessionGuard::getRecallerName`, update `setup.sh`'s `sed` pattern too.

Authorization is role-based via `App\Http\Middleware\CheckRole` (registered as the `role` route middleware in `Kernel.php`). Roles are stored as strings in a `roles.descripcion` column and joined via `user_roles` (pivot: `id_usuario`, `id_rol`). Usage in routes:

```php
Route::middleware(['role:ceo,administrador'])->group(...);
```

The known role names (in Spanish) are `ceo`, `administrador`, `vendedor`, `contador`. There are no Laravel policies — all access control is route-middleware-driven in `routes/web.php`.

### Domain model

Schema and column names are in **Spanish** (e.g. `nombre_cliente`, `descripcion`, `id_orden`, `id_producto`, `vendedor`, `mensajero`). When writing queries/relations, match the existing language rather than translating.

Core entities in `app/Models/`:
- `Order` → `Item` (hasMany, FK `id_orden`) → `Variant` → `Product`
- `Product` → `Variant` (variants of a product) and `ProductCombo` (bundles)
- `Ad`, `AdCost`, `AdProduct` — ad spend tracking, joined to products for ROAS reports
- `Shipment`, `ShipmentCod` — fulfillment / cash-on-delivery
- `Transfer` → `TransferOrder` — inter-warehouse stock moves
- `BankAccount` → `BankStatement` — reconciliation; `Expense`, `Tax` round out accounting
- `InventoryHistory` — daily snapshot table written by `scripts/monthly_inventory.php`

### Controllers & routes

All HTTP entry points are in `routes/web.php` grouped by role. Controllers are fat — `OrderController` and `ProductController` carry index/show/edit/update plus exports, PDF rendering (`barryvdh/laravel-dompdf`), Excel I/O (`phpoffice/phpspreadsheet`), Google Sheets sync (`google/apiclient`), and CSV import flows. The `Report*Controller` family (`ReportProduct`, `ReportAds`, `ReportGeo`, `ReportInventory`, `ReportProfit`) produces dashboard analytics. `AccountingController` and `BankAccountsController` handle reconciliation.

The `routes/web.php` index search pattern (date presets `today`/`yesterday`/`this_week`/`last_week`/`this_month`/`last_month`/`lifetime` driven by `search_fecha` + `search_fecha_inicio`/`search_fecha_fin`) is duplicated across several controllers — keep them consistent if you touch one.

### Global helpers

`app/helpers.php` is autoloaded via composer `files`. Currently exposes `getUsersWithRole($roleId)` and `getUserBatch($sellerCode)` — global functions, callable from anywhere including Blade views.

### Frontend

Views in `resources/views/` are organized by feature (`orders/`, `products/`, `ads/`, `report*/`, `accounting/`, etc.) with shared chrome in `layouts/app.blade.php` + `layouts/navigation.blade.php`. Auth scaffolding is Laravel Breeze (Blade variant, pinned to `1.9.4`). JS is minimal — Alpine.js + Axios bootstrapped in `resources/js/bootstrap.js`.

## Conventions worth knowing

- **Spanish naming** for DB columns, role names, and many controller params — preserve it.
- **No policies/form requests** outside the Breeze auth scaffolding — validation and authorization happen inline in controllers.
- **Migrations are not authoritative** — the schema in `database/migrations/` only covers Laravel's default tables. Real tables (orders, products, variants, items, roles, user_roles, ads, etc.) are managed directly in MySQL.
- **`.env` is required by both Laravel and the standalone scripts** in `scripts/` — they load it via `vlucas/phpdotenv` directly and expect `DB_*` and `APP_CODE` keys.
- **Two asset pipelines coexist** — check which one a given view's assets target before changing build config.

## Metabot (WhatsApp bot subsystem)

A new WhatsApp Cloud API webhook subsystem under construction. Full plan and current phase scope live in `docs/metabot.md` — read it before touching anything under the `Metabot*` / `WhatsApp*` namespace.

- **Entry points:** `GET /webhooks/whatsapp` (Meta's verification handshake) and `POST /webhooks/whatsapp` (incoming events). Both live **outside** every role group — they're unauthenticated from Laravel's perspective.
- **Webhook auth:** Meta's `X-Hub-Signature-256` header, verified against `METABOT_APP_SECRET` over the raw request body. Not session auth, not the `role` middleware. The POST URI must also be added to `App\Http\Middleware\VerifyCsrfToken::$except`.
- **Outbound:** Meta Graph API (`https://graph.facebook.com/v20.0/{phone_number_id}/messages`) via a thin `App\Services\WhatsAppClient`.
- **Config:** new `config/metabot.php` reading `METABOT_*` env keys (`VERIFY_TOKEN`, `APP_SECRET`, `ACCESS_TOKEN`, `PHONE_NUMBER_ID`, `TARGET_AD_ID`).
- **Tables:** new isolated tables prefixed `metabot_` — **deliberate English-naming exception** to the Spanish convention; the bot mirrors WhatsApp's English API fields and is self-contained. Do not extend this exception to other subsystems.
- **Read-only on existing tables:** the bot must never write to products/orders/variants/etc. Phase 1 does not touch them at all; later phases get a read-only DB user.
- **Human-in-the-loop principle:** the bot errs strongly toward silence. A wrong auto-reply is worse than no reply. The bot's only outbound message in phase 1 is the buttons reply on an ad match; everything else is silent.
