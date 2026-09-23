<?php

namespace App\Http\Controllers\Banking;

use App\Actions\Banking\RecordTransfer;
use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Banking\StoreTransferRequest;
use App\Models\Accounting\Account;
use App\Models\Banking\Transfer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class TransferController extends Controller
{
    use FormatsPlainDates;

    public function index(Request $request): Response
    {
        $transfers = Transfer::query()
            ->with(['fromAccount:id,code,name', 'toAccount:id,code,name'])
            ->orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Transfer $transfer) => [
                'id' => $transfer->id,
                'transfer_number' => $transfer->transfer_number,
                'from_account' => ['code' => $transfer->fromAccount->code, 'name' => $transfer->fromAccount->name],
                'to_account' => ['code' => $transfer->toAccount->code, 'name' => $transfer->toAccount->name],
                'transfer_date' => $transfer->transfer_date->toDateString(),
                'amount' => (string) $transfer->amount,
            ]);

        return Inertia::render('Banking/Transfers/Index', [
            'transfers' => $transfers,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Banking/Transfers/Create', [
            'accounts' => $this->assetAccounts(),
        ]);
    }

    public function store(StoreTransferRequest $request, RecordTransfer $action): RedirectResponse
    {
        try {
            $transfer = $action->handle([
                ...$request->validated(),
                'created_by' => $request->user()->id,
            ]);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('banking.transfers.show', $transfer)->with('success', 'Transfer recorded.');
    }

    public function show(Transfer $transfer): Response
    {
        $transfer->load(['fromAccount:id,code,name', 'toAccount:id,code,name', 'journal']);

        return Inertia::render('Banking/Transfers/Show', [
            'transfer' => $this->withPlainDates($transfer, ['transfer_date']),
        ]);
    }

    private function assetAccounts()
    {
        return Account::query()->where('is_active', true)->where('type', 'asset')
            ->select('id', 'code', 'name')->orderBy('code')->get();
    }
}
