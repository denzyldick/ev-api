<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\ChargePoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serialize a charge point for the JSON API.
 *
 * @mixin ChargePoint
 */
final class ChargePointResource extends JsonResource
{
    /**
     * Transform the charge point into its API representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $coordinate = $this->getCoordinate();

        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'status' => $this->getStatus()->value,
            'location' => [
                'latitude' => $coordinate->getLatitude(),
                'longitude' => $coordinate->getLongitude(),
            ],
        ];
    }
}
