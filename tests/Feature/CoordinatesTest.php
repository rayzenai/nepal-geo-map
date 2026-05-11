<?php

declare(strict_types=1);

use RayzenAI\NepaliMap\NepaliMap;

it('finds Bagmati from Kathmandu coords', function (): void {
    expect(NepaliMap::findProvinceByCoords(27.7172, 85.3240)?->slug)->toBe('bagmati');
});

it('finds Kathmandu district from KMC coords', function (): void {
    expect(NepaliMap::findDistrictByCoords(27.7172, 85.3240)?->slug)->toBe('kathmandu');
});

it('returns null for coords outside Nepal', function (): void {
    // London
    expect(NepaliMap::findProvinceByCoords(51.5074, -0.1278))->toBeNull();
    expect(NepaliMap::findDistrictByCoords(51.5074, -0.1278))->toBeNull();
});

it('finds Pokhara district from Pokhara coords', function (): void {
    expect(NepaliMap::findDistrictByCoords(28.2096, 83.9856)?->slug)->toBe('kaski');
});

it('finds Koshi province from Biratnagar coords', function (): void {
    expect(NepaliMap::findProvinceByCoords(26.4525, 87.2718)?->slug)->toBe('koshi');
});
