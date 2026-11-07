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
            $table->string('back_url', 2048)->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table): void {
            $table->dropColumn('back_url');
        });
    }
};

