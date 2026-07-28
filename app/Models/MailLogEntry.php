<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Every message the nodes handle, clean or not.
 *
 * This is the highest-volume table in the product and the one that fills a
 * customer's disk, so it stays lean and is pruned on a schedule. See
 * App\Console\Commands\PruneMailData.
 */
class MailLogEntry extends Model
{
    protected $fillable = [
        'mail_domain_id', 'node_id', 'message_id', 'sender', 'recipient', 'subject',
        'verdict', 'score', 'reason', 'delivery_status', 'delivery_detail',
        'attempts', 'delivered_at', 'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'attempts' => 'integer',
            'delivered_at' => 'datetime',
            'logged_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $e) {
            $e->logged_at ??= now();
        });
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(MailDomain::class, 'mail_domain_id');
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function isDeferred(): bool
    {
        return $this->delivery_status === 'deferred';
    }
}
