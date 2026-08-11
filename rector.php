<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([__DIR__])
    ->withSets([LevelSetList::UP_TO_PHP_81])
    ->withSkip([
        __DIR__ . '/vendor',
        __DIR__ . '/node_modules',
        __DIR__ . '/cache',
        __DIR__ . '/tmp',
        __DIR__ . '/var',
        __DIR__ . '/releases',
    ]);
