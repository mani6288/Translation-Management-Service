<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TranslationController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

RateLimiter::for('api', function ($request) {
    return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
});

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/createTranslation', [TranslationController::class, 'storeTranslation']);
    Route::put('/updateTranslation/{id}', [TranslationController::class, 'updateTranslation']);
    Route::get('/getTranslationById/{id}', [TranslationController::class, 'getTranslationById']);
    Route::get('/getTranslations', [TranslationController::class, 'getTranslationsByParams']);
    Route::get('/translationsJsonExport', [TranslationController::class, 'exportTranslationJson']);
});
