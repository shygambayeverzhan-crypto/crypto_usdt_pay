<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('language_settings', function (Blueprint $table) {
            $table->id();
            $table->json('enabled_locales')->nullable();
            $table->timestamps();
        });

        DB::table('language_settings')->insert([
            'enabled_locales' => json_encode(['ru', 'en', 'uk'], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('language_settings');
    }
};


