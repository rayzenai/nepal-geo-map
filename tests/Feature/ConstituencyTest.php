<?php

declare(strict_types=1);

use RayzenAI\NepaliGeoMap\Constituency;
use RayzenAI\NepaliGeoMap\NepaliGeoMap;

it('lists 165 federal + 330 provincial constituencies', function (): void {
    expect(NepaliGeoMap::constituencies(Constituency::TYPE_FEDERAL))->toHaveCount(165);
    expect(NepaliGeoMap::constituencies(Constituency::TYPE_PROVINCIAL))->toHaveCount(330);
    expect(NepaliGeoMap::constituencies())->toHaveCount(495);
});

it('returns Kathmandu federal constituencies by district', function (): void {
    $kathmandu = NepaliGeoMap::federalConstituenciesByDistrict('kathmandu');

    expect($kathmandu)->toHaveCount(10);
    expect($kathmandu[0]->nameEn)->toBe('Kathmandu-1');
    expect($kathmandu[9]->number)->toBe(10);
});

it('returns Bagmati provincial constituencies by province', function (): void {
    $bagmati = NepaliGeoMap::provincialConstituenciesByProvince(3);

    expect($bagmati)->toHaveCount(66);
});

it('resolves a constituency by slug', function (): void {
    $c = NepaliGeoMap::constituency('kathmandu-5');

    expect($c?->type)->toBe('federal');
    expect($c?->districtId)->toBe('P3.D05');
    expect($c?->number)->toBe(5);
});

it('resolves a provincial constituency by slug', function (): void {
    $c = NepaliGeoMap::constituency('bagmati-provincial-1');

    expect($c?->type)->toBe('provincial');
    expect($c?->provinceId)->toBe('P3');
});

it('handles the Rukum split correctly', function (): void {
    expect(NepaliGeoMap::federalConstituenciesByDistrict('eastern-rukum'))->toHaveCount(1);
    expect(NepaliGeoMap::federalConstituenciesByDistrict('western-rukum'))->toHaveCount(1);
});
