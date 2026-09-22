<?php

namespace Database\Seeders\Banking;

use App\Actions\Banking\RecordTransfer;
use App\Models\Accounting\Account;
use App\Models\Banking\Transfer;
use Illuminate\Database\Seeder;

class TransferSeeder extends Seeder
{
    public function run(): void
    {
        if (Transfer::where('transfer_number', 'like', 'TRF-%')->exists()) {
            return;
        }

        $cash = Account::where('code', '1001')->first();
        $bank = Account::where('code', '1002')->first();

        if (! $cash || ! $bank) {
            return;
        }

        app(RecordTransfer::class)->handle([
            'from_account_id' => $cash->id,
            'to_account_id' => $bank->id,
            'transfer_date' => now()->subDays(3)->toDateString(),
            'amount' => 500,
            'reference' => null,
            'notes' => 'Demo transfer seeded for verification purposes.',
            'created_by' => null,
        ]);
    }
}
