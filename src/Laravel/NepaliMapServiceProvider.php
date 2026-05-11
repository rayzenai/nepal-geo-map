<?php

declare(strict_types=1);

namespace RayzenAI\NepaliMap\Laravel;

use Illuminate\Support\ServiceProvider;
use RayzenAI\NepaliMap\Database\Seeders\NepaliMapSeeder;

/**
 * Registers the package's migrations + makes them publishable. Activated
 * automatically by Laravel package auto-discovery (see `composer.json#extra`).
 *
 * Migrations are loaded directly (auto-run on `php artisan migrate`); publish
 * them with `php artisan vendor:publish --tag=nepali-map-migrations` if you
 * want the timestamps under your own control.
 *
 * The seeder is not auto-registered; call it explicitly:
 *
 *   $this->call(\RayzenAI\NepaliMap\Database\Seeders\NepaliMapSeeder::class);
 */
final class NepaliMapServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $migrationsPath = __DIR__ . '/../../database/migrations';

        $this->loadMigrationsFrom($migrationsPath);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $migrationsPath => $this->app->databasePath('migrations'),
            ], 'nepali-map-migrations');

            $this->commands([
                Console\SeedNepaliMapCommand::class,
            ]);
        }
    }
}
