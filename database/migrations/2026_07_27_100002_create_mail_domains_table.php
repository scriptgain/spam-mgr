<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * A filtered domain: mail for it arrives at the MX nodes, is scored, and is either
 * held or relayed to destination_host. Consolidates SpamNinja's mail_domains plus
 * its later verification and recipient-mode columns.
 *
 * No billing columns: the self-hosted operator bills their own customers however
 * they like, which is not this product's business.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_domains', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spam_policy_id')->nullable()->constrained('spam_policies')->nullOnDelete();
            $table->string('name')->unique(); // the domain, e.g. example.com

            // Ownership proof before we accept mail for a domain.
            $table->string('verification_token', 64)->nullable();
            $table->timestamp('verified_at')->nullable();

            // Where clean mail goes next.
            $table->string('destination_host')->nullable();
            $table->unsignedInteger('destination_port')->default(25);
            $table->string('mx_status', 20)->default('pending')->index(); // pending|verified|failed
            $table->string('tls_policy', 20)->default('opportunistic');   // none|opportunistic|enforced

            // 'list' = only addresses in mail_recipients are accepted (blocks
            // dictionary attacks); 'all' = accept anything at the domain.
            $table->string('recipient_mode', 20)->default('list');

            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_domains');
    }
};
