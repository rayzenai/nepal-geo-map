<?php

declare(strict_types=1);

namespace RayzenAI\NepaliMap\Address;

use RayzenAI\NepaliMap\NepaliMap;

/**
 * Address rendering. Accepts loose parts (slugs / ids / names / numbers) and
 * produces a single human-readable string. Bilingual.
 *
 * Styles:
 *   - `short`  → "Ward N, Tole, LocalUnit, District" (default)
 *   - `long`   → "Ward N, Tole, LocalUnit, District, Province, Country"
 *   - `postal` → "Tole, LocalUnit, District<postal-code>, Country"
 */
final class Formatter
{
    /**
     * @param  array{
     *     ward?: int|null,
     *     tole?: string|null,
     *     localUnit?: string|null,
     *     district?: string|null,
     *     province?: string|int|null,
     *     postalCode?: string|null,
     *     country?: string|null,
     * }  $parts
     * @param  array{lang?: 'en'|'ne', style?: 'short'|'long'|'postal'}|null  $options
     */
    public static function format(array $parts, ?array $options = null): string
    {
        $lang = $options['lang'] ?? 'en';
        $style = $options['style'] ?? 'short';

        $unit = isset($parts['localUnit']) && $parts['localUnit'] !== ''
            ? NepaliMap::localUnit((string) $parts['localUnit'])
            : null;

        $district = isset($parts['district']) && $parts['district'] !== ''
            ? NepaliMap::district((string) $parts['district'])
            : null;

        $province = isset($parts['province']) && $parts['province'] !== '' && $parts['province'] !== null
            ? NepaliMap::province($parts['province'])
            : null;

        $unitName = $unit !== null ? ($lang === 'ne' ? $unit->nameNe : $unit->nameEn) : (isset($parts['localUnit']) ? (string) $parts['localUnit'] : null);
        $districtName = $district !== null ? ($lang === 'ne' ? $district->nameNe : $district->nameEn) : (isset($parts['district']) ? (string) $parts['district'] : null);
        $provinceName = $province !== null ? ($lang === 'ne' ? $province->nameNe : $province->nameEn) : (isset($parts['province']) ? (string) $parts['province'] : null);

        $tokens = [];

        if (isset($parts['ward']) && $parts['ward'] !== null && $parts['ward'] !== '') {
            $tokens[] = $lang === 'ne' ? sprintf('वडा %d', (int) $parts['ward']) : sprintf('Ward %d', (int) $parts['ward']);
        }

        if (isset($parts['tole']) && $parts['tole'] !== '') {
            $tokens[] = (string) $parts['tole'];
        }

        if ($unitName !== null && $unitName !== '') {
            $tokens[] = $unitName;
        }

        if ($style === 'postal') {
            $postalCode = $parts['postalCode'] ?? ($unit?->postalCode);

            if ($districtName !== null && $districtName !== '') {
                $tokens[] = $postalCode !== null
                    ? sprintf('%s %s', $districtName, $postalCode)
                    : $districtName;
            } elseif ($postalCode !== null) {
                $tokens[] = (string) $postalCode;
            }

            $tokens[] = $parts['country'] ?? ($lang === 'ne' ? 'नेपाल' : 'Nepal');

            return self::joinTokens($tokens);
        }

        if ($districtName !== null && $districtName !== '') {
            $tokens[] = $districtName;
        }

        if ($style === 'long') {
            if ($provinceName !== null && $provinceName !== '') {
                $tokens[] = $provinceName;
            }

            $tokens[] = $parts['country'] ?? ($lang === 'ne' ? 'नेपाल' : 'Nepal');
        }

        return self::joinTokens($tokens);
    }

    /**
     * @param  list<string|null>  $tokens
     */
    private static function joinTokens(array $tokens): string
    {
        $filtered = array_values(array_filter(
            $tokens,
            fn ($t): bool => $t !== null && trim((string) $t) !== '',
        ));

        return implode(', ', array_map(fn ($t): string => trim((string) $t), $filtered));
    }
}
