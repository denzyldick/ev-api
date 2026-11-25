<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coordinates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('charge_point_id')->unique();
            $table->decimal('latitude', 10, 7)->default(0.0000000);
            $table->decimal('longitude', 10, 7)->default(0.0000000);
            $table->timestamps();

            $table->foreign('charge_point_id')
                ->references('id')
                ->on('charge_points')
                ->cascadeOnDelete();

            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coordinates');
    }
};
