// Point-in-polygon + equirectangular projection helpers. Standalone — no
// dependency on the upstream package.

import {
    NEPAL_PROVINCES_GEO,
    NEPAL_DISTRICTS_GEO,
    NEPAL_LOCAL_UNITS_GEO,
} from './data';
import { getProvince, getDistrict, getLocalUnitsByDistrict } from './lookup';
import type {
    AdminFeature,
    DistrictGeoFeature,
    DistrictGeoFeatureCollection,
    LocalUnitGeoFeature,
    LocalUnitGeoFeatureCollection,
    PolygonGeometry,
    Position,
    Province,
    District,
    LocalUnit,
    ProvinceGeoFeature,
    ProvinceGeoFeatureCollection,
} from './types';

type AnyFc =
    | DistrictGeoFeatureCollection
    | ProvinceGeoFeatureCollection
    | LocalUnitGeoFeatureCollection;

export type BBox = readonly [number, number, number, number];

// ---------- Point-in-polygon ----------

function pointInRing(point: Position, ring: readonly Position[]): boolean {
    const [x, y] = point;
    let inside = false;
    const n = ring.length;
    if (n < 3) return false;
    for (let i = 0, j = n - 1; i < n; j = i++) {
        const [xi, yi] = ring[i]!;
        const [xj, yj] = ring[j]!;
        const intersects =
            yi > y !== yj > y && x < ((xj - xi) * (y - yi)) / (yj - yi || 1e-12) + xi;
        if (intersects) inside = !inside;
    }
    return inside;
}

function pointInPolygon(point: Position, rings: readonly (readonly Position[])[]): boolean {
    if (rings.length === 0 || !pointInRing(point, rings[0]!)) return false;
    for (let i = 1; i < rings.length; i++) {
        if (pointInRing(point, rings[i]!)) return false; // inside a hole
    }
    return true;
}

export function pointInGeometry(point: Position, geom: PolygonGeometry): boolean {
    if (geom.type === 'Polygon') {
        return pointInPolygon(point, geom.coordinates as readonly (readonly Position[])[]);
    }
    if (geom.type === 'MultiPolygon') {
        for (const poly of geom.coordinates as readonly (readonly (readonly Position[])[])[]) {
            if (pointInPolygon(point, poly)) return true;
        }
    }
    return false;
}

function findFeature<F extends AdminFeature>(
    fc: { readonly features: readonly F[] },
    lat: number,
    lng: number,
): F | undefined {
    for (const feature of fc.features) {
        if (pointInGeometry([lng, lat], feature.geometry)) return feature;
    }
    return undefined;
}

export const findProvinceFeatureByCoords = (lat: number, lng: number): ProvinceGeoFeature | undefined =>
    findFeature(NEPAL_PROVINCES_GEO, lat, lng);

export const findDistrictFeatureByCoords = (lat: number, lng: number): DistrictGeoFeature | undefined =>
    findFeature(NEPAL_DISTRICTS_GEO, lat, lng);

export const findLocalUnitFeatureByCoords = (lat: number, lng: number): LocalUnitGeoFeature | undefined =>
    findFeature(NEPAL_LOCAL_UNITS_GEO, lat, lng);

export function findProvinceByCoords(lat: number, lng: number): Province | undefined {
    const f = findProvinceFeatureByCoords(lat, lng);
    return f ? getProvince(f.properties.id) : undefined;
}

export function findDistrictByCoords(lat: number, lng: number): District | undefined {
    const f = findDistrictFeatureByCoords(lat, lng);
    return f ? getDistrict(f.properties.id) : undefined;
}

export function findLocalUnitByCoords(lat: number, lng: number): LocalUnit | undefined {
    const f = findLocalUnitFeatureByCoords(lat, lng);
    if (!f) return undefined;
    const candidates = getLocalUnitsByDistrict(f.properties.districtId);
    return candidates.find((u) => u.nameEn.toLowerCase() === f.properties.nameEn.toLowerCase());
}

// ---------- BBox + projection ----------

