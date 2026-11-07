<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\ClientController;

Route::prefix('v1')
    ->middleware(['api.key'])
    ->group(function () {
        Route::get('invoices', [InvoiceController::class, 'index']);
        Route::post('invoices', [InvoiceController::class, 'store']);
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::get('invoices/{invoice}/status', [InvoiceController::class, 'status']);

        // Публичные данные и QR как API (без Inertia)
        Route::get('invoices/{invoice}/public', [InvoiceController::class, 'public']);
        Route::get('invoices/{invoice}/qr', [InvoiceController::class, 'qr']);

        // Отмена активного инвойса (до поступления платежа)
        Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel']);

        // Мерчанты текущего пользователя
        Route::get('merchants', [MerchantController::class, 'index']);

        // Клиенты текущего пользователя
        Route::get('clients', [ClientController::class, 'index']);
        Route::post('clients', [ClientController::class, 'store']);
    });


