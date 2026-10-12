<?php

namespace App\Http\Resources;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Vehicle
 */
class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->external_id,
            'brand' => BrandResource::make($this->whenLoaded('brand')),
            'model' => $this->model,
            'version' => $this->version,
            'year' => $this->year,
            'mileage' => $this->mileage,
            'energy' => $this->energy?->label(),
            'gearbox' => $this->gearbox?->label(),
            'price_in_cents' => $this->price,
            'photo_url' => $this->photo_url,
        ];
    }
}