export function computeBBox(fc: AnyFc): BBox {
    let minLng = Infinity,
        minLat = Infinity,
        maxLng = -Infinity,
        maxLat = -Infinity;
    for (const feature of fc.features) {
        const polys =
            feature.geometry.type === 'MultiPolygon'
                ? (feature.geometry.coordinates as readonly (readonly (readonly Position[])[])[])
                : [feature.geometry.coordinates as readonly (readonly Position[])[]];
        for (const rings of polys) {
            for (const ring of rings) {
                for (const [lng, lat] of ring) {
                    if (lng < minLng) minLng = lng;
                    if (lng > maxLng) maxLng = lng;
                    if (lat < minLat) minLat = lat;
                    if (lat > maxLat) maxLat = lat;
                }
            }
        }
    }
    return [minLng, minLat, maxLng, maxLat];
}

export interface ToSvgOptions {
    readonly width?: number;
    readonly height?: number;
    readonly padding?: number;
    readonly fill?: string | ((feature: AdminFeature) => string);
    readonly stroke?: string;
    readonly strokeWidth?: number;
}

export interface SvgPath<F extends AdminFeature = AdminFeature> {
    readonly id: string;
    readonly d: string;
    readonly fill: string;
    readonly feature: F;
}

export interface SvgPathsResult<F extends AdminFeature = AdminFeature> {
    readonly viewBox: string;
    readonly width: number;
    readonly height: number;
    readonly bbox: BBox;
    readonly paths: readonly SvgPath<F>[];
}

export function toSvgPaths<F extends AdminFeature>(
    fc: { readonly features: readonly F[] },
    options: ToSvgOptions = {},
): SvgPathsResult<F> {
    const width = options.width ?? 800;
    const padding = options.padding ?? 8;
    const fillOpt = options.fill ?? '#e0e0e0';

    const bbox = computeBBox(fc as unknown as AnyFc);
    const [minLng, minLat, maxLng, maxLat] = bbox;

    const lngSpan = Math.max(maxLng - minLng, 1e-9);
    const latSpan = Math.max(maxLat - minLat, 1e-9);
    const inner = width - 2 * padding;
    const scale = inner / lngSpan;
    const height = options.height ?? Math.round(latSpan * scale + 2 * padding);

    const project = (lng: number, lat: number): [number, number] => [
        (lng - minLng) * scale + padding,
        (maxLat - lat) * scale + padding,
    ];

    const fillFor = (feature: AdminFeature): string =>
        typeof fillOpt === 'function' ? fillOpt(feature) : fillOpt;

    const paths: SvgPath<F>[] = fc.features.map((feature, i) => {
        const polys =
            feature.geometry.type === 'MultiPolygon'
                ? (feature.geometry.coordinates as readonly (readonly (readonly Position[])[])[])
                : [feature.geometry.coordinates as readonly (readonly Position[])[]];

        const parts: string[] = [];
        for (const rings of polys) {
            for (const ring of rings) {
                let cmd = 'M';
                for (const [lng, lat] of ring) {
                    const [x, y] = project(lng, lat);
                    parts.push(`${cmd}${x.toFixed(2)},${y.toFixed(2)}`);
                    cmd = 'L';
                }
                parts.push('Z');
            }
        }

        const featureProps = feature.properties as Record<string, unknown>;
        const id = (featureProps.id as string | undefined) ?? `path-${i}`;

        return {
            id,
            d: parts.join(' '),
            fill: fillFor(feature),
            feature,
        };
    });

    return {
        viewBox: `0 0 ${width} ${height}`,
        width,
        height,
        bbox,
        paths,
    };
}

export function toSvg<F extends AdminFeature>(
    fc: { readonly features: readonly F[] },
    options: ToSvgOptions = {},
): string {
    const { paths, viewBox, width, height } = toSvgPaths(fc, options);
    const stroke = options.stroke ?? '#fff';
    const strokeWidth = options.strokeWidth ?? 0.5;
    const inner = paths
        .map(
            (p) =>
                `<path d="${p.d}" fill="${escapeAttr(p.fill)}" stroke="${escapeAttr(stroke)}" stroke-width="${strokeWidth}"/>`,
        )
        .join('');
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${viewBox}" width="${width}" height="${height}">${inner}</svg>`;
}

function escapeAttr(value: string): string {
    return value.replace(/[&<>"']/g, (c) =>
        c === '&' ? '&amp;' : c === '<' ? '&lt;' : c === '>' ? '&gt;' : c === '"' ? '&quot;' : '&#39;',
    );
}
