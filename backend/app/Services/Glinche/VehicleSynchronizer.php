<?php

namespace App\Services\Glinche;

use App\Models\Brand;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VehicleSynchronizer
{
    public function __construct(
        private readonly GlincheApiClient $client,
        private readonly VehicleMapper $mapper,
    ) {}

    /**
     * Mirrors the partner feed: vehicles missing from it or flagged as deleted are removed locally.
     *
     * @throws GlincheApiException
     */
    public function sync(): SyncReport
    {
        $adverts = $this->client->fetchVehicles();

        return DB::transaction(function () use ($adverts): SyncReport {
            $report = new SyncReport;
            $syncedAt = now();

            foreach ($adverts as $advert) {
                try {
                    $vehicleData = $this->mapper->map($advert);
                } catch (ValidationException $exception) {
                    $report->skipped++;
                    Log::warning('Skipped an invalid Glinche advert.', [
                        'reference' => $advert['reference'] ?? null,
                        'errors' => $exception->errors(),
                    ]);

                    continue;
                }

                if (! $vehicleData->isDeleted) {
                    $this->upsert($vehicleData, $syncedAt, $report);
                }
            }

            $report->deleted = Vehicle::query()
                ->whereNotIn('external_id', $this->listedReferences($adverts))
                ->delete();

            return $report;
        });
    }

    /**
     * An advert that is still listed but invalid keeps its last valid local copy.
     *
     * @param  list<array<string, mixed>>  $adverts
     * @return list<string>
     */
    private function listedReferences(array $adverts): array
    {
        return collect($adverts)
            ->filter(fn (array $advert) => ($advert['deleteDate'] ?? null) === null)
            ->pluck('reference')
            ->filter(fn (mixed $reference) => is_string($reference))
            ->values()
            ->all();
    }

    private function upsert(VehicleData $vehicleData, Carbon $syncedAt, SyncReport $report): void
    {
        $vehicle = Vehicle::query()->firstOrNew(['external_id' => $vehicleData->externalId]);

        $vehicle->fill([
            'brand_id' => $this->findOrCreateBrand($vehicleData->brandName)->id,
            'model' => $vehicleData->model,
            'version' => $vehicleData->version,
            'year' => $vehicleData->year,
            'mileage' => $vehicleData->mileage,
            'energy' => $vehicleData->energy,
            'gearbox' => $vehicleData->gearbox,
            'price' => $vehicleData->priceInCents,
            'photo_url' => $vehicleData->photoUrl,
        ]);

        // MySQL reorders JSON keys on storage, so a strict comparison would flag every vehicle as changed.
        if ($vehicle->raw != $vehicleData->raw) {
            $vehicle->raw = $vehicleData->raw;
        }

        match (true) {
            ! $vehicle->exists => $report->created++,
            $vehicle->isDirty() => $report->updated++,
            default => $report->unchanged++,
        };

        $vehicle->synced_at = $syncedAt;
        $vehicle->save();
    }

    private function findOrCreateBrand(string $name): Brand
    {
        return Brand::query()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
    }
}
