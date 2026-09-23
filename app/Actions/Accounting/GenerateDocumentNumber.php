<?php

namespace App\Actions\Accounting;

use App\Models\Accounting\AccountingSettings;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared numbering for every document type (Section 50): {prefix}-{year}-
 * {sequence:04d}, where the prefix is configurable in Settings (falling
 * back to self::DEFAULTS) but the format, year-reset behavior, and
 * count-based sequence are unchanged from what each document type
 * previously hardcoded in its own private nextXNumber() method.
 *
 * The unique constraint on each document's number column remains the real
 * guarantee against duplicates — this count-then-format approach is not
 * concurrency-safe, same caveat every prior implementation already carried.
 */
class GenerateDocumentNumber
{
    private const DEFAULTS = [
        'invoice' => 'INV',
        'estimate' => 'EST',
        'credit_note' => 'CN',
        'payment' => 'PAY',
        'bill' => 'BILL',
        'purchase_order' => 'PO',
        'vendor_credit' => 'VC',
        'vendor_payment' => 'VPAY',
        'expense' => 'EXP',
        'bank_deposit' => 'DEP',
        'transfer' => 'TRF',
        'sales_receipt' => 'SR',
    ];

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function handle(string $type, string $modelClass, string $column, string $date): string
    {
        $prefixes = AccountingSettings::current()->document_number_prefixes ?? [];
        $prefix = $prefixes[$type] ?? self::DEFAULTS[$type] ?? throw new \InvalidArgumentException("No default prefix registered for document type [{$type}].");

        $year = date('Y', strtotime($date));
        $count = $modelClass::where($column, 'like', "{$prefix}-{$year}-%")->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $year, $count);
    }
}
