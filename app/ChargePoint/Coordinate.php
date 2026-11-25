<?php

declare(strict_types=1);

namespace App\ChargePoint;

use App\ChargePoint;
use App\ChargePoint\Exceptions\CoordinateType;
use App\ChargePoint\Exceptions\InvalidCoordinate;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property float|null $latitude
 * @property float|null $longitude
 */
#[Table(name: 'coordinates')]
class Coordinate extends Model
{
    public const float LATITUDE_MIN_BOUNDS = -90.0;

    public const float LATITUDE_MAX_BOUNDS = 90.0;

    public const float LONGITUDE_MIN_BOUNDS = -180.0;

    public const float LONGITUDE_MAX_BOUNDS = 180.0;

    protected $fillable = ['charge_point_id', 'latitude', 'longitude'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLatitude(float $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function setLongitude(float $longitude): void
    {
        $this->longitude = $longitude;
    }

    /**
     * @throws InvalidCoordinate when fail is true and a value is invalid.
     */
    public function isValid(bool $fail = false): bool
    {
        foreach ([
            [CoordinateType::LATITUDE, $this->getLatitude()],
            [CoordinateType::LONGITUDE, $this->getLongitude()],
        ] as [$type, $value]) {
            if ($value !== null && $value >= $type->min() && $value <= $type->max()) {
                continue;
            }

            if ($fail) {
                throw InvalidCoordinate::forAxis($type);
            }

            return false;
        }

        return true;
    }

    public function chargePoint(): BelongsTo
    {
        return $this->belongsTo(ChargePoint::class, 'charge_point_id');
    }
}
