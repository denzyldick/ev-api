<?php

declare(strict_types=1);

namespace App\ChargePoint\Exceptions;

use App\ChargePoint\Coordinate;

enum CoordinateType: string
{
    case LATITUDE = 'latitude';
    case LONGITUDE = 'longitude';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function min(): float
    {
        return match ($this) {
            self::LATITUDE => Coordinate::LATITUDE_MIN_BOUNDS,
            self::LONGITUDE => Coordinate::LONGITUDE_MIN_BOUNDS,
        };
    }

    public function max(): float
    {
        return match ($this) {
            self::LATITUDE => Coordinate::LATITUDE_MAX_BOUNDS,
            self::LONGITUDE => Coordinate::LONGITUDE_MAX_BOUNDS,
        };
    }
}
