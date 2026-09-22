# EasyAccountsERP

## Master Product, Architecture & Development Specification

**Project:** EasyAccountsERP
**Type:** Accounting & Business Management ERP
**Functional Reference:** QuickBooks-style accounting/business-management functionality
**Architecture:** Modular, Portable, Reusable, Non-Multi-Tenant

---

# 1. Product Vision

EasyAccountsERP is a professional accounting and business-management ERP.

The functional scope should be broadly comparable to the core accounting and business-management capabilities commonly found in QuickBooks.

**QuickBooks is a functional reference only.**

EasyAccountsERP must have its own:

* Architecture
* Database design
* Accounting engine
* Business logic
* UI
* Laravel implementation
* Inertia/Vue implementation
* Security model
* Extension architecture

Do not copy QuickBooks source code, database structure, proprietary implementation, exact UI, or internal architecture.

The objective is:

> **QuickBooks-inspired functional coverage with an independent, portable EasyAccountsERP architecture.**

The initial functional target is QuickBooks-style accounting.

The long-term goal is a reusable accounting core that can be deployed, unmodified at the core level, across multiple business verticals (e.g. school/college, corporate office, developer/software firm, CNF/clearing-and-forwarding agency). See Section 89 for the multi-vertical reuse strategy. This is a planning constraint on core architecture decisions, not an instruction to build vertical-specific features now.

---

# 2. Technology Stack

Target stack:

* Laravel 13
* PHP 8.4+
* MySQL 8.0+
* Inertia.js
* Vue 3
* Tailwind CSS
* Vite
* Spatie Laravel packages where justified

The actual installed versions and compatibility must always be inspected before implementation.

Never assume that a dependency or version is available.

---

# 3. Core Principles

The following rules are mandatory.

1. Never guess.
2. Inspect before implementation.
3. Verify before claiming.
4. Do not rewrite working functionality unnecessarily.
5. Use incremental development.
6. Maintain backward compatibility.
7. Accounting correctness has priority.
8. Keep financial calculations authoritative on the backend.
9. Keep modules loosely coupled.
10. Keep the accounting engine reusable.
11. Avoid unnecessary dependencies.
12. Avoid unnecessary abstractions.
13. Do not introduce multi-tenancy.
14. Use Spatie packages where they provide clear value.
15. Test critical accounting behavior.
16. Keep documentation synchronized with implementation.
17. Never claim a feature exists without verifying it.
18. Never claim tests passed without actually running them.

---

# 4. ABSOLUTE NO-GUESSING RULE

This project must be developed from the actual repository.

Never guess:

* Laravel version
* PHP version
* Composer packages
* NPM packages
* Database structure
* Existing models
* Existing controllers
* Existing services
* Existing actions
* Existing routes
* Existing middleware
* Authentication
* Authorization
* Inertia structure
* Vue structure
* Tailwind configuration
* Vite configuration
* Existing UI components
* Existing business rules
* Existing tests

If something cannot be verified, explicitly state:

> **Insufficient information — not verified.**

Do not replace missing information with assumptions.

---

# 5. Phase 0 — Repository Inspection

## IMPORTANT

The first task is inspection only.

Do not implement the ERP during Phase 0.

Do not modify production source code.

Do not perform large refactoring.

Do not install unnecessary packages.

Do not guess.

Inspect:

```text
composer.json
package.json
.env.example
artisan

app/
bootstrap/
config/
database/
resources/
routes/
tests/
```

Inspect:

* Models
* Controllers
* Form Requests
* Policies
* Middleware
* Services
* Actions
* Jobs
* Events
* Listeners
* Migrations
* Seeders
* Factories
* Vue pages
* Vue components
* Layouts
* Tailwind
* Vite
* Authentication
* Authorization
* Tests

Also inspect:

```text
git status
git branch
git log
```

Determine:

1. Current architecture
2. Laravel version
3. PHP version
4. Composer dependencies
5. NPM dependencies
6. MySQL configuration
7. Database schema
8. Existing models
9. Existing controllers
10. Existing services/actions
11. Existing routes
12. Existing middleware
13. Authentication
14. Authorization
15. Inertia structure
16. Vue structure
17. Tailwind setup
18. Vite setup
19. Existing layouts
20. Existing components
21. Existing tests
22. Reusable functionality
23. Missing functionality
24. Potential conflicts
25. Portability risks
26. Security risks
27. Performance concerns
28. Recommended architecture
29. Required dependencies
30. Proposed implementation phases

Then STOP.

Wait for explicit approval.

---

# 6. No Multi-Tenancy

EasyAccountsERP is explicitly:

> **NON-MULTI-TENANT**

Do not introduce:

* `tenant_id`
* Tenant model
* Tenant middleware
* Tenant resolver
* Tenant switching
* Tenant repositories
* Tenant databases
* Multiple tenant connections
* Tenant-specific infrastructure

Do not design the application as SaaS multi-tenancy.

---

# 7. Company / Business Profile

A normal company/business profile may exist.

However:

> **Company ≠ Tenant**

The company/business profile represents the accounting business configuration.

It is not a tenant.

Potential configuration:

* Business Name
* Logo
* Address
* Phone
* Email
* Website
* Currency
* Financial Year
* Tax/VAT Information
* Invoice Settings
* Payment Settings
* Numbering Settings
* Business Type / Industry Profile (see Section 89)

Only implement fields supported by actual requirements.

Business Type is a labeling/configuration value only (e.g. "School", "Corporate Office", "Developer Firm", "CNF Agency", "Generic"). It must never introduce multi-tenancy, alternate schemas, or per-vertical database structures. One deployment remains one business (Section 6).

---

# 8. Portability Requirement

EasyAccountsERP must be designed so the accounting functionality can eventually be moved into another Laravel project.

Avoid unnecessary coupling to:

* Host application controllers
* Host-specific models
* Host-specific services
* Host-specific routes
* Host-specific URLs
* Host-specific business rules
* Host-specific database structures
* Client-specific data
* Client-specific namespaces

The accounting core should remain reusable.

---

# 9. Package-Ready Architecture

The project should be structured so the accounting functionality can eventually be extracted into a reusable Laravel package.

Possible future package:

```text
easybdit/laravel-easyaccounts
```

This is a future architectural possibility, not an instruction to immediately create a package.

First establish clean module boundaries.

Do not prematurely extract the system.

---

# 10. Modular Architecture

Maintain clear boundaries.

## Accounting Core

* Chart of Accounts
* Journals
* Journal Entries
* Debit/Credit
* General Ledger
* Trial Balance
* Accounting Posting

## Business Modules

* Customers
* Vendors
* Sales
* Purchases
* Expenses
* Banking
* Inventory
* Tax

## Application Layer

* Controllers
* Form Requests
* Policies
* Actions
* Services

## Presentation

* Inertia
* Vue
* Tailwind
* Reusable components
* Layouts

## Infrastructure

* Eloquent
* MySQL
* Storage
* PDF
* Notifications
* Mail
* Export

Do not introduce unnecessary DDD or microservice complexity.

---

# 11. QuickBooks Functional Baseline

QuickBooks is the primary functional reference for EasyAccountsERP.

Use it to identify common accounting/business-management capabilities.

Do not assume every QuickBooks feature must be implemented.

For each proposed feature:

1. Identify it.
2. Verify its requirement.
3. Define EasyAccountsERP behavior.
4. Determine dependencies.
5. Place it in an approved phase.
6. Implement only after approval.

QuickBooks is NOT:

* A code dependency
* A database dependency
* An API dependency
* A UI dependency
* An architectural dependency
* A licensing dependency

---

# 12. Core Functional Scope

Initial functional areas:

* Dashboard
* Company/Business Management
* Chart of Accounts
* Double-entry Accounting
* Journal
* General Ledger
* Trial Balance
* Customers
* Vendors
* Estimates
* Invoices
* Sales Receipts
* Credit Notes
* Payments
* Purchase Orders
* Bills
* Vendor Payments
* Expenses
* Banking
* Bank Reconciliation
* Inventory
* Tax/VAT
* Financial Reports
* Accounts Receivable
* Accounts Payable
* Users
* Roles
* Permissions
* Audit Logs
* Recurring Transactions
* Documents
* Notifications
* Class / Job / Cost Center Tracking (see Section 89)
* Billable Expenses (see Section 89)

These represent the planned functional scope.

Implementation must remain phase-based.

---

# 13. Dashboard

Potential dashboard metrics:

* Total Income
* Total Expenses
* Net Profit
* Cash Balance
* Bank Balance
* Accounts Receivable
* Accounts Payable
* Sales Summary
* Expense Summary
* Recent Transactions
* Recent Invoices
* Recent Payments
* Overdue Invoices
* Upcoming Bills
* Revenue Trend
* Expense Trend
* Profit Trend

All dashboard figures must come from actual application data.

Never use fake production statistics.

---

# 14. Chart of Accounts

Support:

