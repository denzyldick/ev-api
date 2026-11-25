<?php

declare(strict_types=1);

namespace App\ChargePoint;

use App\ChargePoint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Find charge points within a radius of a geographic location.
 *
 * The database query first narrows candidates with an indexed bounding box.
 * Each remaining candidate is then checked with Haversine so the radius is
 * measured in kilometres along the earth's surface.
 */
final class NearbySearch
{
    /** Mean earth radius used by the Haversine calculation, in kilometres. */
    private const float EARTH_RADIUS_KM = 6371.0;

    /** Kilometres per degree of latitude, used for the bounding box. */
    private const float KM_PER_LATITUDE_DEGREE = 111.32;

    /**
     * Find charge points within the given radius.
     *
     * @return Collection<int, ChargePoint>
     */
    public function find(float $latitude, float $longitude, float $radiusKm): Collection
    {
        $latitudeDelta = $radiusKm / self::KM_PER_LATITUDE_DEGREE;
        $longitudeDelta = $radiusKm / (self::KM_PER_LATITUDE_DEGREE * max(abs(cos(deg2rad($latitude))), 0.000_01));

        $longitudeRanges = $this->longitudeRanges($longitude, $longitudeDelta);

        $query = ChargePoint::query()
            ->select('charge_points.*')
            ->join('coordinates', 'coordinates.charge_point_id', '=', 'charge_points.id')
            ->with('coordinate')
            ->whereBetween('coordinates.latitude', [
                max(-90.0, $latitude - $latitudeDelta),
                min(90.0, $latitude + $latitudeDelta),
            ]);

        if ($longitudeRanges !== []) {
            $query->where(static function (Builder $query) use ($longitudeRanges): void {
                foreach ($longitudeRanges as [$min, $max]) {
                    $query->orWhereBetween('coordinates.longitude', [$min, $max]);
                }
            });
        }

        $center = new Coordinate;
        $center->setLatitude($latitude);
        $center->setLongitude($longitude);

        return $query
            ->get()
            ->filter(fn (ChargePoint $point): bool => $this->distanceKm($center, $point->getCoordinate()) <= $radiusKm)
            ->values();
    }

    /**
     * Calculate longitude ranges to query, accounting for the anti-meridian.
     *
     * An empty list means the search covers every longitude.
     *
     * @return array<int, array{0: float, 1: float}>
     */
    private function longitudeRanges(float $longitude, float $delta): array
    {
        if ($delta >= 180.0) {
            return [];
        }

        $min = $longitude - $delta;
        $max = $longitude + $delta;

        if ($min < -180.0) {
            return [[$min + 360.0, 180.0], [-180.0, $max]];
        }

        if ($max > 180.0) {
            return [[$min, 180.0], [-180.0, $max - 360.0]];
        }

        return [[$min, $max]];
    }

    /**
     * Calculate the great-circle distance between two coordinates.
     */
    private function distanceKm(Coordinate $from, Coordinate $to): float
    {
        $fromLatitude = deg2rad($from->getLatitude());
        $toLatitude = deg2rad($to->getLatitude());
        $latitudeDelta = $toLatitude - $fromLatitude;
        $longitudeDelta = deg2rad($to->getLongitude() - $from->getLongitude());

        $haversine =
            sin($latitudeDelta / 2) ** 2 + (cos($fromLatitude) * cos($toLatitude) * (sin($longitudeDelta / 2) ** 2));

        return self::EARTH_RADIUS_KM * 2 * asin(min(1.0, sqrt($haversine)));
    }
}
