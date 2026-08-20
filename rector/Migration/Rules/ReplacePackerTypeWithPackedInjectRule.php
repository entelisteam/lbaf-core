<?php

declare(strict_types=1);

namespace EntelisTeam\Lbaf\Core\Rector\Migration\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Заменяет InjectGet/InjectPost с PackerType на InjectGetPacked/InjectPostPacked
 *
 * Трансформирует:
 * function foo(#[InjectGet(packerType: PackerType::Json)] Request $request)
 * в:
 * function foo(#[InjectGetPacked] Request $request)
 *
 * И:
 * function foo(#[InjectGet('input', packerType: PackerType::Json)] Request $request)
 * в:
 * function foo(#[InjectGetPacked('input')] Request $request)
 *
 * Аргумент с PackerType просто удаляется: в PackerType был только Json, а InjectGetPacked
 * использует Json как packer по умолчанию.
 */
final class ReplacePackerTypeWithPackedInjectRule extends AbstractRector
{
    /**
     * Атрибут => его Packed-замена
     */
    private const PACKED_REPLACEMENT = [
        'EntelisTeam\Lbaf\Core\Container\Attribute\InjectGet' => 'EntelisTeam\Lbaf\Core\Container\Attribute\InjectGetPacked',
        'EntelisTeam\Lbaf\Core\Container\Attribute\InjectPost' => 'EntelisTeam\Lbaf\Core\Container\Attribute\InjectPostPacked',
    ];

    private const PACKER_TYPE_CLASS = 'EntelisTeam\Lbaf\Core\Packer\PackerType';

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace InjectGet/InjectPost with PackerType by InjectGetPacked/InjectPostPacked',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
function foo(#[InjectGet(packerType: PackerType::Json)] Request $request) {}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
function foo(#[InjectGetPacked] Request $request) {}
CODE_SAMPLE
                ),
                new CodeSample(
                    <<<'CODE_SAMPLE'
function foo(#[InjectGet('input', packerType: PackerType::Json)] Request $request) {}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
function foo(#[InjectGetPacked('input')] Request $request) {}
CODE_SAMPLE
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [Param::class];
    }

    /**
     * @param Param $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanges = false;

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                $packedClass = $this->matchPackedClass($attribute);
                if ($packedClass === null) {
                    continue;
                }

                $args = $this->removePackerTypeArgs($attribute->args);
                if ($args === null) {
                    //PackerType не указан — это обычный InjectGet/InjectPost, его не трогаем
                    continue;
                }

                $attribute->name = new FullyQualified($packedClass);
                $attribute->args = $args;
                $hasChanges = true;
            }
        }

        return $hasChanges ? $node : null;
    }

    private function matchPackedClass(Attribute $attribute): ?string
    {
        foreach (self::PACKED_REPLACEMENT as $injectClass => $packedClass) {
            if ($this->isName($attribute->name, $injectClass)) {
                return $packedClass;
            }
        }

        return null;
    }

    /**
     * @param Arg[] $args
     * @return Arg[]|null список аргументов без PackerType или null, если PackerType не было
     */
    private function removePackerTypeArgs(array $args): ?array
    {
        $result = [];
        $found = false;

        foreach ($args as $arg) {
            if ($this->isPackerTypeArgument($arg)) {
                $found = true;
                continue;
            }
            $result[] = $arg;
        }

        return $found ? $result : null;
    }

    private function isPackerTypeArgument(Arg $arg): bool
    {
        //именованный аргумент packerType: <что угодно>
        if ($arg->name !== null && $arg->name->toString() === 'packerType') {
            return true;
        }

        //позиционный аргумент PackerType::<Case>
        if (!$arg->value instanceof ClassConstFetch) {
            return false;
        }

        $class = $arg->value->class;
        if (!$class instanceof Name) {
            return false;
        }

        return $class->toString() === 'PackerType' || $this->isName($class, self::PACKER_TYPE_CLASS);
    }
}
