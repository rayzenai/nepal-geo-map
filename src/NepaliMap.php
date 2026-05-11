<?php

declare(strict_types=1);

namespace RayzenAI\NepaliMap;

use RayzenAI\NepaliMap\Address\Formatter;
use RayzenAI\NepaliMap\Address\Parser;
use RayzenAI\NepaliMap\Address\Validator;
use RayzenAI\NepaliMap\Geo\PointInPolygon;
use RayzenAI\NepaliMap\Search\Search;

/**
 * Main entry point for Nepal administrative geography.
 *
 * All public methods are static and operate on lazily-loaded vendored JSON.
 * Indexes (id / slug / nameEn / nameNe / aliases) are built on first call
 * and held for the lifetime of the request.
 */
final class NepaliMap
{
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $indexes = null;

    // =====================================================================
    //  PROVINCES
    // =====================================================================

    /** @return list<Province> */
    public static function provinces(): array
    {
        return self::cachedList('provinces', fn (array $row): Province => Province::fromArray($row));
    }

    public static function province(string|int $query): ?Province
    {
        if (is_int($query) || ctype_digit((string) $query)) {
            $number = (int) $query;
            foreach (self::provinces() as $p) {
                if ($p->number === $number) {
                    return $p;
                }
            }

            return null;
        }

        return self::lookup('provinces', (string) $query);
    }

    public static function isValidProvince(string|int $query): bool
    {
        return self::province($query) !== null;
    }

    // =====================================================================
    //  DISTRICTS
    // =====================================================================

    /** @return list<District> */
    public static function districts(): array
    {
        return self::cachedList('districts', fn (array $row): District => District::fromArray($row));
    }

    public static function district(string $query): ?District
    {
        return self::lookup('districts', $query);
    }

    /** @return list<District> */
    public static function districtsByProvince(string|int $query): array
    {
        $province = self::province($query);

        if ($province === null) {
            return [];
        }

        return array_values(array_filter(
            self::districts(),
            fn (District $d): bool => $d->provinceId === $province->id,
        ));
    }

    public static function isValidDistrict(string $query): bool
    {
        return self::district($query) !== null;
    }

    // =====================================================================
    //  LOCAL UNITS (palikas)
    // =====================================================================

    /** @return list<LocalUnit> */
    public static function localUnits(): array
    {
        return self::cachedList('local-units', fn (array $row): LocalUnit => LocalUnit::fromArray($row));
    }

    public static function localUnit(string $query): ?LocalUnit
    {
        return self::lookup('local-units', $query);
    }

    /** @return list<LocalUnit> */
    public static function localUnitsByDistrict(string $query): array
    {
        $district = self::district($query);

        if ($district === null) {
            return [];
        }

        return array_values(array_filter(
            self::localUnits(),
            fn (LocalUnit $u): bool => $u->districtId === $district->id,
        ));
    }

    /** @return list<LocalUnit> */
    public static function localUnitsByProvince(string|int $query): array
    {
        $province = self::province($query);

        if ($province === null) {
            return [];
        }

        return array_values(array_filter(
            self::localUnits(),
            fn (LocalUnit $u): bool => $u->provinceId() === $province->id,
        ));
    }

    public static function isValidLocalUnit(string $query): bool
    {
        return self::localUnit($query) !== null;
    }

    public static function isValidWard(string $localUnitQuery, int $ward): bool
    {
        $unit = self::localUnit($localUnitQuery);

        return $unit !== null && $ward >= 1 && $ward <= $unit->wards;
    }

    // =====================================================================
    //  POSTAL CODES
    // =====================================================================

    public static function findByPostalCode(string $code): ?LocalUnit
    {
        $code = trim($code);
        /** @var array<string, string> $map */
        $map = Data::load('postal-codes.json');

        foreach ($map as $localUnitId => $postal) {
            if ($postal === $code) {
                return self::localUnit($localUnitId);
            }
        }

        /** @var array<string, list<string>> $branches */
        $branches = Data::load('postal-code-branches.json');

        foreach ($branches as $localUnitId => $codes) {
            if (in_array($code, $codes, true)) {
                return self::localUnit($localUnitId);
            }
        }

        return null;
    }

