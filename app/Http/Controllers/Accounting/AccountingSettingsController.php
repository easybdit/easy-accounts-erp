<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\UpdateAccountingSettingsRequest;
use App\Http\Requests\Accounting\UpdateLoginSecuritySettingsRequest;
use App\Http\Requests\Accounting\UpdateLogoRequest;
use App\Http\Requests\Accounting\UpdateMailSettingsRequest;
use App\Http\Requests\Accounting\UpdatePaymentGatewaySettingsRequest;
use App\Models\Accounting\AccountingSettings;
use App\Models\Security\IpWhitelistEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AccountingSettingsController extends Controller
{
    use FormatsPlainDates;

    public function edit(): Response
    {
        $settings = AccountingSettings::current();
        $data = $this->withPlainDates($settings, ['locked_through_date']);

        // The 'encrypted' cast decrypts on access, so $settings->toArray()
        // (inside withPlainDates) would otherwise hand the plaintext
        // passwords straight to the browser. Replace each with a "is one
        // already saved?" flag instead — the edit form only ever writes a
        // new password, never reads the old one back.
        $data['mail_password'] = null;
        $data['mail_password_set'] = filled($settings->mail_password);
        $data['sslcommerz_store_password'] = null;
        $data['sslcommerz_store_password_set'] = filled($settings->sslcommerz_store_password);

        return Inertia::render('Accounting/Settings/Edit', [
            'settings' => $data,
            'ipWhitelistEntries' => IpWhitelistEntry::query()->with('creator:id,name')->latest()->get()
                ->map(fn (IpWhitelistEntry $entry) => [
                    'id' => $entry->id,
                    'ip_address' => $entry->ip_address,
                    'label' => $entry->label,
                    'created_by' => $entry->creator?->name,
                    'created_at' => $entry->created_at?->toDateTimeString(),
                ]),
            'currentIp' => request()->ip(),
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

    public function updateLogo(UpdateLogoRequest $request): RedirectResponse
    {
        $settings = AccountingSettings::current();

        if ($settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
        }

        $settings->update(['logo_path' => $request->file('logo')->store('logos', 'public')]);

        return back()->with('success', 'Logo updated.');
    }

    public function destroyLogo(): RedirectResponse
    {
        $settings = AccountingSettings::current();

        if ($settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
            $settings->update(['logo_path' => null]);
        }

        return back()->with('success', 'Logo removed.');
    }

    public function updateMail(UpdateMailSettingsRequest $request): RedirectResponse
    {
        $settings = AccountingSettings::current();
        $validated = $request->validated();

        // A blank password field means "keep the current one" — never
        // overwrite a saved password with an accidental empty submission.
        if (blank($validated['mail_password'] ?? null)) {
            unset($validated['mail_password']);
        }

        $settings->update($validated);

        return back()->with('success', 'Email settings saved.');
    }

    public function sendTestMail(Request $request): RedirectResponse
    {
        $request->validate(['test_email' => ['required', 'email']]);

        if (! AccountingSettings::current()->mailIsConfigured()) {
            return back()->with('error', 'Save your email settings before sending a test.');
        }

        try {
            Mail::raw(
                'This is a test email from '.config('app.name').". If you're reading this, your email settings are working.",
                fn ($message) => $message->to($request->string('test_email'))->subject('Test email — '.config('app.name'))
            );
        } catch (Throwable $e) {
            return back()->with('error', 'Could not send the test email: '.$e->getMessage());
        }

        return back()->with('success', "Test email sent to {$request->string('test_email')}.");
    }

    public function updatePaymentGateway(UpdatePaymentGatewaySettingsRequest $request): RedirectResponse
    {
        $settings = AccountingSettings::current();
        $validated = $request->validated();

        if (blank($validated['sslcommerz_store_password'] ?? null)) {
            unset($validated['sslcommerz_store_password']);
        }

        $settings->update($validated);

        return back()->with('success', 'Payment gateway settings saved.');
    }

    public function updateLoginSecurity(UpdateLoginSecuritySettingsRequest $request): RedirectResponse
    {
        AccountingSettings::current()->update($request->validated());

        return back()->with('success', 'Login security settings saved.');
    }

    public function updateIpWhitelistEnabled(Request $request): RedirectResponse
    {
        $request->validate(['ip_whitelist_enabled' => ['boolean']]);
        $enabled = $request->boolean('ip_whitelist_enabled');

        if ($enabled && ! IpWhitelistEntry::query()->exists()) {
            return back()->with('error', 'Add at least one IP address before enabling the whitelist, or every login — including your own — would be blocked.');
        }

        AccountingSettings::current()->update(['ip_whitelist_enabled' => $enabled]);

        return back()->with('success', $enabled ? 'IP whitelist enabled.' : 'IP whitelist disabled — logins are no longer restricted by IP.');
    }
}
