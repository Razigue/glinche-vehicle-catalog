<?php

use App\Console\Commands\SyncVehicles;
use App\Enums\Energy;
use App\Models\Brand;
use App\Models\Vehicle;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

it('creates the vehicles and their brands', function () {
    fakeGlincheFeed([
        glincheAdvert(),
        glincheAdvert(['reference' => 'GLI00281653', 'vehicle' => ['manufacturer' => 'Peugeot', 'model' => '308']]),
    ]);

    $this->artisan('vehicles:sync')->assertSuccessful();

    expect(Brand::query()->orderBy('slug')->pluck('name', 'slug')->all())
        ->toBe(['peugeot' => 'Peugeot', 'toyota' => 'Toyota']);
    expect(Vehicle::query()->where('external_id', 'GLI00004457')->sole())
        ->brand->name->toBe('Toyota')
        ->model->toBe('YARIS CROSS')
        ->version->toBe('Hybride 130h Design 2WD')
        ->mileage->toBe(20)
        ->energy->toBe(Energy::PetrolHybrid)
        ->price->toBe(2_670_000)
        ->photo_url->toBe('https://glinche.test/pictures/main.jpg')
        ->raw->toBe(glincheAdvert())
        ->synced_at->not->toBeNull();
});

it('updates a vehicle whose advert has changed', function () {
    fakeGlincheFeed(
        [glincheAdvert()],
        [glincheAdvert(['vehicle' => ['mileage' => 1500, 'prices' => ['merchantPrice' => '25900.00']]])],
    );

    $this->artisan('vehicles:sync');
    $this->artisan('vehicles:sync')
        ->assertSuccessful()
        ->expectsTable(
            ['created', 'updated', 'unchanged', 'deleted', 'skipped'],
            [[0, 1, 0, 0, 0]],
        );

    expect(Vehicle::query()->sole())
        ->mileage->toBe(1500)
        ->price->toBe(2_590_000);
});

it('is idempotent', function () {
    fakeGlincheFeed([glincheAdvert()], [glincheAdvert()]);
    $this->artisan('vehicles:sync');
    $firstSync = Vehicle::query()->sole();

    $this->artisan('vehicles:sync')
        ->assertSuccessful()
        ->expectsTable(
            ['created', 'updated', 'unchanged', 'deleted', 'skipped'],
            [[0, 0, 1, 0, 0]],
        );

    $secondSync = Vehicle::query()->sole();

    expect(Brand::query()->count())->toBe(1)
        ->and($secondSync->id)->toBe($firstSync->id)
        ->and($secondSync->except(['synced_at', 'updated_at']))->toEqual($firstSync->except(['synced_at', 'updated_at']));
});

it('ignores the key order of the stored payload when detecting changes', function () {
    fakeGlincheFeed([glincheAdvert()], [glincheAdvert()]);
    $this->artisan('vehicles:sync');

    $vehicle = Vehicle::query()->sole();
    $vehicle->raw = array_reverse($vehicle->raw, preserve_keys: true);
    $vehicle->save();

    $this->artisan('vehicles:sync')
        ->assertSuccessful()
        ->expectsTable(
            ['created', 'updated', 'unchanged', 'deleted', 'skipped'],
            [[0, 0, 1, 0, 0]],
        );
});

it('groups brand spelling variants under the same brand', function () {
    fakeGlincheFeed([
        glincheAdvert(['reference' => 'GLI00000001', 'vehicle' => ['manufacturer' => 'Citroën']]),
        glincheAdvert(['reference' => 'GLI00000002', 'vehicle' => ['manufacturer' => 'Citroen']]),
    ]);

    $this->artisan('vehicles:sync')->assertSuccessful();

    expect(Brand::query()->sole())
        ->name->toBe('Citroën')
        ->slug->toBe('citroen')
        ->vehicles->toHaveCount(2);
});

it('removes vehicles that left the feed or were deleted from it', function () {
    fakeGlincheFeed(
        [
            glincheAdvert(['reference' => 'GLI00000001']),
            glincheAdvert(['reference' => 'GLI00000002']),
            glincheAdvert(['reference' => 'GLI00000003']),
        ],
        [
            glincheAdvert(['reference' => 'GLI00000001']),
            glincheAdvert(['reference' => 'GLI00000002', 'deleteDate' => '2026-10-01T10:00:00+02:00']),
        ],
    );

    $this->artisan('vehicles:sync');
    $this->artisan('vehicles:sync')
        ->assertSuccessful()
        ->expectsTable(
            ['created', 'updated', 'unchanged', 'deleted', 'skipped'],
            [[0, 0, 1, 2, 0]],
        );

    expect(Vehicle::query()->pluck('external_id')->all())->toBe(['GLI00000001']);
});

it('skips an invalid advert and keeps its last valid copy', function () {
    $invalidAdvert = glincheAdvert(['vehicle' => ['mileage' => 1500]]);
    unset($invalidAdvert['vehicle']['manufacturer']);
    fakeGlincheFeed(
        [glincheAdvert()],
        [$invalidAdvert, glincheAdvert(['reference' => 'GLI00281653'])],
    );

    $this->artisan('vehicles:sync');
    $this->artisan('vehicles:sync')
        ->assertSuccessful()
        ->expectsTable(
            ['created', 'updated', 'unchanged', 'deleted', 'skipped'],
            [[1, 0, 0, 0, 1]],
        );

    expect(Vehicle::query()->orderBy('external_id')->pluck('mileage', 'external_id')->all())
        ->toBe(['GLI00004457' => 20, 'GLI00281653' => 20]);
});

it('keeps synchronizing the other adverts when one does not fit in the database', function () {
    fakeGlincheFeed([
        glincheAdvert(['reference' => 'GLI00000001', 'vehicle' => ['finish' => str_repeat('x', 300)]]),
        glincheAdvert(['reference' => 'GLI00000002']),
    ]);

    $this->artisan('vehicles:sync')->assertSuccessful();

    expect(Vehicle::query()->pluck('external_id')->all())->toBe(['GLI00000002']);
});

it('does not run while another synchronization holds the lock', function () {
    Http::fake();
    $lock = Cache::lock(SyncVehicles::LOCK_NAME, 60);
    $lock->get();

    $this->artisan('vehicles:sync')
        ->expectsOutputToContain('already running')
        ->assertSuccessful();

    Http::assertNothingSent();
    $lock->release();
});

it('releases the lock once the synchronization is over', function () {
    fakeGlincheFeed([]);

    $this->artisan('vehicles:sync')->assertSuccessful();

    expect(Cache::lock(SyncVehicles::LOCK_NAME, 60)->get())->toBeTrue();
});

it('fails without touching the stored vehicles when the API is down', function () {
    Sleep::fake();
    Http::fake([
        'glinche.test/api/partners/login' => Http::response(['token' => 'secret-token']),
        'glinche.test/api/partners/vehicles' => Http::sequence()
            ->push([glincheAdvert()])
            ->whenEmpty(Http::response('Service Unavailable', 503)),
    ]);

    $this->artisan('vehicles:sync');
    $this->artisan('vehicles:sync')->assertFailed();

    expect(Vehicle::query()->count())->toBe(1);
});

it('is scheduled every hour', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn (Event $event) => str_contains($event->command, 'vehicles:sync'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 * * * *')
        ->and($event->withoutOverlapping)->toBeTrue();
});
