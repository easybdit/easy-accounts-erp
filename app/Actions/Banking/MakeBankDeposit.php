<?php

namespace App\Actions\Banking;

use App\Actions\Accounting\PostJournal;
use App\Models\Accounting\Account;
use App\Models\Banking\BankDeposit;
use App\Models\Sales\Payment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Groups several undeposited customer Payments into one Bank Deposit batch
 * — debit the real bank account, credit Undeposited Funds, for the sum of
 * every selected payment — mirroring the single lump sum a bank statement
 * actually shows, rather than one deposit line per payment (Section: see
 * BankDeposit's docblock).
 */
class MakeBankDeposit
{
    public function __construct(private PostJournal $postJournal) {}

    /**
     * @param  array{bank_account_id:int, deposit_date:string, reference:?string, notes:?string, created_by:?int, payment_ids: array<int, int>}  $data
     */
    public function handle(array $data): BankDeposit
    {
        $undepositedFundsAccount = Account::where('is_undeposited_funds', true)->first();

        if (! $undepositedFundsAccount) {
            throw new RuntimeException('No Undeposited Funds account is configured.');
        }

        $payments = Payment::whereIn('id', $data['payment_ids'])->get();

        if ($payments->count() !== count($data['payment_ids'])) {
            throw new RuntimeException('One of the selected payments could not be found.');
        }

        if ($payments->isEmpty()) {
            throw new RuntimeException('Select at least one payment to deposit.');
        }

        foreach ($payments as $payment) {
            if ($payment->bank_deposit_id !== null) {
                throw new RuntimeException("Payment {$payment->payment_number} has already been deposited.");
            }

            if ($payment->deposit_account_id !== $undepositedFundsAccount->id) {
                throw new RuntimeException("Payment {$payment->payment_number} was not received into Undeposited Funds.");
            }
        }

        $total = $payments->reduce(fn (string $carry, Payment $payment) => bcadd($carry, (string) $payment->amount, 4), '0.0000');

        return DB::transaction(function () use ($data, $payments, $total, $undepositedFundsAccount) {
            $deposit = BankDeposit::create([
                'deposit_number' => $this->nextDepositNumber($data['deposit_date']),
                'bank_account_id' => $data['bank_account_id'],
                'deposit_date' => $data['deposit_date'],
                'amount' => $total,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            $this->postJournal->handle([
                'date' => $data['deposit_date'],
                'reference' => $deposit->deposit_number,
                'description' => "Bank deposit {$deposit->deposit_number}",
                'created_by' => $data['created_by'] ?? null,
                'source_type' => BankDeposit::class,
                'source_id' => $deposit->id,
                'lines' => [
                    ['account_id' => $data['bank_account_id'], 'debit' => $total, 'credit' => 0, 'description' => "Bank deposit {$deposit->deposit_number}"],
                    ['account_id' => $undepositedFundsAccount->id, 'debit' => 0, 'credit' => $total, 'description' => "Bank deposit {$deposit->deposit_number}"],
                ],
            ]);

            foreach ($payments as $payment) {
                $payment->update(['bank_deposit_id' => $deposit->id]);
            }

            return $deposit->load('payments', 'journal');
        });
    }

    private function nextDepositNumber(string $depositDate): string
    {
        $year = date('Y', strtotime($depositDate));
        $count = BankDeposit::where('deposit_number', 'like', "DEP-{$year}-%")->count() + 1;

        return sprintf('DEP-%s-%04d', $year, $count);
    }
}
