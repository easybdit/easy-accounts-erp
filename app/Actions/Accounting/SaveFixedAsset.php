<?php

namespace App\Actions\Accounting;

use App\Models\Accounting\FixedAsset;
use RuntimeException;

/**
 * Creates or updates a fixed asset record. Once depreciation has started
 * (months_depreciated > 0), the cost/life fields that drive the monthly
 * depreciation calculation can no longer change — recalculating a running
 * schedule mid-stream would silently invalidate journals already posted
 * for prior months.
 */
class SaveFixedAsset
{
    public function handle(array $data, ?FixedAsset $asset = null): FixedAsset
    {
        if ($asset && $asset->months_depreciated > 0) {
            $locked = ['purchase_cost', 'salvage_value', 'useful_life_months', 'asset_account_id'];
            foreach ($locked as $field) {
                if (array_key_exists($field, $data) && (string) $data[$field] !== (string) $asset->{$field}) {
                    throw new RuntimeException('Cost, salvage value, useful life, and asset account cannot be changed after depreciation has started.');
                }
            }
        }

        $attributes = [
            'name' => $data['name'],
            'asset_account_id' => $data['asset_account_id'],
            'accumulated_depreciation_account_id' => $data['accumulated_depreciation_account_id'],
            'depreciation_expense_account_id' => $data['depreciation_expense_account_id'],
            'purchase_date' => $data['purchase_date'],
            'purchase_cost' => $data['purchase_cost'],
            'salvage_value' => $data['salvage_value'] ?? 0,
            'useful_life_months' => $data['useful_life_months'],
            'notes' => $data['notes'] ?? null,
        ];

        if ($asset) {
            $asset->update($attributes);

            return $asset;
        }

        return FixedAsset::create([
            ...$attributes,
            'status' => 'active',
            'months_depreciated' => 0,
            'created_by' => $data['created_by'] ?? null,
        ]);
    }
}
