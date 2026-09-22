<?php

namespace App\Http\Controllers\Accounting;

use App\Actions\Accounting\PostJournal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreJournalRequest;
use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
        $journal->load(['entries.account', 'createdBy']);

        return Inertia::render('Accounting/Journals/Show', [
            'journal' => $journal,
        ]);
    }
}
