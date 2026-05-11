# @rayzenai/nepali-map

Nepal administrative geography (provinces, districts, palikas) + electoral
constituencies + addresses + an interactive Svelte map. Ships with vendored
data so there are **zero runtime dependencies on third-party packages**.

| Surface | Where |
|---------|-------|
| PHP / Laravel | `composer require rayzenai/nepali-map` → `RayzenAI\NepaliMap\NepaliMap::` |
| JavaScript / Svelte | `npm i @rayzenai/nepali-map` → `import { ... } from '@rayzenai/nepali-map'` |
| Vendored datasets | `data/*.json` — usable from any language that can read JSON |

## What's inside

- **7 provinces, 77 districts, 753 palikas** with bilingual (English + Nepali) names, slugs, postal codes, capital coordinates, ward counts.
- **165 federal + 330 provincial constituencies** for first-past-the-post elections (2079 BS / 2022 AD seat allocations).
- **5 development regions + 14 zones + 75 legacy districts** with crosswalk to the post-2017 federal districts.
- **GeoJSON boundaries** for provinces, districts, and palikas (post-2020 *chuche* map — includes Kalapani / Lipulekh / Limpiyadhura).
- **Postal-code lookup** (legacy 1991 + new 2025 federal-aligned systems) covering the 17 anchor postcodes + 392 cross-walked palikas.
- **Address utilities** — formatter (short / long / postal styles), free-form parser, validator.
- **Fuzzy bilingual search** across all entities.
- **Point-in-polygon** — turn `(lat, lng)` into the containing province / district / palika.
- **Interactive Svelte map** — heatmap fill, hover overlay, configurable slug remap.

## PHP / Laravel

```bash
composer require rayzenai/nepali-map
```

The package auto-registers via Laravel package discovery — its migrations
load automatically and `php artisan nepali-map:seed` becomes available.

```bash
php artisan migrate         # creates 4 tables: nepal_provinces, nepal_districts, nepal_local_units, nepal_constituencies
php artisan nepali-map:seed # populates 7 + 77 + 753 + 495 = 1,332 rows
```

If you'd rather own the migration timestamps:

```bash
php artisan vendor:publish --tag=nepali-map-migrations
```

### Pure-PHP usage

```php
use RayzenAI\NepaliMap\NepaliMap;

NepaliMap::province('bagmati')?->capital;            // "Hetauda"
NepaliMap::province(3)?->nameEn;                     // "Bagmati"
NepaliMap::districtsByProvince(3);                   // 13 District objects

NepaliMap::findByPostalCode('44600')?->nameEn;       // "Kathmandu Metropolitan City"
NepaliMap::findDistrictByCoords(27.7172, 85.3240)?->slug; // "kathmandu"

NepaliMap::formatAddress([
    'ward' => 10, 'tole' => 'Baluwatar',
    'localUnit' => 'P3.D05.L01', 'district' => 'kathmandu',
]);
// "Ward 10, Baluwatar, Kathmandu Metropolitan City, Kathmandu"

NepaliMap::parseAddress('Baluwatar, Kathmandu 44600');
// ['ward' => null, 'tole' => 'Baluwatar', 'localUnit' => 'kathmandu',
//  'district' => 'kathmandu', 'province' => 'bagmati', 'postalCode' => '44600',
//  'confidence' => 0.83]

NepaliMap::search('Kathmandhu', limit: 3);           // fuzzy-corrects to Kathmandu
NepaliMap::federalConstituenciesByDistrict('kathmandu'); // 10 federal seats
```

All lookups accept id (`P3`, `P3.D05`), slug, name (English or Nepali), or
known aliases. Indexes are built once per request and cached.

## JavaScript / Svelte

```bash
npm i @rayzenai/nepali-map
```

```svelte
<script lang="ts">
    import NepalMap from '@rayzenai/nepali-map/svelte';
    import { getDistrict, findByPostalCode, search } from '@rayzenai/nepali-map';

    // District counts keyed by package slug; pass a slugRemap if your DB
    // uses different keys (e.g. 'rukum-east' vs the package's 'eastern-rukum').
    const collegesByDistrict: Record<string, number> = { kathmandu: 42, lalitpur: 18 };
</script>

<NepalMap
    districtCounts={collegesByDistrict}
    slugRemap={{ 'eastern-rukum': 'rukum-east', 'western-rukum': 'rukum-west' }}
    onDistrictClick={(slug) => goToDistrict(slug)}
    class="aspect-[16/10] w-full"
/>
```

