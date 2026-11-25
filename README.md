# EV Charge Point API

A small REST API for managing electric vehicle charge points, built with Laravel, PHP 8.5, MariaDB, and FrankenPHP/Octane.

**Total time spent:** approximately 2–3 hours.

LLM assistance was used; all design decisions and code are documented in this README and the commit history.

## Requirements

- Docker and Docker Compose (recommended), or
- PHP 8.3+, Composer 2, and a MariaDB/MySQL server with `pdo_mysql`.

## Run with Docker

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

The API is then available at `http://localhost:8000/api`.

`compose.yaml` starts MariaDB 11.4 and the app on port `8000`. The `DB_*` variables in `.env` are used by both Laravel and the MariaDB service.

Useful commands:

```bash
docker compose logs -f app
docker compose down
docker compose down -v   # also removes the database volume
```

## Run locally without Docker

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Point `.env` at your local MariaDB instance, for example:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret
```

Then:

```bash
php artisan migrate
php artisan octane:frankenphp --host=0.0.0.0 --port=8000
```

## API

All responses are JSON. Resources are returned unwrapped.

### `POST /api/chargepoints`

Create a charge point. `status` is optional and defaults to `AVAILABLE`.

```bash
curl -X POST http://localhost:8000/api/chargepoints \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "name": "Station Alpha",
    "status": "AVAILABLE",
    "location": {"latitude": 52.37, "longitude": 4.89}
  }'
```

Response `201`:

```json
{
  "id": "01a21317-716b-71ca-971f-4143794cad5b",
  "name": "Station Alpha",
  "status": "AVAILABLE",
  "location": {"latitude": 52.37, "longitude": 4.89}
}
```

Invalid input returns `422` with `errors`.

### `GET /api/chargepoints/{id}`

Fetch one charge point. Returns `404` for an unknown ID.

```bash
curl http://localhost:8000/api/chargepoints/01a21317-716b-71ca-971f-4143794cad5b
```

### `GET /api/chargepoints/nearby`

Find charge points within a radius in kilometres.

```bash
curl 'http://localhost:8000/api/chargepoints/nearby?lat=52.37&lon=4.89&radius=5'
```

Query parameters: `lat` (`-90..90`), `lon` (`-180..180`), `radius` (km, `>= 0`). Invalid parameters return `422`.

### `GET /api/chargepoints`

Bonus list endpoint.

### `GET /api/health`

Health check returning `{"status":"ok"}`.

## Data model

- `ChargePoint`: UUID `id`, `name`, `status` enum (`AVAILABLE`, `OCCUPIED`, `OFFLINE`), and one `Coordinate`.
- `Coordinate`: latitude/longitude in decimal degrees, bounded by ±90 / ±180.
- A new charge point always gets a coordinate at `(0, 0)` if none is supplied.
- `ChargePoint::save()` validates the coordinate and writes the charge point and coordinate in one database transaction, so an invalid or failed coordinate write can never leave a dangling charge point.
- The database has a unique foreign key from `coordinates.charge_point_id` to `charge_points.id` with cascade delete.

## Tests and quality

```bash
docker compose exec app composer test
docker compose exec app composer quality
docker compose exec app vendor/bin/pint --test
```

- 15 DB-free unit tests cover the models.
- 11 feature tests use `RefreshDatabase` against MariaDB.
- CI runs migrations, Mago, Pint, and the full test suite against MariaDB 11.4.

## Design notes for discussion

- **Structure:** domain-first (`App\ChargePoint`), with HTTP-specific FormRequests and Resources kept separate from models.
- **Validation:** request-level validation for HTTP input plus model-level validation at `save()` for non-HTTP callers.
- **Nearby:** an indexed bounding-box prefilter narrows candidates, then Haversine calculates exact great-circle distance. For 1M rows this avoids a full table scan for exact distance; a spatial index or `ST_Distance_Sphere()` would be the next optimisation.
- **Configuration:** `.env`, `compose.yaml`, and CI job-level `DB_*` variables all target the same MariaDB setup.
- **Deployment:** the Dockerfile runs FrankenPHP/Octane. For production, set `APP_DEBUG=false`, use a strong `APP_KEY`, terminate TLS at a reverse proxy, and use a managed MariaDB instance.

## Release archive

Push a version tag and the `Release` GitHub Action automatically builds and publishes `ev-api-<tag>.zip` as a GitHub release:

```bash
git tag v1.0.0
git push origin v1.0.0
```

The same workflow can be run manually from the Actions tab; choose `Release`, click **Run workflow**, and enter a tag such as `v1.0.0`.

The archive contains all source, this README, `composer.json`, `composer.lock`, installed dependencies, and the project's `.git` directory. It excludes `.env` and generated cache/log files. A smaller archive can be built by excluding `vendor/`, which is then rebuilt with `composer install` from `composer.lock`.
