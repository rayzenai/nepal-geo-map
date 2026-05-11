<?php

declare(strict_types=1);

namespace RayzenAI\NepaliMap;

final readonly class LegacyDistrict
{
    /**
     * @param  list<string>  $currentDistrictIds
     * @param  list<string>  $aliases
     */
    public function __construct(
        public string $id,
        public string $nameEn,
        public string $nameNe,
        public string $zoneId,
        public array $currentDistrictIds,
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
            nameEn: (string) $row['nameEn'],
            nameNe: (string) $row['nameNe'],
            zoneId: (string) $row['zoneId'],
            currentDistrictIds: array_values(array_map('strval', (array) ($row['currentDistrictIds'] ?? []))),
            slug: (string) $row['slug'],
            aliases: array_values(array_map('strval', (array) ($row['aliases'] ?? []))),
        );
    }
}
