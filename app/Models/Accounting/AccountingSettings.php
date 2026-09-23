<?php

namespace App\Models\Accounting;

use App\Models\Security\IpWhitelistEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A singleton settings row (always id=1): Period Lock, the company logo,
 * mail (SMTP) and SSLCommerz overrides applied at runtime on top of .env
 * (see AppServiceProvider::boot()), and login-security policy (account
 * lockout thresholds, math captcha toggle).
 */
class AccountingSettings extends Model
{
    use LogsActivity;

    protected $fillable = [
        'locked_through_date',
        'locked_by',
        'logo_path',
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'sslcommerz_enabled',
        'sslcommerz_store_id',
        'sslcommerz_store_password',
        'sslcommerz_sandbox',
        'sslcommerz_currency',
        'login_max_attempts',
        'login_lockout_minutes',
        'login_captcha_enabled',
        'ip_whitelist_enabled',
        'document_number_prefixes',
    ];

    protected $casts = [
        'locked_through_date' => 'date',
        'mail_port' => 'integer',
        // Encrypted at rest: neither is ever the audit log's problem, since
        // both are excluded from getActivitylogOptions()'s logOnly() below.
        'mail_password' => 'encrypted',
        'sslcommerz_store_password' => 'encrypted',
        'sslcommerz_enabled' => 'boolean',
        'sslcommerz_sandbox' => 'boolean',
        'login_captcha_enabled' => 'boolean',
        'ip_whitelist_enabled' => 'boolean',
        'document_number_prefixes' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'locked_through_date', 'locked_by', 'logo_path',
                'mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_encryption',
                'mail_from_address', 'mail_from_name',
                'sslcommerz_enabled', 'sslcommerz_store_id', 'sslcommerz_sandbox', 'sslcommerz_currency',
                'login_max_attempts', 'login_lockout_minutes', 'login_captcha_enabled', 'ip_whitelist_enabled',
                'document_number_prefixes',
                // mail_password / sslcommerz_store_password intentionally excluded.
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function mailIsConfigured(): bool
    {
        return filled($this->mail_host);
    }

    public function sslcommerzIsConfigured(): bool
    {
        return $this->sslcommerz_enabled && filled($this->sslcommerz_store_id) && filled($this->sslcommerz_store_password);
    }

    /**
     * True when the whitelist is off (nothing to enforce) or the IP matches
     * one of its entries. Fails open on a disabled/empty list on purpose —
     * an admin turning this on with zero entries would otherwise lock
     * everyone out, themselves included, with no way back in short of
     * direct database access.
     */
    public function ipIsWhitelisted(string $ip): bool
    {
        if (! $this->ip_whitelist_enabled) {
            return true;
        }

        $entries = IpWhitelistEntry::all();

        if ($entries->isEmpty()) {
            return true;
        }

        return $entries->contains(fn (IpWhitelistEntry $entry) => $entry->matches($ip));
    }

    public static function current(): self
    {
        $settings = self::firstOrCreate(['id' => 1]);

        // Eloquent's create() only populates attributes it was explicitly
        // given — it never re-SELECTs the row afterward, so on the very
        // first call ever (a fresh install/test DB), the boolean/default
        // columns this class relies on (sslcommerz_sandbox,
        // login_captcha_enabled, ...) would read as null in memory despite
        // the database itself holding their real DEFAULT values.
        // wasRecentlyCreated makes this a one-time cost, not a
        // per-request one.
        return $settings->wasRecentlyCreated ? $settings->fresh() : $settings;
    }
}
