<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ChargePoint;
use App\ChargePoint\Coordinate;
use App\ChargePoint\NearbySearch;
use App\ChargePoint\Status\ChargePointStatus;
use App\Http\Requests\NearbyRequest;
use App\Http\Requests\StoreChargePointRequest;
use App\Http\Resources\ChargePointResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use RuntimeException;

final class ChargePointController extends Controller
{
    /**
     * List all charge points.
     */
    public function index(): AnonymousResourceCollection
    {
        return ChargePointResource::collection(ChargePoint::query()->with('coordinate')->get());
    }

    /**
     * Find charge points within a radius of the given coordinates.
     */
    public function nearby(NearbyRequest $request): AnonymousResourceCollection
    {
        $points = (new NearbySearch)->find(
            $request->float('lat'),
            $request->float('lon'),
            $request->float('radius'),
        );

        return ChargePointResource::collection($points);
    }

    /**
     * Get a single charge point by its ID.
     */
    public function show(ChargePoint $chargePoint): ChargePointResource
    {
        $chargePoint->load('coordinate');

        return new ChargePointResource($chargePoint);
    }

    /**
     * Create a new charge point.
     */
    public function store(StoreChargePointRequest $request): JsonResponse
    {
        $chargePoint = new ChargePoint;
        $chargePoint->setName($request->string('name')->toString());

        if ($request->has('status')) {
            $chargePoint->setStatus(ChargePointStatus::from($request->string('status')->toString()));
        }

        $coordinate = new Coordinate;
        $coordinate->setLatitude($request->float('location.latitude'));
        $coordinate->setLongitude($request->float('location.longitude'));
        $chargePoint->setCoordinate($coordinate);

        if (! $chargePoint->save()) {
            throw new RuntimeException('Failed to create the charge point.');
        }

        return (new ChargePointResource($chargePoint))
            ->response()
            ->setStatusCode(201);
    }
}
