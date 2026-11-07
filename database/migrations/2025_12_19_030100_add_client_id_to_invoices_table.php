<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->ulid('client_id')
                ->nullable()
                ->after('merchant_id')
                ->constrained('clients')
                ->nullOnDelete();

            $table->index(['client_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
            $table->dropIndex(['client_id', 'user_id']);
        });
    }
};


