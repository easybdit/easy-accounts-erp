<?php

namespace App\Actions\Banking;

use App\Actions\Accounting\PostJournal;
use App\Models\Banking\Transfer;
use Illuminate\Support\Facades\DB;

/**
 * Moving money between two of the business's own asset accounts (e.g.
 * Cash -> Bank). Like a manual Journal Entry, Payment, or Expense,
 * recording a transfer IS posting it immediately — debit the destination
 * account, credit the source account.
 */
class RecordTransfer
{
    public function __construct(private PostJournal $postJournal) {}

    /**
     * @param  array{from_account_id:int, to_account_id:int, transfer_date:string, amount:numeric-string|float, reference:?string, notes:?string, created_by:?int}  $data
     */
    public function handle(array $data): Transfer
    {
        return DB::transaction(function () use ($data) {
            $transfer = Transfer::create([
                'transfer_number' => $this->nextTransferNumber($data['transfer_date']),
                'from_account_id' => $data['from_account_id'],
                'to_account_id' => $data['to_account_id'],
                'transfer_date' => $data['transfer_date'],
                'amount' => $data['amount'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            $this->postJournal->handle([
                'date' => $data['transfer_date'],
                'reference' => $transfer->transfer_number,
                'description' => "Transfer {$transfer->transfer_number}",
                'created_by' => $data['created_by'] ?? null,
                'source_type' => Transfer::class,
                'source_id' => $transfer->id,
                'lines' => [
                    [
                        'account_id' => $data['to_account_id'],
                        'debit' => $data['amount'],
                        'credit' => 0,
                        'description' => "Transfer {$transfer->transfer_number}",
                    ],
                    [
                        'account_id' => $data['from_account_id'],
                        'debit' => 0,
                        'credit' => $data['amount'],
                        'description' => "Transfer {$transfer->transfer_number}",
                    ],
                ],
            ]);

            return $transfer->load('journal');
        });
    }

    private function nextTransferNumber(string $transferDate): string
    {
        $year = date('Y', strtotime($transferDate));
        $count = Transfer::where('transfer_number', 'like', "TRF-{$year}-%")->count() + 1;

        return sprintf('TRF-%s-%04d', $year, $count);
    }
}
