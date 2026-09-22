<?php

namespace App\Actions\Sales;

use App\Actions\Accounting\PostJournal;
use App\Models\Sales\RevenueRecognitionEntry;
use App\Models\Sales\RevenueRecognitionSchedule;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Posts one period of deferred revenue recognition: debit the deferred
 * revenue (liability) account, credit the income account — the exact
 * reverse of what PostInvoice posted for this line at invoice time. The
 * final scheduled month always posts whatever's left of total_amount
 * rather than the standard monthly amount, since equal installments
 * almost never divide the total evenly (same reasoning as
 * PostDepreciation's final-month cap).
 */
class RecognizeRevenue
{
    public function __construct(private PostJournal $postJournal) {}

    public function handle(RevenueRecognitionSchedule $schedule, ?int $userId = null): RevenueRecognitionEntry
    {
        if (! $schedule->isActive()) {
            throw new RuntimeException('This revenue recognition schedule is not active.');
        }

        if ($schedule->next_period_date === null) {
            throw new RuntimeException('This schedule has no period due.');
        }

        return DB::transaction(function () use ($schedule, $userId) {
            $monthlyAmount = bcdiv((string) $schedule->total_amount, (string) $schedule->months_total, 4);
            $remaining = $schedule->remaining();
            $isLastMonth = ($schedule->months_recognized + 1) >= $schedule->months_total;
            $amount = ($isLastMonth || bccomp($monthlyAmount, $remaining, 4) > 0) ? $remaining : $monthlyAmount;

            if (bccomp($amount, '0', 4) <= 0) {
                throw new RuntimeException('Nothing left to recognize for this schedule.');
            }

            $periodDate = $schedule->next_period_date->toDateString();

            $entry = $schedule->entries()->create([
                'period_date' => $periodDate,
                'amount' => $amount,
                'created_by' => $userId,
            ]);

            $this->postJournal->handle([
                'date' => $periodDate,
                'reference' => "REV-{$schedule->id}-{$entry->id}",
                'description' => 'Revenue recognition — '.date('M Y', strtotime($periodDate)),
                'created_by' => $userId,
                'source_type' => RevenueRecognitionEntry::class,
                'source_id' => $entry->id,
                'lines' => [
                    ['account_id' => $schedule->deferred_revenue_account_id, 'debit' => $amount, 'credit' => 0, 'description' => 'Revenue recognition'],
                    ['account_id' => $schedule->income_account_id, 'debit' => 0, 'credit' => $amount, 'description' => 'Revenue recognition'],
                ],
            ]);

            // recognizedTotal() is a live query and already includes the
            // entry just created above, so it must NOT be added to $amount
            // again here (same pitfall as PostDepreciation).
            $monthsRecognized = $schedule->months_recognized + 1;
            $isComplete = $monthsRecognized >= $schedule->months_total
                || bccomp($schedule->recognizedTotal(), $schedule->total_amount, 4) >= 0;

            $schedule->update([
                'months_recognized' => $monthsRecognized,
                'next_period_date' => $isComplete ? null : $schedule->next_period_date->copy()->addMonthNoOverflow(),
                'status' => $isComplete ? 'completed' : 'active',
            ]);

            return $entry->load('journal');
        });
    }
}