    public static function postalCode(string $localUnitQuery): ?string
    {
        $unit = self::localUnit($localUnitQuery);

        if ($unit?->postalCode !== null) {
            return $unit->postalCode;
        }

        if ($unit === null) {
            return null;
        }

        /** @var array<string, string> $map */
        $map = Data::load('postal-codes.json');

        return $map[$unit->id] ?? null;
    }

    public static function postalCode2025(string $localUnitQuery): ?string
    {
        $unit = self::localUnit($localUnitQuery);

        if ($unit === null) {
            return null;
        }

        /** @var array<string, string> $map */
        $map = Data::load('postal-codes-2025.json');

        return $map[$unit->id] ?? null;
    }

    public static function districtPostcodePrefix(string $districtQuery): ?string
    {
        $district = self::district($districtQuery);

        if ($district === null) {
            return null;
        }

        /** @var array<string, string> $map */
        $map = Data::load('district-postcode-prefixes.json');

        return $map[$district->id] ?? null;
    }

    // =====================================================================
    //  REGIONS (legacy, pre-2015)
    // =====================================================================

    /** @return list<Region> */
    public static function regions(): array
    {
        return self::cachedList('regions', fn (array $row): Region => Region::fromArray($row));
    }

    public static function region(string|int $query): ?Region
    {
        if (is_int($query) || ctype_digit((string) $query)) {
            $number = (int) $query;
            foreach (self::regions() as $r) {
                if ($r->number === $number) {
                    return $r;
                }
            }

            return null;
        }

        return self::lookup('regions', (string) $query);
    }

    public static function isValidRegion(string|int $query): bool
    {
        return self::region($query) !== null;
    }

    // =====================================================================
    //  ZONES (legacy, pre-2015)
    // =====================================================================

    /** @return list<Zone> */
    public static function zones(): array
    {
        return self::cachedList('zones', fn (array $row): Zone => Zone::fromArray($row));
    }

    public static function zone(string $query): ?Zone
    {
        return self::lookup('zones', $query);
    }

    /** @return list<Zone> */
    public static function zonesByRegion(string|int $query): array
    {
        $region = self::region($query);

        if ($region === null) {
            return [];
        }

        return array_values(array_filter(
            self::zones(),
            fn (Zone $z): bool => $z->regionId === $region->id,
        ));
    }

    public static function isValidZone(string $query): bool
    {
        return self::zone($query) !== null;
    }

    // =====================================================================
    //  LEGACY DISTRICTS (pre-2017 federal restructuring)
    // =====================================================================

    /** @return list<LegacyDistrict> */
    public static function legacyDistricts(): array
    {
        return self::cachedList('legacy-districts', fn (array $row): LegacyDistrict => LegacyDistrict::fromArray($row));
    }

    public static function legacyDistrict(string $query): ?LegacyDistrict
    {
        return self::lookup('legacy-districts', $query);
    }

    /** @return list<LegacyDistrict> */
    public static function legacyDistrictsByZone(string $query): array
    {
        $zone = self::zone($query);

        if ($zone === null) {
            return [];
        }

        return array_values(array_filter(
            self::legacyDistricts(),
            fn (LegacyDistrict $l): bool => $l->zoneId === $zone->id,
        ));
    }

    /** @return list<District> */
    public static function currentDistrictsForLegacyDistrict(string $query): array
    {
        $legacy = self::legacyDistrict($query);

        if ($legacy === null) {
            return [];
        }

        $out = [];

        foreach ($legacy->currentDistrictIds as $id) {
            $d = self::district($id);

            if ($d !== null) {
                $out[] = $d;
            }
        }

        return $out;
    }

