<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\SendLowStockAlert;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class LowStockAlertController extends Controller
{
    public function store(SendLowStockAlert $action): RedirectResponse
    {
        $count = $action->handle();

        return back()->with('success', $count > 0
            ? "Alert emailed for {$count} low-stock item(s)."
            : 'Nothing is currently low on stock — no alert sent.');
    }
}
