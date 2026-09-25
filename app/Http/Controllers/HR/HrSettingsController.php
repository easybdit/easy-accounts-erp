<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\UpdateHrSettingsRequest;
use App\Models\HR\HrSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HrSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('HR/Settings/Edit', [
            'settings' => HrSettings::current(),
        ]);
    }

    public function update(UpdateHrSettingsRequest $request): RedirectResponse
    {
        HrSettings::current()->update($request->validated());

        return redirect()->route('hr.settings.edit')->with('success', 'HR settings updated.');
    }
}
