<?php

namespace App\Http\Controllers\Accounting;

use App\Actions\Accounting\DisposeFixedAsset;
use App\Actions\Accounting\PostDepreciation;
use App\Actions\Accounting\SaveFixedAsset;
use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreFixedAssetRequest;
use App\Models\Accounting\Account;
use App\Models\Accounting\FixedAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class FixedAssetController extends Controller
{
    use FormatsPlainDates;

    public function index(Request $request): Response
    {
        $assets = FixedAsset::query()
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (FixedAsset $asset) => [
                'id' => $asset->id,
                'name' => $asset->name,
                'purchase_date' => $asset->purchase_date->toDateString(),
                'purchase_cost' => (string) $asset->purchase_cost,
                'accumulated_depreciation' => $asset->accumulatedDepreciation(),
                'book_value' => $asset->bookValue(),
                'status' => $asset->status,
            ]);

        return Inertia::render('Accounting/FixedAssets/Index', [
            'assets' => $assets,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Accounting/FixedAssets/Create', $this->formOptions());
    }

    public function store(StoreFixedAssetRequest $request, SaveFixedAsset $action): RedirectResponse
    {
        $asset = $action->handle([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('accounting.fixed-assets.show', $asset)->with('success', 'Fixed asset added.');
    }

    public function edit(FixedAsset $fixedAsset): Response
    {
        return Inertia::render('Accounting/FixedAssets/Edit', [
            'asset' => $this->withPlainDates($fixedAsset, ['purchase_date']),
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreFixedAssetRequest $request, FixedAsset $fixedAsset, SaveFixedAsset $action): RedirectResponse
    {
        try {
            $action->handle($request->validated(), $fixedAsset);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('accounting.fixed-assets.show', $fixedAsset)->with('success', 'Fixed asset updated.');
    }

    public function show(FixedAsset $fixedAsset): Response
    {
        $fixedAsset->load([
            'assetAccount:id,code,name',
            'accumulatedDepreciationAccount:id,code,name',
            'depreciationExpenseAccount:id,code,name',
            'disposalProceedsAccount:id,code,name',
            'gainLossAccount:id,code,name',
            'disposalJournal',
            'depreciations' => fn ($query) => $query->orderByDesc('period_date'),
        ]);

        $assetData = $this->withPlainDates($fixedAsset, ['purchase_date']);
        $assetData['depreciations'] = $fixedAsset->depreciations->map(fn ($depreciation) => [
            'id' => $depreciation->id,
            'period_date' => $depreciation->period_date->toDateString(),
            'amount' => (string) $depreciation->amount,
        ])->all();

        return Inertia::render('Accounting/FixedAssets/Show', [
            'asset' => $assetData,
            'accumulatedDepreciation' => $fixedAsset->accumulatedDepreciation(),
            'bookValue' => $fixedAsset->bookValue(),
            'assetAccounts' => Account::query()->where('is_active', true)->where('type', 'asset')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'gainLossAccounts' => Account::query()->where('is_active', true)->whereIn('type', ['income', 'expense'])
                ->select('id', 'code', 'name', 'type')->orderBy('code')->get(),
        ]);
    }

    public function postDepreciation(FixedAsset $fixedAsset, PostDepreciation $action): RedirectResponse
    {
        try {
            $action->handle($fixedAsset, now()->startOfMonth()->toDateString(), request()->user()->id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('accounting.fixed-assets.show', $fixedAsset)->with('success', 'Depreciation posted for this month.');
    }

    public function destroy(FixedAsset $fixedAsset): RedirectResponse
    {
        if ($fixedAsset->months_depreciated > 0) {
            return back()->with('error', 'An asset with posted depreciation cannot be deleted — dispose it instead.');
        }

        $fixedAsset->delete();

        return redirect()->route('accounting.fixed-assets.index')->with('success', 'Fixed asset deleted.');
    }

    public function dispose(FixedAsset $fixedAsset, Request $request, DisposeFixedAsset $action): RedirectResponse
    {
        $validated = $request->validate([
            'disposal_proceeds' => ['nullable', 'numeric', 'min:0'],
            'disposal_proceeds_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'gain_loss_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'disposal_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! empty($validated['disposal_proceeds_account_id'])) {
            $proceedsAccount = Account::find($validated['disposal_proceeds_account_id']);
            if ($proceedsAccount && $proceedsAccount->type !== 'asset') {
                return back()->withErrors(['disposal_proceeds_account_id' => 'The proceeds account must be an asset account.'])->withInput();
            }
        }

        if (! empty($validated['gain_loss_account_id'])) {
            $gainLossAccount = Account::find($validated['gain_loss_account_id']);
            if ($gainLossAccount && ! in_array($gainLossAccount->type, ['income', 'expense'], true)) {
                return back()->withErrors(['gain_loss_account_id' => 'The gain/loss account must be an income or expense account.'])->withInput();
            }
        }

        try {
            $action->handle($fixedAsset, $validated, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('accounting.fixed-assets.show', $fixedAsset)->with('success', 'Asset disposed. No further depreciation will be posted.');
    }

    private function formOptions(): array
    {
        return [
            'assetAccounts' => Account::query()->where('is_active', true)->where('type', 'asset')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'expenseAccounts' => Account::query()->where('is_active', true)->where('type', 'expense')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
        ];
    }
}
