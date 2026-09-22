<?php

namespace App\Http\Controllers\Sales;

use App\Actions\Sales\RecognizeRevenue;
use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Models\Sales\RevenueRecognitionSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class RevenueRecognitionScheduleController extends Controller
{
    use FormatsPlainDates;

    public function index(Request $request): Response
    {
        $schedules = RevenueRecognitionSchedule::query()
            ->with('invoiceItem.invoice:id,invoice_number,customer_id', 'invoiceItem.invoice.customer:id,name')
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (RevenueRecognitionSchedule $schedule) => [
                'id' => $schedule->id,
                'invoice_number' => $schedule->invoiceItem->invoice->invoice_number,
                'invoice_id' => $schedule->invoiceItem->invoice->id,
                'customer' => $schedule->invoiceItem->invoice->customer->name,
                'description' => $schedule->invoiceItem->description,
                'total_amount' => (string) $schedule->total_amount,
                'recognized' => $schedule->recognizedTotal(),
                'months_recognized' => $schedule->months_recognized,
                'months_total' => $schedule->months_total,
                'next_period_date' => $schedule->next_period_date?->toDateString(),
                'status' => $schedule->status,
            ]);

        return Inertia::render('Sales/RevenueRecognition/Index', [
            'schedules' => $schedules,
            'filters' => $request->only(['status']),
        ]);
    }

    public function show(RevenueRecognitionSchedule $revenueRecognitionSchedule): Response
    {
        $revenueRecognitionSchedule->load([
            'invoiceItem.invoice:id,invoice_number,customer_id',
            'invoiceItem.invoice.customer:id,name',
            'deferredRevenueAccount:id,code,name',
            'incomeAccount:id,code,name',
            'entries' => fn ($query) => $query->orderByDesc('period_date'),
        ]);

        $scheduleData = $this->withPlainDates($revenueRecognitionSchedule, ['next_period_date']);
        $scheduleData['entries'] = $revenueRecognitionSchedule->entries->map(fn ($entry) => [
            'id' => $entry->id,
            'period_date' => $entry->period_date->toDateString(),
            'amount' => (string) $entry->amount,
        ])->all();

        return Inertia::render('Sales/RevenueRecognition/Show', [
            'schedule' => $scheduleData,
            'recognized' => $revenueRecognitionSchedule->recognizedTotal(),
            'remaining' => $revenueRecognitionSchedule->remaining(),
        ]);
    }

    public function recognize(RevenueRecognitionSchedule $revenueRecognitionSchedule, RecognizeRevenue $action): RedirectResponse
    {
        try {
            $action->handle($revenueRecognitionSchedule, request()->user()->id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.revenue-recognition.show', $revenueRecognitionSchedule)->with('success', 'Revenue recognized for this period.');
    }
}
