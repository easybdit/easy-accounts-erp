<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreShiftRequest;
use Easybdit\LaravelEasyAttendance\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ShiftController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('HR/Shifts/Index', [
            'shifts' => Shift::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HR/Shifts/Create');
    }

    public function store(StoreShiftRequest $request): RedirectResponse
    {
        Shift::create($request->validated());

        return redirect()->route('hr.shifts.index')->with('success', 'Shift created.');
    }

    public function edit(Shift $shift): Response
    {
        return Inertia::render('HR/Shifts/Edit', ['shift' => $shift]);
    }

    public function update(StoreShiftRequest $request, Shift $shift): RedirectResponse
    {
        $shift->update($request->validated());

        return redirect()->route('hr.shifts.index')->with('success', 'Shift updated.');
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        if ($shift->assignments()->exists()) {
            return back()->with('error', 'This shift has employees assigned and cannot be deleted.');
        }

        $shift->delete();

        return redirect()->route('hr.shifts.index')->with('success', 'Shift deleted.');
    }
}
