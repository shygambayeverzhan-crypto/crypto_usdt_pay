<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_callback_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('invoice_id')->index();
            $table->string('event');
            $table->string('url');
            $table->json('request_payload');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->mediumText('response_body')->nullable();
            $table->string('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_callback_logs');
    }
};


