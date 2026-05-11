<?php

declare(strict_types=1);

use RayzenAI\NepaliGeoMap\NepaliGeoMap;

it('lists 7 provinces', function (): void {
    expect(NepaliGeoMap::provinces())->toHaveCount(7);
});

it('lists 77 districts', function (): void {
    expect(NepaliGeoMap::districts())->toHaveCount(77);
});

it('lists 753 local units', function (): void {
    expect(NepaliGeoMap::localUnits())->toHaveCount(753);
});

it('resolves a province by number, id, slug, name', function (): void {
    expect(NepaliGeoMap::province(3)?->slug)->toBe('bagmati');
    expect(NepaliGeoMap::province('P3')?->slug)->toBe('bagmati');
    expect(NepaliGeoMap::province('bagmati')?->slug)->toBe('bagmati');
    expect(NepaliGeoMap::province('Bagmati')?->slug)->toBe('bagmati');
    expect(NepaliGeoMap::province('Province 3')?->slug)->toBe('bagmati');
});

it('resolves districts by id, slug, name', function (): void {
    expect(NepaliGeoMap::district('P3.D05')?->slug)->toBe('kathmandu');
    expect(NepaliGeoMap::district('kathmandu')?->slug)->toBe('kathmandu');
    expect(NepaliGeoMap::district('Kathmandu')?->slug)->toBe('kathmandu');
});

it('resolves local units by id, slug, name', function (): void {
    $unit = NepaliGeoMap::localUnit('Kathmandu Metropolitan City');

    expect($unit)->not->toBeNull();
    expect($unit?->type)->toBe('metropolitan');
    expect($unit?->wards)->toBe(32);
});

it('groups districts under provinces', function (): void {
    expect(NepaliGeoMap::districtsByProvince(3))->toHaveCount(13);
    expect(NepaliGeoMap::districtsByProvince(2))->toHaveCount(8);
});

it('groups local units under districts', function (): void {
    $units = NepaliGeoMap::localUnitsByDistrict('kathmandu');

    expect($units)->not->toBeEmpty();
    expect(array_column($units, 'nameEn'))->toContain('Kathmandu Metropolitan City');
});

it('validates ward ranges', function (): void {
    $kmcId = 'P3.D05.L01';

    expect(NepaliGeoMap::isValidWard($kmcId, 1))->toBeTrue();
    expect(NepaliGeoMap::isValidWard($kmcId, 32))->toBeTrue();
    expect(NepaliGeoMap::isValidWard($kmcId, 33))->toBeFalse();
    expect(NepaliGeoMap::isValidWard($kmcId, 0))->toBeFalse();
});

it('finds local unit by postal code', function (): void {
    $unit = NepaliGeoMap::findByPostalCode('44600');

    expect($unit?->id)->toBe('P3.D05.L01');
    expect($unit?->nameEn)->toBe('Kathmandu Metropolitan City');
});

it('returns null for unknown queries', function (): void {
    expect(NepaliGeoMap::province('atlantis'))->toBeNull();
    expect(NepaliGeoMap::district('atlantis'))->toBeNull();
    expect(NepaliGeoMap::localUnit('atlantis'))->toBeNull();
});
