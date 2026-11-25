<?php

declare(strict_types=1);

use App\Http\Controllers\ChargePointController;
use Illuminate\Support\Facades\Route;

// The GET endpoints
Route::get('/health', fn () => response()->json(['status' => 'ok']));
Route::get('/chargepoints', [ChargePointController::class, 'index']);
Route::get('/chargepoints/nearby', [ChargePointController::class, 'nearby']);
Route::get('/chargepoints/{chargePoint}', [ChargePointController::class, 'show']);

// The POST endpoints
Route::post('/chargepoints', [ChargePointController::class, 'store']);
