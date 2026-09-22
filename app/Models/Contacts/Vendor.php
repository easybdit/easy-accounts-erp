<?php

namespace App\Models\Contacts;

use App\Models\Accounting\JournalEntry;
use Database\Factories\Contacts\VendorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'billing_address',
        'opening_balance',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): VendorFactory
    {
        return VendorFactory::new();
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * A vendor's balance is inherently credit-normal (an amount owed BY the
     * business), independent of which GL account a tagged line used.
     */
    public function currentBalance(): string
    {
        $row = $this->journalEntries()
            ->selectRaw('COALESCE(SUM(debit), 0) as debit, COALESCE(SUM(credit), 0) as credit')
            ->first();

        $debit = (string) ($row->debit ?? '0.0000');
        $credit = (string) ($row->credit ?? '0.0000');

        return bcsub(bcadd((string) $this->opening_balance, $credit, 4), $debit, 4);
    }
}
