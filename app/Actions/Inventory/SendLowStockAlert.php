<?php

namespace App\Actions\Inventory;

use App\Mail\LowStockAlertMail;
use App\Models\Inventory\Product;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * A single digest email (not one email per product) listing every
 * inventory-tracked product at or below its reorder level, sent to every
 * user who can manage inventory (Spatie's scopePermission resolves this
 * through roles too, e.g. the Inventory role) — no separate "notification
 * recipient" setting needed since the permission model already answers
 * "who cares about this".
 */
class SendLowStockAlert
{
    public function handle(): int
    {
        $lowStockProducts = Product::query()
            ->where('type', 'inventory')
            ->where('is_active', true)
            ->get()
            ->filter(fn (Product $product) => $product->isLowStock())
            ->values();

        if ($lowStockProducts->isEmpty()) {
            return 0;
        }

        $recipients = User::permission('inventory.manage')->pluck('email');

        if ($recipients->isEmpty()) {
            return 0;
        }

        Mail::to($recipients)->send(new LowStockAlertMail($lowStockProducts));

        return $lowStockProducts->count();
    }
}
