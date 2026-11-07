<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('bot_link_token', 48)->nullable()->unique();
            $table->string('customer_telegram_chat_id')->nullable();
            $table->timestamp('customer_notified_at')->nullable();
            $table->string('requested_amount', 64)->nullable();
            $table->string('chain_network', 16)->nullable();
            $table->string('token_contract', 64)->nullable();
            $table->unsignedInteger('event_index')->nullable();
            $table->unsignedBigInteger('block_number')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unique(['address_id', 'amount'], 'invoice_permanent_quote');
            $table->unique(['network', 'txid'], 'invoice_unique_transaction');
            $table->unique(['user_id', 'external_invoice_id'], 'invoice_external_idempotency');
        });
        Schema::create('payment_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->string('event');
            $table->json('details');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_audit');
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoice_permanent_quote');
            $table->dropUnique('invoice_unique_transaction');
            $table->dropUnique('invoice_external_idempotency');
            $table->dropColumn(['bot_link_token', 'customer_telegram_chat_id', 'customer_notified_at', 'requested_amount', 'chain_network', 'token_contract', 'event_index', 'block_number', 'paid_at']);
        });
    }
};
