<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('external_invoice_id')
                ->constrained()
                ->cascadeOnDelete();
        });

        $fallbackUserId = DB::table('users')->orderBy('id')->value('id');

        if ($fallbackUserId !== null) {
            DB::table('addresses')
                ->whereNull('user_id')
                ->update(['user_id' => $fallbackUserId]);

            $addresses = DB::table('addresses')
                ->select(['id', 'user_id'])
                ->get()
                ->keyBy('id');

            DB::table('invoices')
                ->whereNull('user_id')
                ->orderBy('created_at')
                ->chunkById(100, function ($chunk) use ($addresses, $fallbackUserId) {
                    foreach ($chunk as $invoice) {
                        $addressUserId = $addresses[$invoice->address_id]->user_id ?? $fallbackUserId;
                        DB::table('invoices')
                            ->where('id', $invoice->id)
                            ->update(['user_id' => $addressUserId]);
                    }
                }, 'id');
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};


