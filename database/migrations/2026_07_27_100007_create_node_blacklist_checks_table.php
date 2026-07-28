<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * RBL results per node IP. A listed MX node silently loses outbound relay to some
 * destinations, so this is checked on a schedule rather than discovered from a
 * customer complaint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_blacklist_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();
            $table->string('rbl');                 // the zone queried, e.g. zen.spamhaus.org
            $table->string('status', 20)->index(); // clear|listed|error
            $table->string('detail')->nullable();  // TXT answer when listed
            $table->timestamp('checked_at')->nullable()->index();
            $table->timestamps();

            $table->index(['node_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('node_blacklist_checks');
    }
};
