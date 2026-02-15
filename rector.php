<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodeQuality\Rector\Ternary\SwitchNegatedTernaryRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector;
use Rector\Php74\Rector\Assign\NullCoalescingOperatorRector;
use Rector\Php80\Rector\FuncCall\ClassOnObjectRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByMethodCallTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnUnionTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictScalarReturnsRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictStringReturnsRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureNeverReturnTypeRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Closure\ClosureReturnTypeRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/config',
    ])
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
    )
    ->withParallel(
        timeoutSeconds: 180,
        maxNumberOfProcess: 8,
        jobSize: 20,
    )
    ->withSkip([
        // Keep low-noise defaults for OSS maintenance and avoid broad signature churn.
        AddArrowFunctionReturnTypeRector::class,
        AddClosureNeverReturnTypeRector::class,
        AddClosureVoidReturnTypeWhereNoReturnRector::class,
        AddVoidReturnTypeWhereNoReturnRector::class,
        ArrayToFirstClassCallableRector::class,
        ClassOnObjectRector::class,
        ClosureReturnTypeRector::class,
        NullCoalescingOperatorRector::class,
        NullToStrictStringFuncCallArgRector::class,
        ParamTypeByMethodCallTypeRector::class,
        ReadOnlyClassRector::class,
        ReadOnlyPropertyRector::class,
        ReturnNeverTypeRector::class,
        RemoveEmptyClassMethodRector::class,
        RemoveNonExistingVarAnnotationRector::class,
        RemoveUnusedPublicMethodParameterRector::class,
        ReturnTypeFromStrictTypedCallRector::class,
        ReturnUnionTypeRector::class,
        SimplifyIfElseToTernaryRector::class,
        StringReturnTypeFromStrictScalarReturnsRector::class,
        StringReturnTypeFromStrictStringReturnsRector::class,
        SwitchNegatedTernaryRector::class,
    ]);
