# Changelog

All notable changes to EasyAccountsERP are recorded here, newest first. This project doesn't cut version numbers yet, so entries are grouped by the date they landed on `main`. See [`EasyAccountsERP.md`](EasyAccountsERP.md) for the design rationale behind any entry.

## 2026-09-23

### Added

- Low-stock alert digest email — daily to everyone with inventory-manage permission, plus a manual "Send Now" button.
- Overdue invoice payment reminders — daily, with a 7-day cooldown per invoice, plus a manual "Send Payment Reminder" button.
- CSV export on every report (P&L, Balance Sheet, Trial Balance, General Ledger, AR/AP Aging, Cash Flow, Cash Flow Statement, Sales/Purchase/Expense reports, Customer/Vendor Balances, Payments, Inventory, Budget vs Actual, Tax Report).
- Estimate PDF download and email delivery, mirroring the Invoice feature.
- Recurring Bills, with the same scheduled auto-generation Recurring Invoices/Expenses already had.
- Vendor Statement report with PDF export, mirroring Customer Statement.
- Budget vs Actual: annual per-account budgets compared against actual activity, prorated to the selected date range.
- Undeposited Funds holding account and Bank Deposit batching (group several customer payments into one deposit journal entry).
- Customer Statement report with PDF export.
- Period Lock: prevents posting into closed accounting periods, enforced at the single `PostJournal` choke point every posting action already goes through.
- Scheduled auto-generation for Recurring Expenses.
- Invoice email delivery with PDF attachment and an optional payment link.
- Fixed Asset disposal now posts a real gain/loss journal entry instead of just flipping a status flag.

## 2026-09-22

### Added

- Online payment links via SSLCommerz, with server-to-server payment validation.
- Deferred Revenue Recognition for invoice lines, recognized monthly.
- Scheduled Recurring Invoicing.
- Fixed Asset register with automatic monthly straight-line depreciation.
- Categorized (direct-method) Cash Flow Statement — Operating/Investing/Financing.
- Role/Permission-change audit diff view and an activity-log retention policy.
- Expense-level tax, file attachments, and Recurring Expenses (manual "Generate Now" only at this point).
- Tax-inclusive pricing on Invoices and Bills.
- Inventory ↔ Invoice/Bill integration (stock movements and COGS posting from sales/purchase documents).
- Vendor Credits (Purchases) and Credit Notes (Sales).
- Purchase Orders, convertible to a draft Bill.
- Estimates, convertible to a draft Invoice.
- Bank Reconciliation.
- Void/Reversal for posted journal entries (reversing entry, never edits or deletes).
- An Income vs Expense trend chart and a QuickBooks-style Dashboard (live KPIs, module relationship diagram).
- Security & Audit: roles, permissions, route gating, activity log.
- Reports: P&L, Balance Sheet, AR/AP Aging, Cash Flow, and more.
- Tax/VAT: Tax Rates, exclusive tax on Invoices/Bills, Tax Report.
- Inventory: Products, Categories, Stock Movements & Adjustments.
- Banking: Transfers and a Bank Accounts overview.
- Expenses: categories and immediately-posted expense entries.
- Purchases: Bills and Vendor Payments.
- Customer Payments with allocation across invoices.
- Sales Invoices with a draft → post lifecycle.
- Customers and Vendors.
- General Ledger and Trial Balance reports.
- Journal, Journal Entries, and the double-entry posting engine.
- Chart of Accounts and an AdminLTE-style application shell.

### Fixed

- Raw ISO timestamps (e.g. `2026-09-21T00:00:00.000000Z`) showing instead of plain dates across the app, including the Journal index/show pages where it was first spotted.

### Changed

- App config and seed data customized for EasyIT (hosting/networking/IT services, Bangladesh).
- Sidebar redesigned as an AdminLTE-style multi-level treeview.
- Frontend UI/UX polish: toasts, table styling, empty states, form loading states.
- Default Laravel welcome page replaced with an auth redirect.

## 2026-09-21

### Added

- Project bootstrap: fresh Laravel 13 install with Breeze authentication scaffolding.