    public static function legacyDistrictForCurrentDistrict(string $query): ?LegacyDistrict
    {
        $district = self::district($query);

        if ($district === null) {
            return null;
        }

        foreach (self::legacyDistricts() as $legacy) {
            if (in_array($district->id, $legacy->currentDistrictIds, true)) {
                return $legacy;
            }
        }

        return null;
    }

    public static function isValidLegacyDistrict(string $query): bool
    {
        return self::legacyDistrict($query) !== null;
    }

    // =====================================================================
    //  CONSTITUENCIES (federal + provincial)
    // =====================================================================

    /** @return list<Constituency> */
    public static function constituencies(?string $type = null): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = Data::load('constituencies.json');

        $out = [];
        foreach ($rows as $row) {
            if ($type !== null && ($row['type'] ?? null) !== $type) {
                continue;
            }
            $out[] = Constituency::fromArray($row);
        }

        return $out;
    }

    public static function constituency(string $query): ?Constituency
    {
        $needle = mb_strtolower(trim($query), 'UTF-8');

        foreach (self::constituencies() as $c) {
            if (
                strcasecmp($c->id, $query) === 0
                || strcasecmp($c->slug, $needle) === 0
                || strcasecmp($c->nameEn, $needle) === 0
                || strcasecmp($c->nameNe, $needle) === 0
            ) {
                return $c;
            }
        }

        return null;
    }

    /** @return list<Constituency> */
    public static function federalConstituenciesByDistrict(string $districtQuery): array
    {
        $district = self::district($districtQuery);

        if ($district === null) {
            return [];
        }

        return array_values(array_filter(
            self::constituencies(Constituency::TYPE_FEDERAL),
            fn (Constituency $c): bool => $c->districtId === $district->id,
        ));
    }

    /** @return list<Constituency> */
    public static function provincialConstituenciesByProvince(string|int $provinceQuery): array
    {
        $province = self::province($provinceQuery);

        if ($province === null) {
            return [];
        }

        return array_values(array_filter(
            self::constituencies(Constituency::TYPE_PROVINCIAL),
            fn (Constituency $c): bool => $c->provinceId === $province->id,
        ));
    }

    // =====================================================================
    //  COORDINATE LOOKUPS — point-in-polygon
    // =====================================================================

    public static function findProvinceByCoords(float $lat, float $lng): ?Province
    {
        $id = PointInPolygon::findFeatureId('nepal-provinces.json', $lat, $lng);

        return $id === null ? null : self::province($id);
    }

    public static function findDistrictByCoords(float $lat, float $lng): ?District
    {
        $id = PointInPolygon::findFeatureId('nepal-districts.json', $lat, $lng);

        return $id === null ? null : self::district($id);
    }

    public static function findLocalUnitByCoords(float $lat, float $lng): ?LocalUnit
    {
        $props = PointInPolygon::findFeatureProperties('nepal-local-units.json', $lat, $lng);

        if ($props === null || ! isset($props['districtId'], $props['nameEn'])) {
            return null;
        }

        $districtId = (string) $props['districtId'];
        $nameEn = (string) $props['nameEn'];

        foreach (self::localUnitsByDistrict($districtId) as $unit) {
            if (strcasecmp($unit->nameEn, $nameEn) === 0) {
                return $unit;
            }
        }

        return null;
    }

    // =====================================================================
    //  SEARCH
    // =====================================================================

    /** @return list<array{type: string, id: string, slug: string, nameEn: string, nameNe: string, score: float}> */
    public static function search(string $query, int $limit = 10): array
    {
        return Search::run($query, $limit);
    }

    // =====================================================================
    //  ADDRESS
    // =====================================================================

    /**
     * @param  array{
     *     ward?: int,
     *     tole?: string,
     *     localUnit?: string,
     *     district?: string,
     *     province?: string|int,
     *     postalCode?: string,
     *     country?: string,
     * }  $parts
     * @param  array{lang?: 'en'|'ne', style?: 'short'|'long'|'postal'}|null  $options
     */
    public static function formatAddress(array $parts, ?array $options = null): string
    {
        return Formatter::format($parts, $options);
    }

    /**
     * @return array{
     *     ward: ?int,
     *     tole: ?string,
     *     localUnit: ?string,
     *     district: ?string,
     *     province: ?string,
     *     postalCode: ?string,
     *     confidence: float,
     * }
     */
    public static function parseAddress(string $raw): array
    {
        return Parser::parse($raw);
    }

    /**
     * @param  array<string, mixed>  $parts
     * @return array{valid: bool, errors: list<string>}
     */
    public static function validateAddress(array $parts): array
    {
        return Validator::validate($parts);
    }

    // =====================================================================
    //  INTERNAL
    // =====================================================================

    /**
     * @template T
     *
     * @param  callable(array<string, mixed>): T  $factory
     * @return list<T>
     */
    private static function cachedList(string $key, callable $factory): array
    {
        self::ensureIndexes();

        /** @var list<T> $items */
        $items = self::$indexes[$key]['items'];

        return $items;
    }

    /**
     * @template T of Province|District|LocalUnit|Region|Zone|LegacyDistrict
     *
     * @return T|null
     */
    private static function lookup(string $key, string $query)
    {
        self::ensureIndexes();

        $needle = self::normalize($query);

        if ($needle === '') {
            return null;
        }

        /** @var array<string, T> $byKey */
        $byKey = self::$indexes[$key]['byKey'];

        return $byKey[$needle] ?? null;
    }

    private static function ensureIndexes(): void
    {
        if (self::$indexes !== null) {
            return;
        }

        self::$indexes = [];

        self::buildIndex('provinces', 'provinces.json', fn (array $row) => Province::fromArray($row), [
            'id', 'slug', 'nameEn', 'nameNe', 'number',
        ]);
        self::buildIndex('districts', 'districts.json', fn (array $row) => District::fromArray($row), [
            'id', 'slug', 'nameEn', 'nameNe',
        ]);
        self::buildIndex('local-units', 'local-units.json', fn (array $row) => LocalUnit::fromArray($row), [
            'id', 'slug', 'nameEn', 'nameNe',
        ]);
        self::buildIndex('regions', 'regions.json', fn (array $row) => Region::fromArray($row), [
            'id', 'slug', 'nameEn', 'nameNe', 'number',
        ]);
        self::buildIndex('zones', 'zones.json', fn (array $row) => Zone::fromArray($row), [
            'id', 'slug', 'nameEn', 'nameNe',
        ]);
        self::buildIndex('legacy-districts', 'legacy-districts.json', fn (array $row) => LegacyDistrict::fromArray($row), [
            'id', 'slug', 'nameEn', 'nameNe',
        ]);
    }

    /**
     * @param  list<string>  $keyFields
     */
    private static function buildIndex(string $bucket, string $file, callable $factory, array $keyFields): void
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = Data::load($file);

        $items = [];
        $byKey = [];

        foreach ($rows as $row) {
            $entity = $factory($row);
            $items[] = $entity;

            foreach ($keyFields as $field) {
                $value = $row[$field] ?? null;

                if ($value === null) {
                    continue;
                }

                $key = self::normalize((string) $value);

                if ($key !== '') {
                    $byKey[$key] = $entity;
                }
            }

            foreach ((array) ($row['aliases'] ?? []) as $alias) {
                $key = self::normalize((string) $alias);

                if ($key !== '') {
                    $byKey[$key] = $entity;
                }
            }
        }

        self::$indexes[$bucket] = ['items' => $items, 'byKey' => $byKey];
    }

    /**
     * Normalize a lookup query: lowercase, trim, collapse whitespace.
     * Keeps Devanagari intact; only Latin characters get folded.
     */
    private static function normalize(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        // mb_strtolower handles Latin; Devanagari has no case so it's a no-op.
        return mb_strtolower($value, 'UTF-8');
    }

    /** @internal */
    public static function flush(): void
    {
        self::$indexes = null;
        Data::flush();
    }
}
