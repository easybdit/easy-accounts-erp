<?php

namespace App\Actions\Accounting;

use App\Models\Accounting\Budget;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a budget and its per-account lines. Mirrors
 * SaveRecurringInvoice's delete-and-recreate approach for the lines —
 * simpler and safer than diffing than trying to match existing rows to a
 * submitted set, and a budget's lines are never referenced by anything
 * else that would need their ids preserved across an edit.
 */
class SaveBudget
{
    /**
     * @param  array{name:string, fiscal_year:int, notes:?string, created_by?:?int, lines: array<int, array{account_id:int, amount:numeric-string|float}>}  $data
     */
    public function handle(array $data, ?Budget $budget = null): Budget
    {
        return DB::transaction(function () use ($data, $budget) {
            $budget = $budget
                ? tap($budget)->update([
                    'name' => $data['name'],
                    'fiscal_year' => $data['fiscal_year'],
                    'notes' => $data['notes'] ?? null,
                ])
                : Budget::create([
                    'name' => $data['name'],
                    'fiscal_year' => $data['fiscal_year'],
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $data['created_by'] ?? null,
                ]);

            $budget->lines()->delete();

            foreach ($data['lines'] as $line) {
                if (bccomp((string) $line['amount'], '0', 4) === 0) {
                    continue;
                }

                $budget->lines()->create([
                    'account_id' => $line['account_id'],
                    'amount' => $line['amount'],
                ]);
            }

            return $budget->load('lines.account');
        });
    }
}
