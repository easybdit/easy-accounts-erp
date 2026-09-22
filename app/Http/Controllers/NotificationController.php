<?php

namespace App\Http\Controllers;

use App\Models\Inventory\Product;
use App\Models\Purchases\Bill;
use App\Models\Sales\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Live-computed in-app alerts (Section 60: no notifications persisted by a
 * background scheduler — every request here re-derives the current overdue
 * invoices/bills and low-stock products from already-posted data, the same
 * bulk-query approach as the Reports module). Each category is only
 * included if the viewer actually has permission to see that module, so
 * this never leaks data the sidebar itself would hide.
 */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = now()->toDateString();
        $groups = [];

        if ($user->can('invoices.view')) {
            $overdue = Invoice::query()
                ->where('status', 'posted')
                ->whereNotNull('due_date')
                ->where('due_date', '<', $today)
                ->with('customer:id,name')
                ->withSum('paymentAllocations as amount_paid', 'amount')
                ->get()
                ->filter(fn (Invoice $invoice) => bccomp(bcsub((string) $invoice->total, (string) ($invoice->amount_paid ?? 0), 4), '0', 4) > 0)
                ->map(fn (Invoice $invoice) => [
                    'message' => "Invoice {$invoice->invoice_number} ({$invoice->customer->name}) is overdue",
                    'link' => route('sales.invoices.show', $invoice->id),
                ])
                ->values();

            if ($overdue->isNotEmpty()) {
                $groups[] = ['label' => 'Overdue Invoices', 'items' => $overdue];
            }
        }

        if ($user->can('bills.view')) {
            $overdue = Bill::query()
                ->where('status', 'posted')
                ->whereNotNull('due_date')
                ->where('due_date', '<', $today)
                ->with('vendor:id,name')
                ->withSum('paymentAllocations as amount_paid', 'amount')
                ->get()
                ->filter(fn (Bill $bill) => bccomp(bcsub((string) $bill->total, (string) ($bill->amount_paid ?? 0), 4), '0', 4) > 0)
                ->map(fn (Bill $bill) => [
                    'message' => "Bill {$bill->bill_number} ({$bill->vendor->name}) is overdue",
                    'link' => route('purchases.bills.show', $bill->id),
                ])
                ->values();

            if ($overdue->isNotEmpty()) {
                $groups[] = ['label' => 'Overdue Bills', 'items' => $overdue];
            }
        }

        if ($user->can('inventory.view')) {
            $lowStock = Product::query()
                ->where('is_active', true)
                ->where('type', 'inventory')
                ->whereNotNull('low_stock_threshold')
                ->get()
                ->filter(fn (Product $product) => $product->isLowStock())
                ->map(fn (Product $product) => [
                    'message' => "{$product->name} is low on stock ({$product->currentStock()} {$product->unit})",
                    'link' => route('inventory.products.show', $product->id),
                ])
                ->values();

            if ($lowStock->isNotEmpty()) {
                $groups[] = ['label' => 'Low Stock', 'items' => $lowStock];
            }
        }

        return response()->json([
            'groups' => $groups,
            'count' => collect($groups)->sum(fn (array $group) => $group['items']->count()),
        ]);
    }
}
