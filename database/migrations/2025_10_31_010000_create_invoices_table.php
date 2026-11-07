<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('external_invoice_id', 64)->nullable()->index();

            $table->unsignedBigInteger('address_id');
            $table->string('amount', 64);
            $table->string('currency', 32);
            $table->string('network', 32);
            $table->string('status', 32);
            $table->string('txid', 255)->nullable();
            $table->string('amount_received', 64)->default('0');
            $table->unsignedInteger('confirmations')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->string('callback_url', 255)->nullable();
            $table->string('tag', 255)->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->foreign('address_id')
                ->references('id')->on('addresses')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index(['status', 'currency', 'network']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};


