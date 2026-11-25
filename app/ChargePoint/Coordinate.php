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
 * A geographic location attached to a charge point.
 *
 * Coordinates are stored in decimal degrees. Both axes always hold a value:
 * new instances start at the origin (0, 0), so validation never has to deal
 * with a missing latitude or longitude. Values outside the geographic bounds
 * are rejected by {@see self::isValid()}.
 *
 * @property float $latitude Latitude in decimal degrees, from -90 to 90.
 * @property float $longitude Longitude in decimal degrees, from -180 to 180.
 * @property string|null $charge_point_id UUID of the charge point this location belongs to.
 */
#[Table(name: 'coordinates')]
class Coordinate extends Model
{
    /** Lowest latitude allowed, in decimal degrees (South Pole). */
    public const float LATITUDE_MIN_BOUNDS = -90.0;

    /** Highest latitude allowed, in decimal degrees (North Pole). */
    public const float LATITUDE_MAX_BOUNDS = 90.0;

    /** Lowest longitude allowed, in decimal degrees (anti-meridian, west). */
    public const float LONGITUDE_MIN_BOUNDS = -180.0;

    /** Highest longitude allowed, in decimal degrees (anti-meridian, east). */
    public const float LONGITUDE_MAX_BOUNDS = 180.0;

    /** Attributes that may be assigned in bulk when creating a coordinate. */
    protected $fillable = ['charge_point_id', 'latitude', 'longitude'];

    /** Defaults applied to new instances so neither axis can be missing. */
    protected $attributes = [
        'latitude' => 0.0,
        'longitude' => 0.0,
    ];

    /**
     * Convert the stored coordinate attributes to floats.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * Get the latitude in decimal degrees.
     *
     * @return float A value between {@see self::LATITUDE_MIN_BOUNDS} and {@see self::LATITUDE_MAX_BOUNDS}.
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * Get the longitude in decimal degrees.
     *
     * @return float A value between {@see self::LONGITUDE_MIN_BOUNDS} and {@see self::LONGITUDE_MAX_BOUNDS}.
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * Set the latitude in decimal degrees.
     *
     * Bounds are not checked here; call {@see self::isValid()} after assigning values.
     */
    public function setLatitude(float $latitude): void
    {
        $this->latitude = $latitude;
    }

    /**
     * Set the longitude in decimal degrees.
     *
     * Bounds are not checked here; call {@see self::isValid()} after assigning values.
     */
    public function setLongitude(float $longitude): void
    {
        $this->longitude = $longitude;
    }

    /**
     * Check that both axes fall within their geographic bounds.
     *
     * Latitude must be between -90 and 90 degrees; longitude between -180 and
     * 180. Boundaries are inclusive. By default an invalid value simply makes
     * this method return false; with $fail enabled the first invalid axis is
     * reported as an exception instead.
     *
     * @param  bool  $fail  Whether to throw for the first invalid axis.
     * @return bool True when both latitude and longitude are in bounds.
     *
     * @throws InvalidCoordinate When $fail is true and an axis is out of bounds.
     */
    public function isValid(bool $fail = false): bool
    {
        $latitude = $this->getLatitude();
        if ($latitude < self::LATITUDE_MIN_BOUNDS || $latitude > self::LATITUDE_MAX_BOUNDS) {
            if ($fail) {
                throw InvalidCoordinate::forAxis(CoordinateType::LATITUDE);
            }

            return false;
        }

        $longitude = $this->getLongitude();
        if ($longitude < self::LONGITUDE_MIN_BOUNDS || $longitude > self::LONGITUDE_MAX_BOUNDS) {
            if ($fail) {
                throw InvalidCoordinate::forAxis(CoordinateType::LONGITUDE);
            }

            return false;
        }

        return true;
    }

    /**
     * Get the charge point this coordinate describes.
     *
     * @return BelongsTo<ChargePoint, static>
     */
    public function chargePoint(): BelongsTo
    {
        return $this->belongsTo(ChargePoint::class, 'charge_point_id');
    }
}
