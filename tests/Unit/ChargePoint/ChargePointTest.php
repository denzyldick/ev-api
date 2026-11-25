<?php

declare(strict_types=1);

namespace Tests\Unit\ChargePoint;

use App\ChargePoint;
use App\ChargePoint\Coordinate;
use App\ChargePoint\Exceptions\InvalidCoordinate;
use App\ChargePoint\Status\ChargePointStatus;
use Tests\TestCase;

class ChargePointTest extends TestCase
{
    public function test_default_status_is_available(): void
    {
        static::assertSame(ChargePointStatus::AVAILABLE, (new ChargePoint)->getStatus());
    }

    public function test_set_location_stores_valid_coordinate(): void
    {
        $chargePoint = new ChargePoint;
        $coordinate = $this->coordinate(52.37, 4.89);

        static::assertTrue($chargePoint->setLocation($coordinate));
        static::assertSame($coordinate, $chargePoint->getCoordinate());
    }

    public function test_set_location_rejects_invalid_coordinate(): void
    {
        $this->expectException(InvalidCoordinate::class);

        (new ChargePoint)->setLocation($this->coordinate(100, 4.89));
    }

    public function test_status_accepts_all_supported_values(): void
    {
        foreach (ChargePointStatus::cases() as $status) {
            $chargePoint = new ChargePoint;
            $chargePoint->setStatus($status);

            static::assertSame($status, $chargePoint->getStatus());
        }
    }

    public function test_getters_return_assigned_name_and_status(): void
    {
        $chargePoint = new ChargePoint;
        $chargePoint->setName('Station Alpha');
        $chargePoint->setStatus(ChargePointStatus::OCCUPIED);

        static::assertSame('Station Alpha', $chargePoint->getName());
        static::assertSame(ChargePointStatus::OCCUPIED, $chargePoint->getStatus());
    }

    private function coordinate(float $latitude, float $longitude): Coordinate
    {
        $coordinate = new Coordinate;
        $coordinate->setLatitude($latitude);
        $coordinate->setLongitude($longitude);

        return $coordinate;
    }
}
