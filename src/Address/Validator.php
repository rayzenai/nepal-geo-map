<?php

declare(strict_types=1);

namespace RayzenAI\NepaliGeoMap\Address;

use RayzenAI\NepaliGeoMap\NepaliGeoMap;

/**
 * Validate that a structured address is internally consistent — district
 * belongs to province, local-unit belongs to district, ward number exists.
 */
final class Validator
{
    /**
     * @param  array{
     *     ward?: int|null,
     *     localUnit?: string|null,
     *     district?: string|null,
     *     province?: string|int|null,
     *     postalCode?: string|null,
     * }  $parts
     * @return array{valid: bool, errors: list<string>}
     */
    public static function validate(array $parts): array
    {
        $errors = [];

        $province = isset($parts['province']) && $parts['province'] !== '' && $parts['province'] !== null
            ? NepaliGeoMap::province($parts['province'])
            : null;

        if (isset($parts['province']) && $parts['province'] !== '' && $parts['province'] !== null && $province === null) {
            $errors[] = sprintf('Unknown province: %s', (string) $parts['province']);
        }

        $district = isset($parts['district']) && $parts['district'] !== ''
            ? NepaliGeoMap::district((string) $parts['district'])
            : null;

        if (isset($parts['district']) && $parts['district'] !== '' && $district === null) {
            $errors[] = sprintf('Unknown district: %s', (string) $parts['district']);
        }

        if ($district !== null && $province !== null && $district->provinceId !== $province->id) {
            $errors[] = sprintf(
                'District %s does not belong to province %s',
                $district->nameEn,
                $province->nameEn,
            );
        }

        $unit = isset($parts['localUnit']) && $parts['localUnit'] !== ''
            ? NepaliGeoMap::localUnit((string) $parts['localUnit'])
            : null;

        if (isset($parts['localUnit']) && $parts['localUnit'] !== '' && $unit === null) {
            $errors[] = sprintf('Unknown local unit: %s', (string) $parts['localUnit']);
        }

        if ($unit !== null && $district !== null && $unit->districtId !== $district->id) {
            $errors[] = sprintf(
                'Local unit %s does not belong to district %s',
                $unit->nameEn,
                $district->nameEn,
            );
        }

        if (isset($parts['ward']) && $parts['ward'] !== null && $parts['ward'] !== '') {
            $ward = (int) $parts['ward'];

            if ($ward < 1) {
                $errors[] = sprintf('Invalid ward number: %d', $ward);
            } elseif ($unit !== null && $ward > $unit->wards) {
                $errors[] = sprintf(
                    'Ward %d is out of range for %s (1-%d)',
                    $ward,
                    $unit->nameEn,
                    $unit->wards,
                );
            }
        }

        if (isset($parts['postalCode']) && $parts['postalCode'] !== '' && $parts['postalCode'] !== null) {
            $code = (string) $parts['postalCode'];

            if (! preg_match('/^\d{5}$/', $code)) {
                $errors[] = sprintf('Postal code must be 5 digits: %s', $code);
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }
}