* Account Code
* Account Name
* Account Type
* Parent Account
* Child Account
* Active/Inactive
* Opening Balance where appropriate

Core account types:

```text
Assets
Liabilities
Equity
Income
Expenses
```

The final hierarchy must follow the approved accounting design.

---

# 15. Double-Entry Accounting Engine

This is the core of EasyAccountsERP.

Every posted financial transaction must follow double-entry accounting.

Fundamental invariant:

```text
TOTAL DEBIT = TOTAL CREDIT
```

Example:

```text
Invoice
    ↓
Invoice Items
    ↓
Accounting Posting
    ↓
Journal
    ↓
Debit / Credit Entries
```

Payment:

```text
Payment
    ↓
Payment Allocation
    ↓
Accounting Posting
    ↓
Journal
```

Expense:

```text
Expense
    ↓
Accounting Posting
    ↓
Journal
```

Bill:

```text
Bill
    ↓
Accounting Posting
    ↓
Journal
```

---

# 16. Journal

Support:

* Journal
* Journal Entries
* Date
* Reference
* Description
* Debit
* Credit
* Source Document
* Source Transaction

A journal must not be posted when:

```text
Debit != Credit
```

---

# 17. General Ledger

Support:

* Account
* Date
* Reference
* Description
* Debit
* Credit
* Running Balance
* Source Transaction

Ledger entries must be traceable to their source.

---

# 18. Trial Balance

Trial Balance must be generated from actual accounting records.

Support:

* Account
* Debit
* Credit
* Balance
* Appropriate date filtering

Accounting consistency must be automatically testable.

---

# 19. Financial Transaction Atomicity

Financial posting must use database transactions.

Example:

```text
BEGIN TRANSACTION

Create Source Document
Create Source Items
Validate Totals
Create Journal
Create Debit Entries
Create Credit Entries

COMMIT
```

On failure:

```text
ROLLBACK
```

Never leave:

* Invoice without accounting
* Payment without accounting
* Bill without accounting
* Expense without accounting
* Partial journal
* Partial financial posting

---

# 20. Financial Immutability

Posted financial transactions must not be casually deleted.

Use an approved mechanism such as:

* Void
* Reversal
* Adjustment
* Correction Entry

The exact workflow must be defined before implementation.

Do not invent accounting policy.

---

# 21. Money Handling

Financial values must use appropriate decimal database types.

Initial proposed precision:

```text
DECIMAL(19,4)
```

This is a proposal and must be confirmed against the business scope before becoming a fixed rule.

Do not use floating-point arithmetic as the authoritative financial calculation mechanism.

**Implemented (Chart of Accounts, 2026-09-22):** `accounts.opening_balance` uses `DECIMAL(19,4)`, using the proposed default above. This is applied consistently but still pending final business confirmation before later modules (Journal, Invoices) treat it as permanently fixed.

---

# 22. Currency Precision

Currency decimal precision must be verified.

Do not assume every currency uses the same number of decimal places.

The architecture should allow appropriate currency precision where required.

---

# 23. Rounding

Proposed default:

```text
Half-up rounding
```

Rounding rules must be explicitly verified before becoming a system-wide accounting rule.

Backend calculations are authoritative.

Frontend calculations are for UI feedback only.

---

# 24. Financial Year

Do not hard-code a country-specific financial year.

The financial year start should be configurable.

For example:

```text
Financial Year Start Month
```

must be stored/configured according to the business requirement.

Do not assume Bangladesh July unless explicitly approved.

---

# 25. Period Lock

Period locking should be treated as a controlled accounting feature.

Before implementation, confirm whether it is:

* Required in the current phase
* Required later
* Not required

Do not implement complex period locking without an approved accounting workflow.

---

# 26. Customers

Features:

* Customer CRUD
* Contact Information
* Billing Information
* Opening Balance where appropriate
* Current Balance
* Transaction History
* Customer Statement
* Payment History
* Accounts Receivable

---

# 27. Vendors

Features:

* Vendor CRUD
* Contact Information
* Billing Information
* Opening Balance where appropriate
* Payable Balance
* Transaction History
* Vendor Statement
* Payment History
* Accounts Payable

---

# 28. Sales

Potential features:

* Estimates / Quotations
* Invoices
* Sales Receipts
* Credit Notes
* Customer Payments
* Recurring Invoices

Invoice fields may include:

* Invoice Number
* Customer
* Invoice Date
* Due Date
* Items
* Quantity
* Unit Price
* Discount
* Tax
* Subtotal
* Total
* Paid
* Due
* Status

Backend validation is mandatory.

---

# 29. Purchases

Potential features:

* Purchase Orders
* Bills
* Vendor Payments
* Purchase Returns
* Vendor Credits

Bill fields may include:

* Vendor
* Bill Number
* Bill Date
* Due Date
* Items
* Quantity
* Unit Price
* Discount
* Tax
* Total
* Paid
* Due
* Status

---

# 30. Expenses

Features:

* Expense Categories
* Expense Entry
* Payee
* Payment Account
* Date
* Amount
* Tax
* Reference
* Notes
* Attachments where appropriate
* Recurring Expenses

Expenses must integrate correctly with accounting.

---

# 31. Banking

Features:

* Bank Accounts
* Cash Accounts
* Deposits
* Withdrawals
* Transfers
* Bank Transactions
* Reconciliation

Do not maintain financial balances by arbitrary manual modification.

Maintain transaction history.

---

# 32. Inventory

Features:

* Products
* Services
* SKU
* Categories
* Units
* Purchase Price
* Selling Price
* Tax
* Stock Quantity
* Stock Adjustment
* Stock Movement
* Low Stock Threshold
* Inventory Valuation

Every stock movement must be traceable.

---

# 33. Tax / VAT

Create a flexible tax architecture.

Potential features:

* Tax Rates
* Tax Types
* Tax Inclusive
* Tax Exclusive
* Taxable Transactions
* Non-taxable Transactions
* Tax Calculation
* Tax Reports

Future Bangladesh VAT/Mushak support should be possible.

Do not implement jurisdiction-specific rules without verified requirements.

---

# 34. Payments

Features:

* Receive Payment
* Make Payment
* Partial Payment
* Payment Allocation
* Payment Methods
* Payment Reference
* Payment Date
* Payment History

Payment allocation must remain traceable.

---

# 35. Reports

Create a dedicated Reports section.

Potential reports:

```text
Profit & Loss
Balance Sheet
Cash Flow
Trial Balance
General Ledger
Account Statement

Accounts Receivable Aging
Accounts Payable Aging

Sales Report
Purchase Report
Expense Report

Customer Balance
Vendor Balance

Payment Report
Tax Report

Inventory Report
Stock Movement Report
```

Reports should support appropriate filters.

Large reports should use server-side processing.

---

# 36. Users, Roles & Permissions

Use **Spatie Laravel Permission** where justified.

Preferred package:

```text
spatie/laravel-permission
```

Before installing:

1. Inspect existing authorization.
2. Verify Laravel/PHP compatibility.
3. Confirm the package is actually needed.
4. Avoid duplicate authorization systems.

Potential roles:

```text
Administrator
Accountant
Sales
Purchase
Inventory
Manager
Viewer
```

These are examples only.

Do not create roles blindly.

Potential permissions:

```text
dashboard.view

accounts.view
accounts.create
accounts.update
accounts.delete

journal.view
journal.create
journal.post
journal.reverse

customers.view
customers.create
customers.update
customers.delete

vendors.view
vendors.create
vendors.update
vendors.delete

invoices.view
invoices.create
invoices.update
invoices.post
invoices.void

payments.view
payments.create
payments.update
payments.reverse

reports.view
reports.export

settings.view
settings.update

users.view
users.create
users.update
users.delete

roles.view
roles.create
roles.update
roles.delete
```

Final permissions must match actual modules.

Backend authorization is authoritative.

---

# 37. Audit Logging

Evaluate:

```text
spatie/laravel-activitylog
```

Before installing:

* Inspect existing audit functionality.
* Verify compatibility.
* Confirm actual requirement.
* Avoid duplicate audit systems.

Important actions may include:

* Financial posting
* Financial reversal
* Invoice changes
* Payment changes
* Account changes
* Permission changes
* User actions

Do not unnecessarily store sensitive information.

---

# 38. Spatie Package Policy

Use Spatie packages only when they solve a verified requirement.

Potential packages:

```text
spatie/laravel-permission
spatie/laravel-activitylog
```

Do not install every available Spatie package.

Before adding any dependency:

1. Inspect current dependencies.
2. Check Laravel compatibility.
3. Check PHP compatibility.
4. Check whether Laravel already provides the feature.
5. Check whether existing code already provides it.
6. Check maintenance status.
7. Check portability impact.
8. Add only if justified.

---

# 39. Authentication

Authentication should integrate with the host Laravel application.

Do not unnecessarily build a separate authentication system.

If authentication is missing, inspect the project and select an appropriate Laravel-compatible solution.

