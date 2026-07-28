<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Known-good addresses at a filtered domain. With recipient_mode='list' the node
 * rejects anything not in here at SMTP time, which stops dictionary attacks before
 * they cost anything.
 *
 * customer_id is denormalised so portal scoping never needs a join through domains.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_recipients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('mail_domain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('address')->index(); // full address, e.g. bob@example.com
            // false = bypass spam scoring for this address. Virus scanning always stays on.
            $table->boolean('filtering_enabled')->default(true);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->unique(['mail_domain_id', 'address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_recipients');
    }
};
