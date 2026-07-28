<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use App\Models\SpamPolicy;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SpamBootstrap extends Command
{
    protected $signature = 'spam:bootstrap {--fresh-token : Issue a new full-access token even if one exists}';

    protected $description = 'Seed the default filtering policies and issue a first full-access API token.';

    /**
     * Shipped policies. A fresh install can accept mail immediately instead of
     * making the operator invent thresholds before anything works.
     *
     * Scores are Rspamd's: tag adds a header, tag2 rewrites the subject, kill
     * quarantines. Standard is deliberately the default because an aggressive
     * out-of-box policy quarantines real mail and costs trust on day one.
     */
    private const POLICIES = [
        ['Permissive', 'Tags generously but quarantines little. For domains where a missed spam costs less than a held invoice.', 6.0, 10.0, 15.0, false],
        ['Standard', 'Balanced defaults. Suits most domains.', 5.0, 8.0, 12.0, true],
        ['Aggressive', 'Quarantines earlier. Expect to check the quarantine regularly.', 4.0, 6.0, 9.0, false],
    ];

    public function handle(): int
    {
        foreach (self::POLICIES as [$name, $description, $tag, $tag2, $kill, $isDefault]) {
            SpamPolicy::firstOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'tag_level' => $tag,
                    'tag2_level' => $tag2,
                    'kill_level' => $kill,
                    'is_default' => $isDefault,
                ]
            );
        }
        $this->info('Filtering policies seeded (' . count(self::POLICIES) . ').');

        $user = User::orderBy('id')->first();
        if (! $user) {
            $this->warn('No user yet; create the admin user, then re-run `spam:bootstrap` to issue the API token.');

            return self::SUCCESS;
        }

        if ($this->option('fresh-token') || ApiToken::count() === 0) {
            [$token, $plain] = ApiToken::issue($user, 'bootstrap-full-access');
            Storage::disk('local')->put('bootstrap-token.txt', $plain . "\n");
            $this->info('Full-access API token written to storage/app/private/bootstrap-token.txt');
        } else {
            $this->line('API token already exists; use --fresh-token to issue another.');
        }

        return self::SUCCESS;
    }
}
