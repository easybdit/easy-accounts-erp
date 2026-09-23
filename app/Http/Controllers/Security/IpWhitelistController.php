<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreIpWhitelistEntryRequest;
use App\Models\Accounting\AccountingSettings;
use App\Models\Security\IpWhitelistEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IpWhitelistController extends Controller
{
    public function store(StoreIpWhitelistEntryRequest $request): RedirectResponse
    {
        IpWhitelistEntry::create([
            'ip_address' => $request->string('ip_address')->trim()->toString(),
            'label' => $request->validated('label'),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'IP address added to the whitelist.');
    }

    public function destroy(IpWhitelistEntry $ip_whitelist_entry, Request $request): RedirectResponse
    {
        // The current request's own IP can't be removed while the
        // whitelist is enforced — deleting it here would either be a no-op
        // once the check runs again on the next request, or (worse, if
        // enforcement ever moves beyond login) lock the admin out
        // mid-session for no benefit.
        $settings = AccountingSettings::current();
        if ($settings->ip_whitelist_enabled && $ip_whitelist_entry->matches($request->ip())) {
            return back()->with('error', "You can't remove the entry matching your own current IP address while the whitelist is enabled.");
        }

        $ip_whitelist_entry->delete();

        return back()->with('success', 'IP address removed from the whitelist.');
    }
}
