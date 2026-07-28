<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Allow and block rules. Scope widens as the foreign keys fall away: an entry with
 * a mail_recipient_id applies to one mailbox, with only a mail_domain_id to a whole
 * domain, with only a customer_id to everything that customer owns, and with none
 * of the three it is an operator-wide rule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allow_block_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_domain_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('mail_recipient_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('type', 20);            // sender|domain|ip
            $table->string('value')->index();
            $table->string('list', 20)->index();   // allow|block|spam_bypass|rbl_bypass
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['mail_domain_id', 'list']);
            $table->index(['mail_recipient_id', 'list']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allow_block_entries');
    }
};
