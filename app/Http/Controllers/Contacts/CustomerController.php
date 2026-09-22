<?php

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contacts\StoreCustomerRequest;
use App\Models\Contacts\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $customers = Customer::query()
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
            ->through(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'is_active' => $customer->is_active,
                'current_balance' => bcsub(
                    bcadd((string) $customer->opening_balance, (string) ($customer->entries_debit ?? '0.0000'), 4),
                    (string) ($customer->entries_credit ?? '0.0000'),
                    4
                ),
            ]);

        return Inertia::render('Contacts/Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Contacts/Customers/Create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        return redirect()->route('customers.index')->with('success', 'Customer created.');
    }

    public function edit(Customer $customer): Response
    {
        return Inertia::render('Contacts/Customers/Edit', [
            'customer' => $customer,
        ]);
    }

    public function update(StoreCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('customers.index')->with('success', 'Customer updated.');
    }

    public function show(Customer $customer): Response
    {
        $entries = $customer->journalEntries()
            ->with('journal:id,reference,description', 'account:id,code,name')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $running = (string) $customer->opening_balance;

        $rows = $entries->map(function ($entry) use (&$running) {
            $running = bcsub(bcadd($running, (string) $entry->debit, 4), (string) $entry->credit, 4);

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

        return Inertia::render('Contacts/Customers/Show', [
            'customer' => $customer,
            'openingBalance' => (string) $customer->opening_balance,
            'currentBalance' => $customer->currentBalance(),
            'entries' => $rows,
        ]);
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->journalEntries()->exists()) {
            return back()->withErrors([
                'customer' => 'This customer has transaction history and cannot be deleted.',
            ]);
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }
}
