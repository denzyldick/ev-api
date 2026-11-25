<?php

use App\ChargePoint\Exceptions\InvalidCoordinate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(
            fn (InvalidCoordinate $exception): JsonResponse => response()->json(['message' => $exception->getMessage()], 422)
        );
    })
    ->prefersJsonResponses()
    ->create();
