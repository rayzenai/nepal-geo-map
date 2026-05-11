// Vendored JSON imports. Bundlers tree-shake unused datasets.

import provinces from '../../data/provinces.json';
import districts from '../../data/districts.json';
import localUnits from '../../data/local-units.json';
import regions from '../../data/regions.json';
import zones from '../../data/zones.json';
import legacyDistricts from '../../data/legacy-districts.json';
import postalCodes from '../../data/postal-codes.json';
import postalCodes2025 from '../../data/postal-codes-2025.json';
import postalCodeBranches from '../../data/postal-code-branches.json';
import districtPostcodePrefixes from '../../data/district-postcode-prefixes.json';
import meta from '../../data/meta.json';

import nepalProvincesGeo from '../../data/nepal-provinces.json';
import nepalDistrictsGeo from '../../data/nepal-districts.json';
import nepalLocalUnitsGeo from '../../data/nepal-local-units.json';

import type {
    Province,
    District,
    LocalUnit,
    Region,
    Zone,
    LegacyDistrict,
    DistrictGeoFeatureCollection,
    ProvinceGeoFeatureCollection,
    LocalUnitGeoFeatureCollection,
} from './types';

export const PROVINCES = provinces as readonly Province[];
export const DISTRICTS = districts as readonly District[];
export const LOCAL_UNITS = localUnits as readonly LocalUnit[];
export const REGIONS = regions as readonly Region[];
export const ZONES = zones as readonly Zone[];
export const LEGACY_DISTRICTS = legacyDistricts as readonly LegacyDistrict[];

export const POSTAL_CODES = postalCodes as Readonly<Record<string, string>>;
export const POSTAL_CODES_2025 = postalCodes2025 as Readonly<Record<string, string>>;
export const POSTAL_CODE_BRANCHES = postalCodeBranches as Readonly<
    Record<string, readonly string[]>
>;
export const DISTRICT_POSTCODE_PREFIXES = districtPostcodePrefixes as Readonly<
    Record<string, string>
>;

export const META = meta as {
    sourcePackage: string;
    sourceVersion: string;
    generatedAt: string;
    totalLocalUnits: number;
    totalLegacyDistricts: number;
    districtsByProvinceCount: Record<string, number>;
    localUnitTypeCounts: Record<string, number>;
};

// GeoJSON imports come back with `number[][][]` for coordinates whereas our
// types use `Position = readonly [number, number]`. Casts via `unknown` are
// safe because the JSON shape matches at runtime.
export const NEPAL_PROVINCES_GEO = nepalProvincesGeo as unknown as ProvinceGeoFeatureCollection;
export const NEPAL_DISTRICTS_GEO = nepalDistrictsGeo as unknown as DistrictGeoFeatureCollection;
export const NEPAL_LOCAL_UNITS_GEO = nepalLocalUnitsGeo as unknown as LocalUnitGeoFeatureCollection;
