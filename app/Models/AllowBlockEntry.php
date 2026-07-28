<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An allow or block rule. Scope widens as the foreign keys fall away: a
 * mail_recipient_id makes it a single-mailbox rule, a mail_domain_id a whole
 * domain, a customer_id everything that customer owns, and none of the three an
 * operator-wide rule.
 */
class AllowBlockEntry extends Model
{
    use Concerns\Auditable;

    protected $fillable = [
        'mail_domain_id', 'mail_recipient_id', 'customer_id',
        'type', 'value', 'list', 'notes',
    ];

    public const TYPES = ['sender', 'domain', 'ip'];

    public const LISTS = ['allow', 'block', 'spam_bypass', 'rbl_bypass'];

    protected static function booted(): void
    {
        static::creating(function (self $e) {
            // IPs and CIDR keep their case; addresses and domains do not.
            if ($e->type !== 'ip') {
                $e->value = strtolower(trim($e->value));
            }
        });
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(MailDomain::class, 'mail_domain_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(MailRecipient::class, 'mail_recipient_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** How wide this rule reaches, for display. Not a query scope, hence the name. */
    public function reachLabel(): string
    {
        return match (true) {
            $this->mail_recipient_id !== null => 'Mailbox',
            $this->mail_domain_id !== null => 'Domain',
            $this->customer_id !== null => 'Customer',
            default => 'Global',
        };
    }
}
