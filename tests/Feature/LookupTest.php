<?php

declare(strict_types=1);

use RayzenAI\NepaliMap\NepaliMap;

it('lists 7 provinces', function (): void {
    expect(NepaliMap::provinces())->toHaveCount(7);
});

it('lists 77 districts', function (): void {
    expect(NepaliMap::districts())->toHaveCount(77);
});

it('lists 753 local units', function (): void {
    expect(NepaliMap::localUnits())->toHaveCount(753);
});

it('resolves a province by number, id, slug, name', function (): void {
    expect(NepaliMap::province(3)?->slug)->toBe('bagmati');
    expect(NepaliMap::province('P3')?->slug)->toBe('bagmati');
    expect(NepaliMap::province('bagmati')?->slug)->toBe('bagmati');
    expect(NepaliMap::province('Bagmati')?->slug)->toBe('bagmati');
    expect(NepaliMap::province('Province 3')?->slug)->toBe('bagmati');
});

it('resolves districts by id, slug, name', function (): void {
    expect(NepaliMap::district('P3.D05')?->slug)->toBe('kathmandu');
    expect(NepaliMap::district('kathmandu')?->slug)->toBe('kathmandu');
    expect(NepaliMap::district('Kathmandu')?->slug)->toBe('kathmandu');
});

it('resolves local units by id, slug, name', function (): void {
    $unit = NepaliMap::localUnit('Kathmandu Metropolitan City');

    expect($unit)->not->toBeNull();
    expect($unit?->type)->toBe('metropolitan');
    expect($unit?->wards)->toBe(32);
});

it('groups districts under provinces', function (): void {
    expect(NepaliMap::districtsByProvince(3))->toHaveCount(13);
    expect(NepaliMap::districtsByProvince(2))->toHaveCount(8);
});

it('groups local units under districts', function (): void {
    $units = NepaliMap::localUnitsByDistrict('kathmandu');

    expect($units)->not->toBeEmpty();
    expect(array_column($units, 'nameEn'))->toContain('Kathmandu Metropolitan City');
});

it('validates ward ranges', function (): void {
    $kmcId = 'P3.D05.L01';

    expect(NepaliMap::isValidWard($kmcId, 1))->toBeTrue();
    expect(NepaliMap::isValidWard($kmcId, 32))->toBeTrue();
    expect(NepaliMap::isValidWard($kmcId, 33))->toBeFalse();
    expect(NepaliMap::isValidWard($kmcId, 0))->toBeFalse();
});

it('finds local unit by postal code', function (): void {
    $unit = NepaliMap::findByPostalCode('44600');

    expect($unit?->id)->toBe('P3.D05.L01');
    expect($unit?->nameEn)->toBe('Kathmandu Metropolitan City');
});

it('returns null for unknown queries', function (): void {
    expect(NepaliMap::province('atlantis'))->toBeNull();
    expect(NepaliMap::district('atlantis'))->toBeNull();
    expect(NepaliMap::localUnit('atlantis'))->toBeNull();
});
