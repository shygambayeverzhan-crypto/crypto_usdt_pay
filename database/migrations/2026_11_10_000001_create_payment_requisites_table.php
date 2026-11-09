<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_requisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 80);
            $table->string('method', 10);
            $table->string('currency', 4);
            $table->string('bank', 20)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->text('holder')->nullable();
            $table->text('number')->nullable();
            $table->string('last4', 4)->nullable();
            $table->string('address', 100)->nullable();
            $table->string('network', 20)->nullable();
            $table->decimal('min_amount', 18, 6);
            $table->decimal('max_amount', 18, 6);
            $table->decimal('liquidity_limit', 18, 6)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_requisites');
    }
};
