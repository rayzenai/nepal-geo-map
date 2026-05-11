<?php

declare(strict_types=1);

use RayzenAI\NepaliGeoMap\NepaliGeoMap;

it('formats a short address', function (): void {
    $out = NepaliGeoMap::formatAddress([
        'ward' => 10,
        'tole' => 'Baluwatar',
        'localUnit' => 'P3.D05.L01',
        'district' => 'kathmandu',
    ]);

    expect($out)->toBe('Ward 10, Baluwatar, Kathmandu Metropolitan City, Kathmandu');
});

it('formats a postal-style address using the unit postal code', function (): void {
    $out = NepaliGeoMap::formatAddress([
        'tole' => 'Baluwatar',
        'localUnit' => 'P3.D05.L01',
        'district' => 'kathmandu',
    ], ['style' => 'postal']);

    expect($out)->toContain('44600');
    expect($out)->toContain('Nepal');
});

it('parses a postal code into a full address', function (): void {
    $parsed = NepaliGeoMap::parseAddress('Baluwatar, Kathmandu 44600');

    expect($parsed['postalCode'])->toBe('44600');
    expect($parsed['district'])->toBe('kathmandu');
    expect($parsed['province'])->toBe('bagmati');
    expect($parsed['confidence'])->toBeGreaterThan(0.5);
});

it('parses ward + tole + district', function (): void {
    $parsed = NepaliGeoMap::parseAddress('Ward 10, Baluwatar, Kathmandu');

    expect($parsed['ward'])->toBe(10);
    expect($parsed['district'])->toBe('kathmandu');
});

it('validates a well-formed address', function (): void {
    $r = NepaliGeoMap::validateAddress([
        'ward' => 10,
        'localUnit' => 'P3.D05.L01',
        'district' => 'kathmandu',
        'province' => 'bagmati',
    ]);

    expect($r['valid'])->toBeTrue();
    expect($r['errors'])->toBeEmpty();
});

it('rejects a district that does not belong to the given province', function (): void {
    $r = NepaliGeoMap::validateAddress([
        'district' => 'kathmandu',
        'province' => 'koshi',
    ]);

    expect($r['valid'])->toBeFalse();
    expect($r['errors'])->not->toBeEmpty();
});

it('rejects a ward outside the unit range', function (): void {
    $r = NepaliGeoMap::validateAddress([
        'ward' => 99,
        'localUnit' => 'P3.D05.L01',
    ]);

    expect($r['valid'])->toBeFalse();
});
