<?php

namespace App\Models\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class IpWhitelistEntry extends Model
{
    use LogsActivity;

    protected $fillable = ['ip_address', 'label', 'created_by'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['ip_address', 'label'])->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Matches a single IP ("203.0.113.7") or CIDR range ("203.0.113.0/24").
     * IPv4 only — this app has no IPv6-specific deployment requirement, and
     * silently failing closed (no match) on anything else is the safe
     * default for an allowlist.
     */
    public function matches(string $ip): bool
    {
        if (! str_contains($this->ip_address, '/')) {
            return $this->ip_address === $ip;
        }

        [$subnet, $bits] = explode('/', $this->ip_address, 2);
        $bits = (int) $bits;

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false || $bits < 0 || $bits > 32) {
            return false;
        }

        $mask = $bits === 0 ? 0 : (-1 << (32 - $bits));

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
