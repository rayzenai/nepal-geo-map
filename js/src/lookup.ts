// Lookup helpers built over indexed PROVINCES / DISTRICTS / LOCAL_UNITS.

import {
    PROVINCES,
    DISTRICTS,
    LOCAL_UNITS,
    REGIONS,
    ZONES,
    LEGACY_DISTRICTS,
    POSTAL_CODES,
    POSTAL_CODES_2025,
    POSTAL_CODE_BRANCHES,
    DISTRICT_POSTCODE_PREFIXES,
} from './data';
import type {
    Province,
    District,
    LocalUnit,
    Region,
    Zone,
    LegacyDistrict,
} from './types';

const normalize = (v: string | number): string =>
    String(v).trim().replace(/\s+/g, ' ').toLowerCase();

interface Indexable {
    id: string;
    slug: string;
    nameEn: string;
    nameNe: string;
    aliases?: readonly string[];
}

function buildIndex<T extends Indexable>(rows: readonly T[]): Map<string, T> {
    const map = new Map<string, T>();
    for (const row of rows) {
        for (const key of [row.id, row.slug, row.nameEn, row.nameNe]) {
            if (key) {
                map.set(normalize(key), row);
            }
        }
        for (const alias of row.aliases ?? []) {
            map.set(normalize(alias), row);
        }
    }
    return map;
}

const provinceIndex = buildIndex(PROVINCES);
const districtIndex = buildIndex(DISTRICTS);
const localUnitIndex = buildIndex(LOCAL_UNITS);
const regionIndex = buildIndex(REGIONS);
const zoneIndex = buildIndex(ZONES);
const legacyDistrictIndex = buildIndex(LEGACY_DISTRICTS);

// ---------- Provinces ----------
export const getProvinces = (): readonly Province[] => PROVINCES;
export function getProvince(query: string | number): Province | undefined {
    if (typeof query === 'number' || /^\d+$/.test(String(query).trim())) {
        const n = Number(query);
        return PROVINCES.find((p) => p.number === n);
    }
    return provinceIndex.get(normalize(query));
}
export const isValidProvince = (query: string | number): boolean => getProvince(query) !== undefined;

// ---------- Districts ----------
export const getDistricts = (): readonly District[] => DISTRICTS;
export const getDistrict = (query: string): District | undefined => districtIndex.get(normalize(query));
export function getDistrictsByProvince(query: string | number): readonly District[] {
    const p = getProvince(query);
    if (!p) return [];
    return DISTRICTS.filter((d) => d.provinceId === p.id);
}
export const isValidDistrict = (query: string): boolean => getDistrict(query) !== undefined;

// ---------- Local units ----------
export const getLocalUnits = (): readonly LocalUnit[] => LOCAL_UNITS;
export const getLocalUnit = (query: string): LocalUnit | undefined =>
    localUnitIndex.get(normalize(query));
export function getLocalUnitsByDistrict(query: string): readonly LocalUnit[] {
    const d = getDistrict(query);
    if (!d) return [];
    return LOCAL_UNITS.filter((u) => u.districtId === d.id);
}
export function getLocalUnitsByProvince(query: string | number): readonly LocalUnit[] {
    const p = getProvince(query);
    if (!p) return [];
    return LOCAL_UNITS.filter((u) => u.districtId.startsWith(`${p.id}.`));
}
export const isValidLocalUnit = (query: string): boolean => getLocalUnit(query) !== undefined;
export const isValidWard = (localUnit: string, ward: number): boolean => {
    const u = getLocalUnit(localUnit);
    return u !== undefined && ward >= 1 && ward <= u.wards;
};

// ---------- Postal codes ----------
export function findByPostalCode(code: string): LocalUnit | undefined {
    const clean = code.trim();
    for (const [unitId, postal] of Object.entries(POSTAL_CODES)) {
        if (postal === clean) return getLocalUnit(unitId);
    }
    for (const [unitId, codes] of Object.entries(POSTAL_CODE_BRANCHES)) {
        if (codes.includes(clean)) return getLocalUnit(unitId);
    }
    return undefined;
}
export function getPostalCode(localUnit: string): string | undefined {
    const u = getLocalUnit(localUnit);
    if (!u) return undefined;
    return u.postalCode ?? POSTAL_CODES[u.id];
}
export function getPostalCode2025(localUnit: string): string | undefined {
    const u = getLocalUnit(localUnit);
    return u ? POSTAL_CODES_2025[u.id] : undefined;
}
export function getDistrictPostcodePrefix(district: string): string | undefined {
    const d = getDistrict(district);
    return d ? DISTRICT_POSTCODE_PREFIXES[d.id] : undefined;
}

// ---------- Regions / Zones / Legacy districts ----------
export const getRegions = (): readonly Region[] => REGIONS;
export function getRegion(query: string | number): Region | undefined {
    if (typeof query === 'number' || /^\d+$/.test(String(query).trim())) {
        const n = Number(query);
        return REGIONS.find((r) => r.number === n);
    }
    return regionIndex.get(normalize(query));
}
export const isValidRegion = (query: string | number): boolean => getRegion(query) !== undefined;

export const getZones = (): readonly Zone[] => ZONES;
export const getZone = (query: string): Zone | undefined => zoneIndex.get(normalize(query));
export function getZonesByRegion(query: string | number): readonly Zone[] {
    const r = getRegion(query);
    if (!r) return [];
    return ZONES.filter((z) => z.regionId === r.id);
}
export const isValidZone = (query: string): boolean => getZone(query) !== undefined;

export const getLegacyDistricts = (): readonly LegacyDistrict[] => LEGACY_DISTRICTS;
export const getLegacyDistrict = (query: string): LegacyDistrict | undefined =>
    legacyDistrictIndex.get(normalize(query));
export function getLegacyDistrictsByZone(query: string): readonly LegacyDistrict[] {
    const z = getZone(query);
    if (!z) return [];
    return LEGACY_DISTRICTS.filter((l) => l.zoneId === z.id);
}
export function getCurrentDistrictsForLegacyDistrict(query: string): readonly District[] {
    const legacy = getLegacyDistrict(query);
    if (!legacy) return [];
    return legacy.currentDistrictIds
        .map((id) => getDistrict(id))
        .filter((d): d is District => d !== undefined);
}
export function getLegacyDistrictForCurrentDistrict(query: string): LegacyDistrict | undefined {
    const d = getDistrict(query);
    if (!d) return undefined;
    return LEGACY_DISTRICTS.find((l) => l.currentDistrictIds.includes(d.id));
}
export const isValidLegacyDistrict = (query: string): boolean =>
    getLegacyDistrict(query) !== undefined;
