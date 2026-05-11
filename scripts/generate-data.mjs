#!/usr/bin/env node
// One-time data dump from `nepali-geo-pro-max` into the vendored `data/`
// directory. Run from this package root with `nepali-geo-pro-max` installed
// somewhere reachable (we resolve it from the gyansocial workspace).
//
// Usage:
//   node scripts/generate-data.mjs

import { writeFileSync, mkdirSync } from 'node:fs';
import { resolve, dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';

const here = dirname(fileURLToPath(import.meta.url));
const root = resolve(here, '..');
const dataDir = join(root, 'data');
mkdirSync(dataDir, { recursive: true });

// Resolve the source package from the gyansocial workspace (it lives there as a devDep).
const require = createRequire(resolve(root, '..', 'gyansocial', 'package.json'));
const sourcePath = require.resolve('nepali-geo-pro-max');
const geoPath = require.resolve('nepali-geo-pro-max/geo/districts');
const provincesGeoPath = require.resolve('nepali-geo-pro-max/geo/provinces');
const localUnitsGeoPath = require.resolve('nepali-geo-pro-max/geo/local-units');

const source = await import(sourcePath);
const districtsGeo = await import(geoPath);
const provincesGeo = await import(provincesGeoPath);
const localUnitsGeo = await import(localUnitsGeoPath);

const write = (name, value) => {
    const path = join(dataDir, name);
    // GeoJSON files (`nepal-*.json`) are large — minify them; small lookup
    // tables stay pretty-printed for diff-readability.
    const isGeo = name.startsWith('nepal-');
    writeFileSync(path, JSON.stringify(value, null, isGeo ? 0 : 2));
    const bytes = (JSON.stringify(value).length / 1024).toFixed(1);
    console.log(`  ${name.padEnd(36)} ${bytes.padStart(7)} KB`);
};

console.log('Dumping vendored data to', dataDir);

write('provinces.json', source.PROVINCES);
write('districts.json', source.DISTRICTS);
write('local-units.json', source.LOCAL_UNITS);
write('regions.json', source.REGIONS);
write('zones.json', source.ZONES);
write('legacy-districts.json', source.LEGACY_DISTRICTS);

write('postal-codes.json', source.POSTAL_CODES);
write('postal-codes-2025.json', source.POSTAL_CODES_2025);
write('postal-code-branches.json', source.POSTAL_CODE_BRANCHES);
write('district-postcode-prefixes.json', source.DISTRICT_POSTCODE_PREFIXES);

write(
    'meta.json',
    {
        sourcePackage: 'nepali-geo-pro-max',
        sourceVersion: (await import(require.resolve('nepali-geo-pro-max/package.json'), { with: { type: 'json' } })).default.version,
        generatedAt: new Date().toISOString(),
        districtsByProvinceCount: source.DISTRICTS_BY_PROVINCE_COUNT,
        localUnitTypeCounts: source.LOCAL_UNIT_TYPE_COUNTS,
        totalLocalUnits: source.TOTAL_LOCAL_UNITS,
        totalLegacyDistricts: source.TOTAL_LEGACY_DISTRICTS,
    },
);

write('nepal-provinces.json', provincesGeo.NEPAL_PROVINCES_GEO);
write('nepal-districts.json', districtsGeo.NEPAL_DISTRICTS_GEO);
write('nepal-local-units.json', localUnitsGeo.NEPAL_LOCAL_UNITS_GEO);

console.log('Done.');
