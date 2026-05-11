<?php

declare(strict_types=1);

namespace RayzenAI\NepaliMap\Geo;

use RayzenAI\NepaliMap\Data;

/**
 * Equirectangular projection helpers. Fine for country-scale Nepal maps;
 * for globe-grade accuracy, project externally before rendering.
 */
final class Projection
{
    /**
     * Compute `[minLng, minLat, maxLng, maxLat]` for a feature collection.
     *
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    public static function bbox(string $geoFile): array
    {
        /** @var array{features: list<array{geometry: array{type: string, coordinates: mixed}}>} $fc */
        $fc = Data::load($geoFile);

        $minLng = PHP_FLOAT_MAX;
        $minLat = PHP_FLOAT_MAX;
        $maxLng = -PHP_FLOAT_MAX;
        $maxLat = -PHP_FLOAT_MAX;

        foreach ($fc['features'] as $feature) {
            $geom = $feature['geometry'];
            $polygons = $geom['type'] === 'MultiPolygon' ? $geom['coordinates'] : [$geom['coordinates']];

            foreach ($polygons as $rings) {
                foreach ($rings as $ring) {
                    foreach ($ring as [$lng, $lat]) {
                        if ($lng < $minLng) { $minLng = $lng; }
                        if ($lng > $maxLng) { $maxLng = $lng; }
                        if ($lat < $minLat) { $minLat = $lat; }
                        if ($lat > $maxLat) { $maxLat = $lat; }
                    }
                }
            }
        }

        return [$minLng, $minLat, $maxLng, $maxLat];
    }

    /**
     * Render a GeoJSON feature collection as SVG markup.
     *
     * @param  array{
     *     width?: int,
     *     padding?: int,
     *     fill?: string|callable(array<string, mixed>): string,
     *     stroke?: string,
     *     strokeWidth?: float,
     * }  $options
     */
    public static function toSvg(string $geoFile, array $options = []): string
    {
        $width = $options['width'] ?? 800;
        $padding = $options['padding'] ?? 8;
        $fill = $options['fill'] ?? '#e0e0e0';
        $stroke = $options['stroke'] ?? '#fff';
        $strokeWidth = $options['strokeWidth'] ?? 0.5;

        [$minLng, $minLat, $maxLng, $maxLat] = self::bbox($geoFile);

        $lngSpan = max($maxLng - $minLng, 1e-9);
        $latSpan = max($maxLat - $minLat, 1e-9);

        $inner = $width - 2 * $padding;
        $scale = $inner / $lngSpan;
        $height = (int) round($latSpan * $scale + 2 * $padding);

        $project = function (float $lng, float $lat) use ($minLng, $maxLat, $scale, $padding): array {
            return [
                ($lng - $minLng) * $scale + $padding,
                ($maxLat - $lat) * $scale + $padding,
            ];
        };

        /** @var array{features: list<array{properties: array<string, mixed>, geometry: array{type: string, coordinates: mixed}}>} $fc */
        $fc = Data::load($geoFile);

        $paths = [];

        foreach ($fc['features'] as $feature) {
            $d = self::pathFromGeometry($feature['geometry'], $project);

            $color = is_callable($fill) ? $fill($feature['properties']) : $fill;
            $paths[] = sprintf(
                '<path d="%s" fill="%s" stroke="%s" stroke-width="%s"/>',
                $d,
                htmlspecialchars((string) $color, ENT_QUOTES | ENT_HTML5),
                htmlspecialchars($stroke, ENT_QUOTES | ENT_HTML5),
                $strokeWidth,
            );
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d">%s</svg>',
            $width,
            $height,
            $width,
            $height,
            implode('', $paths),
        );
    }

    /**
     * @param  array{type: string, coordinates: mixed}  $geometry
     * @param  callable(float, float): array{0: float, 1: float}  $project
     */
    public static function pathFromGeometry(array $geometry, callable $project): string
    {
        $polygons = $geometry['type'] === 'MultiPolygon'
            ? $geometry['coordinates']
            : [$geometry['coordinates']];

        $parts = [];

        foreach ($polygons as $rings) {
            foreach ($rings as $ring) {
                $cmd = 'M';
                foreach ($ring as [$lng, $lat]) {
                    [$x, $y] = $project($lng, $lat);
                    $parts[] = sprintf('%s%.2f,%.2f', $cmd, $x, $y);
                    $cmd = 'L';
                }
                $parts[] = 'Z';
            }
        }

        return implode(' ', $parts);
    }
}
