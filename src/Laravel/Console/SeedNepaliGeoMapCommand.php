<?php

declare(strict_types=1);

namespace RayzenAI\NepaliGeoMap\Laravel\Console;

use Illuminate\Console\Command;
use RayzenAI\NepaliGeoMap\Database\Seeders\NepaliGeoMapSeeder;

/**
 * One-shot artisan wrapper around `NepaliGeoMapSeeder` for ops convenience.
 *
 *   php artisan nepali-geo-map:seed
 */
final class SeedNepaliGeoMapCommand extends Command
{
    protected $signature = 'nepali-geo-map:seed';

    protected $description = 'Populate Nepal provinces, districts, local units, and constituencies tables.';

    public function handle(): int
    {
        $this->info('Seeding Nepal administrative + electoral geography...');

        $seeder = new NepaliGeoMapSeeder;
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Done.');

        return self::SUCCESS;
    }
}
