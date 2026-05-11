<?php

declare(strict_types=1);

use RayzenAI\NepaliGeoMap\NepaliGeoMap;

it('lists 5 regions and 14 zones', function (): void {
    expect(NepaliGeoMap::regions())->toHaveCount(5);
    expect(NepaliGeoMap::zones())->toHaveCount(14);
});

it('lists 75 legacy districts', function (): void {
    expect(NepaliGeoMap::legacyDistricts())->toHaveCount(75);
});

it('crosswalks legacy → current districts (Rukum split into east + west)', function (): void {
    $currents = NepaliGeoMap::currentDistrictsForLegacyDistrict('rukum');

    $slugs = array_column($currents, 'slug');

    expect($slugs)->toContain('eastern-rukum');
    expect($slugs)->toContain('western-rukum');
});

it('crosswalks current → legacy district', function (): void {
    $legacy = NepaliGeoMap::legacyDistrictForCurrentDistrict('kathmandu');

    expect($legacy?->slug)->toBe('kathmandu');
});

it('resolves zone by slug', function (): void {
    expect(NepaliGeoMap::zone('bagmati-zone')?->id)->toBe('Z05');
});

it('groups zones under a region', function (): void {
    expect(NepaliGeoMap::zonesByRegion(1))->not->toBeEmpty();
});
