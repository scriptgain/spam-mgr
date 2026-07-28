<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One RBL lookup against one node's IP. A listed MX node silently loses relay to
 * some destinations, so this runs on a schedule rather than being discovered from
 * a customer complaint.
 */
class NodeBlacklistCheck extends Model
{
    protected $fillable = ['node_id', 'rbl', 'status', 'detail', 'checked_at'];

    protected function casts(): array
    {
        return ['checked_at' => 'datetime'];
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function isListed(): bool
    {
        return $this->status === 'listed';
    }
}
