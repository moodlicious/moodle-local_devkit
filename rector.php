<?php
// phpcs:ignoreFile

declare(strict_types=1);

use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/classes',
        __DIR__ . '/cli',
        __DIR__ . '/db',
        __DIR__ . '/debugbar',
        __DIR__ . '/demo',
        __DIR__ . '/lang',
        __DIR__ . '/lib',
        __DIR__ . '/tests',
        __DIR__ . '/phpstan-bootstrap.php',
        __DIR__ . '/rector.php',
        __DIR__ . '/settings.php',
        __DIR__ . '/version.php',
    ])
    ->withSkip([
        __DIR__ . '/tests/fixtures',
    ])
    ->withPhpSets()
    ->withImportNames(importShortClasses: false)
    ->withTypeCoverageLevel(70)
    ->withTypeCoverageDocblockLevel(20)
    ->withDeadCodeLevel(70)
    ->withCodeQualityLevel(70)
    ->withSkip([
        RemoveUselessVarTagRector::class,
        ShortenElseIfRector::class,
    ])
;
