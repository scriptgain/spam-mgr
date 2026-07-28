<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Held mail. The body stays on the node that caught it; the panel holds the
 * metadata and the release decision.
 *
 * Release is a work queue the node polls, never a push, so the panel needs no
 * inbound access to any node.
 */
class QuarantineMessage extends Model
{
    protected $fillable = [
        'mail_domain_id', 'node_id', 'sender', 'recipient', 'subject',
        'spam_score', 'reason', 'verdict', 'quarantined_at', 'body_path',
    ];

    protected function casts(): array
    {
        return [
            'spam_score' => 'float',
            'quarantined_at' => 'datetime',
            'released_at' => 'datetime',
            'release_completed_at' => 'datetime',
            'purged_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            $m->uuid ??= (string) Str::uuid();
            $m->quarantined_at ??= now();
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

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * Marked for release but not yet confirmed delivered by a node. This is what
     * GET /api/agent/v1/releases hands out.
     */
    public function scopeAwaitingRelease(Builder $q): Builder
    {
        return $q->where('verdict', 'released')->whereNull('release_completed_at');
    }

    public function markReleased(): void
    {
        $this->update(['verdict' => 'released', 'released_at' => now()]);
    }

    public function shortSubject(int $limit = 60): string
    {
        return Str::limit($this->subject ?: '(no subject)', $limit);
    }
}
