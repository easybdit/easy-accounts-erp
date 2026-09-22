# EasyAccountsERP

A portable, QuickBooks-inspired double-entry accounting core, built on Laravel 13, Inertia.js, and Vue 3. Designed to later be consumed/extended by industry-specific vertical modules (school, corporate office, developer firm, CNF agency, etc.) without modifying the core itself.

The full governing specification — architecture rules, scope decisions, and a phase-by-phase implementation status — lives in [`EasyAccountsERP.md`](EasyAccountsERP.md). Read that first for the "why" behind anything in this codebase.

## Status

Phases 1–11 of the roadmap are implemented: Chart of Accounts, Journal/Ledger/Trial Balance, Customers & Vendors, Sales (Invoices & Payments), Purchases (Bills & Vendor Payments), Expenses, Banking (Transfers), Inventory, Tax/VAT, Reports, and Security & Audit (roles, permissions, activity log). See `EasyAccountsERP.md` Section 90 for what's implemented, what's deliberately deferred, and why.

## Stack

* PHP 8.3+ (developed against 8.4), Laravel 13
* Inertia.js 2 + Vue 3 + Tailwind CSS
* MariaDB/MySQL (SQLite for the test suite)
* `spatie/laravel-permission`, `spatie/laravel-activitylog`

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure a MySQL/MariaDB database in `.env` (or leave `DB_CONNECTION=sqlite` for a zero-config local database), then:

```bash
php artisan migrate --seed
npm install
npm run build   # or `npm run dev` during development
php artisan serve
```

### Demo logins (seeded)

| Email | Password | Role |
| --- | --- | --- |
| `test@example.com` | `password` | Administrator (full access) |
| `accountant@example.com` | `password` | Accountant (no Inventory/Security access) |

## Running Tests

```bash
composer test
# or
php artisan test
```

The base `Tests\TestCase` auto-seeds permissions/roles for any test using `RefreshDatabase`, and `UserFactory` defaults every created user to the Administrator role (existing business-logic tests assume an unrestricted actor). Tests that exercise the permission matrix itself override this with `$user->syncRoles([...])`.

## Code Style

```bash
./vendor/bin/pint
```
