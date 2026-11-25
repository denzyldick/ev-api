<?php

namespace Tests\Feature;

use App\ChargePoint;
use App\ChargePoint\Coordinate;
use App\ChargePoint\Status\ChargePointStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChargePointApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_charge_point_with_location(): void
    {
        $response = $this->postJson('/api/chargepoints', [
            'name' => 'Station Alpha',
            'status' => 'OCCUPIED',
            'location' => ['latitude' => 52.37, 'longitude' => 4.89],
        ]);

        /** @var string $id */
        $id = $response->json('id');
        static::assertNotEmpty($id);

        $response->assertCreated()
            ->assertJsonPath('name', 'Station Alpha')
            ->assertJsonPath('status', 'OCCUPIED')
            ->assertJsonPath('location.latitude', 52.37)
            ->assertJsonPath('location.longitude', 4.89);

        $this->assertDatabaseHas('charge_points', [
            'id' => $id,
            'name' => 'Station Alpha',
            'status' => 'OCCUPIED',
        ]);
        $this->assertDatabaseHas('coordinates', [
            'charge_point_id' => $id,
            'latitude' => '52.3700000',
            'longitude' => '4.8900000',
        ]);
    }

    public function test_store_validates_payload_before_writing(): void
    {
        $this->postJson('/api/chargepoints', [
            'name' => '',
            'location' => ['latitude' => 100, 'longitude' => 4.89],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'location.latitude']);

        $this->assertDatabaseCount('charge_points', 0);
        $this->assertDatabaseCount('coordinates', 0);
    }

    public function test_show_returns_charge_point(): void
    {
        $point = $this->chargePoint('Station Alpha', 52.37, 4.89);

        $this->getJson("/api/chargepoints/{$point->getId()}")
            ->assertOk()
            ->assertJsonPath('id', $point->getId())
            ->assertJsonPath('name', 'Station Alpha')
            ->assertJsonPath('status', ChargePointStatus::AVAILABLE->value)
            ->assertJsonPath('location.latitude', 52.37);
    }

    public function test_show_returns_404_for_unknown_id(): void
    {
        $this->getJson('/api/chargepoints/unknown-id')->assertNotFound();
    }

    public function test_index_lists_charge_points(): void
    {
        $this->chargePoint('Station Alpha', 52.37, 4.89);

        $this->getJson('/api/chargepoints')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_nearby_returns_only_points_within_radius(): void
    {
        $near = $this->chargePoint('Near', 52.3705, 4.8905);
        $this->chargePoint('Far', 53.37, 4.89);

        $this->getJson('/api/chargepoints/nearby?lat=52.37&lon=4.89&radius=1')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $near->getId())
            ->assertJsonPath('0.name', 'Near');
    }

    public function test_nearby_validates_query_parameters(): void
    {
        $this->getJson('/api/chargepoints/nearby?lat=100&lon=4.89&radius=5')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat']);
    }

    private function chargePoint(string $name, float $latitude, float $longitude): ChargePoint
    {
        $point = new ChargePoint;
        $point->setName($name);

        $coordinate = new Coordinate;
        $coordinate->setLatitude($latitude);
        $coordinate->setLongitude($longitude);
        $point->setCoordinate($coordinate);
        $point->save();

        return $point;
    }
}
