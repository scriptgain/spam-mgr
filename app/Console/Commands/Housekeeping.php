<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\MailLogEntry;
use App\Models\NodeBlacklistCheck;
use App\Models\QuarantineMessage;
use App\Models\Setting;
use Illuminate\Console\Command;

/**
 * Retention. This is not optional maintenance.
 *
 * mail_log_entries grows by one row per message handled, forever. On the vendor's
 * own infrastructure an unbounded log is a manual DELETE when disk gets tight; on
 * a customer's server it is their disk filling at 3am and becoming a support call.
 * So every high-volume table gets an age limit out of the box.
 *
 * Deletes run in bounded chunks: a single DELETE across months of mail on a busy
 * gateway locks the table long enough to back up the ingest endpoint.
 */
class Housekeeping extends Command
{
    protected $signature = 'spam:housekeeping {--dry-run : Report what would be pruned without deleting}';

    protected $description = 'Prune mail logs, released and deleted quarantine, blacklist checks and audit rows per the retention settings.';

    /** Rows per DELETE, so a long prune never holds a table lock. */
    private const CHUNK = 2000;

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $mailLogDays = (int) Setting::get('retention_mail_log_days', 30);
        $quarantineDays = (int) Setting::get('retention_quarantine_days', 30);
        $blacklistDays = (int) Setting::get('retention_blacklist_days', 90);
        $auditDays = (int) Setting::get('retention_audit_days', 180);

        $total = 0;

        $total += $this->prune(
            'mail log entries',
            fn () => MailLogEntry::where('logged_at', '<', now()->subDays($mailLogDays)),
            $mailLogDays,
            $dry
        );

        // Only settled quarantine is prunable. Mail still sitting at 'quarantined'
        // is mail nobody has looked at yet, and deleting it silently would lose a
        // message the recipient never got the chance to release.
        $total += $this->prune(
            'released/deleted quarantine messages',
            fn () => QuarantineMessage::whereIn('verdict', ['released', 'deleted'])
                ->where('quarantined_at', '<', now()->subDays($quarantineDays)),
            $quarantineDays,
            $dry
        );

        $total += $this->prune(
            'blacklist checks',
            fn () => NodeBlacklistCheck::where('checked_at', '<', now()->subDays($blacklistDays)),
            $blacklistDays,
            $dry
        );

        $total += $this->prune(
            'audit rows',
            fn () => AuditLog::where('created_at', '<', now()->subDays($auditDays)),
            $auditDays,
            $dry
        );

        $this->info(($dry ? 'Would prune ' : 'Pruned ') . $total . ' row(s) in total.');

        return self::SUCCESS;
    }

    /**
     * @param  callable():\Illuminate\Database\Eloquent\Builder  $query
     */
    private function prune(string $label, callable $query, int $days, bool $dry): int
    {
        if ($days <= 0) {
            $this->line("Skipping {$label}: retention disabled.");

            return 0;
        }

        if ($dry) {
            $count = $query()->count();
            $this->line("Would prune {$count} {$label} older than {$days} day(s).");

            return $count;
        }

        $deleted = 0;
        do {
            $chunk = $query()->limit(self::CHUNK)->delete();
            $deleted += $chunk;
        } while ($chunk === self::CHUNK);

        $this->line("Pruned {$deleted} {$label} older than {$days} day(s).");

        return $deleted;
    }
}
