<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Who the operator filters mail for: an MSP's client, or a department in an
 * enterprise install. Portal users belong to one; operator admins belong to none
 * and see everything.
 */
class Customer extends Model
{
    use Concerns\Auditable;

    protected $fillable = ['name', 'contact_email', 'phone', 'notes', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $c) {
            $c->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(MailDomain::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(MailRecipient::class);
    }

    /** Mailboxes across every domain. Shown on the customer list. */
    public function mailboxCount(): int
    {
        return $this->recipients()->where('active', true)->count();
    }
}
