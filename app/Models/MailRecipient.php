<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A known-good address at a filtered domain. With the domain in 'list' recipient
 * mode the node rejects anything not in here at SMTP time, which stops dictionary
 * attacks before they cost bandwidth or scoring time.
 */
class MailRecipient extends Model
{
    use Concerns\Auditable;

    protected $fillable = [
        'mail_domain_id', 'customer_id', 'address', 'filtering_enabled', 'active',
    ];

    protected function casts(): array
    {
        return [
            'filtering_enabled' => 'boolean',
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $r) {
            $r->uuid ??= (string) Str::uuid();
            $r->address = strtolower(trim($r->address));
            // customer_id is denormalised so portal scoping never joins through domains.
            if (! $r->customer_id && $r->mail_domain_id) {
                $r->customer_id = MailDomain::find($r->mail_domain_id)?->customer_id;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(MailDomain::class, 'mail_domain_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(AllowBlockEntry::class);
    }

    /** The local part, for display next to the domain. */
    public function localPart(): string
    {
        return Str::before($this->address, '@');
    }
}
