<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_token_allowed_ips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_token_id')->constrained()->cascadeOnDelete();
            $table->string('ip', 45);
            $table->timestamps();

            $table->unique(['api_token_id', 'ip']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_token_allowed_ips');
    }
};


