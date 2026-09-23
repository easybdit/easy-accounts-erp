<?php

namespace App\Http\Controllers\Tax;

use App\Http\Controllers\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Purchases\BillItem;
use App\Models\Sales\InvoiceItem;
use App\Models\Tax\TaxRate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Section 33's "Tax Reports" / Phase 9's "Tax Reporting foundation" —
 * derived entirely from already-posted Invoice/Bill item tax_amount
 * fields (Section 68: reports must use authoritative accounting data,
 * never a duplicated manual total).
 */
class TaxReportController extends Controller
{
    use ExportsCsv;

    public function index(Request $request): Response|StreamedResponse
    {
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString();

        $collected = InvoiceItem::query()
            ->whereNotNull('tax_rate_id')
            ->whereHas('invoice', function ($query) use ($from, $to) {
                $query->where('status', 'posted')
                    ->when($from, fn ($q) => $q->where('invoice_date', '>=', $from))
                    ->when($to, fn ($q) => $q->where('invoice_date', '<=', $to));
            })
            ->selectRaw('tax_rate_id, SUM(tax_amount) as total')
            ->groupBy('tax_rate_id')
            ->get()
            ->keyBy('tax_rate_id');

        $paid = BillItem::query()
            ->whereNotNull('tax_rate_id')
            ->whereHas('bill', function ($query) use ($from, $to) {
                $query->where('status', 'posted')
                    ->when($from, fn ($q) => $q->where('bill_date', '>=', $from))
                    ->when($to, fn ($q) => $q->where('bill_date', '<=', $to));
            })
            ->selectRaw('tax_rate_id, SUM(tax_amount) as total')
            ->groupBy('tax_rate_id')
            ->get()
            ->keyBy('tax_rate_id');

        $rows = TaxRate::query()->orderBy('name')->get()->map(function (TaxRate $rate) use ($collected, $paid) {
            $collectedAmount = bcadd((string) ($collected->get($rate->id)->total ?? 0), '0', 4);
            $paidAmount = bcadd((string) ($paid->get($rate->id)->total ?? 0), '0', 4);

            return [
                'id' => $rate->id,
                'name' => $rate->name,
                'rate' => (string) $rate->rate,
                'collected' => $collectedAmount,
                'paid' => $paidAmount,
                'net' => bcsub($collectedAmount, $paidAmount, 4),
            ];
        });

        $totalCollected = $rows->reduce(fn (string $carry, array $row) => bcadd($carry, $row['collected'], 4), '0.0000');
        $totalPaid = $rows->reduce(fn (string $carry, array $row) => bcadd($carry, $row['paid'], 4), '0.0000');
        $totalNet = $rows->reduce(fn (string $carry, array $row) => bcadd($carry, $row['net'], 4), '0.0000');

        if ($this->wantsCsv()) {
            return $this->csvResponse('tax-report.csv', ['Tax Rate', 'Rate %', 'Collected', 'Paid', 'Net'], [
                ...$rows->map(fn (array $r) => [$r['name'], $r['rate'], $r['collected'], $r['paid'], $r['net']]),
                ['Total', '', $totalCollected, $totalPaid, $totalNet],
            ]);
        }

        return Inertia::render('Tax/Report', [
            'rows' => $rows,
            'totalCollected' => $totalCollected,
            'totalPaid' => $totalPaid,
            'totalNet' => $totalNet,
            'from' => $from,
            'to' => $to,
        ]);
    }
}
