<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('chat_id', 32)->nullable()->unique();
            $table->string('username', 64)->nullable();
            $table->string('first_name', 128)->nullable();
            $table->string('last_name', 128)->nullable();
            $table->string('link_token', 64)->unique();
            $table->boolean('is_active')->default(false);
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_accounts');
    }
};

