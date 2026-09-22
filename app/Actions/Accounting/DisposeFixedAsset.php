<?php

namespace App\Actions\Accounting;

use App\Models\Accounting\FixedAsset;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Disposing an asset removes it from the books and records any gain or
 * loss — the difference between what was received (disposal_proceeds) and
 * its book value at the time (cost less accumulated depreciation). This is
 * the actual accounting event a "mark as disposed" status flip alone
 * cannot represent: selling a partially-depreciated server for more or
 * less than its book value is a real P&L impact, not a no-op.
 *
 * Journal (always balances by construction — see the class's tests for
 * the algebra): debit accumulated depreciation (removing the contra-asset
 * balance), debit any proceeds received, debit a loss or credit a gain for
 * the difference, credit the asset account for its full original cost.
 */
class DisposeFixedAsset
{
    public function __construct(private PostJournal $postJournal) {}

    /**
     * @param  array{disposal_proceeds?:numeric-string|float|null, disposal_proceeds_account_id?:?int, gain_loss_account_id?:?int, disposal_notes?:?string}  $data
     */
    public function handle(FixedAsset $asset, array $data, ?int $userId): FixedAsset
    {
        if ($asset->status === 'disposed') {
            throw new RuntimeException('This asset has already been disposed.');
        }

        $proceeds = (string) ($data['disposal_proceeds'] ?? '0');
        $accumulatedDep = $asset->accumulatedDepreciation();
        $bookValue = $asset->bookValue();
        $gainLoss = bcsub($proceeds, $bookValue, 4);

        if (bccomp($proceeds, '0', 4) > 0 && empty($data['disposal_proceeds_account_id'])) {
            throw new RuntimeException('A proceeds account is required when disposal proceeds are greater than zero.');
        }

        if (bccomp($gainLoss, '0', 4) !== 0 && empty($data['gain_loss_account_id'])) {
            throw new RuntimeException('A gain/loss account is required — this disposal is not a wash.');
        }

        return DB::transaction(function () use ($asset, $data, $proceeds, $accumulatedDep, $gainLoss, $userId) {
            $lines = [];

            if (bccomp($accumulatedDep, '0', 4) > 0) {
                $lines[] = ['account_id' => $asset->accumulated_depreciation_account_id, 'debit' => $accumulatedDep, 'credit' => 0, 'description' => "Disposal — {$asset->name}"];
            }

            if (bccomp($proceeds, '0', 4) > 0) {
                $lines[] = ['account_id' => $data['disposal_proceeds_account_id'], 'debit' => $proceeds, 'credit' => 0, 'description' => "Disposal proceeds — {$asset->name}"];
            }

            if (bccomp($gainLoss, '0', 4) > 0) {
                $lines[] = ['account_id' => $data['gain_loss_account_id'], 'debit' => 0, 'credit' => $gainLoss, 'description' => "Gain on disposal — {$asset->name}"];
            } elseif (bccomp($gainLoss, '0', 4) < 0) {
                $lines[] = ['account_id' => $data['gain_loss_account_id'], 'debit' => ltrim($gainLoss, '-'), 'credit' => 0, 'description' => "Loss on disposal — {$asset->name}"];
            }

            $lines[] = ['account_id' => $asset->asset_account_id, 'debit' => 0, 'credit' => (string) $asset->purchase_cost, 'description' => "Disposal — {$asset->name}"];

            $this->postJournal->handle([
                'date' => now()->toDateString(),
                'reference' => "DISP-{$asset->id}",
                'description' => "Disposal of {$asset->name}",
                'created_by' => $userId,
                'source_type' => FixedAsset::class,
                'source_id' => $asset->id,
                'lines' => $lines,
            ]);

            $asset->update([
                'status' => 'disposed',
                'disposed_at' => now(),
                'disposal_notes' => $data['disposal_notes'] ?? null,
                'disposal_proceeds' => $proceeds,
                'disposal_proceeds_account_id' => $data['disposal_proceeds_account_id'] ?? null,
                'gain_loss_account_id' => $data['gain_loss_account_id'] ?? null,
            ]);

            return $asset->fresh(['disposalJournal']);
        });
    }
}