Never install authentication packages blindly.

---

# 40. Authorization

Authorization must be enforced server-side.

Do not rely on:

* Hidden buttons
* Vue route restrictions
* Frontend permission checks

Frontend checks are only for user experience.

Backend authorization remains authoritative.

---

# 41. AdminLTE-Style UI

The entire application must use one consistent AdminLTE-style administration interface.

Required shell:

```text
Sidebar
Navbar
Breadcrumb
Page Header
Content Area
Footer
```

Use Tailwind CSS for implementation.

Do not introduce another CSS framework unless inspection proves it is necessary.

---

# 42. Sidebar

Proposed navigation:

```text
Dashboard

Accounting
    Chart of Accounts
    Journal
    General Ledger
    Trial Balance

Sales
    Customers
    Estimates
    Invoices
    Payments
    Credit Notes

Purchases
    Vendors
    Purchase Orders
    Bills
    Payments

Expenses

Banking
    Bank Accounts
    Transactions
    Reconciliation

Inventory
    Products
    Categories
    Stock
    Stock Movements

Tax / VAT

Reports

Users
Roles
Permissions

Audit Logs

Settings
```

This is a proposed navigation structure.

Verify existing routes before implementation.

---

# 43. Reusable Vue Components

Create reusable components where appropriate.

Examples:

```text
AppLayout
Sidebar
Navbar
Breadcrumb
PageHeader

DataTable
SearchInput
FilterPanel
Pagination

FormInput
SelectInput
DateInput
CurrencyInput

MoneyDisplay
StatusBadge

Modal
ConfirmDialog
Toast

LoadingState
EmptyState
ErrorState
```

Do not create duplicate components with the same responsibility.

---

# 44. Inertia + Vue Architecture

Use Inertia for internal application pages.

Laravel handles:

* Business rules
* Validation
* Authorization
* Financial calculations
* Accounting posting
* Database operations

Vue handles:

* Presentation
* Interactive forms
* Dynamic line items
* Filters
* Tables
* Modals
* UI state

Business rules must not exist exclusively in Vue.

---

# 45. Frontend Financial Calculations

Vue may calculate values for immediate UI feedback.

Example:

```text
Quantity × Unit Price
Subtotal
Discount
Tax
Total
```

The backend must recalculate and validate the final values.

Frontend calculations are never authoritative.

---

# 46. Validation

Use Laravel validation/Form Requests where appropriate.

Validate:

* Amounts
* Dates
* Customers
* Vendors
* Accounts
* Taxes
* Quantities
* Invoice items
* Bill items
* Payment allocations
* Duplicate document numbers
* Financial relationships

---

# 47. Database Transactions

Use database transactions for financial operations.

Examples:

* Invoice posting
* Bill posting
* Payment posting
* Expense posting
* Bank transfer
* Inventory accounting transaction

Failure must rollback the complete logical operation.

---

# 48. Database Indexing

Use appropriate indexes for:

* Foreign keys
* Document numbers
* Dates
* Status
* Account references
* Customer references
* Vendor references
* Searchable fields
* Frequently filtered fields

Do not add indexes blindly.

Review actual query patterns.

---

# 49. MySQL Requirement

Production target:

```text
MySQL 8.0+
```

Before implementation verify:

* Installed version
* Character set
* Collation
* SQL mode
* Timezone configuration
* Storage engine
* Connection configuration

Avoid MySQL-specific behavior unless justified.

## Verified (Local Dev Environment, 2026-09-22)

* Installed engine: **MariaDB 10.4.32** (via XAMPP), not MySQL 8.0+.
* Character set: `utf8mb4`. Collation: `utf8mb4_general_ci`. SQL mode: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`.
* Database: `laravel_13_maindb` (was empty at the time of this decision).

**Confirmed decision:** proceed with MariaDB 10.4 for local development. Avoid MySQL-8-exclusive features (e.g. `utf8mb4_0900_ai_ci` collation, MySQL-8-only JSON functions) until the actual production MySQL version is confirmed. This is a recorded deviation from the production target, not an assumption — re-verify against the real production server before go-live.

---

# 50. Document Numbering

Document numbering must be configurable.

Potential format:

```text
INV-2026-0001
```

Possible document types:

```text
INV
EST
BILL
PO
PAY
EXP
CN
```

The exact format must be confirmed.

Document numbers must have appropriate uniqueness constraints.

Do not hard-code one numbering scheme unnecessarily.

---

# 51. Documents

Potential documents:

* Invoice
* Estimate
* Sales Receipt
* Payment Receipt
* Customer Statement
* Vendor Statement
* Purchase Order
* Bill

Support where appropriate:

* Print
* PDF
* Company Branding
* Logo
* Numbering

PDF implementation should remain replaceable.

---

# 52. Attachments

Attachment storage must use Laravel's filesystem abstraction.

Possible storage:

```text
Local
S3-compatible storage
```

Do not tightly couple business logic to one storage provider.

Before implementation confirm:

* Maximum file size
* Allowed file types
* Storage location
* Retention requirements
* Access permissions

---

# 53. Backup Strategy

Backup must be designed for the actual production database.

For MySQL, a possible implementation may use:

```text
mysqldump
```

But backup architecture should not unnecessarily couple the application to one command.

Verify:

* Backup frequency
* Retention
* Storage destination
* Encryption requirements
* Restore testing
* Scheduled execution
* Failure notifications

A backup is not considered reliable until restoration has been tested.

---

# 54. Performance

Avoid:

* N+1 queries
* Unnecessary eager loading
* Duplicate calculations
* Huge controllers
* Huge Vue components
* Unnecessary API calls
* Unnecessary global state

Use:

* Pagination
* Server-side filtering
* Proper indexes
* Appropriate eager loading
* Query optimization

Do not optimize blindly.

---

# 55. Security

Follow Laravel security best practices.

Ensure:

* Authentication
* Authorization
* CSRF protection
* Validation
* Mass-assignment protection
* Secure file handling
* Permission enforcement
* Financial transaction protection
* Audit logging

Never trust frontend authorization.

---

# 56. No Hard-Coded Environment Values

Do not hard-code:

* URLs
* Domains
* Database credentials
* Company names
* Email addresses
* File paths
* API keys
* Secrets

Use Laravel configuration and environment variables.

---

# 57. No Client-Specific Logic

EasyAccountsERP must not contain:

* Client-specific names
* Client-specific domains
* Client-specific logos
* Client-specific business rules
* Client-specific accounting assumptions
* Client-specific database assumptions

Use generic demo data.

---

# 58. API Strategy

Do not build a REST API merely because it may be useful later.

The initial application may operate through Laravel + Inertia.

Keep the architecture API-friendly.

Potential future API areas:

* Customers
* Vendors
* Products
* Invoices
* Bills
* Payments
* Accounting transactions
* Reports

Implement only after approval.

---

# 59. Export Strategy

Potential future formats:

* CSV
* Excel
* PDF
* Print

Do not implement every export format prematurely.

Keep export mechanisms modular.

---

# 60. Automation

Potential future features:

* Recurring invoices
* Recurring expenses
* Payment reminders
* Overdue notifications
* Scheduled reports
* Low-stock notifications

Do not implement prematurely.

---

# 61. Settings

Potential settings:

```text
Company
Accounting
Currency
Tax
Invoice
Payments
Inventory
Notifications
Users
Roles
Permissions
Numbering
System
```

Only implement settings backed by actual requirements.

---

# 62. Seeders & Factories

Create realistic demo data when appropriate.

Potential demo data:

* Business profile
* Chart of Accounts
* Customers
* Vendors
* Products
* Invoices
* Bills
* Expenses
* Payments
* Bank accounts
* Journal entries

All accounting demo data must remain balanced.

---

# 63. Testing

Testing is mandatory.

## Accounting

Test:

* Account creation
* Account hierarchy
* Journal creation
* Debit/Credit validation
* Ledger
* Trial Balance

## Sales

Test:

* Customer creation
* Invoice creation
* Invoice calculations
* Invoice posting
* Payment
* Payment allocation

## Purchases

Test:

* Vendor creation
* Bill creation
* Bill posting
* Vendor payment

## Expenses

Test:

* Expense creation
* Expense posting

## Banking

Test:

* Bank account
* Transaction
* Transfer
* Reconciliation

## Inventory

Test:

* Product
* Stock movement
* Stock adjustment

## Security

Test:

* Authentication
* Authorization
* Permissions
* Validation

## Audit

Test:

* Audit log creation

---

# 64. Accounting Invariant Tests

Create explicit automated tests enforcing:

```text
SUM(DEBIT) = SUM(CREDIT)
```

for every posted journal.

Also test rollback.

Example:

```text
If accounting posting fails:

