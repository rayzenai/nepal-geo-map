<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use RayzenAI\NepaliGeoMap\Database\Seeders\NepaliGeoMapSeeder;

beforeEach(function (): void {
    $capsule = new Capsule;
    $capsule->addConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    $container = new Container;
    $container['db'] = $capsule->getDatabaseManager();
    Container::setInstance($container);
    Facade::setFacadeApplication($container);

    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    $schema = $capsule->getConnection()->getSchemaBuilder();

    // Mirror the migration here so we don't need full testbench bootstrapping.
    $schema->create('nepal_provinces', function (Blueprint $t): void {
        $t->id();
        $t->string('code', 8)->unique();
        $t->unsignedTinyInteger('number')->unique();
        $t->string('slug')->unique();
        $t->string('name_en');
        $t->string('name_ne');
        $t->string('capital');
        $t->string('capital_ne');
        $t->decimal('capital_lat', 9, 6)->nullable();
        $t->decimal('capital_lng', 9, 6)->nullable();
        $t->float('area_km2')->nullable();
        $t->unsignedBigInteger('population')->nullable();
        $t->timestamps();
    });

    $schema->create('nepal_districts', function (Blueprint $t): void {
        $t->id();
        $t->string('code', 16)->unique();
        $t->foreignId('nepal_province_id')->constrained('nepal_provinces')->cascadeOnDelete();
        $t->string('slug')->unique();
        $t->string('name_en');
        $t->string('name_ne');
        $t->string('headquarters');
        $t->string('headquarters_ne')->nullable();
        $t->timestamps();
    });

    $schema->create('nepal_local_units', function (Blueprint $t): void {
        $t->id();
        $t->string('code', 32)->unique();
        $t->foreignId('nepal_district_id')->constrained('nepal_districts')->cascadeOnDelete();
        $t->string('slug');
        $t->string('name_en');
        $t->string('name_ne');
        $t->string('type', 24);
        $t->unsignedTinyInteger('wards');
        $t->string('postal_code', 8)->nullable();
        $t->decimal('lat', 9, 6)->nullable();
        $t->decimal('lng', 9, 6)->nullable();
        $t->timestamps();
    });

    $schema->create('nepal_constituencies', function (Blueprint $t): void {
        $t->id();
        $t->string('code', 32)->unique();
        $t->string('type', 16);
        $t->foreignId('nepal_province_id')->constrained('nepal_provinces')->cascadeOnDelete();
        $t->foreignId('nepal_district_id')->nullable()->constrained('nepal_districts')->cascadeOnDelete();
        $t->unsignedSmallInteger('number');
        $t->string('slug');
        $t->string('name_en');
        $t->string('name_ne');
        $t->timestamps();
    });
});

it('seeds provinces, districts, local units, and constituencies', function (): void {
    $seeder = new NepaliGeoMapSeeder;
    $seeder->run();

    expect(Illuminate\Support\Facades\DB::table('nepal_provinces')->count())->toBe(7);
    expect(Illuminate\Support\Facades\DB::table('nepal_districts')->count())->toBe(77);
    expect(Illuminate\Support\Facades\DB::table('nepal_local_units')->count())->toBe(753);
    expect(Illuminate\Support\Facades\DB::table('nepal_constituencies')->count())->toBe(495);
});

it('seeder is idempotent (re-running upserts without errors)', function (): void {
    $seeder = new NepaliGeoMapSeeder;
    $seeder->run();
    $seeder->run();

    expect(Illuminate\Support\Facades\DB::table('nepal_provinces')->count())->toBe(7);
    expect(Illuminate\Support\Facades\DB::table('nepal_districts')->count())->toBe(77);
});

it('links constituencies to provinces and districts', function (): void {
    $seeder = new NepaliGeoMapSeeder;
    $seeder->run();

    $kathmandu = Illuminate\Support\Facades\DB::table('nepal_districts')->where('slug', 'kathmandu')->first();
    expect($kathmandu)->not->toBeNull();

    $kathmanduFederal = Illuminate\Support\Facades\DB::table('nepal_constituencies')
        ->where('type', 'federal')
        ->where('nepal_district_id', $kathmandu->id)
        ->count();

    expect($kathmanduFederal)->toBe(10);
});
