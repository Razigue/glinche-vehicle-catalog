<?php

namespace App\Http\Controllers;

use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BrandController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $brands = Brand::query()
            ->whereHas('vehicles')
            ->withCount('vehicles')
            ->orderBy('name')
            ->get();

        return BrandResource::collection($brands);
    }
}
