<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table): void {
            $table->boolean('white_label_enabled')->default(true)->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table): void {
            $table->dropColumn('white_label_enabled');
        });
    }
};


