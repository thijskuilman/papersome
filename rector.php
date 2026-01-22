<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\Enum_\EnumCaseToPascalCaseRector;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\CodeQuality\Rector\Class_\AddSeeTestAnnotationRector;
use RectorLaravel\Rector\ClassMethod\MigrateToSimplifiedAttributeRector;
use RectorLaravel\Rector\FuncCall\RemoveDumpDataDeadCodeRector;
use RectorLaravel\Rector\If_\ThrowIfRector;
use RectorLaravel\Rector\StaticCall\DispatchToHelperFunctionsRector;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withSetProviders(LaravelSetProvider::class)
    ->withComposerBased(laravel: true)
    ->withPhpSets(php84: true)
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/tests',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/config',
        __DIR__.'/resources/views',
    ])
    ->withParallel()
    ->withPreparedSets(
        deadCode: true,
        typeDeclarations: true,
        carbon: true
    )
    ->withRules([
        ThrowIfRector::class,
        EnumCaseToPascalCaseRector::class,
        DispatchToHelperFunctionsRector::class, // Changes Job::dispatch($arg) to dispatch(new Job($arg)). This seems to be broken. See: https://github.com/driftingly/rector-laravel/pull/225
        AddSeeTestAnnotationRector::class,
        MigrateToSimplifiedAttributeRector::class,
        RemoveDumpDataDeadCodeRector::class,
    ])
    ->withConfiguredRule(RemoveDumpDataDeadCodeRector::class, [
        'dd', 'dump', 'var_dump',
    ]);
