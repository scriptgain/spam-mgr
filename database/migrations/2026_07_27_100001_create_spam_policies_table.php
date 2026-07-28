<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * A named filtering policy. Domains point at one; the node pulls the thresholds
 * with its domain list and enforces them locally, so a panel outage never stops
 * mail flowing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spam_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            // Score thresholds: tag adds a header, tag2 rewrites the subject,
            // kill quarantines outright.
            $table->decimal('tag_level', 4, 1)->default(5.0);
            $table->decimal('tag2_level', 4, 1)->default(8.0);
            $table->decimal('kill_level', 4, 1)->default(12.0);
            $table->boolean('block_foreign_charset')->default(false);
            $table->boolean('block_bulk')->default(false);
            $table->text('subject_block_keywords')->nullable(); // one per line
            $table->text('body_block_keywords')->nullable();    // one per line
            $table->text('uri_allowlist')->nullable();          // one per line
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spam_policies');
    }
};
