<?php

declare(strict_types=1);

namespace RayzenAI\NepaliMap\Search;

use RayzenAI\NepaliMap\Data;

/**
 * Bilingual fuzzy search across provinces, districts, palikas, and their
 * aliases. Returns ranked hits with a score in `[0, 1]` (higher = closer).
 *
 * Scoring:
 *   - exact normalized match            → 1.0
 *   - prefix match                      → 0.9
 *   - substring match                   → 0.75 (length-discounted)
 *   - levenshtein within edit budget    → 0.6 .. 0.4 (distance-discounted)
 */
final class Search
{
    /**
     * @return list<array{type: string, id: string, slug: string, nameEn: string, nameNe: string, score: float}>
     */
    public static function run(string $query, int $limit = 10): array
    {
        $needle = self::normalize($query);

        if ($needle === '') {
            return [];
        }

        $hits = [];

        foreach (self::corpus() as $entry) {
            $best = 0.0;

            foreach ($entry['keys'] as $key) {
                $score = self::score($needle, $key);
                if ($score > $best) {
                    $best = $score;
                }
            }

            if ($best > 0.0) {
                $hits[] = [
                    'type' => $entry['type'],
                    'id' => $entry['id'],
                    'slug' => $entry['slug'],
                    'nameEn' => $entry['nameEn'],
                    'nameNe' => $entry['nameNe'],
                    'score' => $best,
                ];
            }
        }

        usort($hits, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_slice($hits, 0, max(0, $limit));
    }

    private static function score(string $needle, string $key): float
    {
        if ($key === '') {
            return 0.0;
        }

        if ($needle === $key) {
            return 1.0;
        }

        if (str_starts_with($key, $needle) || str_starts_with($needle, $key)) {
            return 0.9;
        }

        if (str_contains($key, $needle) || str_contains($needle, $key)) {
            $ratio = min(mb_strlen($needle), mb_strlen($key)) / max(mb_strlen($needle), mb_strlen($key));

            return 0.6 + 0.15 * $ratio;
        }

        // levenshtein only operates on bytes, so it works best on ASCII.
        // Skip it for Devanagari (which generally yields garbage distances).
        if (! self::isAscii($needle) || ! self::isAscii($key)) {
            return 0.0;
        }

        $maxLen = max(strlen($needle), strlen($key));
        $budget = max(1, (int) floor($maxLen * 0.34));

        $distance = levenshtein($needle, $key);

        if ($distance > $budget) {
            return 0.0;
        }

        return 0.6 - 0.2 * ($distance / max(1, $budget));
    }

    /**
     * @return iterable<array{type: string, id: string, slug: string, nameEn: string, nameNe: string, keys: list<string>}>
     */
    private static function corpus(): iterable
    {
        foreach ([
            ['type' => 'province', 'file' => 'provinces.json'],
            ['type' => 'district', 'file' => 'districts.json'],
            ['type' => 'localUnit', 'file' => 'local-units.json'],
        ] as $bucket) {
            /** @var list<array<string, mixed>> $rows */
            $rows = Data::load($bucket['file']);

            foreach ($rows as $row) {
                $keys = [];

                foreach (['slug', 'nameEn', 'nameNe'] as $field) {
                    if (isset($row[$field])) {
                        $keys[] = self::normalize((string) $row[$field]);
                    }
                }

                foreach ((array) ($row['aliases'] ?? []) as $alias) {
                    $keys[] = self::normalize((string) $alias);
                }

                yield [
                    'type' => $bucket['type'],
                    'id' => (string) $row['id'],
                    'slug' => (string) $row['slug'],
                    'nameEn' => (string) $row['nameEn'],
                    'nameNe' => (string) $row['nameNe'],
                    'keys' => array_values(array_unique(array_filter($keys, fn (string $k): bool => $k !== ''))),
                ];
            }
        }
    }

    private static function normalize(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? $value;

        return mb_strtolower($value, 'UTF-8');
    }

    private static function isAscii(string $value): bool
    {
        return preg_match('/^[\x00-\x7F]*$/', $value) === 1;
    }
}
