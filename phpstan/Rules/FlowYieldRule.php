<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\PHPStan\Rules;

use Maxiviper117\ResultFlow\Result;
use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

/**
 * Validates yielded values in inline Result::flow() workflows.
 *
 * @implements Rule<StaticCall>
 */
final class FlowYieldRule implements Rule
{
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node instanceof StaticCall || ! $node->class instanceof Node\Name) {
            return [];
        }

        if ($scope->resolveName($node->class) !== Result::class) {
            return [];
        }

        if (! $node->name instanceof Node\Identifier || $node->name->toString() !== 'flow') {
            return [];
        }

        $workflow = $node->args[0]->value ?? null;

        if (! $workflow instanceof Closure) {
            return [];
        }

        /** @var list<Yield_|YieldFrom> $yieldNodes */
        $yieldNodes = [];
        $this->collectYieldNodes($workflow->stmts, $yieldNodes);

        $errors = [];
        $resultType = new ObjectType(Result::class);

        foreach ($yieldNodes as $yieldNode) {
            if ($yieldNode instanceof YieldFrom) {
                if (! $this->isResultBind($yieldNode->expr, $scope)) {
                    $errors[] = RuleErrorBuilder::message(
                        'Result::flow() supports yield from only with Result::bind().',
                    )
                        ->identifier('resultFlow.unsupportedYieldFrom')
                        ->build();
                }

                continue;
            }

            if ($yieldNode->value === null) {
                $errors[] = RuleErrorBuilder::message(
                    'Result::flow() must yield Result values. null yielded.',
                )
                    ->identifier('resultFlow.invalidYield')
                    ->build();

                continue;
            }

            $yieldedType = $scope->getType($yieldNode->value);

            if (! $resultType->isSuperTypeOf($yieldedType)->yes()) {
                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Result::flow() must yield Result values. %s yielded.',
                    $yieldedType->describe(VerbosityLevel::typeOnly()),
                ))
                    ->identifier('resultFlow.invalidYield')
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * @param  list<Node>  $nodes
     * @param  list<Yield_|YieldFrom>  $yieldNodes
     */
    private function collectYieldNodes(array $nodes, array &$yieldNodes): void
    {
        foreach ($nodes as $node) {
            if ($node instanceof Yield_ || $node instanceof YieldFrom) {
                $yieldNodes[] = $node;
            }

            if ($node instanceof Closure || $node instanceof Node\Expr\ArrowFunction) {
                continue;
            }

            foreach ($node->getSubNodeNames() as $subNodeName) {
                $subNode = $node->{$subNodeName};

                if ($subNode instanceof Node) {
                    $this->collectYieldNodes([$subNode], $yieldNodes);
                } elseif (is_array($subNode)) {
                    /** @var list<Node> $childNodes */
                    $childNodes = array_values(array_filter(
                        $subNode,
                        static fn (mixed $child): bool => $child instanceof Node,
                    ));

                    $this->collectYieldNodes($childNodes, $yieldNodes);
                }
            }
        }
    }

    private function isResultBind(Node $node, Scope $scope): bool
    {
        return $node instanceof StaticCall
            && $node->class instanceof Node\Name
            && $node->name instanceof Node\Identifier
            && $scope->resolveName($node->class) === Result::class
            && $node->name->toString() === 'bind';
    }
}
