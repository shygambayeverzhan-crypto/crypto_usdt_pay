<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('external_id', 128);
            $table->string('name', 255)->nullable();
            $table->string('telegram', 255)->nullable();
            $table->string('contact', 255)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'external_id']);
            $table->index(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};


