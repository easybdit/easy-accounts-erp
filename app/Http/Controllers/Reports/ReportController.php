<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use App\Models\Accounting\Budget;
use App\Models\Accounting\JournalEntry;
use App\Models\Contacts\Customer;
use App\Models\Contacts\Vendor;
use App\Models\Expenses\Expense;
use App\Models\Inventory\Product;
use App\Models\Purchases\Bill;
use App\Models\Purchases\VendorPayment;
use App\Models\Sales\Invoice;
use App\Models\Sales\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Section 35's "dedicated Reports section" / Phase 10. Every report here is
 * read-only and derived entirely from already-posted data (Section 68) —
 * no new posting logic. Trial Balance, General Ledger, and the Tax Report
 * already existed (Phases 2 and 9) and are linked from the hub rather than
 * duplicated.
 */
class ReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Reports/Index');
    }

    /**
     * Profit & Loss (Income Statement): income minus expenses over a period.
     */
    public function profitAndLoss(Request $request): Response
    {
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();

        $rows = $this->accountAmountsByType(['income', 'expense'], $from, $to);

        $income = $rows->where('type', 'income');
        $expense = $rows->where('type', 'expense');

        $totalIncome = $income->reduce(fn (string $c, array $r) => bcadd($c, $r['amount'], 4), '0.0000');
        $totalExpense = $expense->reduce(fn (string $c, array $r) => bcadd($c, $r['amount'], 4), '0.0000');

        return Inertia::render('Reports/ProfitAndLoss', [
            'income' => $income->values(),
            'expense' => $expense->values(),
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netProfit' => bcsub($totalIncome, $totalExpense, 4),
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * Balance Sheet as of a date. Since there is no period-close/retained-
     * earnings-rollover feature (Section 25 Period Lock is still open),
     * accumulated net income to date is folded into Equity as its own line
     * — the standard way to keep Assets = Liabilities + Equity true without
     * a closing-entry mechanism.
     */
    public function balanceSheet(Request $request): Response
    {
        $asOf = $request->date('as_of')?->toDateString() ?? now()->toDateString();

        $assets = $this->accountBalancesByType('asset', $asOf);
        $liabilities = $this->accountBalancesByType('liability', $asOf);
        $equity = $this->accountBalancesByType('equity', $asOf);

        $incomeExpense = $this->accountAmountsByType(['income', 'expense'], null, $asOf);
        $currentEarnings = bcsub(
            $incomeExpense->where('type', 'income')->reduce(fn (string $c, array $r) => bcadd($c, $r['amount'], 4), '0.0000'),
            $incomeExpense->where('type', 'expense')->reduce(fn (string $c, array $r) => bcadd($c, $r['amount'], 4), '0.0000'),
            4
        );

        $totalAssets = $assets->reduce(fn (string $c, array $r) => bcadd($c, $r['balance'], 4), '0.0000');
        $totalLiabilities = $liabilities->reduce(fn (string $c, array $r) => bcadd($c, $r['balance'], 4), '0.0000');
        $totalEquity = bcadd($equity->reduce(fn (string $c, array $r) => bcadd($c, $r['balance'], 4), '0.0000'), $currentEarnings, 4);

        return Inertia::render('Reports/BalanceSheet', [
            'assets' => $assets->values(),
            'liabilities' => $liabilities->values(),
            'equity' => $equity->values(),
            'currentEarnings' => $currentEarnings,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'isBalanced' => bccomp($totalAssets, bcadd($totalLiabilities, $totalEquity, 4), 4) === 0,
            'asOf' => $asOf,
        ]);
    }

    public function arAging(Request $request): Response
    {
        $asOf = $request->date('as_of')?->toDateString() ?? now()->toDateString();

        $invoices = Invoice::query()
            ->where('status', 'posted')
            ->where('invoice_date', '<=', $asOf)
            ->with('customer:id,name')
            ->withSum('paymentAllocations as amount_paid', 'amount')
            ->get();

        $rows = $this->buildAging($invoices, $asOf, fn (Invoice $i) => $i->customer_id, fn (Invoice $i) => $i->customer->name, fn (Invoice $i) => ($i->due_date ?? $i->invoice_date)->toDateString());

        return Inertia::render('Reports/ArAging', [
            'rows' => $rows['rows'],
            'totals' => $rows['totals'],
            'asOf' => $asOf,
        ]);
    }

    public function apAging(Request $request): Response
    {
        $asOf = $request->date('as_of')?->toDateString() ?? now()->toDateString();

        $bills = Bill::query()
            ->where('status', 'posted')
            ->where('bill_date', '<=', $asOf)
            ->with('vendor:id,name')
            ->withSum('paymentAllocations as amount_paid', 'amount')
            ->get();

        $rows = $this->buildAging($bills, $asOf, fn (Bill $b) => $b->vendor_id, fn (Bill $b) => $b->vendor->name, fn (Bill $b) => ($b->due_date ?? $b->bill_date)->toDateString());

        return Inertia::render('Reports/ApAging', [
            'rows' => $rows['rows'],
            'totals' => $rows['totals'],
            'asOf' => $asOf,
        ]);
    }

    /**
     * A per-account cash movement summary for Cash/Bank accounts (opening,
     * in, out, closing) — kept alongside cashFlowStatement() below for
     * account-level reconciliation-style detail; the categorized
     * Operating/Investing/Financing breakdown lives there.
     */
    public function cashFlow(Request $request): Response
    {
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();

        $accounts = Account::query()
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($from, $to) {
                $opening = $from ? $account->balanceAsOf(date('Y-m-d', strtotime($from.' -1 day'))) : (string) $account->opening_balance;
                $movement = $account->netMovement($from, $to);

                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'opening' => $opening,
                    'in' => $movement['debit'],
                    'out' => $movement['credit'],
                    'closing' => $account->balanceAsOf($to),
                ];
            });

        return Inertia::render('Reports/CashFlow', [
            'accounts' => $accounts,
            'totalOpening' => $accounts->reduce(fn (string $c, array $r) => bcadd($c, $r['opening'], 4), '0.0000'),
            'totalIn' => $accounts->reduce(fn (string $c, array $r) => bcadd($c, $r['in'], 4), '0.0000'),
            'totalOut' => $accounts->reduce(fn (string $c, array $r) => bcadd($c, $r['out'], 4), '0.0000'),
            'totalClosing' => $accounts->reduce(fn (string $c, array $r) => bcadd($c, $r['closing'], 4), '0.0000'),
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * The Categorized (direct-method) Cash Flow Statement (Section 90
     * Phase 10, resolved): every bank/cash account's movement in the
     * period, classified into Operating/Investing/Financing by the
     * cash_flow_category of the account on the OTHER side of the journal
     * (Account::CASH_FLOW_CATEGORIES). A movement whose only contra
     * accounts are themselves bank accounts is an internal transfer and is
     * excluded entirely, same as a real cash flow statement excludes
     * moving money between your own accounts. A multi-line journal (e.g.
     * an Expense with tax) splits the cash movement across its non-bank
     * contra lines proportionally to their own amounts.
     */
    public function cashFlowStatement(Request $request): Response
    {
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();

        $bankAccounts = Account::where('is_bank_account', true)->where('is_active', true)->get();
        $bankAccountIds = $bankAccounts->pluck('id')->all();

        $totals = ['operating' => '0.0000', 'investing' => '0.0000', 'financing' => '0.0000'];
        $byCategory = ['operating' => [], 'investing' => [], 'financing' => []];

        $entries = JournalEntry::query()
            ->whereIn('account_id', $bankAccountIds)
            ->whereHas('journal', fn ($q) => $q->whereDate('date', '>=', $from ?? '0001-01-01')->whereDate('date', '<=', $to))
            ->with('journal.entries.account')
            ->get();

        foreach ($entries as $entry) {
            $siblings = $entry->journal->entries->reject(fn (JournalEntry $e) => $e->id === $entry->id);
            $nonBankSiblings = $siblings->reject(fn (JournalEntry $e) => in_array($e->account_id, $bankAccountIds, true));

            // All contra lines are also bank accounts: an internal transfer.
            if ($nonBankSiblings->isEmpty()) {
                continue;
            }

            $netMovement = bcsub((string) $entry->debit, (string) $entry->credit, 4);

            $totalSiblingMagnitude = $nonBankSiblings->reduce(
                fn (string $carry, JournalEntry $e) => bcadd($carry, ltrim(bcsub((string) $e->credit, (string) $e->debit, 4), '-'), 4),
                '0.0000'
            );

            foreach ($nonBankSiblings as $sibling) {
                $siblingMagnitude = ltrim(bcsub((string) $sibling->credit, (string) $sibling->debit, 4), '-');

                $share = bccomp($totalSiblingMagnitude, '0', 4) === 0
                    ? '0.0000'
                    : bcdiv(bcmul($netMovement, $siblingMagnitude, 10), $totalSiblingMagnitude, 4);

                $category = $sibling->account->cash_flow_category;
                $totals[$category] = bcadd($totals[$category], $share, 4);

                $accountId = $sibling->account_id;
                $byCategory[$category][$accountId] ??= [
                    'id' => $accountId,
                    'code' => $sibling->account->code,
                    'name' => $sibling->account->name,
                    'amount' => '0.0000',
                ];
                $byCategory[$category][$accountId]['amount'] = bcadd($byCategory[$category][$accountId]['amount'], $share, 4);
            }
        }

        $openingCash = $bankAccounts->reduce(
            fn (string $carry, Account $account) => bcadd($carry, $from ? $account->balanceAsOf(date('Y-m-d', strtotime($from.' -1 day'))) : (string) $account->opening_balance, 4),
            '0.0000'
        );
        $closingCash = $bankAccounts->reduce(fn (string $carry, Account $account) => bcadd($carry, $account->balanceAsOf($to), 4), '0.0000');
        $netChange = bcadd(bcadd($totals['operating'], $totals['investing'], 4), $totals['financing'], 4);

        return Inertia::render('Reports/CashFlowStatement', [
            'operating' => array_values($byCategory['operating']),
            'investing' => array_values($byCategory['investing']),
            'financing' => array_values($byCategory['financing']),
            'totalOperating' => $totals['operating'],
            'totalInvesting' => $totals['investing'],
            'totalFinancing' => $totals['financing'],
            'netChange' => $netChange,
            'openingCash' => $openingCash,
            'closingCash' => $closingCash,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function sales(Request $request): Response
    {
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString();

        $invoices = Invoice::query()
            ->where('status', 'posted')
            ->when($from, fn ($q) => $q->where('invoice_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('invoice_date', '<=', $to))
            ->with('customer:id,name')
            ->get();

        $rows = $invoices->groupBy('customer_id')->map(function ($group) {
            return [
                'customer' => $group->first()->customer->name,
                'count' => $group->count(),
                'total' => $group->reduce(fn (string $c, Invoice $i) => bcadd($c, (string) $i->total, 4), '0.0000'),
            ];
        })->sortByDesc('total')->values();

        return Inertia::render('Reports/SalesReport', [
            'rows' => $rows,
            'total' => $rows->reduce(fn (string $c, array $r) => bcadd($c, $r['total'], 4), '0.0000'),
            'count' => $invoices->count(),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function purchases(Request $request): Response
    {
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString();

        $bills = Bill::query()
            ->where('status', 'posted')
            ->when($from, fn ($q) => $q->where('bill_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('bill_date', '<=', $to))
            ->with('vendor:id,name')
            ->get();

        $rows = $bills->groupBy('vendor_id')->map(function ($group) {
            return [
                'vendor' => $group->first()->vendor->name,
                'count' => $group->count(),
                'total' => $group->reduce(fn (string $c, Bill $b) => bcadd($c, (string) $b->total, 4), '0.0000'),
            ];
        })->sortByDesc('total')->values();

        return Inertia::render('Reports/PurchaseReport', [
            'rows' => $rows,
            'total' => $rows->reduce(fn (string $c, array $r) => bcadd($c, $r['total'], 4), '0.0000'),
            'count' => $bills->count(),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function expenses(Request $request): Response
    {
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString();

        $expenses = Expense::query()
            ->when($from, fn ($q) => $q->where('expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('expense_date', '<=', $to))
            ->with('category:id,name')
            ->get();

        $rows = $expenses->groupBy('expense_category_id')->map(function ($group) {
            return [
                'category' => $group->first()->category->name,
                'count' => $group->count(),
                'total' => $group->reduce(fn (string $c, Expense $e) => bcadd($c, (string) $e->amount, 4), '0.0000'),
            ];
        })->sortByDesc('total')->values();

        return Inertia::render('Reports/ExpenseReport', [
            'rows' => $rows,
            'total' => $rows->reduce(fn (string $c, array $r) => bcadd($c, $r['total'], 4), '0.0000'),
            'count' => $expenses->count(),
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * A customer-facing Statement of Account: opening balance carried in
     * from before "from", every transaction touching this customer's
     * receivable ledger within the period in order, and a running balance
     * — mirrors GeneralLedgerController's per-account view exactly, just
     * scoped to a customer's tagged journal entries instead of one account.
     */
    public function customerStatement(Request $request): Response
    {
        $customers = Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $customerId = $request->integer('customer_id') ?: null;
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();

        return Inertia::render('Reports/CustomerStatement', [
            'customers' => $customers,
            'statement' => $customerId ? $this->buildCustomerStatement(Customer::findOrFail($customerId), $from, $to) : null,
            'filters' => [
                'customer_id' => $customerId,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function customerStatementPdf(Request $request): HttpResponse
    {
        $customer = Customer::findOrFail($request->integer('customer_id'));
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();

        $statement = $this->buildCustomerStatement($customer, $from, $to);

        $pdf = Pdf::loadView('pdfs.customer-statement', [
            'customer' => $customer,
            'statement' => $statement,
            'from' => $from,
            'to' => $to,
            'appName' => config('app.name'),
        ]);

        return $pdf->download("Statement-{$customer->name}.pdf");
    }

    private function buildCustomerStatement(Customer $customer, ?string $from, ?string $to): array
    {
        $startingBalance = $from
            ? $customer->balanceAsOf(Carbon::parse($from)->subDay()->toDateString())
            : (string) $customer->opening_balance;

        $entries = $customer->journalEntries()
            ->with('journal:id,reference,description')
            ->when($from, fn ($query) => $query->whereDate('date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('date', '<=', $to))
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $running = $startingBalance;

        // A customer's ledger is debit-normal (an amount owed TO the
        // business) regardless of which GL account each line used —
        // mirrors Customer::balanceAsOf's own debit-increases convention.
        $rows = $entries->map(function (JournalEntry $entry) use (&$running) {
            $debit = (string) $entry->debit;
            $credit = (string) $entry->credit;

            $running = bcsub(bcadd($running, $debit, 4), $credit, 4);

            return [
                'id' => $entry->id,
                'date' => $entry->date->toDateString(),
                'reference' => $entry->journal->reference,
                'description' => $entry->description ?? $entry->journal->description,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $running,
                'journal_id' => $entry->journal_id,
            ];
        });

        return [
            'customer' => ['id' => $customer->id, 'name' => $customer->name, 'email' => $customer->email],
            'starting_balance' => $startingBalance,
            'ending_balance' => $running,
            'entries' => $rows,
        ];
    }

    /**
     * Compares each budget line's annual amount, prorated to the selected
     * date range's share of the fiscal year, against the account's actual
     * activity in that same range — so a mid-year run still compares like
     * with like rather than a full year's budget against a partial year's
     * actual.
     */
    public function budgetVsActual(Request $request): Response
    {
        $budgets = Budget::query()->orderByDesc('fiscal_year')->orderBy('name')->get(['id', 'name', 'fiscal_year']);

        $budgetId = $request->integer('budget_id') ?: null;
        $budget = $budgetId ? Budget::with('lines.account')->findOrFail($budgetId) : null;

        $from = $request->date('from')?->toDateString() ?? ($budget ? "{$budget->fiscal_year}-01-01" : null);
        $to = $request->date('to')?->toDateString() ?? ($budget ? "{$budget->fiscal_year}-12-31" : null);

        return Inertia::render('Reports/BudgetVsActual', [
            'budgets' => $budgets,
            'comparison' => $budget ? $this->buildBudgetComparison($budget, $from, $to) : null,
            'filters' => [
                'budget_id' => $budgetId,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    private function buildBudgetComparison(Budget $budget, string $from, string $to): array
    {
        $daysInRange = (int) floor((strtotime($to) - strtotime($from)) / 86400) + 1;
        $daysInFiscalYear = (date('L', mktime(0, 0, 0, 1, 1, $budget->fiscal_year)) ? 366 : 365);
        $proration = max(0, min(1, $daysInRange / $daysInFiscalYear));

        $accountIds = $budget->lines->pluck('account_id')->all();
        $actuals = $this->accountAmountsByType(['income', 'expense'], $from, $to)
            ->whereIn('id', $accountIds)
            ->keyBy('id');

        $rows = $budget->lines->map(function ($line) use ($proration, $actuals) {
            $budgeted = bcmul((string) $line->amount, (string) round($proration, 6), 4);
            $actualRow = $actuals->get($line->account_id);
            $actual = $actualRow ? $actualRow['amount'] : '0.0000';
            $variance = bcsub($actual, $budgeted, 4);
            $variancePercent = bccomp($budgeted, '0', 4) !== 0
                ? round((float) bcdiv(bcmul($variance, '100', 6), $budgeted, 6), 2)
                : null;

            return [
                'account' => ['id' => $line->account->id, 'code' => $line->account->code, 'name' => $line->account->name, 'type' => $line->account->type],
                'budgeted' => $budgeted,
                'actual' => $actual,
                'variance' => $variance,
                'variance_percent' => $variancePercent,
            ];
        })->sortBy('account.code')->values();

        return [
            'rows' => $rows,
            'totalBudgeted' => $rows->reduce(fn (string $c, array $r) => bcadd($c, $r['budgeted'], 4), '0.0000'),
            'totalActual' => $rows->reduce(fn (string $c, array $r) => bcadd($c, $r['actual'], 4), '0.0000'),
        ];
    }

    public function customerBalances(): Response
    {
        $rows = Customer::query()
            ->where('is_active', true)
            ->withSum('journalEntries as entries_debit', 'debit')
            ->withSum('journalEntries as entries_credit', 'credit')
            ->get()
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'balance' => bcsub(
                    bcadd((string) $customer->opening_balance, (string) ($customer->entries_debit ?? '0.0000'), 4),
                    (string) ($customer->entries_credit ?? '0.0000'),
                    4
                ),
            ])
            ->sortByDesc('balance')
            ->values();

        return Inertia::render('Reports/CustomerBalances', [
            'rows' => $rows,
            'total' => $rows->reduce(fn (string $c, array $r) => bcadd($c, $r['balance'], 4), '0.0000'),
        ]);
    }

    public function vendorBalances(): Response
    {
        $rows = Vendor::query()
            ->where('is_active', true)
            ->withSum('journalEntries as entries_debit', 'debit')
            ->withSum('journalEntries as entries_credit', 'credit')
            ->get()
            ->map(fn (Vendor $vendor) => [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'balance' => bcsub(
                    bcadd((string) $vendor->opening_balance, (string) ($vendor->entries_credit ?? '0.0000'), 4),
                    (string) ($vendor->entries_debit ?? '0.0000'),
                    4
                ),
            ])
            ->sortByDesc('balance')
            ->values();

        return Inertia::render('Reports/VendorBalances', [
            'rows' => $rows,
            'total' => $rows->reduce(fn (string $c, array $r) => bcadd($c, $r['balance'], 4), '0.0000'),
        ]);
    }

    public function payments(Request $request): Response
    {
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString();

        $received = Payment::query()
            ->when($from, fn ($q) => $q->where('payment_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('payment_date', '<=', $to))
            ->with('customer:id,name')
            ->orderByDesc('payment_date')
            ->get();

        $made = VendorPayment::query()
            ->when($from, fn ($q) => $q->where('payment_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('payment_date', '<=', $to))
            ->with('vendor:id,name')
            ->orderByDesc('payment_date')
            ->get();

        return Inertia::render('Reports/PaymentsReport', [
            'received' => $received,
            'made' => $made,
            'totalReceived' => $received->reduce(fn (string $c, Payment $p) => bcadd($c, (string) $p->amount, 4), '0.0000'),
            'totalMade' => $made->reduce(fn (string $c, VendorPayment $p) => bcadd($c, (string) $p->amount, 4), '0.0000'),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function inventory(): Response
    {
        $rows = Product::query()
            ->where('type', 'inventory')
            ->where('is_active', true)
            ->with('category:id,name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category->name,
                'unit' => $product->unit,
                'current_stock' => $product->currentStock(),
                'purchase_price' => (string) $product->purchase_price,
                'stock_value' => $product->stockValue(),
                'is_low_stock' => $product->isLowStock(),
            ]);

        return Inertia::render('Reports/InventoryReport', [
            'rows' => $rows,
            'totalValue' => $rows->reduce(fn (string $c, array $r) => bcadd($c, $r['stock_value'], 4), '0.0000'),
        ]);
    }

    /**
     * @return Collection<int, array{id:int,code:string,name:string,type:string,amount:string}>
     */
    private function accountAmountsByType(array $types, ?string $from, ?string $to): Collection
    {
        return Account::query()
            ->whereIn('type', $types)
            ->where('is_active', true)
            ->withSum(['journalEntries as period_debit' => fn ($q) => $q->when($from, fn ($q2) => $q2->whereDate('date', '>=', $from))->when($to, fn ($q2) => $q2->whereDate('date', '<=', $to))], 'debit')
            ->withSum(['journalEntries as period_credit' => fn ($q) => $q->when($from, fn ($q2) => $q2->whereDate('date', '>=', $from))->when($to, fn ($q2) => $q2->whereDate('date', '<=', $to))], 'credit')
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) {
                $debit = (string) ($account->period_debit ?? '0.0000');
                $credit = (string) ($account->period_credit ?? '0.0000');
                $amount = $account->type === 'income' ? bcsub($credit, $debit, 4) : bcsub($debit, $credit, 4);

                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'amount' => $amount,
                ];
            })
            ->filter(fn (array $row) => bccomp($row['amount'], '0', 4) !== 0)
            ->values();
    }

    /**
     * @return Collection<int, array{id:int,code:string,name:string,balance:string}>
     */
    private function accountBalancesByType(string $type, string $asOf): Collection
    {
        return Account::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn (Account $account) => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'balance' => $account->balanceAsOf($asOf),
            ])
            ->filter(fn (array $row) => bccomp($row['balance'], '0', 4) !== 0)
            ->values();
    }

    /**
     * Shared aging-bucket builder for AR/AP (Current, 1-30, 31-60, 61-90, 90+).
     */
    private function buildAging($documents, string $asOf, callable $partyId, callable $partyName, callable $dueDate): array
    {
        $buckets = ['current', 'd1_30', 'd31_60', 'd61_90', 'd90_plus'];
        $byParty = [];

        foreach ($documents as $document) {
            $amountPaid = (string) ($document->amount_paid ?? '0.0000');
            $due = bcsub((string) $document->total, $amountPaid, 4);

            if (bccomp($due, '0', 4) <= 0) {
                continue;
            }

            $daysOverdue = (int) floor((strtotime($asOf) - strtotime($dueDate($document))) / 86400);
            $bucket = match (true) {
                $daysOverdue <= 0 => 'current',
                $daysOverdue <= 30 => 'd1_30',
                $daysOverdue <= 60 => 'd31_60',
                $daysOverdue <= 90 => 'd61_90',
                default => 'd90_plus',
            };

            $id = $partyId($document);
            $byParty[$id] ??= ['id' => $id, 'name' => $partyName($document), 'current' => '0.0000', 'd1_30' => '0.0000', 'd31_60' => '0.0000', 'd61_90' => '0.0000', 'd90_plus' => '0.0000', 'total' => '0.0000'];
            $byParty[$id][$bucket] = bcadd($byParty[$id][$bucket], $due, 4);
            $byParty[$id]['total'] = bcadd($byParty[$id]['total'], $due, 4);
        }

        $totals = array_fill_keys([...$buckets, 'total'], '0.0000');
        foreach ($byParty as $row) {
            foreach ([...$buckets, 'total'] as $key) {
                $totals[$key] = bcadd($totals[$key], $row[$key], 4);
            }
        }

        return [
            'rows' => array_values($byParty),
            'totals' => $totals,
        ];
    }
}