Source transaction must not remain partially posted.
```

---

# 65. Regression Testing

After every meaningful implementation increment run:

* Relevant feature tests
* Unit tests
* Regression tests
* Migration tests where applicable
* Frontend build
* Static analysis/code quality checks where available

Do not skip tests because a change appears small.

---

# 66. UI States

Major pages should support:

## Loading

Clearly indicate loading.

## Empty

Explain when no records exist.

## Error

Provide useful error information.

Never leave unexplained blank screens.

---

# 67. Search & Filtering

Large datasets should support appropriate:

* Search
* Date filters
* Status filters
* Customer filters
* Vendor filters
* Account filters
* Category filters

Use server-side filtering for large datasets.

---

# 68. Reporting Principle

Reports must use authoritative accounting data.

Avoid duplicated manual totals.

Financial reports should derive from the accounting records and approved source transactions.

---

# 69. Future Extensibility

Potential future modules:

* Bangladesh VAT/Mushak
* Payroll
* Fixed Assets
* Budgeting
* Cost Centers
* Projects
* Multi-currency
* Bank Import
* Payment Gateway Integration
* External APIs
* Webhooks
* Business Analytics
* Vertical Packs: School/College, Developer/Software Firm, CNF/Clearing & Forwarding Agency (see Section 89)

Do not implement future functionality unless explicitly approved.

Do not create unnecessary complexity for hypothetical requirements.

---

# 70. Code Quality

Follow Laravel conventions.

Prefer:

* Thin controllers
* Form Requests
* Policies
* Actions/Services where justified
* Reusable Vue components
* Clear relationships
* Small methods
* Meaningful names
* Explicit business rules

Avoid:

* Giant controllers
* Giant Vue pages
* Duplicated business logic
* Magic values
* Hidden financial calculations
* Unnecessary abstractions

---

# 71. Git Safety

Before changes:

```text
git status
git branch
git log
```

Understand the repository state.

Never:

* Reset user changes
* Delete uncommitted work
* Force push
* Rewrite history

unless explicitly authorized.

---

# 72. Development Workflow

Follow this sequence:

```text
Inspect
   ↓
Verify
   ↓
Report findings
   ↓
Identify gaps
   ↓
Propose smallest safe increment
   ↓
Wait for approval
   ↓
Implement
   ↓
Test
   ↓
Security review
   ↓
Accounting review
   ↓
Performance review
   ↓
Portability review
   ↓
Regression test
   ↓
Document
   ↓
Next approved increment
```

---

# 73. Small-Increment Rule

Never implement an entire module as one giant change.

Example:

```text
Chart of Accounts
    ↓
Tests
    ↓
Journal
    ↓
Tests
    ↓
Journal Entries
    ↓
Tests
    ↓
Posting Engine
    ↓
Tests
    ↓
Ledger
    ↓
Tests
    ↓
Trial Balance
    ↓
Regression
```

Every increment should be:

* Small
* Testable
* Reviewable
* Safe
* Reversible where practical

---

# 74. Architecture Review After Each Increment

Review:

## Accounting

Is the accounting behavior correct?

## Security

Are permissions enforced?

## Database

Is data integrity preserved?

## Performance

Are queries reasonable?

## Portability

Did unnecessary coupling appear?

## UI

Is the interface consistent?

## Regression

Did existing functionality break?

---

# 75. Portability Audit

At major milestones ask:

> Could EasyAccountsERP be moved into another Laravel 13 project without rewriting the core accounting engine?

Check for:

* Hard-coded namespaces
* Hard-coded URLs
* Hard-coded business names
* Host-specific models
* Host-specific services
* Host-specific tables
* Host-specific authentication assumptions
* Host-specific notification assumptions
* Unnecessary dependencies

Reduce unnecessary coupling.

---

# 76. Package Extraction Readiness

Before considering the architecture mature, verify that these areas could reasonably be separated:

```text
Accounting Models
Accounting Migrations
Accounting Services
Accounting Actions
Accounting Policies
Accounting Events
Accounting Reports
Accounting Configuration
Accounting Vue Components
Accounting Inertia Pages
```

Do not extract them into a package unless explicitly approved.

---

# 77. Error Handling

Never silently ignore errors.

Use:

* Validation errors
* Exceptions
* Database rollback
* Logging
* User-friendly error messages

Financial errors must remain visible and traceable.

---

# 78. Inventory Integrity

Never allow:

```text
Stock changed
without a stock movement.
```

Every inventory change must have an identifiable source.

If inventory affects accounting, the accounting transaction must also be traceable.

---

# 79. Payment Integrity

Never allow:

```text
Payment marked paid
without corresponding payment/accounting records.
```

The exact relationship must follow the approved data model.

---

# 80. Invoice Integrity

Never allow:

```text
Invoice total != accounting posting
```

The backend must validate the complete invoice before posting.

---

# 81. Balance Integrity

Never allow manually inconsistent:

* Customer balance
* Vendor balance
* Bank balance
* Account balance
* Inventory balance

unless a specific opening-balance or adjustment mechanism exists.

Balances should be based on authoritative transactions.

---

# 82. Documentation Rules

Documentation must represent actual implementation.

Never document:

* Future functionality as completed
* Untested behavior
* Hypothetical APIs as existing
* Unverified accounting rules

Use clear status labels when appropriate:

```text
Implemented
Planned
Not Implemented
```

---

# 83. Phase Roadmap

## Phase 0 — Inspection

Repository inspection only.

No production source changes.

---

## Phase 1 — Foundation

After approval:

* AdminLTE-style layout
* Sidebar
* Navbar
* Breadcrumb
* Dashboard foundation
* Company/business foundation
* Reusable UI components

---

## Phase 2 — Accounting Core

Implement incrementally:

```text
Chart of Accounts
    ↓
Journal
    ↓
Journal Entries
    ↓
Double-entry Engine
    ↓
General Ledger
    ↓
Trial Balance
```

---

## Phase 3 — Customers & Vendors

Implement:

* Customers
* Vendors
* Balances
* Statements
* Transaction history

---

## Phase 4 — Sales

Implement:

* Estimates
* Invoices
* Invoice Items
* Invoice Posting
* Customer Payments
* Payment Allocation
* Credit Notes

---

## Phase 5 — Purchases

Implement:

* Purchase Orders
* Bills
* Bill Items
* Bill Posting
* Vendor Payments

---

## Phase 6 — Expenses

Implement:

* Expense Categories
* Expenses
* Expense Posting
* Recurring Expense foundation if approved

---

## Phase 7 — Banking

Implement:

* Bank Accounts
* Cash Accounts
* Transactions
* Transfers
* Reconciliation

---

## Phase 8 — Inventory

Implement:

* Products
* Categories
* Stock
* Stock Movements
* Adjustments
* Inventory Accounting

---

## Phase 9 — Tax / VAT

Implement:

* Tax Rates
* Tax Calculation
* Tax Reporting foundation

---

## Phase 10 — Reports

Implement:

* Profit & Loss
* Balance Sheet
* Cash Flow
* Trial Balance
* General Ledger
* Account Statement
* AR Aging
* AP Aging
* Sales
* Purchases
* Expenses
* Payments
* Tax
* Inventory

---

## Phase 11 — Security & Audit

Implement:

* Spatie Roles
* Spatie Permissions
* Accounting Permissions
* Audit Logs
* Financial Action Tracking

---

## Phase 12 — Finalization

After approval:

* Recurring transactions
* Notifications
* Scheduled reports
* Export
* Documentation
* Portability audit
* Security audit
* Performance audit
* Full regression testing

---

## Phase 13+ — Industry Vertical Modules (Deferred, Out of Current Scope)

Confirmed decision: Phases 1–12 build the complete, industry-agnostic **Accounts Module** (Chart of Accounts through Reports, per Section 10's Core + Business Modules, i.e. the full QuickBooks-style baseline).

Industry verticals (School/College, Corporate Office, Developer/Software Firm, CNF/Clearing & Forwarding Agency) are **separate modules built afterward**, each consuming/extending the Accounts Module rather than modifying it. See Section 89.

Do not begin any vertical module until the Accounts Module (Phases 1–12) is complete, tested, and approved.

---

# 84. Phase 0 / Architecture Review Checklist

Before implementation, verify the following against the actual repository, deployment requirements and approved business rules.

| #  | Item                                                         | Check |
| -- | ------------------------------------------------------------ | ----- |
| 1  | Laravel 13 and required PHP version actually available?      | [ ]   |
| 2  | Current Composer dependencies inspected?                     | [ ]   |
| 3  | Current NPM dependencies inspected?                          | [ ]   |
| 4  | MySQL 8.0+ installed and verified?                           | [ ]   |
| 5  | MySQL character set and collation verified?                  | [ ]   |
| 6  | MySQL SQL mode verified?                                     | [ ]   |
| 7  | Money precision (`DECIMAL(19,4)` or another value) approved? | [ ]   |
| 8  | Currency decimal precision requirements verified?            | [ ]   |
| 9  | Rounding strategy verified and approved?                     | [ ]   |
| 10 | Financial year start configurable and approved?              | [ ]   |
| 11 | Period-lock requirement confirmed?                           | [ ]   |
| 12 | `brick/math` requirement verified before installation?       | [ ]   |
| 13 | Document numbering format verified?                          | [ ]   |
| 14 | Document numbering uniqueness rules verified?                | [ ]   |
| 15 | Backup strategy defined for MySQL?                           | [ ]   |
| 16 | Backup restoration process tested?                           | [ ]   |
| 17 | Attachment storage strategy verified?                        | [ ]   |
| 18 | Local/S3 storage abstraction preserved?                      | [ ]   |
| 19 | Authentication architecture inspected?                       | [ ]   |
| 20 | Authorization architecture inspected?                        | [ ]   |
| 21 | Spatie Permission requirement verified?                      | [ ]   |
| 22 | Spatie Activity Log requirement verified?                    | [ ]   |
| 23 | Existing audit mechanism checked?                            | [ ]   |
| 24 | Existing accounting tables/models inspected?                 | [ ]   |
| 25 | Existing routes/controllers/services inspected?              | [ ]   |
| 26 | Existing Inertia/Vue/Tailwind architecture inspected?        | [ ]   |
| 27 | No `tenant_id` / multi-tenancy introduced?                   | [ ]   |
| 28 | No client-specific business logic introduced?                | [ ]   |
| 29 | Accounting core remains portable?                            | [ ]   |
| 30 | Financial posting uses database transactions?                | [ ]   |
| 31 | Debit/Credit equality enforced and tested?                   | [ ]   |
| 32 | Backend is authoritative for financial calculations?         | [ ]   |
| 33 | Posted transaction reversal/void strategy verified?          | [ ]   |
| 34 | Inventory changes are traceable?                             | [ ]   |
| 35 | Payment allocation is traceable?                             | [ ]   |
| 36 | Critical accounting workflows have automated tests?          | [ ]   |
| 37 | Database indexes reviewed?                                   | [ ]   |
| 38 | No unnecessary package/dependency added?                     | [ ]   |
| 39 | Git working tree checked before modification?                | [ ]   |
| 40 | Full Phase 0 inspection report completed?                    | [ ]   |
| 41 | Phase 0 findings approved before implementation?             | [ ]   |

### Verification Rule

No item may be marked verified based on assumption.

If information is unavailable:

> **Insufficient information — not verified.**

Business decisions such as:

* Money precision
* Currency precision
* Rounding method
* Financial year
* Period locking
* Document numbering
* Backup policy
* Attachment storage

must be explicitly confirmed before becoming fixed system rules.

---

# 85. Required Claude Code Behavior

When working on EasyAccountsERP:

## First

Inspect.

## Second

Verify.

## Third

Report findings.

## Fourth

Identify gaps.

## Fifth

Propose the smallest safe increment.

## Sixth

Wait for approval.

## Seventh

Implement.

## Eighth

Run tests.

## Ninth

Review:

* Accounting
* Security
* Database
* Performance
* Portability
* Regression

## Tenth

Document the actual result.

Then continue only after approval.

---

# 86. Final First Command for Claude Code

Use this as the first instruction after placing this file in the repository:

```text
READ THE EasyAccountsERP.md SPECIFICATION COMPLETELY.

