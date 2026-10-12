<?php

namespace App\Console\Commands;

use App\Services\Glinche\GlincheApiException;
use App\Services\Glinche\VehicleSynchronizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('vehicles:sync')]
#[Description('Synchronize the local vehicles with the Glinche partners API')]
class SyncVehicles extends Command
{
    public function handle(VehicleSynchronizer $synchronizer): int
    {
        try {
            $report = $synchronizer->sync();
        } catch (GlincheApiException $exception) {
            Log::error('Vehicle synchronization failed.', ['reason' => $exception->getMessage()]);
            $this->components->error("Vehicle synchronization failed: {$exception->getMessage()}");

            return self::FAILURE;
        }

        Log::info('Vehicle synchronization completed.', $report->toArray());
        $this->components->info('Vehicle synchronization completed.');
        $this->table(array_keys($report->toArray()), [$report->toArray()]);

        return self::SUCCESS;
    }
}
