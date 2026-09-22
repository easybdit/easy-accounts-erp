<?php

namespace App\Actions\Accounting;

use App\Models\Accounting\FixedAsset;
use App\Models\Accounting\FixedAssetDepreciation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Posts one month of straight-line depreciation for a fixed asset:
 * monthly = (purchase_cost - salvage_value) / useful_life_months, debited
 * to the depreciation expense account and credited to the accumulated
 * depreciation (contra-asset) account — never the asset account itself,
 * so original cost stays visible on the books. The final month is capped
 * to whatever's left of the depreciable base, so rounding across the
 * schedule never depreciates more than (purchase_cost - salvage_value)
 * in total.
 */
class PostDepreciation
{
    public function __construct(private PostJournal $postJournal) {}

    public function handle(FixedAsset $asset, string $periodDate, ?int $userId = null): FixedAssetDepreciation
    {
        if (! $asset->isActive()) {
            throw new RuntimeException('Only an active fixed asset can be depreciated.');
        }

        if ($asset->months_depreciated >= $asset->useful_life_months) {
            throw new RuntimeException('This asset is already fully depreciated.');
        }

        if ($asset->depreciations()->where('period_date', $periodDate)->exists()) {
            throw new RuntimeException('Depreciation has already been posted for this asset and period.');
        }

        return DB::transaction(function () use ($asset, $periodDate, $userId) {
            $depreciableBase = $asset->depreciableBase();
            $monthlyAmount = bcdiv($depreciableBase, (string) $asset->useful_life_months, 4);
            $remaining = bcsub($depreciableBase, $asset->accumulatedDepreciation(), 4);

            // The last scheduled month always posts whatever's left rather
            // than the standard monthly amount — equal installments almost
            // never divide the depreciable base evenly (e.g. 1000/3 =
            // 333.3333, and 3 x 333.3333 = 999.9999, short by 0.0001), so
            // the final month plugs that rounding remainder rather than
            // silently leaving 0.0001 of the asset never depreciated.
            $isLastScheduledMonth = ($asset->months_depreciated + 1) >= $asset->useful_life_months;
            $amount = ($isLastScheduledMonth || bccomp($monthlyAmount, $remaining, 4) > 0) ? $remaining : $monthlyAmount;

            if (bccomp($amount, '0', 4) <= 0) {
                throw new RuntimeException('Nothing left to depreciate for this asset.');
            }

            $depreciation = $asset->depreciations()->create([
                'period_date' => $periodDate,
                'amount' => $amount,
                'created_by' => $userId,
            ]);

            $this->postJournal->handle([
                'date' => $periodDate,
                'reference' => "DEP-{$asset->id}-{$depreciation->id}",
                'description' => "Depreciation for {$asset->name} — ".date('M Y', strtotime($periodDate)),
                'created_by' => $userId,
                'source_type' => FixedAssetDepreciation::class,
                'source_id' => $depreciation->id,
                'lines' => [
                    ['account_id' => $asset->depreciation_expense_account_id, 'debit' => $amount, 'credit' => 0, 'description' => "Depreciation — {$asset->name}"],
                    ['account_id' => $asset->accumulated_depreciation_account_id, 'debit' => 0, 'credit' => $amount, 'description' => "Depreciation — {$asset->name}"],
                ],
            ]);

            // accumulatedDepreciation() is a live query and already includes
            // the row just created above, so it must NOT be added to $amount
            // again here.
            $monthsDepreciated = $asset->months_depreciated + 1;
            $isFullyDepreciated = $monthsDepreciated >= $asset->useful_life_months
                || bccomp($asset->accumulatedDepreciation(), $depreciableBase, 4) >= 0;

            $asset->update([
                'months_depreciated' => $monthsDepreciated,
                'status' => $isFullyDepreciated ? 'fully_depreciated' : 'active',
            ]);

            return $depreciation->load('journal');
        });
    }
}
