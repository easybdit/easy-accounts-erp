<?php

namespace App\Http\Controllers\Sales;

use App\Actions\Sales\PostCreditNote;
use App\Actions\Sales\SaveCreditNoteDraft;
use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreCreditNoteRequest;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\CreditNote;
use App\Models\Tax\TaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class CreditNoteController extends Controller
{
    use FormatsPlainDates;

    public function index(Request $request): Response
    {
        $creditNotes = CreditNote::query()
            ->with('customer:id,name')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('credit_note_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('credit_note_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (CreditNote $creditNote) => [
                'id' => $creditNote->id,
                'credit_note_number' => $creditNote->credit_note_number,
                'customer' => ['id' => $creditNote->customer->id, 'name' => $creditNote->customer->name],
                'credit_note_date' => $creditNote->credit_note_date->toDateString(),
                'total' => (string) $creditNote->total,
                'status' => $creditNote->status,
            ]);

        return Inertia::render('Sales/CreditNotes/Index', [
            'creditNotes' => $creditNotes,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Sales/CreditNotes/Create', $this->formOptions());
    }

    public function store(StoreCreditNoteRequest $request, SaveCreditNoteDraft $action): RedirectResponse
    {
        $creditNote = $action->handle([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('sales.credit-notes.show', $creditNote)->with('success', 'Credit note saved as draft.');
    }

    public function edit(CreditNote $creditNote): Response
    {
        abort_unless($creditNote->isDraft(), 403, 'A posted credit note cannot be edited.');

        return Inertia::render('Sales/CreditNotes/Edit', [
            'creditNote' => $creditNote->load('items'),
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreCreditNoteRequest $request, CreditNote $creditNote, SaveCreditNoteDraft $action): RedirectResponse
    {
        abort_unless($creditNote->isDraft(), 403, 'A posted credit note cannot be edited.');

        $action->handle($request->validated(), $creditNote);

        return redirect()->route('sales.credit-notes.show', $creditNote)->with('success', 'Credit note updated.');
    }

    public function show(CreditNote $creditNote): Response
    {
        $creditNote->load([
            'customer:id,name',
            'receivableAccount:id,code,name',
            'invoice:id,invoice_number',
            'items.account:id,code,name',
            'items.taxRate:id,name,rate',
            'journal',
        ]);

        return Inertia::render('Sales/CreditNotes/Show', [
            'creditNote' => $this->withPlainDates($creditNote, ['credit_note_date']),
        ]);
    }

    public function destroy(CreditNote $creditNote): RedirectResponse
    {
        abort_unless($creditNote->isDraft(), 403, 'A posted credit note cannot be deleted.');

        $creditNote->delete();

        return redirect()->route('sales.credit-notes.index')->with('success', 'Credit note deleted.');
    }

    public function post(CreditNote $creditNote, PostCreditNote $action): RedirectResponse
    {
        try {
            $action->handle($creditNote);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.credit-notes.show', $creditNote)->with('success', 'Credit note posted.');
    }

    private function formOptions(): array
    {
        return [
            'customers' => Customer::query()->where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'receivableAccounts' => Account::query()->where('is_active', true)->where('type', 'asset')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'incomeAccounts' => Account::query()->where('is_active', true)->where('type', 'income')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'taxRates' => TaxRate::query()->where('is_active', true)->select('id', 'name', 'rate')->orderBy('name')->get(),
        ];
    }
}
