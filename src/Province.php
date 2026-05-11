<?php

declare(strict_types=1);

namespace RayzenAI\NepaliGeoMap;

final readonly class Province
{
    /**
     * @param  array{lat: float, lng: float}  $capitalCoords
     * @param  list<string>  $aliases
     */
    public function __construct(
        public string $id,
        public int $number,
        public string $nameEn,
        public string $nameNe,
        public string $capital,
        public string $capitalNe,
        public array $capitalCoords,
        public float $areaKm2,
        public int $population,
        public string $slug,
        public array $aliases,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (string) $row['id'],
            number: (int) $row['number'],
            nameEn: (string) $row['nameEn'],
            nameNe: (string) $row['nameNe'],
            capital: (string) $row['capital'],
            capitalNe: (string) $row['capitalNe'],
            capitalCoords: [
                'lat' => (float) $row['capitalCoords']['lat'],
                'lng' => (float) $row['capitalCoords']['lng'],
            ],
            areaKm2: (float) $row['areaKm2'],
            population: (int) $row['population'],
            slug: (string) $row['slug'],
            aliases: array_values(array_map('strval', (array) ($row['aliases'] ?? []))),
        );
    }
}
