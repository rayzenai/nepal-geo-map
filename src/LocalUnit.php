<?php

declare(strict_types=1);

namespace RayzenAI\NepaliGeoMap;

final readonly class LocalUnit
{
    public const TYPE_METROPOLITAN = 'metropolitan';
    public const TYPE_SUB_METROPOLITAN = 'sub-metropolitan';
    public const TYPE_MUNICIPALITY = 'municipality';
    public const TYPE_RURAL_MUNICIPALITY = 'rural-municipality';

    /**
     * @param  list<string>  $aliases
     * @param  array{lat: float, lng: float}|null  $coords
     */
    public function __construct(
        public string $id,
        public string $nameEn,
        public string $nameNe,
        public string $type,
        public string $districtId,
        public int $wards,
        public ?array $coords,
        public ?string $postalCode,
        public string $slug,
        public array $aliases,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        $coords = null;
        if (isset($row['coords']) && is_array($row['coords'])) {
            $coords = [
                'lat' => (float) $row['coords']['lat'],
                'lng' => (float) $row['coords']['lng'],
            ];
        }

        return new self(
            id: (string) $row['id'],
            nameEn: (string) $row['nameEn'],
            nameNe: (string) $row['nameNe'],
            type: (string) $row['type'],
            districtId: (string) $row['districtId'],
            wards: (int) $row['wards'],
            coords: $coords,
            postalCode: isset($row['postalCode']) ? (string) $row['postalCode'] : null,
            slug: (string) $row['slug'],
            aliases: array_values(array_map('strval', (array) ($row['aliases'] ?? []))),
        );
    }

    public function provinceId(): string
    {
        return explode('.', $this->districtId)[0];
    }
}
