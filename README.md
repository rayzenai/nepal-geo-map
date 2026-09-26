# @rayzenai/nepali-geo-map

Nepal administrative geography (provinces, districts, palikas) + electoral
constituencies + addresses + an interactive Svelte map. Ships with vendored
data so there are **zero runtime dependencies on third-party packages**.

| Surface | Where |
|---------|-------|
| PHP / Laravel | `composer require rayzenai/nepali-geo-map` → `RayzenAI\NepaliGeoMap\NepaliGeoMap::` |
| JavaScript / Svelte | `npm i @rayzenai/nepali-geo-map` → `import { ... } from '@rayzenai/nepali-geo-map'` |
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
- **Interactive Svelte map** — hand-drawn district outlines, heatmap fill, hover overlay, configurable slug remap.

## PHP / Laravel

```bash
composer require rayzenai/nepali-geo-map
```

The package auto-registers via Laravel package discovery — its migrations
load automatically and `php artisan nepali-geo-map:seed` becomes available.

```bash
php artisan migrate         # creates 4 tables: nepal_provinces, nepal_districts, nepal_local_units, nepal_constituencies
php artisan nepali-geo-map:seed # populates 7 + 77 + 753 + 495 = 1,332 rows
```

If you'd rather own the migration timestamps:

```bash
php artisan vendor:publish --tag=nepali-geo-map-migrations
```

### Pure-PHP usage

```php
use RayzenAI\NepaliGeoMap\NepaliGeoMap;

NepaliGeoMap::province('bagmati')?->capital;            // "Hetauda"
NepaliGeoMap::province(3)?->nameEn;                     // "Bagmati"
NepaliGeoMap::districtsByProvince(3);                   // 13 District objects

NepaliGeoMap::findByPostalCode('44600')?->nameEn;       // "Kathmandu Metropolitan City"
NepaliGeoMap::findDistrictByCoords(27.7172, 85.3240)?->slug; // "kathmandu"

NepaliGeoMap::formatAddress([
    'ward' => 10, 'tole' => 'Baluwatar',
    'localUnit' => 'P3.D05.L01', 'district' => 'kathmandu',
]);
// "Ward 10, Baluwatar, Kathmandu Metropolitan City, Kathmandu"

NepaliGeoMap::parseAddress('Baluwatar, Kathmandu 44600');
// ['ward' => null, 'tole' => 'Baluwatar', 'localUnit' => 'kathmandu',
//  'district' => 'kathmandu', 'province' => 'bagmati', 'postalCode' => '44600',
//  'confidence' => 0.83]

NepaliGeoMap::search('Kathmandhu', limit: 3);           // fuzzy-corrects to Kathmandu
NepaliGeoMap::federalConstituenciesByDistrict('kathmandu'); // 10 federal seats
```

All lookups accept id (`P3`, `P3.D05`), slug, name (English or Nepali), or
known aliases. Indexes are built once per request and cached.

## JavaScript / Svelte

The Composer install already drops the entire JS source under
`vendor/rayzenai/nepali-geo-map/js/src/` — **no separate `npm install`
needed**. Wire it up via a Vite + TypeScript alias:

```ts
// vite.config.ts
import { fileURLToPath } from 'node:url';

export default defineConfig({
    resolve: {
        alias: {
            '@nepali-geo-map': fileURLToPath(
                new URL('./vendor/rayzenai/nepali-geo-map/js/src', import.meta.url),
            ),
        },
    },
});
```

```json
// tsconfig.json
"paths": {
    "@nepali-geo-map": ["./vendor/rayzenai/nepali-geo-map/js/src/index.ts"],
    "@nepali-geo-map/*": ["./vendor/rayzenai/nepali-geo-map/js/src/*"]
}
```

```svelte
<script lang="ts">
    import NepalMap from '@nepali-geo-map/components/NepalMap.svelte';
    import { getDistrict, findByPostalCode, search } from '@nepali-geo-map';

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

`composer update` refreshes both PHP and JS together — no two-place
version drift.

> Using this from a non-Laravel JS project? The package has a `package.json`
> too, so `npm i github:rayzenai/nepal-geo-map` works as well. The Vite
> alias is just the simpler path when you're already running Composer.

Full JS surface (tree-shakeable):

```ts
import {
    // Data
    PROVINCES, DISTRICTS, LOCAL_UNITS, REGIONS, ZONES, LEGACY_DISTRICTS,
    POSTAL_CODES, POSTAL_CODES_2025, POSTAL_CODE_BRANCHES,
    NEPAL_PROVINCES_GEO, NEPAL_DISTRICTS_GEO, NEPAL_LOCAL_UNITS_GEO,
    NEPAL_DISTRICTS_SVG, // display-only outlines used by <NepalMap />

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
} from '@rayzenai/nepali-geo-map';
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
nepali-geo-map/
├── data/                            # vendored JSON — single source of truth
│   ├── provinces.json               # 7 provinces (+ capitals, coords, population)
│   ├── districts.json               # 77 districts
│   ├── local-units.json             # 753 palikas
│   ├── constituencies.json          # 165 federal + 330 provincial
│   ├── postal-codes*.json           # 1991 legacy + 2025 federal codes
│   ├── regions.json / zones.json    # pre-2015 dev-region structure
│   ├── legacy-districts.json        # pre-2017 75-district map + crosswalk
│   ├── nepal-*.json                 # GeoJSON FeatureCollections
│   ├── nepal-districts-svg.json     # hand-drawn SVG district outlines (display only)
│   └── meta.json
├── src/                             # PHP (PSR-4 → RayzenAI\NepaliGeoMap\)
│   ├── NepaliGeoMap.php                # the static facade
│   ├── Province / District / LocalUnit / Region / Zone / LegacyDistrict / Constituency
│   ├── Geo/{PointInPolygon, Projection}
│   ├── Address/{Formatter, Parser, Validator}
│   ├── Search/Search
│   └── Laravel/{NepaliGeoMapServiceProvider, Console/SeedNepaliGeoMapCommand}
├── database/
│   ├── migrations/2026_05_11_000000_create_nepal_administrative_tables.php
│   └── seeders/NepaliGeoMapSeeder.php
├── js/src/                          # TypeScript (peer-deps Svelte 5)
│   ├── data.ts                      # JSON imports
│   ├── lookup.ts / geo.ts / search.ts / address.ts
│   ├── types.ts
│   ├── index.ts
│   └── components/NepalMap.svelte
└── scripts/
    └── generate-constituencies.mjs  # builds constituencies.json from EC counts
```

## Regenerating vendored data

The JSON in `data/` is checked in and is the source of truth — edit it
directly. Constituencies are the one generated file:

```bash
node scripts/generate-constituencies.mjs
vendor/bin/pest   # verify nothing regressed
```

## License

MIT.
Federal + provincial seat counts are from the Nepal Election Commission's
2079 BS / 2022 AD official allocation.
