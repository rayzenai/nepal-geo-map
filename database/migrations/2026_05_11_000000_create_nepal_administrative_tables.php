<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 7 provinces. `code` is the package-canonical id (P1..P7).
        Schema::create('nepal_provinces', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 8)->unique();
            $table->unsignedTinyInteger('number')->unique();
            $table->string('slug')->unique();
            $table->string('name_en');
            $table->string('name_ne');
            $table->string('capital');
            $table->string('capital_ne');
            $table->decimal('capital_lat', 9, 6)->nullable();
            $table->decimal('capital_lng', 9, 6)->nullable();
            $table->float('area_km2')->nullable();
            $table->unsignedBigInteger('population')->nullable();
            $table->timestamps();
        });

        // 77 districts.
        Schema::create('nepal_districts', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 16)->unique();             // e.g. P3.D05
            $table->foreignId('nepal_province_id')
                ->constrained('nepal_provinces')
                ->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('name_en');
            $table->string('name_ne');
            $table->string('headquarters');
            $table->string('headquarters_ne')->nullable();
            $table->timestamps();
            $table->index('nepal_province_id');
        });

        // 753 palikas (metropolitan / sub-metropolitan / municipality / rural-municipality).
        Schema::create('nepal_local_units', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();             // e.g. P3.D05.L01
            $table->foreignId('nepal_district_id')
                ->constrained('nepal_districts')
                ->cascadeOnDelete();
            $table->string('slug');                            // not globally unique — KMC and Kathmandu district both 'kathmandu'
            $table->string('name_en');
            $table->string('name_ne');
            $table->string('type', 24);
            $table->unsignedTinyInteger('wards');
            $table->string('postal_code', 8)->nullable();
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();
            $table->timestamps();
            $table->index('nepal_district_id');
            $table->index('slug');
            $table->index('postal_code');
        });

        // 165 federal + 330 provincial constituencies = 495 rows total.
        Schema::create('nepal_constituencies', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();             // e.g. F.P3.D05.1 / PR.P3.1
            $table->string('type', 16);                       // federal | provincial
            $table->foreignId('nepal_province_id')
                ->constrained('nepal_provinces')
                ->cascadeOnDelete();
            $table->foreignId('nepal_district_id')
                ->nullable()
                ->constrained('nepal_districts')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            $table->string('slug');
            $table->string('name_en');
            $table->string('name_ne');
            $table->timestamps();

            $table->index('type');
            $table->index('nepal_province_id');
            $table->index('nepal_district_id');
            $table->unique(['type', 'slug'], 'nepal_constituencies_type_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nepal_constituencies');
        Schema::dropIfExists('nepal_local_units');
        Schema::dropIfExists('nepal_districts');
        Schema::dropIfExists('nepal_provinces');
    }
};
