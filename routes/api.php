<?php

use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('v1')->group(function () {
    Route::apiResource('notifications', NotificationController::class)->only(['index', 'store', 'show']);
    Route::post('notifications/batch', [NotificationController::class, 'batchStore']);
    Route::post('notifications/{notification}/cancel', [NotificationController::class, 'cancel']);

    Route::apiResource('templates', TemplateController::class);
});
