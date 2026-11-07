<?php

use App\Http\Controllers\VercelMaintenanceController;
use Illuminate\Support\Facades\Route;

// Deliberately outside sessions/CSRF/API throttles. The controller requires CRON_SECRET.
Route::get('/internal/{task}/tick', VercelMaintenanceController::class)->whereIn('task', ['payments', 'queue']);
