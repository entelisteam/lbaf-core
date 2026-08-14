<?php

declare(strict_types=1);

namespace EntelisTeam\Lbaf\Core\Rector\Migration\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Переносит атрибуты Inject* с методов на параметры
 *
 * Трансформирует:
 * #[InjectGet('bar')] function foo(string $bar)
 * в:
 * function foo(#[InjectGet()] string $bar)
 *
 * И:
 * #[InjectGet('bar', 'anyGetKey')] function foo(string $bar)
 * в:
 * function foo(#[InjectGet('anyGetKey')] string $bar)
 */
final class MoveInjectAttributesToParametersRule extends AbstractRector
{
    private const INJECT_ATTRIBUTES = [
        'Lbaf\Container\Attribute\InjectGet',
        'Lbaf\Container\Attribute\InjectPost',
         //'Lbaf\Container\Attribute\InjectHeader', //не работает в параметрах из-за кривой логики внутри
        'Lbaf\Container\Attribute\InjectEnv',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Move Inject* attributes from method to parameters',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
#[InjectGet('bar')]
function foo(string $bar) {}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
function foo(#[InjectGet()] string $bar) {}
CODE_SAMPLE
                ),
                new CodeSample(
                    <<<'CODE_SAMPLE'
#[InjectGet('bar', 'anyGetKey')]
function foo(string $bar) {}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
function foo(#[InjectGet('anyGetKey')] string $bar) {}
CODE_SAMPLE
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->attrGroups === []) {
            return null;
        }

        $hasChanges = false;
        $attributesToRemove = [];

        // Перебираем все группы атрибутов метода
        foreach ($node->attrGroups as $groupIndex => $attrGroup) {
            foreach ($attrGroup->attrs as $attrIndex => $attribute) {
                // Проверяем, является ли это одним из Inject* атрибутов
                if (!$this->isInjectAttribute($attribute)) {
                    continue;
                }

                // Проверяем, что у атрибута есть хотя бы один аргумент
                if ($attribute->args === []) {
                    continue;
                }

                // Первый аргумент - это имя параметра
                $firstArg = $attribute->args[0];

                if (!$firstArg->value instanceof String_) {
                    continue;
                }

                $paramName = $firstArg->value->value;

                // Ищем параметр с таким именем
                $targetParam = $this->findParamByName($node, $paramName);
                if ($targetParam === null) {
                    continue;
                }

                // Создаем новый атрибут для параметра
                $newAttribute = $this->createParameterAttribute($attribute);

                // Добавляем атрибут к параметру
                $targetParam->attrGroups[] = new AttributeGroup([$newAttribute]);

                // Помечаем атрибут метода для удаления
                $attributesToRemove[] = ['group' => $groupIndex, 'attr' => $attrIndex];
                $hasChanges = true;
            }
        }

        if (!$hasChanges) {
            return null;
        }

        // Удаляем использованные атрибуты из метода
        $this->removeAttributes($node, $attributesToRemove);

        return $node;
    }

    private function isInjectAttribute(Attribute $attribute): bool
    {
        foreach (self::INJECT_ATTRIBUTES as $injectAttributeClass) {
            if ($this->isName($attribute->name, $injectAttributeClass)) {
                return true;
            }
        }

        return false;
    }

    private function findParamByName(ClassMethod $method, string $name): ?Param
    {
        foreach ($method->params as $param) {
            if ($param->var instanceof Variable && $param->var->name === $name) {
                return $param;
            }
        }

        return null;
    }

    private function createParameterAttribute(Attribute $originalAttribute): Attribute
    {
        // Если у оригинального атрибута только один аргумент (имя параметра),
        // создаем атрибут без аргументов
        if (count($originalAttribute->args) === 1) {
            return new Attribute(
                $originalAttribute->name,
                []
            );
        }

        // Если аргументов больше одного, берем все кроме первого
        $newArgs = array_slice($originalAttribute->args, 1);

        // Проверяем, является ли второй или третий аргумент PackerType
        // Если да, то делаем его именованным параметром $packerType
        $processedArgs = [];
        foreach ($newArgs as $index => $arg) {
            if ($this->isPackerTypeArgument($arg)) {
                // Создаем именованный аргумент
                $processedArgs[] = new Arg(
                    $arg->value,
                    false,
                    false,
                    [],
                    new Identifier('packerType')
                );
            } else {
                $processedArgs[] = $arg;
            }
        }

        return new Attribute(
            $originalAttribute->name,
            $processedArgs
        );
    }

    private function isPackerTypeArgument(Arg $arg): bool
    {
        // Проверяем, является ли значение аргумента ссылкой на PackerType enum
        if ($arg->value instanceof ClassConstFetch) {
            $class = $arg->value->class;
            if ($class instanceof Name) {
                $className = $class->toString();
                return $className === 'Lbaf\Packer\PackerType'
                    || $className === 'PackerType';
            }
        }

        return false;
    }

    private function removeAttributes(ClassMethod $method, array $attributesToRemove): void
    {
        // Группируем индексы для удаления по группам
        $groupedForRemoval = [];
        foreach ($attributesToRemove as $item) {
            $groupedForRemoval[$item['group']][] = $item['attr'];
        }

        // Удаляем атрибуты в обратном порядке, чтобы не нарушить индексы
        foreach (array_reverse($groupedForRemoval, true) as $groupIndex => $attrIndices) {
            rsort($attrIndices);
            foreach ($attrIndices as $attrIndex) {
                unset($method->attrGroups[$groupIndex]->attrs[$attrIndex]);
            }

            // Если в группе не осталось атрибутов, удаляем саму группу
            if (count($method->attrGroups[$groupIndex]->attrs) === 0) {
                unset($method->attrGroups[$groupIndex]);
            }
        }

        // Переиндексируем массив групп атрибутов
        $method->attrGroups = array_values($method->attrGroups);
    }
}
