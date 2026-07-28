<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Every message the nodes handle, clean or not. This is the highest-volume table
 * in the product by a wide margin and the one that fills a customer's disk, so it
 * is written lean and pruned on a schedule (see the retention setting).
 *
 * Consolidates SpamNinja's mail_log_entries plus its later delivery-status columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_log_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_domain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('node_id')->nullable()->constrained()->nullOnDelete();
            $table->string('message_id')->nullable()->index();

            $table->string('sender')->nullable()->index();
            $table->string('recipient')->nullable()->index();
            $table->string('subject')->nullable();
            $table->string('verdict', 20)->index(); // clean|tagged|quarantined|rejected
            $table->decimal('score', 5, 1)->nullable();
            $table->string('reason')->nullable();

            $table->string('delivery_status', 20)->default('pending')->index(); // pending|delivered|deferred|failed
            $table->string('delivery_detail', 512)->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('delivered_at')->nullable();

            $table->timestamp('logged_at')->nullable()->index();
            $table->timestamps();

            // Retention prunes by age within a domain; the log screen reads the same way.
            $table->index(['mail_domain_id', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_log_entries');
    }
};
