<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class NearbyRequest extends FormRequest
{
    /**
     * Allow every client to search for nearby charge points.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rules for a nearby search.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'numeric', 'min:0'],
        ];
    }
}
