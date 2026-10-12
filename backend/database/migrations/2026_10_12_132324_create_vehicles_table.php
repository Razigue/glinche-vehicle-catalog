<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->string('model');
            $table->string('version')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedInteger('mileage');
            $table->string('energy', 2)->nullable();
            $table->string('gearbox', 3)->nullable();
            $table->unsignedInteger('price')->nullable()->comment('In euro cents, VAT included');
            $table->string('photo_url', 2048)->nullable();
            $table->json('raw');
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->index(['brand_id', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
