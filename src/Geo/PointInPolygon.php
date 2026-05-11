<?php

declare(strict_types=1);

namespace RayzenAI\NepaliMap\Geo;

use RayzenAI\NepaliMap\Data;

/**
 * Even-odd ray-casting point-in-polygon over the vendored GeoJSON files.
 *
 * Accepts `(lat, lng)` for ergonomics (most consumers carry coordinates in
 * that order). GeoJSON internally stores `[lng, lat]`; the conversion is
 * done here.
 */
final class PointInPolygon
{
    /**
     * Return the first feature's `properties` whose geometry contains the
     * point, or `null` if outside every feature.
     *
     * @return array<string, mixed>|null
     */
    public static function findFeatureProperties(string $geoFile, float $lat, float $lng): ?array
    {
        /** @var array{type: string, features: list<array{properties: array<string, mixed>, geometry: array<string, mixed>}>} $fc */
        $fc = Data::load($geoFile);

        foreach ($fc['features'] as $feature) {
            if (self::pointInGeometry([$lng, $lat], $feature['geometry'])) {
                return $feature['properties'];
            }
        }

        return null;
    }

    /**
     * Convenience for feature collections whose properties carry a stable
     * `id` field (provinces, districts). For local-unit features (no id),
     * use {@see findFeatureProperties} and resolve via `(districtId, nameEn)`.
     *
     * When `$includeName` is true and no `id` is present, returns the feature's
     * `nameEn` value.
     */
    public static function findFeatureId(string $geoFile, float $lat, float $lng, bool $includeName = false): ?string
    {
        $props = self::findFeatureProperties($geoFile, $lat, $lng);

        if ($props === null) {
            return null;
        }

        if (isset($props['id']) && is_string($props['id'])) {
            return $props['id'];
        }

        if ($includeName && isset($props['nameEn']) && is_string($props['nameEn'])) {
            return $props['nameEn'];
        }

        return null;
    }

    /**
     * @param  array{0: float, 1: float}  $point  `[lng, lat]`
     * @param  array<string, mixed>  $geometry  GeoJSON Polygon or MultiPolygon
     */
    public static function pointInGeometry(array $point, array $geometry): bool
    {
        $type = $geometry['type'] ?? null;
        $coords = $geometry['coordinates'] ?? null;

        if (! is_array($coords)) {
            return false;
        }

        if ($type === 'Polygon') {
            /** @var list<list<list<float>>> $coords */
            return self::pointInPolygon($point, $coords);
        }

        if ($type === 'MultiPolygon') {
            /** @var list<list<list<list<float>>>> $coords */
            foreach ($coords as $polygon) {
                if (self::pointInPolygon($point, $polygon)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array{0: float, 1: float}  $point  `[lng, lat]`
     * @param  list<list<list<float>>>  $rings   first ring is the outer boundary; subsequent rings are holes
     */
    private static function pointInPolygon(array $point, array $rings): bool
    {
        if ($rings === []) {
            return false;
        }

        if (! self::pointInRing($point, $rings[0])) {
            return false;
        }

        for ($i = 1, $n = count($rings); $i < $n; $i++) {
            if (self::pointInRing($point, $rings[$i])) {
                // Inside a hole.
                return false;
            }
        }

        return true;
    }

    /**
     * Classic even-odd rule.
     *
     * @param  array{0: float, 1: float}  $point   `[x=lng, y=lat]`
     * @param  list<list<float>>  $ring
     */
    private static function pointInRing(array $point, array $ring): bool
    {
        [$x, $y] = $point;
        $inside = false;
        $n = count($ring);

        if ($n < 3) {
            return false;
        }

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $ring[$i][0];
            $yi = $ring[$i][1];
            $xj = $ring[$j][0];
            $yj = $ring[$j][1];

            $intersects = (($yi > $y) !== ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-12) + $xi);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