PHASE 0 ONLY.

INSPECT THE REPOSITORY.

DO NOT IMPLEMENT ANY FEATURE YET.

DO NOT MODIFY PRODUCTION SOURCE CODE.

DO NOT INSTALL UNNECESSARY PACKAGES.

DO NOT GUESS.

Inspect the actual:

- Laravel version
- PHP version
- Composer dependencies
- NPM dependencies
- MySQL configuration
- Database schema
- Models
- Controllers
- Form Requests
- Policies
- Middleware
- Services
- Actions
- Routes
- Authentication
- Authorization
- Inertia
- Vue
- Tailwind
- Vite
- Layouts
- Components
- Migrations
- Seeders
- Factories
- Tests
- Git state

Also identify existing reusable functionality,
conflicts, security risks, performance concerns
and portability risks.

Compare the actual repository against the
EasyAccountsERP specification.

Produce a detailed Phase 0 inspection report.

For anything that cannot be verified, explicitly write:

"Insufficient information — not verified."

Do not silently assume anything.

Do not implement missing features during Phase 0.

At the end of the report, provide:

1. What already exists
2. What is missing
3. What can be reused
4. What conflicts with the specification
5. Required dependencies
6. Recommended architecture
7. Recommended implementation order
8. Risks
9. Open decisions requiring confirmation

Then STOP.

WAIT FOR EXPLICIT APPROVAL BEFORE MAKING ANY PRODUCTION CHANGE.
```

---

# 87. Final Architecture Statement

EasyAccountsERP is:

```text
QuickBooks-style Functional Baseline
              +
Independent Accounting Engine
              +
Laravel 13
              +
Inertia.js
              +
Vue 3
              +
Tailwind CSS
              +
AdminLTE-style UI
              +
Spatie Permission / Activity Log where justified
              +
MySQL 8.0+
              +
Double-entry Accounting
              +
Portable Modular Architecture
              +
No Multi-tenancy
              +
No Client-specific Coupling
              +
Test-first Incremental Development
```

The central objective is:

> **EasyAccountsERP must be a professional, independent, portable accounting ERP whose core accounting functionality can later be reused or extracted into another Laravel application without rewriting the accounting engine.**

The central development rule is:

> **Inspect first. Verify everything. Never guess. Implement the smallest safe increment. Test it. Review it. Then continue.**

---

# 88. Definition of Done

A feature is considered complete only when:

* Requirement is verified.
* Architecture is approved.
* Implementation is complete.
* Validation exists.
* Authorization exists where required.
* Database integrity is preserved.
* Accounting integrity is preserved where applicable.
* Automated tests pass.
* Regression tests pass.
* UI states are handled.
* Performance is acceptable.
* Documentation is updated.
* Portability is preserved.
* No unnecessary dependency was introduced.

A feature is **not complete merely because the UI exists**.

---

# 89. Multi-Vertical Reuse Strategy (Planned)

## Status

This section is a **planning constraint**, not an implementation instruction.

Nothing in this section may be implemented outside its approved phase.

## Goal

The accounting core, once built for the QuickBooks-style baseline, must be reusable — without rewriting the core — across multiple business verticals, including but not limited to:

* School / College (education)
* Corporate Office (general business/services)
* Developer / Software Firm (project and client billing)
* CNF / Clearing & Forwarding Agency (shipment/job billing)
* Generic / Other

One deployment still represents one business (Section 6, Section 7). Multi-vertical means the **codebase** is reusable across separate deployments/projects, not that one deployment serves multiple businesses.

## Core vs. Vertical Pack

```text
Core (industry-agnostic, never modified per vertical)
    Chart of Accounts
    Journal / Ledger / Trial Balance
    Customers, Vendors
    Invoices, Bills, Payments, Credit Notes
    Products/Services
    Reports

Vertical Pack (optional, additive, built per deployment/phase)
    Terminology/label overrides
    Extra domain fields (via separate linked tables, not core schema changes)
    Extra workflows/pages
    Extra seed/demo data
