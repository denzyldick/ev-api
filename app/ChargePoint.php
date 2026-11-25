<?php

declare(strict_types=1);

namespace App;

use App\ChargePoint\Coordinate;
use App\ChargePoint\Exceptions\InvalidCoordinate;
use App\ChargePoint\Status\ChargePointStatus;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $id
 * @property string $name
 * @property ChargePointStatus $status
 * @property Coordinate $coordinate
 */
#[Table(name: 'charge_points', key: 'id', keyType: 'string', incrementing: false)]
class ChargePoint extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'status'];

    protected function casts(): array
    {
        return [
            'status' => ChargePointStatus::class,
        ];
    }

    protected $attributes = [
        'name' => '',
        'status' => 'AVAILABLE',
    ];

    /**
     * Create a charge point and assign its unique ID immediately.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if ($this->getKey() === null) {
            $this->setAttribute($this->getKeyName(), $this->newUniqueId());
        }
    }

    public function getId(): string
    {
        /** @var string $id */
        $id = $this->getKey();

        return $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getStatus(): ChargePointStatus
    {
        return $this->status;
    }

    public function setStatus(ChargePointStatus $status): void
    {
        $this->status = $status;
    }

    public function coordinate(): HasOne
    {
        return $this->hasOne(Coordinate::class, 'charge_point_id');
    }

    public function getCoordinate(): Coordinate
    {
        if (! $this->relationLoaded('coordinate') && $this->exists) {
            $this->load('coordinate');
        }

        if (! $this->relationLoaded('coordinate')) {
            $this->setRelation('coordinate', new Coordinate);
        }

        /** @var Coordinate|null $coordinate */
        $coordinate = $this->getRelation('coordinate');

        return $coordinate ?? new Coordinate;
    }

    /**
     * @throws InvalidCoordinate when the coordinate is out of bounds.
     */
    public function setLocation(Coordinate $coordinate): bool
    {
        if (! $coordinate->isValid()) {
            throw InvalidCoordinate::forChargePoint($this->getId());
        }

        $this->setRelation('coordinate', $coordinate);

        return true;
    }
}
