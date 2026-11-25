<?php

declare(strict_types=1);

namespace Tests\Unit\ChargePoint;

use App\ChargePoint\Coordinate;
use App\ChargePoint\Exceptions\InvalidCoordinate;
use Tests\TestCase;

class CoordinateTest extends TestCase
{
    private function coordinate(float $latitude, float $longitude): Coordinate
    {
        $coordinate = new Coordinate;
        $coordinate->setLatitude($latitude);
        $coordinate->setLongitude($longitude);

        return $coordinate;
    }

    public function test_valid_coordinate_is_valid(): void
    {
        static::assertTrue($this->coordinate(52.37, 4.89)->isValid());
    }

    public function test_latitude_below_minimum_is_invalid(): void
    {
        static::assertFalse($this->coordinate(-90.01, 4.89)->isValid());
    }

    public function test_latitude_above_maximum_is_invalid(): void
    {
        static::assertFalse($this->coordinate(90.01, 4.89)->isValid());
    }

    public function test_longitude_below_minimum_is_invalid(): void
    {
        static::assertFalse($this->coordinate(52.37, -180.01)->isValid());
    }

    public function test_longitude_above_maximum_is_invalid(): void
    {
        static::assertFalse($this->coordinate(52.37, 180.01)->isValid());
    }

    public function test_boundary_coordinates_are_valid(): void
    {
        foreach ([[-90, -180], [-90, 180], [90, -180], [90, 180]] as [$latitude, $longitude]) {
            static::assertTrue($this->coordinate($latitude, $longitude)->isValid());
        }
    }

    public function test_invalid_latitude_throws_when_fail_is_enabled(): void
    {
        $this->expectException(InvalidCoordinate::class);

        $this->coordinate(-100, 0)->isValid(true);
    }

    public function test_invalid_longitude_throws_when_fail_is_enabled(): void
    {
        $this->expectException(InvalidCoordinate::class);

        $this->coordinate(0, 200)->isValid(true);
    }

    public function test_new_coordinate_defaults_to_origin(): void
    {
        $coordinate = new Coordinate;

        static::assertSame(0.0, $coordinate->getLatitude());
        static::assertSame(0.0, $coordinate->getLongitude());
        static::assertTrue($coordinate->isValid());
    }

    public function test_exception_message_names_axis_and_bounds(): void
    {
        try {
            $this->coordinate(91, 0)->isValid(true);
        } catch (InvalidCoordinate $exception) {
            static::assertStringContainsString('Latitude', $exception->getMessage());
            static::assertStringContainsString('-90', $exception->getMessage());
            static::assertStringContainsString('90', $exception->getMessage());

            return;
        }

        static::fail('Expected InvalidCoordinate was not thrown.');
    }
}
