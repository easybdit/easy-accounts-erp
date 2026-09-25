<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreHolidayRequest;
use Easybdit\LaravelEasyAttendance\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HolidayController extends Controller
{
    use FormatsPlainDates;

    public function index(): Response
    {
        return Inertia::render('HR/Holidays/Index', [
            'holidays' => Holiday::query()->orderBy('date')->get()
                ->map(fn (Holiday $holiday) => $this->withPlainDates($holiday, ['date'])),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HR/Holidays/Create');
    }

    public function store(StoreHolidayRequest $request): RedirectResponse
    {
        Holiday::create($request->validated());

        return redirect()->route('hr.holidays.index')->with('success', 'Holiday created.');
    }

    public function edit(Holiday $holiday): Response
    {
        return Inertia::render('HR/Holidays/Edit', ['holiday' => $this->withPlainDates($holiday, ['date'])]);
    }

    public function update(StoreHolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        $holiday->update($request->validated());

        return redirect()->route('hr.holidays.index')->with('success', 'Holiday updated.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return redirect()->route('hr.holidays.index')->with('success', 'Holiday deleted.');
    }
}
