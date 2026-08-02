<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\PHPStan\Type;

use Generator;
use Maxiviper117\ResultFlow\Result;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

/**
 * Infers Result success and failure types from a flow callback's Generator type.
 */
final class FlowReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Result::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['flow', 'tryFlow'], true);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): ?Type {
        $workflow = $methodCall->args[0]->value ?? null;

        if ($workflow === null) {
            return null;
        }

        $acceptors = $scope->getType($workflow)->getCallableParametersAcceptors($scope);
        $returnType = $acceptors[0]->getReturnType() ?? null;

        if (! $returnType instanceof GenericObjectType || $returnType->getClassName() !== Generator::class) {
            return null;
        }

        $generatorTypes = $returnType->getTypes();

        if (count($generatorTypes) < 4) {
            return null;
        }

        $yieldedType = $generatorTypes[1];
        $returnedType = $generatorTypes[3];
        $failureType = $this->failureType($yieldedType);
        $successType = $returnedType;

        if ($returnedType instanceof GenericObjectType && $returnedType->getClassName() === Result::class) {
            $resultTypes = $returnedType->getTypes();
            $successType = $resultTypes[0] ?? $successType;
            $failureType = TypeCombinator::union($failureType, $resultTypes[1] ?? new NeverType);
        }

        if ($methodReflection->getName() === 'tryFlow') {
            $failureType = TypeCombinator::union($failureType, new ObjectType(\Throwable::class));
        }

        return new GenericObjectType(Result::class, [$successType, $failureType]);
    }

    private function failureType(Type $type): Type
    {
        if ($type instanceof UnionType) {
            $failureTypes = [];

            foreach ($type->getTypes() as $unionType) {
                $failureTypes[] = $this->failureType($unionType);
            }

            return TypeCombinator::union(...$failureTypes);
        }

        if (! $type instanceof GenericObjectType || $type->getClassName() !== Result::class) {
            return new NeverType;
        }

        return $type->getTypes()[1] ?? new NeverType;
    }
}
