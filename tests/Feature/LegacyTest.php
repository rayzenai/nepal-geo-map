<?php

declare(strict_types=1);

use RayzenAI\NepaliMap\NepaliMap;

it('lists 5 regions and 14 zones', function (): void {
    expect(NepaliMap::regions())->toHaveCount(5);
    expect(NepaliMap::zones())->toHaveCount(14);
});

it('lists 75 legacy districts', function (): void {
    expect(NepaliMap::legacyDistricts())->toHaveCount(75);
});

it('crosswalks legacy → current districts (Rukum split into east + west)', function (): void {
    $currents = NepaliMap::currentDistrictsForLegacyDistrict('rukum');

    $slugs = array_column($currents, 'slug');

    expect($slugs)->toContain('eastern-rukum');
    expect($slugs)->toContain('western-rukum');
});

it('crosswalks current → legacy district', function (): void {
    $legacy = NepaliMap::legacyDistrictForCurrentDistrict('kathmandu');

    expect($legacy?->slug)->toBe('kathmandu');
});

it('resolves zone by slug', function (): void {
    expect(NepaliMap::zone('bagmati-zone')?->id)->toBe('Z05');
});

it('groups zones under a region', function (): void {
    expect(NepaliMap::zonesByRegion(1))->not->toBeEmpty();
});
