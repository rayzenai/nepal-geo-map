<?php

declare(strict_types=1);

namespace RayzenAI\NepaliMap;

final readonly class Region
{
    /**
     * @param  list<string>  $aliases
     */
    public function __construct(
        public string $id,
        public int $number,
        public string $nameEn,
        public string $nameNe,
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
            slug: (string) $row['slug'],
            aliases: array_values(array_map('strval', (array) ($row['aliases'] ?? []))),
        );
    }
}
