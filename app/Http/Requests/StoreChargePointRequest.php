<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\ChargePoint\Status\ChargePointStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreChargePointRequest extends FormRequest
{
    /**
     * Allow every client to create a charge point.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rules for creating a charge point.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', new Enum(ChargePointStatus::class)],
            'location.latitude' => ['required', 'numeric', 'between:-90,90'],
            'location.longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