Full JS surface (tree-shakeable):

```ts
import {
    // Data
    PROVINCES, DISTRICTS, LOCAL_UNITS, REGIONS, ZONES, LEGACY_DISTRICTS,
    POSTAL_CODES, POSTAL_CODES_2025, POSTAL_CODE_BRANCHES,
    NEPAL_PROVINCES_GEO, NEPAL_DISTRICTS_GEO, NEPAL_LOCAL_UNITS_GEO,

    // Lookups
    getProvince, getDistrict, getLocalUnit,
    getDistrictsByProvince, getLocalUnitsByDistrict, getLocalUnitsByProvince,
    findByPostalCode, getPostalCode, getPostalCode2025,
    getRegion, getZone, getLegacyDistrict,
    getCurrentDistrictsForLegacyDistrict, getLegacyDistrictForCurrentDistrict,
    isValidProvince, isValidDistrict, isValidLocalUnit, isValidWard,

    // Coordinates
    findProvinceByCoords, findDistrictByCoords, findLocalUnitByCoords,

    // Search
    search,

    // Address
    formatAddress, parseAddress, validateAddress,

    // SVG rendering
    toSvg, toSvgPaths, computeBBox,
} from '@rayzenai/nepali-map';
```

### `<NepalMap />` props

| Prop | Type | Default | |
|------|------|---------|---|
| `districtCounts` | `Record<string, number>` | `{}` | Drives the teal heatmap. Keyed by district slug (after `slugRemap`). |
| `highlightSlug` | `string \| null` | `null` | Externally controlled "active" district. |
| `onDistrictClick` | `(slug: string) => void` | — | When omitted, paths become non-interactive (`role="img"`). |
| `onDistrictHover` | `(slug: string \| null) => void` | — | |
| `slugRemap` | `Record<string, string>` | `{}` | Map package slugs → your DB's slugs. The package emits remapped slugs everywhere outside the component. |
| `provinceColors` | `Partial<Record<number, string>>` | pastel-200 palette | Hex per province number 1..7. |
| `heatHue` | `number` | `199` (teal) | HSL hue for the heatmap. |
| `class` | `string` | `''` | Forwarded to the outer `<div>`. |

## Architecture

```
nepali-map/
├── data/                            # vendored JSON — single source of truth
│   ├── provinces.json               # 7 provinces (+ capitals, coords, population)
│   ├── districts.json               # 77 districts
│   ├── local-units.json             # 753 palikas
│   ├── constituencies.json          # 165 federal + 330 provincial
│   ├── postal-codes*.json           # 1991 legacy + 2025 federal codes
│   ├── regions.json / zones.json    # pre-2015 dev-region structure
│   ├── legacy-districts.json        # pre-2017 75-district map + crosswalk
│   ├── nepal-*.json                 # GeoJSON FeatureCollections
│   └── meta.json
├── src/                             # PHP (PSR-4 → RayzenAI\NepaliMap\)
│   ├── NepaliMap.php                # the static facade
│   ├── Province / District / LocalUnit / Region / Zone / LegacyDistrict / Constituency
│   ├── Geo/{PointInPolygon, Projection}
│   ├── Address/{Formatter, Parser, Validator}
│   ├── Search/Search
│   └── Laravel/{NepaliMapServiceProvider, Console/SeedNepaliMapCommand}
├── database/
│   ├── migrations/2026_05_11_000000_create_nepal_administrative_tables.php
│   └── seeders/NepaliMapSeeder.php
├── js/src/                          # TypeScript (peer-deps Svelte 5)
│   ├── data.ts                      # JSON imports
│   ├── lookup.ts / geo.ts / search.ts / address.ts
│   ├── types.ts
│   ├── index.ts
│   └── components/NepalMap.svelte
└── scripts/
    ├── generate-data.mjs            # one-shot dump from `nepali-geo-pro-max`
    └── generate-constituencies.mjs  # builds constituencies.json from EC counts
```

## Regenerating vendored data

The JSON in `data/` is checked in. To refresh from upstream:

```bash
# 1. Have nepali-geo-pro-max installed in a sibling workspace (e.g. gyansocial).
# 2. Re-dump:
node scripts/generate-data.mjs
node scripts/generate-constituencies.mjs

# 3. Verify nothing regressed:
vendor/bin/pest
```

## License

MIT. Vendored data derived from `nepali-geo-pro-max` (MIT, © l3lackcurtains).
Federal + provincial seat counts are from the Nepal Election Commission's
2079 BS / 2022 AD official allocation.
