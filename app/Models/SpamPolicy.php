<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A named filtering policy. Nodes pull the resolved thresholds with their domain
 * list and enforce them locally, so a panel outage never stops mail flowing.
 */
class SpamPolicy extends Model
{
    use Concerns\Auditable;

    protected $fillable = [
        'name', 'description', 'tag_level', 'tag2_level', 'kill_level',
        'block_foreign_charset', 'block_bulk', 'subject_block_keywords',
        'body_block_keywords', 'uri_allowlist', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'tag_level' => 'float',
            'tag2_level' => 'float',
            'kill_level' => 'float',
            'block_foreign_charset' => 'boolean',
            'block_bulk' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Exactly one default. Promoting a policy demotes the incumbent.
        static::saved(function (self $p) {
            if ($p->is_default) {
                static::where('id', '!=', $p->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function domains(): HasMany
    {
        return $this->hasMany(MailDomain::class);
    }

    /** Free-text keyword blocks are stored one per line. */
    public function subjectKeywords(): array
    {
        return $this->splitLines($this->subject_block_keywords);
    }

    public function bodyKeywords(): array
    {
        return $this->splitLines($this->body_block_keywords);
    }

    public function uriAllowlist(): array
    {
        return $this->splitLines($this->uri_allowlist);
    }

    private function splitLines(?string $value): array
    {
        if (! $value) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value))));
    }
}
