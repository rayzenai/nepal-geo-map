<?php

declare(strict_types=1);

namespace RayzenAI\NepaliMap\Laravel\Console;

use Illuminate\Console\Command;
use RayzenAI\NepaliMap\Database\Seeders\NepaliMapSeeder;

/**
 * One-shot artisan wrapper around `NepaliMapSeeder` for ops convenience.
 *
 *   php artisan nepali-map:seed
 */
final class SeedNepaliMapCommand extends Command
{
    protected $signature = 'nepali-map:seed';

    protected $description = 'Populate Nepal provinces, districts, local units, and constituencies tables.';

    public function handle(): int
    {
        $this->info('Seeding Nepal administrative + electoral geography...');

        $seeder = new NepaliMapSeeder;
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Done.');

        return self::SUCCESS;
    }
}
