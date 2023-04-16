<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/Tests',
    ]);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_74,
        SetList::TYPE_DECLARATION,
    ]);

    $rectorConfig->skip([
        AddLiteralSeparatorToNumberRector::class,
        StringClassNameToClassConstantRector::class,
    ]);

    // Convert @var annotations to type declarations
};
