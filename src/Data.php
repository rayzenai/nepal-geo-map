<?php

declare(strict_types=1);

namespace RayzenAI\NepaliMap;

use RuntimeException;

/**
 * @internal
 *
 * Lazy, statically-cached JSON loader. The vendored `data/*.json` files are
 * decoded on first use and held in memory for the lifetime of the request.
 */
final class Data
{
    /** @var array<string, mixed> */
    private static array $cache = [];

    /**
     * @return mixed
     */
    public static function load(string $name)
    {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        $path = self::dataDir() . DIRECTORY_SEPARATOR . $name;

        if (! is_file($path)) {
            throw new RuntimeException("Vendored data file not found: {$name}");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Failed to read vendored data file: {$name}");
        }

        return self::$cache[$name] = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    public static function dataDir(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
    }

    /** @internal */
    public static function flush(): void
    {
        self::$cache = [];
    }
}
