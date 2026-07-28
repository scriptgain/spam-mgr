<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * An MX filtering node. Run as many as you want: point mx1, mx2, ... at them and
 * every one reports into this panel.
 *
 * Each node enrols once and gets its own key. Both the one-time enrolment token
 * and the key are stored hashed, so a leak on one node never exposes the others.
 */
class Node extends Model
{
    use Concerns\Auditable;

    protected $fillable = ['hostname', 'ip', 'notes', 'active'];

    protected $hidden = ['api_key', 'enrollment_token'];

    /** No heartbeat for this long and the node is treated as stale. */
    public const STALE_AFTER_MINUTES = 15;

    protected function casts(): array
    {
        return [
            'postfix_ok' => 'boolean',
            'rspamd_ok' => 'boolean',
            'clamav_ok' => 'boolean',
            'active' => 'boolean',
            'load' => 'float',
            'last_seen_at' => 'datetime',
            'cert_expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $n) {
            $n->uuid ??= (string) Str::uuid();
            $n->hostname = strtolower(trim($n->hostname));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function blacklistChecks(): HasMany
    {
        return $this->hasMany(NodeBlacklistCheck::class);
    }

    public function quarantined(): HasMany
    {
        return $this->hasMany(QuarantineMessage::class);
    }

    /**
     * Issue a one-time enrolment token. Returns the plaintext, which is shown once
     * and pasted into the node installer.
     */
    public function issueEnrollmentToken(): string
    {
        $plain = 'spmenr_' . Str::random(40);
        $this->forceFill(['enrollment_token' => hash('sha256', $plain)])->save();

        return $plain;
    }

    /**
     * Trade a valid enrolment token for a permanent agent key. The token is
     * one-time: it is cleared here so a replayed installer cannot re-enrol.
     */
    public function completeEnrollment(): string
    {
        $plain = 'spmnod_' . Str::random(48);
        $this->forceFill([
            'api_key' => hash('sha256', $plain),
            'enrollment_token' => null,
            'status' => 'online',
            'last_seen_at' => now(),
        ])->save();

        return $plain;
    }

    public static function findByEnrollmentToken(string $plain): ?self
    {
        return static::where('enrollment_token', hash('sha256', $plain))->first();
    }

    public static function findByApiKey(string $plain): ?self
    {
        return static::where('api_key', hash('sha256', $plain))->where('active', true)->first();
    }

    /**
     * Health as displayed. A node that stopped reporting reads Stale rather than
     * keeping whatever status it last wrote, which is the failure an external port
     * check never catches.
     */
    public function displayStatus(): string
    {
        if (! $this->active) {
            return 'disabled';
        }
        if (! $this->last_seen_at) {
            return 'pending';
        }

        return $this->last_seen_at->lt(now()->subMinutes(self::STALE_AFTER_MINUTES))
            ? 'stale'
            : 'online';
    }

    /** True when every mail service the node depends on reported healthy. */
    public function servicesHealthy(): bool
    {
        return $this->postfix_ok && $this->rspamd_ok && $this->clamav_ok;
    }
}
