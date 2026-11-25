<?php

declare(strict_types=1);

use App\ChargePoint\Status\ChargePointStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charge_points', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->default('');
            $table->string('status', 20)->default(ChargePointStatus::AVAILABLE->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_points');
    }
};
