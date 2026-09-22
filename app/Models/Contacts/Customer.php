<?php

namespace App\Models\Contacts;

use App\Models\Accounting\JournalEntry;
use Database\Factories\Contacts\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
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

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * A customer's balance is inherently debit-normal (an amount owed TO the
     * business), independent of which GL account a tagged line used.
     */
    public function currentBalance(): string
    {
        $row = $this->journalEntries()
            ->selectRaw('COALESCE(SUM(debit), 0) as debit, COALESCE(SUM(credit), 0) as credit')
            ->first();

        $debit = (string) ($row->debit ?? '0.0000');
        $credit = (string) ($row->credit ?? '0.0000');

        return bcsub(bcadd((string) $this->opening_balance, $debit, 4), $credit, 4);
    }
}
