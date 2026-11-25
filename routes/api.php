<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// The GET endpoints
Route::get('/health', fn () => response()->json(['status' => 'ok']));
Route::get('/chargepoints', fn () => response()->json(['status' => 'ok']));
Route::get('/chargepoints/nearby', fn () => response()->json(['status' => 'ok']));
Route::get('/chargepoints/{id}', fn () => response()->json(['status' => 'ok']));

// The POST endpoints
Route::post('/chargepoints', fn () => response()->json(['status' => 'ok']));
