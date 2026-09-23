<?php

namespace App\Http\Controllers\Concerns;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Every report page (ReportController, TrialBalanceController,
 * GeneralLedgerController, TaxReportController) can hand its already-
 * computed rows to this instead of Inertia::render when the request asks
 * for a CSV — a plain CSV (not a binary .xlsx) needs no new dependency and
 * opens directly in Excel/Sheets, which is what "export this report"
 * actually means for every reader who isn't Claude.
 */
trait ExportsCsv
{
    /**
     * @param  string[]  $header
     * @param  iterable<int, array<int, scalar|null>>  $rows
     */
    protected function csvResponse(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function wantsCsv(): bool
    {
        return request()->boolean('export');
    }
}
