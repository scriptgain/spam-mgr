<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Held mail. The message body stays on the node that caught it (body_path); the
 * panel holds the metadata and the release decision. Release is a work queue the
 * node polls, not a push, so the panel never needs inbound access to a node.
 *
 * Consolidates SpamNinja's quarantine_messages plus its later release-tracking
 * columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quarantine_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('mail_domain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('node_id')->nullable()->constrained()->nullOnDelete();

            $table->string('sender')->nullable()->index();
            $table->string('recipient')->nullable()->index();
            $table->string('subject')->nullable();
            $table->decimal('spam_score', 5, 1)->nullable();
            $table->string('reason')->nullable(); // rule / verdict summary
            $table->string('verdict', 20)->default('quarantined')->index(); // quarantined|released|deleted

            $table->timestamp('quarantined_at')->nullable()->index();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('release_completed_at')->nullable();
            $table->text('release_error')->nullable();
            $table->unsignedSmallInteger('release_attempts')->default(0);
            $table->timestamp('purged_at')->nullable();

            $table->string('body_path')->nullable(); // path on the holding node
            $table->timestamps();

            // The node's release work queue reads exactly this pair.
            $table->index(['verdict', 'release_completed_at'], 'qm_release_pending_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quarantine_messages');
    }
};
