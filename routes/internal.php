<?php

use App\Http\Controllers\VercelMaintenanceController;
use Illuminate\Support\Facades\Route;

// No session, cache, login or payment work; safe for a deployment readiness probe.
Route::get('/ready', \App\Http\Controllers\ReadinessController::class)->name('deployment.ready');

// Deliberately outside sessions/CSRF/API throttles. The controller requires CRON_SECRET.
Route::get('/internal/{task}/tick', VercelMaintenanceController::class)->whereIn('task', ['payments', 'queue']);
