<?php

declare(strict_types=1);

namespace RayzenAI\NepaliMap;

final readonly class District
{
    /**
     * @param  list<string>  $aliases
     */
    public function __construct(
        public string $id,
        public string $nameEn,
        public string $nameNe,
        public string $provinceId,
        public string $headquarters,
        public ?string $headquartersNe,
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
            provinceId: (string) $row['provinceId'],
            headquarters: (string) $row['headquarters'],
            headquartersNe: isset($row['headquartersNe']) ? (string) $row['headquartersNe'] : null,
            slug: (string) $row['slug'],
            aliases: array_values(array_map('strval', (array) ($row['aliases'] ?? []))),
        );
    }
}
