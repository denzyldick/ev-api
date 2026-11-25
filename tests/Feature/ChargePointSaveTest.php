<?php

namespace Tests\Feature;

use App\ChargePoint;
use App\ChargePoint\Coordinate;
use App\ChargePoint\Exceptions\InvalidCoordinate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ChargePointSaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_persists_charge_point_with_coordinate(): void
    {
        $point = new ChargePoint;
        $point->setName('Station Alpha');
        $point->setCoordinate($this->coordinate(52.37, 4.89));

        static::assertTrue($point->save());

        $this->assertDatabaseHas('charge_points', [
            'id' => $point->getId(),
            'name' => 'Station Alpha',
            'status' => 'AVAILABLE',
        ]);
        $this->assertDatabaseHas('coordinates', [
            'charge_point_id' => $point->getId(),
            'latitude' => '52.3700000',
            'longitude' => '4.8900000',
        ]);
    }

    public function test_save_creates_default_coordinate_when_none_is_set(): void
    {
        $point = new ChargePoint;
        $point->setName('Station Beta');

        static::assertTrue($point->save());

        $this->assertDatabaseHas('coordinates', [
            'charge_point_id' => $point->getId(),
            'latitude' => '0.0000000',
            'longitude' => '0.0000000',
        ]);
    }

    public function test_save_rejects_invalid_coordinate_before_writing(): void
    {
        $point = new ChargePoint;
        $point->setCoordinate($this->coordinate(52.37, 4.89));
        $point->getCoordinate()->setLatitude(100);

        try {
            $point->save();
            static::fail('Expected InvalidCoordinate was not thrown.');
        } catch (InvalidCoordinate) {
            $this->assertDatabaseCount('charge_points', 0);
            $this->assertDatabaseCount('coordinates', 0);
        }
    }

    public function test_coordinate_failure_rolls_back_charge_point(): void
    {
        Coordinate::saving(static fn () => false);

        $point = new ChargePoint;
        $point->setName('Station Gamma');
        $point->setCoordinate($this->coordinate(52.37, 4.89));

        try {
            $point->save();
            static::fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException) {
            $this->assertDatabaseCount('charge_points', 0);
            $this->assertDatabaseCount('coordinates', 0);
        } finally {
            Coordinate::flushEventListeners();
        }
    }

    private function coordinate(float $latitude, float $longitude): Coordinate
    {
        $coordinate = new Coordinate;
        $coordinate->setLatitude($latitude);
        $coordinate->setLongitude($longitude);

        return $coordinate;
    }
}
