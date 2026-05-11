<?php

declare(strict_types=1);

namespace RayzenAI\NepaliGeoMap;

final readonly class Constituency
{
    public const TYPE_FEDERAL = 'federal';
    public const TYPE_PROVINCIAL = 'provincial';

    public function __construct(
        public string $id,
        public string $type,
        public int $number,
        public string $nameEn,
        public string $nameNe,
        public string $slug,
        public string $provinceId,
        public ?string $districtId,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (string) $row['id'],
            type: (string) $row['type'],
            number: (int) $row['number'],
            nameEn: (string) $row['nameEn'],
            nameNe: (string) $row['nameNe'],
            slug: (string) $row['slug'],
            provinceId: (string) $row['provinceId'],
            districtId: isset($row['districtId']) ? (string) $row['districtId'] : null,
        );
    }
}
