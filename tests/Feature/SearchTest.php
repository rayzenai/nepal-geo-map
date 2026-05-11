<?php

declare(strict_types=1);

use RayzenAI\NepaliGeoMap\NepaliGeoMap;

it('returns exact matches first', function (): void {
    $hits = NepaliGeoMap::search('Kathmandu');

    expect($hits)->not->toBeEmpty();
    expect($hits[0]['nameEn'])->toBe('Kathmandu');
    expect($hits[0]['score'])->toBe(1.0);
});

it('matches Devanagari (character-exact)', function (): void {
    // Use the form stored in the data ("बागमती" province).
    $hits = NepaliGeoMap::search('बागमती', 3);

    expect($hits)->not->toBeEmpty();
    expect($hits[0]['nameNe'])->toBe('बागमती');
});

it('does fuzzy ASCII matching within an edit budget', function (): void {
    $hits = NepaliGeoMap::search('Kathmandhu');

    expect($hits)->not->toBeEmpty();
    expect(array_column($hits, 'nameEn'))->toContain('Kathmandu');
});

it('returns an empty list for an empty query', function (): void {
    expect(NepaliGeoMap::search(''))->toBeEmpty();
});
