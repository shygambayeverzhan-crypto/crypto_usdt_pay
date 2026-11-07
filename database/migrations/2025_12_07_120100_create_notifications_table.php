<?php

declare(strict_types=1);

use App\Enums\NotificationDeliveryStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event', 64)->index();
            $table->string('channel', 32)->index();
            $table->string('status', 32)->default(NotificationDeliveryStatus::PENDING->value)->index();
            $table->string('title');
            $table->text('body');
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

