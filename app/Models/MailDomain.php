<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A filtered domain. Mail for it arrives at the MX nodes, is scored, and is either
 * held in quarantine or relayed on to destination_host.
 */
class MailDomain extends Model
{
    use Concerns\Auditable;

    protected $fillable = [
        'customer_id', 'spam_policy_id', 'name', 'destination_host', 'destination_port',
        'mx_status', 'tls_policy', 'recipient_mode', 'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'verified_at' => 'datetime',
            'destination_port' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $d) {
            $d->uuid ??= (string) Str::uuid();
            $d->verification_token ??= Str::random(32);
            $d->name = strtolower(trim($d->name));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(SpamPolicy::class, 'spam_policy_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(MailRecipient::class);
    }

    public function quarantine(): HasMany
    {
        return $this->hasMany(QuarantineMessage::class);
    }

    public function logEntries(): HasMany
    {
        return $this->hasMany(MailLogEntry::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(AllowBlockEntry::class);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * The policy actually enforced: the domain's own, else the system default.
     * Nodes get the resolved thresholds, never a null policy.
     */
    public function effectivePolicy(): ?SpamPolicy
    {
        return $this->policy ?: SpamPolicy::where('is_default', true)->first();
    }

    /** The TXT record value proving control of the domain. */
    public function verificationRecord(): string
    {
        return 'spammgr-verification=' . $this->verification_token;
    }
}
