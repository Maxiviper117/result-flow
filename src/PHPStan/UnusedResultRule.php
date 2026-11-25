<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\PHPStan;

use Maxiviper117\ResultFlow\Result;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/** @implements Rule<Expression> */
final class UnusedResultRule implements Rule
{
    public function getNodeType(): string
    {
        return Expression::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $expression = $node->expr;
        if (! $expression instanceof FuncCall && ! $expression instanceof MethodCall && ! $expression instanceof StaticCall && ! $expression instanceof NullsafeMethodCall) {
            return [];
        }

        if (! new ObjectType(Result::class)->isSuperTypeOf($scope->getType($expression))->yes()) {
            return [];
        }

        return [RuleErrorBuilder::message('Result returned by this call is discarded.')->identifier('resultFlow.ignoredResult')->build()];
    }
}
