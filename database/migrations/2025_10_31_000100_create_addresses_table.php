<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->string('currency', 32);
            $table->string('network', 32);
            $table->string('address', 255);
            $table->boolean('is_active')->default(true);
            $table->string('balance', 64)->default('0');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->index(['network', 'currency']);
            $table->unique(['network', 'currency', 'address'], 'uniq_network_currency_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};


