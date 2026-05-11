<?php

declare(strict_types=1);

namespace RayzenAI\NepaliGeoMap\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RayzenAI\NepaliGeoMap\NepaliGeoMap;

/**
 * Populate `nepal_provinces`, `nepal_districts`, `nepal_local_units`, and
 * `nepal_constituencies` from the vendored JSON. Idempotent — uses `upsert`
 * keyed on `code`, so re-running it just refreshes mutable fields.
 *
 * Usage:
 *
 *   php artisan db:seed --class="RayzenAI\\NepaliGeoMap\\Database\\Seeders\\NepaliGeoMapSeeder"
 *
 * Or from your own seeder:
 *
 *   $this->call(\RayzenAI\NepaliGeoMap\Database\Seeders\NepaliGeoMapSeeder::class);
 */
final class NepaliGeoMapSeeder extends Seeder
{
    public function run(): void
    {
        $now = new \DateTimeImmutable;

        $this->command?->info('Seeding 7 provinces...');
        $provinceRows = [];
        $provinceCodeToId = [];

        foreach (NepaliGeoMap::provinces() as $p) {
            $provinceRows[] = [
                'code' => $p->id,
                'number' => $p->number,
                'slug' => $p->slug,
                'name_en' => $p->nameEn,
                'name_ne' => $p->nameNe,
                'capital' => $p->capital,
                'capital_ne' => $p->capitalNe,
                'capital_lat' => $p->capitalCoords['lat'] ?? null,
                'capital_lng' => $p->capitalCoords['lng'] ?? null,
                'area_km2' => $p->areaKm2,
                'population' => $p->population,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('nepal_provinces')->upsert(
            $provinceRows,
            ['code'],
            ['number', 'slug', 'name_en', 'name_ne', 'capital', 'capital_ne', 'capital_lat', 'capital_lng', 'area_km2', 'population', 'updated_at'],
        );

        foreach (DB::table('nepal_provinces')->get(['id', 'code']) as $row) {
            $provinceCodeToId[$row->code] = $row->id;
        }

        $this->command?->info('Seeding 77 districts...');
        $districtRows = [];
        $districtCodeToId = [];

        foreach (NepaliGeoMap::districts() as $d) {
            $districtRows[] = [
                'code' => $d->id,
                'nepal_province_id' => $provinceCodeToId[$d->provinceId],
                'slug' => $d->slug,
                'name_en' => $d->nameEn,
                'name_ne' => $d->nameNe,
                'headquarters' => $d->headquarters,
                'headquarters_ne' => $d->headquartersNe,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('nepal_districts')->upsert(
            $districtRows,
            ['code'],
            ['nepal_province_id', 'slug', 'name_en', 'name_ne', 'headquarters', 'headquarters_ne', 'updated_at'],
        );

        foreach (DB::table('nepal_districts')->get(['id', 'code']) as $row) {
            $districtCodeToId[$row->code] = $row->id;
        }

        $this->command?->info('Seeding 753 local units...');
        $unitChunks = array_chunk(
            iterator_to_array($this->localUnitRows($districtCodeToId, $now)),
            200,
        );

        foreach ($unitChunks as $chunk) {
            DB::table('nepal_local_units')->upsert(
                $chunk,
                ['code'],
                ['nepal_district_id', 'slug', 'name_en', 'name_ne', 'type', 'wards', 'postal_code', 'lat', 'lng', 'updated_at'],
            );
        }

        $this->command?->info('Seeding 165 federal + 330 provincial constituencies...');
        $constituencyRows = [];

        foreach (NepaliGeoMap::constituencies() as $c) {
            $constituencyRows[] = [
                'code' => $c->id,
                'type' => $c->type,
                'nepal_province_id' => $provinceCodeToId[$c->provinceId],
                'nepal_district_id' => $c->districtId !== null ? ($districtCodeToId[$c->districtId] ?? null) : null,
                'number' => $c->number,
                'slug' => $c->slug,
                'name_en' => $c->nameEn,
                'name_ne' => $c->nameNe,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($constituencyRows, 200) as $chunk) {
            DB::table('nepal_constituencies')->upsert(
                $chunk,
                ['code'],
                ['type', 'nepal_province_id', 'nepal_district_id', 'number', 'slug', 'name_en', 'name_ne', 'updated_at'],
            );
        }

        $this->command?->info(sprintf(
            '  provinces: %d, districts: %d, local units: %d, constituencies: %d',
            count(NepaliGeoMap::provinces()),
            count(NepaliGeoMap::districts()),
            count(NepaliGeoMap::localUnits()),
            count(NepaliGeoMap::constituencies()),
        ));
    }

    /**
     * @param  array<string, int>  $districtCodeToId
     * @return iterable<int, array<string, mixed>>
     */
    private function localUnitRows(array $districtCodeToId, \DateTimeInterface $now): iterable
    {
        foreach (NepaliGeoMap::localUnits() as $u) {
            yield [
                'code' => $u->id,
                'nepal_district_id' => $districtCodeToId[$u->districtId],
                'slug' => $u->slug,
                'name_en' => $u->nameEn,
                'name_ne' => $u->nameNe,
                'type' => $u->type,
                'wards' => $u->wards,
                'postal_code' => $u->postalCode,
                'lat' => $u->coords['lat'] ?? null,
                'lng' => $u->coords['lng'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
    }
}
