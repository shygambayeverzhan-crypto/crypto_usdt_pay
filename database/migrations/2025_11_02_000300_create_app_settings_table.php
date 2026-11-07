<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('currency', 16)->index();
            // Значения в минорных единицах, как строки, без потери точности
            $table->string('min_invoice_amount_minor', 64);
            $table->string('max_invoice_amount_minor', 64);
            $table->timestamps();

            $table->unique(['currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};


