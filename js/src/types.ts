// Shared type definitions for the JS surface.

export type ProvinceId = `P${1 | 2 | 3 | 4 | 5 | 6 | 7}`;
export type DistrictId = `${ProvinceId}.D${string}`;
export type LocalUnitId = `${DistrictId}.L${string}`;
export type LocalUnitType =
    | 'metropolitan'
    | 'sub-metropolitan'
    | 'municipality'
    | 'rural-municipality';

export interface LatLng {
    readonly lat: number;
    readonly lng: number;
}

export interface Province {
    readonly id: ProvinceId;
    readonly number: 1 | 2 | 3 | 4 | 5 | 6 | 7;
    readonly nameEn: string;
    readonly nameNe: string;
    readonly capital: string;
    readonly capitalNe: string;
    readonly capitalCoords: LatLng;
    readonly areaKm2: number;
    readonly population: number;
    readonly slug: string;
    readonly aliases: readonly string[];
}

export interface District {
    readonly id: DistrictId;
    readonly nameEn: string;
    readonly nameNe: string;
    readonly provinceId: ProvinceId;
    readonly headquarters: string;
    readonly headquartersNe?: string;
    readonly slug: string;
    readonly aliases: readonly string[];
}

export interface LocalUnit {
    readonly id: LocalUnitId;
    readonly nameEn: string;
    readonly nameNe: string;
    readonly type: LocalUnitType;
    readonly districtId: DistrictId;
    readonly wards: number;
    readonly coords?: LatLng;
    readonly postalCode?: string;
    readonly slug: string;
    readonly aliases: readonly string[];
}

export interface Region {
    readonly id: `R${1 | 2 | 3 | 4 | 5}`;
    readonly number: 1 | 2 | 3 | 4 | 5;
    readonly nameEn: string;
    readonly nameNe: string;
    readonly slug: string;
    readonly aliases: readonly string[];
}

export interface Zone {
    readonly id: `Z${string}`;
    readonly nameEn: string;
    readonly nameNe: string;
    readonly regionId: Region['id'];
    readonly slug: string;
    readonly aliases: readonly string[];
}

export interface LegacyDistrict {
    readonly id: `LD${string}`;
    readonly nameEn: string;
    readonly nameNe: string;
    readonly zoneId: Zone['id'];
    readonly currentDistrictIds: readonly DistrictId[];
    readonly slug: string;
    readonly aliases: readonly string[];
}

export type Position = readonly [number, number];
export type Ring = readonly Position[];
export type Polygon = readonly Ring[];
export type MultiPolygon = readonly Polygon[];

export interface PolygonGeometry {
    readonly type: 'Polygon' | 'MultiPolygon';
    readonly coordinates: Polygon | MultiPolygon;
}

export interface DistrictGeoFeature {
    readonly type: 'Feature';
    readonly properties: {
        readonly id: DistrictId;
        readonly provinceId: ProvinceId;
        readonly nameEn: string;
    };
    readonly geometry: PolygonGeometry;
}

export interface ProvinceGeoFeature {
    readonly type: 'Feature';
    readonly properties: {
        readonly id: ProvinceId;
        readonly nameEn: string;
        readonly nameNe: string;
    };
    readonly geometry: PolygonGeometry;
}

export interface LocalUnitGeoFeature {
    readonly type: 'Feature';
    readonly properties: {
        readonly nameEn: string;
        readonly nameNe: string;
        readonly districtId: DistrictId;
        readonly provinceId: ProvinceId;
        readonly type: LocalUnitType;
    };
    readonly geometry: PolygonGeometry;
}

export interface DistrictGeoFeatureCollection {
    readonly type: 'FeatureCollection';
    readonly features: readonly DistrictGeoFeature[];
}

export interface ProvinceGeoFeatureCollection {
    readonly type: 'FeatureCollection';
    readonly features: readonly ProvinceGeoFeature[];
}

export interface LocalUnitGeoFeatureCollection {
    readonly type: 'FeatureCollection';
    readonly features: readonly LocalUnitGeoFeature[];
}

export type AdminFeature = ProvinceGeoFeature | DistrictGeoFeature | LocalUnitGeoFeature;
