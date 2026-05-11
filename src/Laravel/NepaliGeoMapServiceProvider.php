<?php

declare(strict_types=1);

namespace RayzenAI\NepaliGeoMap\Laravel;

use Illuminate\Support\ServiceProvider;
use RayzenAI\NepaliGeoMap\Database\Seeders\NepaliGeoMapSeeder;

/**
 * Registers the package's migrations + makes them publishable. Activated
 * automatically by Laravel package auto-discovery (see `composer.json#extra`).
 *
 * Migrations are loaded directly (auto-run on `php artisan migrate`); publish
 * them with `php artisan vendor:publish --tag=nepali-geo-map-migrations` if you
 * want the timestamps under your own control.
 *
 * The seeder is not auto-registered; call it explicitly:
 *
 *   $this->call(\RayzenAI\NepaliGeoMap\Database\Seeders\NepaliGeoMapSeeder::class);
 */
final class NepaliGeoMapServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $migrationsPath = __DIR__ . '/../../database/migrations';

        $this->loadMigrationsFrom($migrationsPath);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $migrationsPath => $this->app->databasePath('migrations'),
            ], 'nepali-geo-map-migrations');

            $this->commands([
                Console\SeedNepaliGeoMapCommand::class,
            ]);
        }
    }
}
