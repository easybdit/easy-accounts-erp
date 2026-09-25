# EasyAccountsERP

A portable, QuickBooks-inspired double-entry accounting core, built on Laravel 13, Inertia.js, and Vue 3. Designed to later be consumed/extended by industry-specific vertical modules (school, corporate office, developer firm, CNF agency, etc.) without modifying the core itself.

The full governing specification — architecture rules, scope decisions, and a phase-by-phase implementation status — lives in [`EasyAccountsERP.md`](EasyAccountsERP.md). Read that first for the "why" behind anything in this codebase.

## Status

The core double-entry engine and every major QuickBooks-equivalent module are implemented and tested: Chart of Accounts, Journal/Ledger/Trial Balance, Customers & Vendors (with Customer & Vendor Statements), Sales (Invoices, Sales Receipts, Estimates, Credit Notes, Recurring Invoicing, Deferred Revenue Recognition, online payment links via SSLCommerz), Purchases (Bills, Purchase Orders, Vendor Credits, Recurring Bills), Expenses (with recurring auto-generation), Banking (Transfers, Reconciliation, Undeposited Funds/Bank Deposits), Inventory, Tax/VAT, Fixed Assets (depreciation + disposal gain/loss), Budgets (Budget vs Actual), Period Lock, configurable per-document-type numbering, Reports (every report exportable to CSV), and Security & Audit (roles, permissions, activity log).

An HR & Payroll module sits alongside the core, built on top of the [`easybdit/laraveleasyattendance`](https://packagist.org/packages/easybdit/laraveleasyattendance) package rather than reimplementing attendance from scratch: Employees, Departments, Designations, Shifts, Holidays, Special Working Days (grade-based pay), biometric device sync (ZKTeco, pull mode by IP, scheduled every 5 minutes), manual/self-service attendance capture with correction requests, Leave (with an optional Department Head approval stage before HR, configurable in HR Settings), Overtime, bulk shift assignment, and Payroll — a configurable component→Chart-of-Accounts mapping (`payroll_components`) turns each generated salary slip into a balanced journal through the same `PostJournal` choke point every other module posts through, with a PDF payslip and full self-service (My Attendance, My Leave, My Payslips, Team Leave Approvals/Attendance for department heads).

The UI is responsive throughout — data tables scroll within their own container instead of breaking the page layout on small screens. See [`CHANGELOG.md`](CHANGELOG.md) for what shipped and when, and `EasyAccountsERP.md` Section 90 for the fuller design rationale behind each decision.

## Stack

* PHP 8.3+ (developed against 8.4), Laravel 13
* Inertia.js 2 + Vue 3 + Tailwind CSS
* MariaDB/MySQL (SQLite for the test suite)
* `spatie/laravel-permission`, `spatie/laravel-activitylog`, `barryvdh/laravel-dompdf` (PDF exports)
* `easybdit/laraveleasyattendance` (HR/attendance/leave/payroll data — see the HR & Payroll paragraph above)

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

## Scheduled Jobs

Several features run on Laravel's scheduler (see `routes/console.php` for the full list and cadence): monthly depreciation, deferred revenue recognition, recurring invoice/expense/bill auto-generation, overdue invoice reminders, low-stock alerts, audit log retention, and attendance device sync (every 5 minutes, pull-mode biometric devices only). In production, point cron at Laravel's scheduler once:

```
* * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
```

Locally, `php artisan schedule:work` runs it in the foreground for testing.

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
