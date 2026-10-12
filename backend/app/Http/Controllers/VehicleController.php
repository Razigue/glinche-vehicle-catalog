<?php

namespace App\Http\Controllers;

use App\Http\Resources\VehicleResource;
use App\Models\Brand;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VehicleController extends Controller
{
    public const PER_PAGE = 6;

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'brand' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $vehicles = Vehicle::query()
            ->with('brand')
            ->when($filters['brand'] ?? null, fn ($query, string $slug) => $query->whereRelation('brand', 'slug', $slug))
            ->orderBy(Brand::query()->select('name')->whereColumn('brands.id', 'vehicles.brand_id'))
            ->orderBy('model')
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return VehicleResource::collection($vehicles);
    }
}
