<?php
// phpcs:ignoreFile

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/classes',
        __DIR__ . '/cli',
        __DIR__ . '/db',
        __DIR__ . '/debugbar',
        __DIR__ . '/demo',
        __DIR__ . '/lang',
        __DIR__ . '/lib',
        __DIR__ . '/tests/local',
        __DIR__ . '*.php',
    ])
    // uncomment to reach your current PHP version
    // ->withPhpSets()
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
