<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\UpdateAccountingSettingsRequest;
use App\Models\Accounting\AccountingSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AccountingSettingsController extends Controller
{
    use FormatsPlainDates;

    public function edit(): Response
    {
        return Inertia::render('Accounting/Settings/Edit', [
            'settings' => $this->withPlainDates(AccountingSettings::current(), ['locked_through_date']),
        ]);
    }

    public function update(UpdateAccountingSettingsRequest $request): RedirectResponse
    {
        $settings = AccountingSettings::current();
        $settings->update([
            'locked_through_date' => $request->validated('locked_through_date'),
            'locked_by' => $request->validated('locked_through_date') ? $request->user()->id : null,
        ]);

        return back()->with('success', $settings->locked_through_date
            ? "Period locked through {$settings->locked_through_date->toDateString()}."
            : 'Period lock removed — all dates are open for posting.');
    }
}
