<?php

namespace App\Http\Controllers\Banking;

use App\Actions\Banking\MakeBankDeposit;
use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Banking\StoreBankDepositRequest;
use App\Models\Accounting\Account;
use App\Models\Banking\BankDeposit;
use App\Models\Sales\Payment;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class BankDepositController extends Controller
{
    use FormatsPlainDates;

    public function index(): Response
    {
        $undepositedFundsAccountId = Account::where('is_undeposited_funds', true)->value('id');

        $undepositedPayments = $undepositedFundsAccountId
            ? Payment::query()
                ->where('deposit_account_id', $undepositedFundsAccountId)
                ->whereNull('bank_deposit_id')
                ->with('customer:id,name')
                ->orderBy('payment_date')
                ->get()
                ->map(fn (Payment $payment) => [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'customer' => ['name' => $payment->customer->name],
                    'payment_date' => $payment->payment_date->toDateString(),
                    'method' => $payment->method,
                    'amount' => (string) $payment->amount,
                ])
            : collect();

        $deposits = BankDeposit::query()
            ->with('bankAccount:id,code,name')
            ->orderByDesc('deposit_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (BankDeposit $deposit) => [
                'id' => $deposit->id,
                'deposit_number' => $deposit->deposit_number,
                'bank_account' => ['code' => $deposit->bankAccount->code, 'name' => $deposit->bankAccount->name],
                'deposit_date' => $deposit->deposit_date->toDateString(),
                'amount' => (string) $deposit->amount,
            ]);

        return Inertia::render('Banking/Deposits/Index', [
            'undepositedPayments' => $undepositedPayments,
            'deposits' => $deposits,
            'bankAccounts' => Account::query()->where('is_active', true)->where('is_bank_account', true)
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'hasUndepositedFundsAccount' => $undepositedFundsAccountId !== null,
        ]);
    }

    public function store(StoreBankDepositRequest $request, MakeBankDeposit $action): RedirectResponse
    {
        try {
            $deposit = $action->handle([
                ...$request->validated(),
                'created_by' => $request->user()->id,
            ]);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('banking.deposits.show', $deposit)->with('success', "Deposit {$deposit->deposit_number} recorded.");
    }

    public function show(BankDeposit $deposit): Response
    {
        $deposit->load([
            'bankAccount:id,code,name',
            'payments.customer:id,name',
            'journal',
        ]);

        $depositData = $this->withPlainDates($deposit, ['deposit_date']);
        $depositData['payments'] = $deposit->payments->map(fn (Payment $payment) => [
            'id' => $payment->id,
            'payment_number' => $payment->payment_number,
            'customer' => ['name' => $payment->customer->name],
            'payment_date' => $payment->payment_date->toDateString(),
            'amount' => (string) $payment->amount,
        ])->all();

        return Inertia::render('Banking/Deposits/Show', [
            'deposit' => $depositData,
        ]);
    }
}
