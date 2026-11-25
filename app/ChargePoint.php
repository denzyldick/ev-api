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
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * An electric vehicle charge point.
 *
 * Every charge point has a UUID assigned at construction, a human-readable
 * name, a status, and exactly one geographic coordinate. New instances start
 * with an empty name, an AVAILABLE status, and a coordinate at the origin
 * (0, 0); a persisted charge point loads its stored coordinate on first access.
 *
 * @property string $id Unique identifier, generated when the model is created.
 * @property string $name Human-readable name of the charge point.
 * @property ChargePointStatus $status Current availability status.
 * @property Coordinate $coordinate Geographic location of the charge point.
 */
#[Table(name: 'charge_points', key: 'id', keyType: 'string', incrementing: false)]
class ChargePoint extends Model
{
    use HasUuids;

    /** Attributes that may be assigned in bulk when creating a charge point. */
    protected $fillable = ['name', 'status'];

    /**
     * Convert the stored status string into its enum.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ChargePointStatus::class,
        ];
    }

    /** Defaults that ensure a new charge point always has a name and status. */
    protected $attributes = [
        'name' => '',
        'status' => 'AVAILABLE',
    ];

    /**
     * Create a charge point and assign a unique ID when none is supplied.
     *
     * Models hydrated from the database keep the ID read from storage.
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

    /**
     * Get the unique identifier of this charge point.
     *
     * @return string The UUID assigned when the model was created.
     */
    public function getId(): string
    {
        /** @var string $id */
        $id = $this->getKey();

        return $id;
    }

    /**
     * Get the human-readable name of this charge point.
     *
     * @return string An empty string when no name has been assigned.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the human-readable name of this charge point.
     *
     * @param  string  $name  Name to display for the charge point.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the current availability status.
     *
     * @return ChargePointStatus AVAILABLE by default.
     */
    public function getStatus(): ChargePointStatus
    {
        return $this->status;
    }

    /**
     * Set the current availability status.
     *
     * @param  ChargePointStatus  $status  One of AVAILABLE, OCCUPIED, or OFFLINE.
     */
    public function setStatus(ChargePointStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * Define the one-to-one relation to this charge point's location.
     *
     * @return HasOne<Coordinate, static>
     */
    public function coordinate(): HasOne
    {
        return $this->hasOne(Coordinate::class, 'charge_point_id');
    }

    /**
     * Save this charge point together with its coordinate.
     *
     * Eloquent does not normally save relations from save(), so the coordinate
     * relation is persisted here. The coordinate is validated before any write,
     * and both writes run in a single transaction, so a failed coordinate write
     * can never leave a charge point without a location.
     *
     * @param  array<array-key, mixed>  $options
     * @return bool Whether the charge point and its coordinate were saved.
     *
     * @throws InvalidCoordinate When the attached coordinate is out of bounds.
     * @throws RuntimeException When the coordinate could not be saved.
     */
    public function save(array $options = []): bool
    {
        $coordinate = $this->getCoordinate();
        $coordinate->isValid(true);

        /** @var bool $saved */
        $saved = DB::transaction(function () use ($options, $coordinate): bool {
            if (! parent::save($options)) {
                return false;
            }

            if ($this->coordinate()->save($coordinate) === false) {
                throw new RuntimeException('Failed to save the charge point coordinate.');
            }

            return true;
        });

        return $saved;
    }

    /**
     * Get the location of this charge point.
     *
     * Persisted models load the relation on first access. Unsaved models, and
     * models with no stored location, get a coordinate at the origin (0, 0).
     *
     * @return Coordinate The current location, never null.
     */
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
     * Attach a validated coordinate to this charge point.
     *
     * The coordinate is attached as a relation and is written to the database
     * when the charge point is saved. Out-of-bounds coordinates are rejected
     * before they can be attached.
     *
     * @param  Coordinate  $coordinate  Candidate latitude and longitude.
     * @return bool True when the coordinate was accepted.
     *
     * @throws InvalidCoordinate When the coordinate is out of bounds.
     */
    public function setCoordinate(Coordinate $coordinate): bool
    {
        if (! $coordinate->isValid()) {
            throw InvalidCoordinate::forChargePoint($this->getId());
        }

        $this->setRelation('coordinate', $coordinate);

        return true;
    }
}
