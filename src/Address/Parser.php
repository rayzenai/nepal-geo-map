<?php

declare(strict_types=1);

namespace RayzenAI\NepaliGeoMap\Address;

use RayzenAI\NepaliGeoMap\NepaliGeoMap;

/**
 * Best-effort parser for free-form Nepali addresses. Returns structured
 * parts with a confidence in `[0, 1]`. Tolerant of mixed Latin/Devanagari,
 * arbitrary punctuation, and out-of-order tokens.
 */
final class Parser
{
    /**
     * @return array{
     *     ward: int|null,
     *     tole: string|null,
     *     localUnit: string|null,
     *     district: string|null,
     *     province: string|null,
     *     postalCode: string|null,
     *     confidence: float,
     * }
     */
    public static function parse(string $raw): array
    {
        $raw = trim($raw);

        $result = [
            'ward' => null,
            'tole' => null,
            'localUnit' => null,
            'district' => null,
            'province' => null,
            'postalCode' => null,
            'confidence' => 0.0,
        ];

        if ($raw === '') {
            return $result;
        }

        $hits = 0;
        $work = $raw;

        // 1) Postal code — five-digit run.
        if (preg_match('/\b(\d{5})\b/u', $work, $m)) {
            $code = $m[1];
            $unit = NepaliGeoMap::findByPostalCode($code);
            $result['postalCode'] = $code;
            $work = (string) preg_replace('/\b\d{5}\b/u', ' ', $work, 1);

            if ($unit !== null) {
                $result['localUnit'] = $unit->slug;
                $district = NepaliGeoMap::district($unit->districtId);
                if ($district !== null) {
                    $result['district'] = $district->slug;
                    $province = NepaliGeoMap::province($district->provinceId);
                    if ($province !== null) {
                        $result['province'] = $province->slug;
                    }
                }
                $hits += 3;
            } else {
                $hits++;
            }
        }

        // 2) Ward number — "Ward N", "Ward No. N", "W-N", "वडा N".
        $wardPatterns = [
            '/\b(?:ward|w)[\s\.\-]*(?:no[\.\s]*)?(\d{1,2})\b/iu',
            '/वडा[\s\.\-]*(?:नं[\.\s]*)?(\d{1,2})/u',
        ];

        foreach ($wardPatterns as $pattern) {
            if (preg_match($pattern, $work, $m)) {
                $result['ward'] = (int) $m[1];
                $work = (string) preg_replace($pattern, ' ', $work, 1);
                $hits++;
                break;
            }
        }

        // 3) Split remaining tokens and try to identify each.
        $tokens = array_values(array_filter(
            array_map('trim', preg_split('/[,;\/\|\n]+/u', $work) ?: []),
            fn (string $t): bool => $t !== '',
        ));

        $unresolved = [];

        foreach ($tokens as $token) {
            $clean = trim((string) preg_replace('/\s+/u', ' ', $token));

            if ($clean === '') {
                continue;
            }

            // Try province / district / local-unit lookup against the whole token first.
            if ($result['localUnit'] === null) {
                $unit = NepaliGeoMap::localUnit($clean);
                if ($unit !== null) {
                    $result['localUnit'] = $unit->slug;
                    $hits++;
                    continue;
                }
            }

            if ($result['district'] === null) {
                $district = NepaliGeoMap::district($clean);
                if ($district !== null) {
                    $result['district'] = $district->slug;
                    $hits++;
                    continue;
                }
            }

            if ($result['province'] === null) {
                $province = NepaliGeoMap::province($clean);
                if ($province !== null) {
                    $result['province'] = $province->slug;
                    $hits++;
                    continue;
                }
            }

            $unresolved[] = $clean;
        }

        // 4) Use the first leftover token as the tole when we have other anchor data.
        if ($result['tole'] === null && $unresolved !== [] && ($result['ward'] !== null || $result['localUnit'] !== null || $result['district'] !== null)) {
            $result['tole'] = $unresolved[0];
            $hits++;
        }

        // 5) Derive parent entities if we have a child.
        if ($result['localUnit'] !== null && $result['district'] === null) {
            $unit = NepaliGeoMap::localUnit($result['localUnit']);
            if ($unit !== null) {
                $district = NepaliGeoMap::district($unit->districtId);
                if ($district !== null) {
                    $result['district'] = $district->slug;
                }
            }
        }

        if ($result['district'] !== null && $result['province'] === null) {
            $district = NepaliGeoMap::district($result['district']);
            if ($district !== null) {
                $province = NepaliGeoMap::province($district->provinceId);
                if ($province !== null) {
                    $result['province'] = $province->slug;
                }
            }
        }

        // Confidence: rough heuristic on how many anchors we resolved.
        $maxHits = 6.0; // postal(3) + ward(1) + localUnit(1) + district(1) — cap
        $result['confidence'] = min(1.0, $hits / $maxHits);

        return $result;
    }
}
