<?php

namespace App\Models;

use App\Enums\Energy;
use App\Enums\Gearbox;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    protected $fillable = [
        'external_id',
        'brand_id',
        'model',
        'version',
        'year',
        'mileage',
        'energy',
        'gearbox',
        'price',
        'photo_url',
        'raw',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'energy' => Energy::class,
            'gearbox' => Gearbox::class,
            'raw' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
