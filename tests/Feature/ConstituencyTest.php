<?php

declare(strict_types=1);

use RayzenAI\NepaliMap\Constituency;
use RayzenAI\NepaliMap\NepaliMap;

it('lists 165 federal + 330 provincial constituencies', function (): void {
    expect(NepaliMap::constituencies(Constituency::TYPE_FEDERAL))->toHaveCount(165);
    expect(NepaliMap::constituencies(Constituency::TYPE_PROVINCIAL))->toHaveCount(330);
    expect(NepaliMap::constituencies())->toHaveCount(495);
});

it('returns Kathmandu federal constituencies by district', function (): void {
    $kathmandu = NepaliMap::federalConstituenciesByDistrict('kathmandu');

    expect($kathmandu)->toHaveCount(10);
    expect($kathmandu[0]->nameEn)->toBe('Kathmandu-1');
    expect($kathmandu[9]->number)->toBe(10);
});

it('returns Bagmati provincial constituencies by province', function (): void {
    $bagmati = NepaliMap::provincialConstituenciesByProvince(3);

    expect($bagmati)->toHaveCount(66);
});

it('resolves a constituency by slug', function (): void {
    $c = NepaliMap::constituency('kathmandu-5');

    expect($c?->type)->toBe('federal');
    expect($c?->districtId)->toBe('P3.D05');
    expect($c?->number)->toBe(5);
});

it('resolves a provincial constituency by slug', function (): void {
    $c = NepaliMap::constituency('bagmati-provincial-1');

    expect($c?->type)->toBe('provincial');
    expect($c?->provinceId)->toBe('P3');
});

it('handles the Rukum split correctly', function (): void {
    expect(NepaliMap::federalConstituenciesByDistrict('eastern-rukum'))->toHaveCount(1);
    expect(NepaliMap::federalConstituenciesByDistrict('western-rukum'))->toHaveCount(1);
});
