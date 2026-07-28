<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * An MX filtering node. The operator runs as many as they want; each enrolls once,
 * gets its own key, and thereafter polls the panel for its domain list and release
 * queue. Nodes are stateless workers, the panel is the single control plane.
 *
 * Auth is per node, unlike SpamNinja's single shared INGEST_TOKEN: one leaked
 * credential there exposed every node and rotating it meant touching every box.
 * enrollment_token and api_key are both stored hashed, matching the scaffold's
 * agent enrolment.
 *
 * The health columns exist because an external port check only proves Postfix
 * answers on :25. It stays green while ClamAV is dead, the queue is backing up,
 * the cert has expired or the disk is full, and mail rots with nothing to show it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('hostname')->unique(); // the node's MX FQDN
            $table->string('ip', 45)->nullable();

            // Enrolment + auth.
            $table->string('api_key')->nullable();          // hashed
            $table->string('enrollment_token')->nullable();  // hashed, one-time
            $table->string('agent_version', 40)->nullable();

            // Staleness is the signal that matters most: a node that stops
            // reporting looks identical to a healthy one unless we track this.
            $table->timestamp('last_seen_at')->nullable()->index();

            $table->boolean('postfix_ok')->default(false);
            $table->boolean('rspamd_ok')->default(false);
            $table->boolean('clamav_ok')->default(false);

            $table->unsignedInteger('queue_depth')->default(0);
            $table->unsignedTinyInteger('disk_percent')->default(0);
            $table->decimal('load', 6, 2)->default(0);
            $table->timestamp('cert_expires_at')->nullable();

            // Reported with the heartbeat. Not metered or enforced: unlimited nodes
            // is the whole pitch. Recorded so a future volume tier is a pricing
            // decision rather than a guess.
            $table->unsignedInteger('domain_count')->default(0);
            $table->unsignedInteger('mailbox_count')->default(0);

            $table->string('status', 20)->default('pending')->index(); // pending|online|offline|stale
            $table->boolean('active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
