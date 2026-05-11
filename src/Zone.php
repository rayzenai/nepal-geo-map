<?php

declare(strict_types=1);

namespace RayzenAI\NepaliGeoMap;

final readonly class Zone
{
    /**
     * @param  list<string>  $aliases
     */
    public function __construct(
        public string $id,
        public string $nameEn,
        public string $nameNe,
        public string $regionId,
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
            regionId: (string) $row['regionId'],
            slug: (string) $row['slug'],
            aliases: array_values(array_map('strval', (array) ($row['aliases'] ?? []))),
        );
    }
}
