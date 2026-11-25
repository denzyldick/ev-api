<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Return API resources without the default "data" wrapper.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();
    }
}