```

A vertical pack must only **consume** core APIs/models (Customer, Invoice, Journal, etc.). It must never fork, duplicate, or bypass core accounting logic.

## Terminology / Label Mapping

Core entity names (Customer, Vendor, Invoice, Bill) remain fixed in code and database.

Displayed labels may be overridden per Business Type via configuration only, for example:

| Core Entity | School/College | CNF Agency | Developer Firm | Corporate Office |
| ----------- | --------------- | ---------- | --------------- | ------------------ |
| Customer    | Student/Guardian | Importer/Exporter | Client | Customer |
| Invoice     | Fee Bill | Job Invoice | Milestone/Retainer Invoice | Invoice |
| Vendor      | Vendor | Shipping Line/C&F Agent/Transport | Vendor/Contractor | Vendor |

This table is illustrative. Exact label sets must be approved before implementation.

## Class / Job / Cost Center Tracking

Identified as the single most important reusability feature across the listed verticals (equivalent to QuickBooks "Class"/"Job" tracking).

Purpose: tag journal entries, invoices, and bills with an optional dimension so revenue/expense can be segmented without separate ledgers.

Vertical usage examples:

* CNF Agency: per-shipment/per-job profit tracking
* Developer Firm: per-project/per-client profit tracking
* School/College: per-department/per-session budget tracking
* Corporate Office: per-cost-center expense tracking

Architectural note for Phase 2 (Accounting Core): journal entries and source documents should reserve an optional, nullable tracking-dimension reference (e.g. `class_id` / `cost_center_id`) at schema design time, even if the feature itself is implemented later. Retrofitting this dimension after core tables are finalized is significantly more costly than reserving it now.

This is a schema-design consideration for Phase 2, not an instruction to build the feature in Phase 2.

## Billable / Reimbursable Expenses

Purpose: allow a vendor bill or expense to be marked as billable to a specific customer (and optionally a job/class), then pulled onto that customer's next invoice.

Primarily relevant to:

* CNF Agency: pass-through freight/customs/port charges billed to the importer/exporter
* Developer Firm: reimbursable travel, third-party licenses, subcontractor costs billed to the client

This must reuse the core Expense/Bill and Invoice models; it is an additional flag/relationship, not a parallel billing system.

## What Must NOT Happen

* No vertical-specific columns added directly to core tables (`students`, `shipments`, `projects` are separate, optional, linked tables — never merged into `customers` or `invoices`).
* No vertical-specific business logic inside core Services/Actions.
* No multi-tenancy introduced to support "multiple verticals" (Section 6 still applies).
* No vertical pack implemented without explicit approval and its own phase.

## Sequencing

Vertical packs are **out of scope** until the QuickBooks-style core (Phases 1–12) is complete, tested, and portability-audited (Section 75). Section 89 exists to inform core schema/architecture decisions now so that vertical reuse remains possible later without rewriting the core.

**Confirmed:** the current build target is the full Accounts Module only (Phases 1–12, Section 83). Industry modules (School/College, Corporate Office, Developer Firm, CNF Agency) will be built later as separate modules that inherit/consume the Accounts Module. See Phase 13+ in Section 83.

---

# 90. Implementation Status

Per Section 82 (Documentation Rules), status is tracked here as work proceeds. Only actually-built and actually-tested items may be marked Implemented.

## Environment (Verified 2026-09-22)

* Laravel: 13.0.0 (verified via `php artisan --version`)
* PHP: 8.4.16 installed (verified via `php -v`); `composer.json` currently constrains `^8.3` — should be widened/confirmed to match, not yet done.
* Database: MariaDB 10.4.32, not MySQL 8.0+ — see Section 49 deviation note.
* Frontend: Inertia.js 2.0, Vue 3.4, Tailwind 3.2, Vite 7 (Laravel Breeze scaffold).

## Phase 1 — Foundation: Partially Implemented

* Implemented: `AppLayout.vue` (Sidebar + Navbar + Breadcrumb + Page Header + Footer, Tailwind, mobile-responsive), applied to Dashboard, Profile, and Chart of Accounts pages.
* Implemented: Reusable `Sidebar`, `Navbar`, `Breadcrumb`, `PageHeader` components (Section 43).
* Not implemented: full sidebar navigation for modules beyond Chart of Accounts (added incrementally as each module is built, to avoid dead links per Section 42).

## Phase 2 — Accounting Core: Chart of Accounts Implemented; Journal/Ledger/Trial Balance Not Started

Implemented (Chart of Accounts):

* Migration: `accounts` table (`code`, `name`, `type` enum, `parent_id` self-referential FK with `restrictOnDelete`, `opening_balance` `DECIMAL(19,4)`, `is_active`).
* Model: `App\Models\Accounting\Account` (namespaced for future package extraction per Section 76), with `normalBalance()` helper and a `saving` guard preventing a child account type from mismatching its parent.
* Validation: `StoreAccountRequest` / `UpdateAccountRequest` — unique code, valid type, parent type must match, no self/descendant as parent (cycle prevention).
* Controller + routes: full CRUD at `accounting.accounts.*` (`routes/accounting.php`), behind `auth`+`verified` middleware. Fine-grained permission checks (`accounts.view`/`create`/`update`/`delete`) are deferred to Phase 11 (Spatie Permission not yet installed — installing it now for one module would violate Section 38's "add only if justified").
* Seeder: `ChartOfAccountsSeeder` — minimal default 5-type chart (Assets/Liabilities/Equity/Income/Expenses + common children), idempotent, opening balances zero (trivially balanced per Section 62).
* UI: Inertia/Vue pages — Index (search + type filter + pagination + delete confirmation modal), Create, Edit, shared `AccountForm` partial.
* Tests: 12 feature/unit tests covering CRUD, validation, type-matching rule, cycle prevention, delete-with-children protection, and seeder correctness. Full regression suite (38 tests) passes.

Implemented (Journal, Journal Entries, Double-entry Posting Engine):

* Migrations: `journals` (date, reference, description, `posted_at`, nullable polymorphic `source` for future Invoice/Bill/Payment traceability, `created_by`) and `journal_entries` (`account_id`, `debit`/`credit` as `DECIMAL(19,4)`, line `description`).
* Models: `Journal` (with `totalDebit()`/`totalCredit()`/`isBalanced()` using bcmath, never float, per Section 21) and `JournalEntry`.
* Shared posting engine: `App\Actions\Accounting\PostJournal` — the single entry point every financial transaction (manual journal now; invoices/bills/payments/expenses later per Section 15) must post through. Enforces `SUM(debit) = SUM(credit)` before writing anything, wraps creation in `DB::transaction` (Section 19), and is verified to leave zero rows on any failure (Section 64 invariant tests).
* Validation (`StoreJournalRequest`): minimum 2 lines, each line exactly one of debit/credit (not both, not neither), all referenced accounts must exist and be active, total debit = total credit via bcmath, journal cannot be entirely zero.
* Routes/UI: `accounting.journals.index|create|store|show` only — **no edit/update/destroy routes are registered**, since Section 20 (Financial Immutability) requires an approved void/reversal/adjustment workflow before posted transactions can be changed, and that workflow has not been defined/approved yet. This is a deliberate omission, not an oversight.
* `AccountController@destroy` now also blocks deleting an account that has journal entries (Section 81 Balance Integrity).
* Tests: 12 new tests (validation rules, atomicity/rollback on both a pre-check rejection and a mid-transaction DB failure, route-non-existence assertions for edit/update/destroy). Full suite: 52 tests passing.

Implemented (General Ledger, Trial Balance):

* `journal_entries.date` added (denormalized copy of the parent journal's date, per Section 17's "Ledger entries carry a Date" and Section 54 performance — avoids joining `journals` on every report query). Populated by `PostJournal` at posting time.
* `Account::netMovement()` / `Account::balanceAsOf()` — bcmath-based, DB-aggregated (no N+1) helpers shared by both reports.
* General Ledger (`accounting.ledger.index`): pick an account, optional date range; shows a per-line running balance computed server-side (starting balance = opening balance + all prior movement before the range, correctly signed per the account's normal balance side), each line traceable to its source journal (Section 17).
* Trial Balance (`accounting.trial-balance.index`): all active accounts as of a given date, each shown in its natural Debit or Credit column (opening balance + period movement, split by normal side); grand totals with an automatic **Balanced/Not Balanced** indicator (Section 18: "accounting consistency must be automatically testable").
* Tests: 7 new tests covering running-balance arithmetic, date-range starting-balance correctness, trial balance staying balanced after posting, as-of filtering, and opening-balance placement on the correct side. Full suite: 60 tests passing.

Not implemented yet: none of the remaining Phase 2 items — Chart of Accounts, Journal, Posting Engine, General Ledger, and Trial Balance are now all implemented. Phase 2 is functionally complete pending real-world review. Void/Reversal workflow for posted journals remains an open decision (Section 20, Section 84 item 33) before Phase 3 (Customers & Vendors) needs it.

## Phase 3 — Customers & Vendors: Implemented

* Migrations: `customers` and `vendors` (name, email, phone, address, billing_address, `opening_balance` `DECIMAL(19,4)`, is_active). `journal_entries` gained optional `customer_id`/`vendor_id` (nullable, `restrictOnDelete`) so any journal line can tag a subsidiary-ledger party — a line cannot be tagged to both (Section 26/27: Transaction History without needing Invoices/Bills yet).
* Models: `App\Models\Contacts\Customer` / `Vendor` (kept out of the `Accounting` namespace per Section 10's "Business Modules" vs "Accounting Core" split, for portability — Section 76). Each has `currentBalance()`: opening balance + tagged entries, debit-normal for customers (amount owed to the business) and credit-normal for vendors (amount owed by the business).
* CRUD: full create/edit/delete for both, with a delete guard when a party has transaction history (mirrors the Account guard).
* Show page doubles as **Customer/Vendor Statement + Transaction History**: opening balance, running balance per tagged journal line, each line traceable to its source journal.
* The manual Journal Entry form (`accounting.journals.create`) gained an optional per-line "Customer/Vendor" tag, so this data is usable today, not just scaffolding for a future Invoices module.
* Demo data (Section 62 — realistic, generic, not client-specific): `CustomerSeeder` (3 customers), `VendorSeeder` (3 vendors), and `DemoTransactionsSeeder`, which posts two real balanced journals through `PostJournal` — a sale on account tagged to "Acme Traders" and an expense on account tagged to "Global Supplies Co" — so `currentBalance()`, the Statement page, and the Trial Balance are all populated and verifiably correct out of the box, not empty tables. Idempotent (safe to re-run).
* Tests: 18 new tests (CRUD, validation, delete guards, balance arithmetic on both party types, the customer-and-vendor-on-one-line rejection, and a dedicated seeder test asserting the demo journals are balanced and the demo balances are exactly right). Full suite: 79 tests passing.

Not implemented: Payment History (needs the Payments module, Phase 4/5) — the Statement page currently shows all tagged journal-entry activity, which is the only transaction type that exists so far.

## Phase 4 — Sales: Invoices Implemented (Estimates, Payments, Credit Notes not yet)

* Migrations: `invoices` (invoice_number unique, `customer_id`, `receivable_account_id`, invoice_date, due_date, status `draft`/`posted`, subtotal/discount_total/total as `DECIMAL(19,4)`, notes, posted_at) and `invoice_items` (account_id = the line's income account, quantity/unit_price/discount/line_total).
* Models: `App\Models\Sales\Invoice` / `InvoiceItem` (Business Modules namespace, Section 10/76). `Invoice::journal()` is a `morphOne` reusing `Journal`'s existing `source_type`/`source_id` columns — no separate `journal_id` column needed.
* **Bug fixed while wiring this up:** `Journal::$fillable` was missing `source_type`/`source_id`, so every prior "traceable to source" claim (Section 16/17/89/90) was silently a no-op — mass-assignment dropped those columns and they were always `NULL`. Fixed; verified with a fresh reseed that `Invoice::journal` now resolves correctly.
* Draft/Posted lifecycle (Section 20 Financial Immutability): `SaveInvoiceDraft` creates/updates a draft and always recomputes subtotal/discount/total from the submitted items server-side via bcmath (Section 45 — frontend total is feedback only). `PostInvoice` recomputes the total fresh from the DB (never trusts a cached value), then posts one balanced journal through the same `PostJournal` engine — debit the invoice's receivable account (tagged to the customer), credit each line's income account — and only then flips status to `posted`. A posted invoice cannot be edited or deleted (`403`); no void/reversal yet, same open decision as Journals (Section 20).
* Validation: receivable account must be an active asset account; every line's account must be an active income account; a line's discount cannot exceed its own line amount; an invoice needs ≥1 item and a positive total to post.
* Numbering (Section 50): default `INV-{year}-{4-digit sequence}`, generated server-side, enforced unique at the DB level. Documented as a starting default, not a confirmed fixed policy — see Section 84 item 13.
* UI: Invoice list (search/status filter), Create/Edit (dynamic line items, live client-side total preview only), Show (items, totals, link to the posted journal, Post/Edit/Delete actions gated by status).
* Demo data: `InvoiceSeeder` creates and **posts** a real 2-line invoice for "Rahman Enterprise" (idempotent), so the Invoices list, the posted journal, and the customer's current balance are all populated and correct out of the box.
* Tests: 10 new tests (computed totals, both account-type validations, discount-exceeds-line validation, draft editing, posting producing a balanced customer-tagged journal, immutability after posting, draft deletion, posting-without-items rejection). Full suite: 89 tests passing.

Not implemented yet: Estimates, Credit Notes (remaining Phase 4 items).

## Phase 4 — Sales: Customer Payments & Payment Allocation Implemented

* Migrations: `payments` (payment_number unique, customer_id, deposit_account_id, payment_date, reference, method, amount `DECIMAL(19,4)`, notes) and `payment_allocations` (payment_id, invoice_id, amount).
* Model: `App\Models\Sales\Payment` / `PaymentAllocation`. `Payment::journal()` reuses the same `source_type`/`source_id` `morphOne` pattern as `Invoice::journal()`.
* `Invoice::amountPaid()` / `amountDue()` / `isFullyPaid()` are always computed live from real `PaymentAllocation` rows — never a separately-editable stored column — so an invoice can structurally never be "marked paid" without a corresponding payment/accounting record (Section 79 Payment Integrity).
* Posting model: unlike Invoices, a Payment has no draft state — like a manual Journal Entry, creating it **is** posting it, atomically, through the same shared `PostJournal` engine: debit the deposit account (Cash/Bank) for the full amount, credit each allocated invoice's own `receivable_account_id` (tagged to the customer) for its allocated share. No edit/update/destroy routes (same immutability stance as Journals, Section 20).
* **Every dollar received must be allocated to an open invoice at creation time** — there is no "unapplied/on-account payment" concept yet, since that GL treatment (which account holds unapplied cash) is a business-policy decision nobody has confirmed (Section 20/84). Documented as a scope limit, not an oversight.
* Validation (defense-in-depth in both `StorePaymentRequest` and `ReceivePayment`): allocated total must equal the payment amount exactly (bcmath); every allocated invoice must be `posted` and belong to the paying customer; an allocation cannot exceed that invoice's current `amountDue()`; the same invoice cannot be allocated twice in one payment; the deposit account must be an active asset account.
* **Bug fixed while wiring this up:** `PostInvoice` computed the invoice's total for the journal but never wrote it back onto the `invoices` row — an invoice created with a stale/inconsistent stored total (which cannot happen via the normal Create→Post UI flow, but is exactly the kind of drift Section 80 warns about) would keep the wrong `total` forever. `PostInvoice` now recomputes and persists `subtotal`/`discount_total`/`total` from the actual items at the moment of posting, so the stored total is self-healing and always matches what was posted, not just "whatever `SaveInvoiceDraft` last cached."
* UI: Payments list, "Receive Payment" form (pick customer → shows that customer's open invoices with remaining due, check to allocate, live remaining-unallocated indicator), Payment show page (allocations, link to the posted journal). Invoice show page now displays Paid/Due and a Payment History table.
* Demo data: `PaymentSeeder` posts a real partial payment (half the demo invoice's total) via Bank, so a partial-payment scenario — not just "fully paid" — is visible and verifiable out of the box.
* Tests: 11 new tests (full payment, partial payment, allocation-total mismatch, over-allocation, wrong customer, draft invoice, wrong account type, journal correctness, two-partial-payments-fully-pay, and an action-level atomicity test). Full suite: 100 tests passing.

Not implemented yet: Estimates, Credit Notes, unapplied/on-account payments.

## Phase 5 — Purchases: Bills & Vendor Payments Implemented (Purchase Orders, Purchase Returns not yet)

Structural mirror of Phase 4's Sales module, in `App\Models\Purchases`:

* Migrations: `bills`/`bill_items` (mirrors `invoices`/`invoice_items`, but `payable_account_id` must be a **liability** account and each item's account must be an **expense** account — the reverse of Invoices) and `vendor_payments`/`vendor_payment_allocations` (mirrors `payments`/`payment_allocations`).
* Actions: `SaveBillDraft` (mirrors `SaveInvoiceDraft`), `PostBill` (mirrors `PostInvoice` — debits each item's expense account, credits the bill's payable account tagged to the vendor, self-healing total per Section 80), `MakePayment` (mirrors `ReceivePayment` — debits each allocated bill's payable account tagged to the vendor, credits the payment account). Same draft/posted immutability rule as Invoices (Section 20); vendor payments are immediately posted on creation, same as customer Payments — no edit/update/destroy routes for either.
* `Bill::amountPaid()`/`amountDue()`/`isFullyPaid()` mirror `Invoice`'s, always computed live from real `VendorPaymentAllocation` rows (Section 79). Full allocation required at payment creation time — no "on account" vendor payment yet, same documented scope limit as customer Payments.
* Numbering: `BILL-{year}-{seq}` and `VPAY-{year}-{seq}` (kept visually distinct from customer `PAY-{year}-{seq}` on purpose, both are implementation defaults pending confirmation, Section 50).
* Vendor's existing `currentBalance()`/subsidiary ledger (built in Phase 3) needed no changes — Bill and Vendor Payment posting both just use the `vendor_id` tagging column on `journal_entries` that already existed.
* UI: Bills list/Create/Edit/Show (mirrors Invoices exactly, vendor/payable/expense in place of customer/receivable/income), Vendor Payments list/Create ("apply to bills" picker with live remaining-unallocated indicator)/Show. Vendor show page's existing statement already surfaces this activity automatically (no changes needed there either, same subsidiary-ledger reuse).
* Demo data: `BillSeeder` posts a real 2-line bill for "City Hardware"; `VendorPaymentSeeder` pays half of it in Cash — so both a payable balance and a partial vendor payment are visible and verifiable out of the box, on both sides of the books.
* Tests: 18 new tests (9 Bill, 9 VendorPayment) closely mirroring the Invoice/Payment test suites. Full suite: 118 tests passing.

Not implemented yet: Purchase Orders, Purchase Returns, Vendor Credits (remaining Phase 5 items); Estimates and Credit Notes (remaining Phase 4 items).

## Phase 6 — Expenses: Expense Categories & Expense Entry Implemented (Tax, Attachments, Recurring not yet)

* Migrations: `expense_categories` (name unique, optional `default_account_id` for UI convenience only) and `expenses` (expense_category_id, `account_id` = the actual GL expense account posted to — authoritative, not the category's default; `payment_account_id`; optional `vendor_id`; required free-text `payee`; date; amount `DECIMAL(19,4)`; reference; notes).
* Model: `App\Models\Expenses\Expense` / `ExpenseCategory`.
* Like a manual Journal Entry or a Payment, recording an expense **is** posting it (Section 15's diagram shows `Expense -> Accounting Posting -> Journal` directly, with no "Expense Items" step, unlike Invoice/Bill) — `RecordExpense` debits the expense account, credits the payment account, atomically, through the same `PostJournal` engine. No edit/update/destroy routes.
* **Deliberate design decision, not an oversight:** `Expense.vendor_id` is optional and purely informational (e.g. "show all expenses paid to vendor X" via a query on the `expenses` table). It is intentionally **not** tagged onto the posted journal lines, because `Vendor::currentBalance()` is strictly an Accounts Payable subsidiary ledger fed by Bills/Vendor Payments — a cash expense isn't a payable movement, and tagging it there would silently corrupt that balance. A dedicated test (`expense can optionally be linked to a vendor without affecting vendor balance`) locks this in.
* Validation: expense account must be an active expense-type account; payment account must be an active asset account.
* Numbering: `EXP-{year}-{seq}` (Section 50).
* UI: Expense Categories (full CRUD, delete blocked if expenses reference it — mirrors the Account/Customer/Vendor delete-guard pattern), Expenses list/Create (category selection pre-fills its default account as a convenience; selecting a vendor pre-fills the payee name)/Show.
* Demo data: `ExpenseCategorySeeder` (4 categories) and `ExpenseSeeder` — a cash expense with **no** vendor link (a one-off payee), deliberately distinct from the vendor-linked Bill/Vendor Payment demo data already seeded, so both paths are visible.
* Tests: 12 new tests (5 category, 7 expense, including the vendor-balance-isolation test). Full suite: 130 tests passing.

Not implemented yet: Tax on expenses, Attachments, Recurring Expenses (all explicitly deferred per Section 30/52/60 — no confirmed policy or storage decision yet, not guessed).

## Phase 7 — Banking: Transfers & Bank Accounts Overview Implemented (Reconciliation not yet)

* Schema addition: `accounts.is_bank_account` (boolean, default false) — distinguishes actual Cash/Bank accounts from other asset-type accounts (Accounts Receivable, Inventory, ...), which the Chart of Accounts had no way to express before. Only settable when `type = 'asset'` (enforced in `StoreAccountRequest`/`UpdateAccountRequest`); exposed as a checkbox on the Account form, shown only for asset accounts. Seeded COA marks Cash (1001) and Bank (1002) as bank accounts; Accounts Receivable and Inventory are not.
* Migration: `transfers` (transfer_number unique, from_account_id, to_account_id, date, amount `DECIMAL(19,4)`, reference, notes).
* Model: `App\Models\Banking\Transfer`. Like a manual Journal Entry, Payment, Expense, etc., recording a transfer **is** posting it immediately — `RecordTransfer` debits the destination account, credits the source account, through the existing `PostJournal` engine. No edit/update/destroy routes.
* Validation: both accounts must be active asset accounts; source and destination must differ.
* "Bank Accounts" overview (`banking.accounts.index`) deliberately does **not** duplicate the General Ledger — it lists only `is_bank_account = true` asset accounts with their current balance, and links each one to the existing per-account General Ledger view (built in Phase 2) for transaction history, satisfying Section 31's "Bank Transactions" register requirement through reuse rather than a second implementation.
* Demo data: `TransferSeeder` moves 500 from Cash to Bank. Also **fixed the demo data's realism**, not correctness: the Chart of Accounts seeder now gives Cash a starting balance of 2000, offset by an equal increase to Owner's Equity (so the books still balance) — previously Cash drifted to a negative balance once the Payment/Expense/Transfer demo seeders had all drawn against a zero starting balance. This surfaced a **latent weakness in `ChartOfAccountsSeederTest`**: its "opening balances are balanced" check was `SUM(opening_balance) == 0`, which only happened to hold in the trivial all-zero case and doesn't express the actual accounting equation. Fixed the test to check `SUM(asset+expense opening balances) == SUM(liability+equity+income opening balances)`, which holds for a real funded starting position and would have caught the wrong condition if the seeder had actually been unbalanced.
* Tests: 8 new tests (transfer posting/balances, same-account rejection, wrong-account-type rejection, correct post-transfer balances, bank-account-flag filtering and validation). Full suite: 138 tests passing.

Not implemented: Reconciliation — deliberately deferred, same reasoning as Void/Reversal (Section 20): matching against a bank statement needs a defined workflow (does marking an entry "cleared" lock it? how are prior reconciled periods protected? is there a saved statement-balance history?) that hasn't been confirmed, so it isn't guessed at. `journal_entries` has no `reconciled_at` column yet — that's a real schema decision for whenever this is picked up, not an oversight.

## Phase 8 — Inventory: Products, Categories, Stock Movements & Adjustments Implemented (Invoice/Bill line-item integration not yet)

* Migrations: `product_categories` (simple lookup), `products` (sku unique, category, `type` enum `inventory`/`service`, unit, purchase/selling price, `income_account_id` always required, `cogs_account_id`/`inventory_account_id` required only for `type = 'inventory'`, optional low-stock threshold), `stock_movements` (product_id, date, signed quantity, reason, reference, notes).
* Models: `App\Models\Inventory\Product` / `ProductCategory` / `StockMovement`. `Product::currentStock()` is always computed live from `StockMovement` rows (Section 32: every stock movement must be traceable) — never a stored, independently-editable quantity column, same discipline as `Invoice`/`Bill`'s `amountPaid()`.
* Valuation is deliberately simple: `stockValue()` = quantity on hand × current purchase price. This is **not** FIFO/LIFO/weighted-average cost layering — that is a materially larger feature and wasn't attempted; documented as a scope limit.
* Two distinct movement reasons, two different accounting treatments:
  - `'opening'` (set at product creation): treated exactly like Account/Customer/Vendor opening balances — an assumed starting position, **not** posted to accounting.
  - `'adjustment'` (via `AdjustStock`, the "Stock Adjustment" screen): the user enters the physically-**counted** quantity, not a delta; the system computes the difference and, if it has a non-zero value at the current purchase price, posts a real balanced journal through the existing `PostJournal` engine — an increase debits the product's inventory (asset) account and credits its COGS account (a "found" cost reduction); a decrease is the reverse (shrinkage becomes a cost).
* Validation: only `type = 'inventory'` products can have their accounts required or their stock adjusted; `income_account_id` must be an income account; `cogs_account_id`/`inventory_account_id` must be expense/asset accounts respectively when required.
* UI: Categories (full CRUD, delete blocked if products reference it), Products (full CRUD; Show page doubles as an inventory card — current stock, stock value, low-stock flag, full movement history with running balance, mirroring the Customer/Vendor Statement pattern), Stock Movements (list, filterable by product; the adjustment form shows the current system quantity next to the counted-quantity input).
* **Two real bugs found and fixed while building this, both the same class of mistake:** `Product::currentStock()`, and — audited and found in `Invoice::amountPaid()` and `Bill::amountPaid()` too — returned a raw `->sum()` query result cast directly to string, instead of passing it through `bcadd(..., '0', 4)` like every other balance method in the codebase. Under MySQL/MariaDB this mostly reads fine, but SQLite (used by the test suite) does not preserve `DECIMAL(19,4)` column scale on `SUM()`, so these returned `"25"` instead of `"25.0000"` — silently inconsistent formatting that fed directly into two Show pages (`Invoice`, `Bill`) and Inventory's own displays. All three fixed; regression tests added (`amount paid is formatted with four decimals` in both `PaymentTest` and `VendorPaymentTest`, plus direct assertions in `ProductTest`/`StockAdjustmentTest`).
* Demo data: `ProductCategorySeeder` (2 categories) and `ProductSeeder` — an inventory-tracked "Widget A" with a 100-unit opening quantity, a "Consulting Hour" service product (no stock tracking), and a demo stock adjustment (shrinkage of 5 units) that posts a real Dr COGS / Cr Inventory journal — so both product types and both movement reasons are visible and verifiable out of the box.
* Tests: 18 new tests (10 Product, 6 StockAdjustment, plus the 2 amountPaid regression tests placed in the existing Payment/VendorPayment suites). Full suite: 154 tests passing.

Not implemented yet: wiring Product selection into Invoice/Bill line items (auto-filling account/price and triggering a stock movement + COGS posting when an inventory-tracked product is actually sold or purchased) — Inventory exists as a correct, standalone module first; this integration is the natural next increment, deliberately not bundled in here, same sequencing as Payments following Invoices rather than shipping together. Also not implemented: Tax on products (Phase 9), Reconciliation (Phase 7, still open).

## Everything Else

Not implemented. See Section 83 for phase order.

---

# END OF EasyAccountsERP.md
