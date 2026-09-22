<?php

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contacts\StoreVendorRequest;
use App\Models\Contacts\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorController extends Controller
{
    public function index(Request $request): Response
    {
        $vendors = Vendor::query()
            ->withSum('journalEntries as entries_debit', 'debit')
            ->withSum('journalEntries as entries_credit', 'credit')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Vendor $vendor) => [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'email' => $vendor->email,
                'phone' => $vendor->phone,
                'is_active' => $vendor->is_active,
                'current_balance' => bcsub(
                    bcadd((string) $vendor->opening_balance, (string) ($vendor->entries_credit ?? '0.0000'), 4),
                    (string) ($vendor->entries_debit ?? '0.0000'),
                    4
                ),
            ]);

        return Inertia::render('Contacts/Vendors/Index', [
            'vendors' => $vendors,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Contacts/Vendors/Create');
    }

    public function store(StoreVendorRequest $request): RedirectResponse
    {
        Vendor::create($request->validated());

        return redirect()->route('vendors.index')->with('success', 'Vendor created.');
    }

    public function edit(Vendor $vendor): Response
    {
        return Inertia::render('Contacts/Vendors/Edit', [
            'vendor' => $vendor,
        ]);
    }

    public function update(StoreVendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $vendor->update($request->validated());

        return redirect()->route('vendors.index')->with('success', 'Vendor updated.');
    }

    public function show(Vendor $vendor): Response
    {
        $entries = $vendor->journalEntries()
            ->with('journal:id,reference,description', 'account:id,code,name')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $running = (string) $vendor->opening_balance;

        $rows = $entries->map(function ($entry) use (&$running) {
            $running = bcsub(bcadd($running, (string) $entry->credit, 4), (string) $entry->debit, 4);

            return [
                'id' => $entry->id,
                'date' => $entry->date->toDateString(),
                'account' => $entry->account->code.' — '.$entry->account->name,
                'reference' => $entry->journal->reference,
                'description' => $entry->description ?? $entry->journal->description,
                'debit' => (string) $entry->debit,
                'credit' => (string) $entry->credit,
                'running_balance' => $running,
                'journal_id' => $entry->journal_id,
            ];
        });

        return Inertia::render('Contacts/Vendors/Show', [
            'vendor' => $vendor,
            'openingBalance' => (string) $vendor->opening_balance,
            'currentBalance' => $vendor->currentBalance(),
            'entries' => $rows,
        ]);
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        if ($vendor->journalEntries()->exists()) {
            return back()->withErrors([
                'vendor' => 'This vendor has transaction history and cannot be deleted.',
            ]);
        }

        $vendor->delete();

        return redirect()->route('vendors.index')->with('success', 'Vendor deleted.');
    }
}
