<?php

namespace App\Http\Controllers\Accounting;

use App\Actions\Accounting\PostJournal;
use App\Actions\Accounting\VoidJournal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreJournalRequest;
use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\Contacts\Customer;
use App\Models\Contacts\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class JournalController extends Controller
{
    public function index(Request $request): Response
    {
        $journals = Journal::query()
            ->withSum('entries as total_debit', 'debit')
            ->withSum('entries as total_credit', 'credit')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('reference', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Accounting/Journals/Index', [
            'journals' => $journals,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Accounting/Journals/Create', [
            'accounts' => Account::query()
                ->where('is_active', true)
                ->select('id', 'code', 'name', 'type')
                ->orderBy('code')
                ->get(),
            'customers' => Customer::query()->where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'vendors' => Vendor::query()->where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreJournalRequest $request, PostJournal $postJournal): RedirectResponse
    {
        $journal = $postJournal->handle([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('accounting.journals.show', $journal)
            ->with('success', 'Journal posted.');
    }

    public function show(Journal $journal): Response
    {
        $journal->load(['entries.account', 'createdBy', 'reversalOfJournal:id,reference', 'reversalJournal:id,reference']);

        return Inertia::render('Accounting/Journals/Show', [
            'journal' => $journal,
            'canBeVoided' => $journal->canBeVoided(),
        ]);
    }

    public function void(Journal $journal, VoidJournal $action): RedirectResponse
    {
        try {
            $action->handle($journal, request()->user()->id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('accounting.journals.show', $journal)->with('success', 'Journal voided — a reversing journal was posted.');
    }
}
