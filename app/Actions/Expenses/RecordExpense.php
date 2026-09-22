<?php

namespace App\Actions\Expenses;

use App\Actions\Accounting\PostJournal;
use App\Models\Expenses\Expense;
use App\Models\Tax\TaxRate;
use Illuminate\Support\Facades\DB;

/**
 * Like a manual Journal Entry or a customer Payment, recording an expense
 * IS posting it — there is no draft state (Section 15's diagram shows
 * Expense -> Accounting Posting -> Journal directly, unlike Invoice/Bill's
 * extra "Items" step). Debits the expense's GL account, credits the
 * payment account (Cash/Bank) for the amount plus any tax paid on top
 * (exclusive tax, same convention as Invoices/Bills — Section 33).
 */
class RecordExpense
{
    public function __construct(private PostJournal $postJournal) {}

    /**
     * @param  array{expense_category_id:int, account_id:int, payment_account_id:int, vendor_id:?int, payee:string, expense_date:string, amount:numeric-string|float, tax_rate_id:?int, reference:?string, notes:?string, created_by:?int}  $data
     */
    public function handle(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $amount = (string) $data['amount'];
            $taxRate = ! empty($data['tax_rate_id']) ? TaxRate::find($data['tax_rate_id']) : null;
            $taxAmount = $taxRate ? $taxRate->calculate($amount) : '0.0000';
            $totalPaid = bcadd($amount, $taxAmount, 4);

            $expense = Expense::create([
                'expense_number' => $this->nextExpenseNumber($data['expense_date']),
                'expense_category_id' => $data['expense_category_id'],
                'account_id' => $data['account_id'],
                'payment_account_id' => $data['payment_account_id'],
                'vendor_id' => $data['vendor_id'] ?? null,
                'payee' => $data['payee'],
                'expense_date' => $data['expense_date'],
                'amount' => $amount,
                'tax_rate_id' => $taxRate?->id,
                'tax_amount' => $taxAmount,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            $lines = [
                [
                    'account_id' => $data['account_id'],
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => "Expense {$expense->expense_number}",
                ],
            ];

            if ($taxRate && bccomp($taxAmount, '0', 4) > 0) {
                $lines[] = [
                    'account_id' => $taxRate->tax_account_id,
                    'debit' => $taxAmount,
                    'credit' => 0,
                    'description' => "Tax paid — Expense {$expense->expense_number}",
                ];
            }

            $lines[] = [
                'account_id' => $data['payment_account_id'],
                'debit' => 0,
                'credit' => $totalPaid,
                'description' => "Expense {$expense->expense_number}",
            ];

            $this->postJournal->handle([
                'date' => $data['expense_date'],
                'reference' => $expense->expense_number,
                'description' => "Expense {$expense->expense_number} — {$data['payee']}",
                'created_by' => $data['created_by'] ?? null,
                'source_type' => Expense::class,
                'source_id' => $expense->id,
                'lines' => $lines,
            ]);

            return $expense->load('journal');
        });
    }

    private function nextExpenseNumber(string $expenseDate): string
    {
        $year = date('Y', strtotime($expenseDate));
        $count = Expense::where('expense_number', 'like', "EXP-{$year}-%")->count() + 1;

        return sprintf('EXP-%s-%04d', $year, $count);
    }
}
