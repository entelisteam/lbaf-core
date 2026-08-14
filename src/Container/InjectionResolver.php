<?php
declare(strict_types=1);

namespace EntelisTeam\Lbaf\Core\Container;

use EntelisTeam\Lbaf\Core\Container\Attribute\Inject;
use EntelisTeam\Lbaf\Core\Container\Attribute\InjectValueAbstract;
use EntelisTeam\Lbaf\Core\Container\Attribute\InjectValueArrayAbstract;
use EntelisTeam\Lbaf\Core\Container\Exception\InjectArgumentTypeException;
use EntelisTeam\Lbaf\Core\Container\Exception\InjectRequiredArgumentException;
use EntelisTeam\Lbaf\Exception\BadRequestException;
use EntelisTeam\Lbaf\Hydrator\Attribute\ArrayTypeOf;
use EntelisTeam\Lbaf\Hydrator\Exception\ArgumentTypeException;
use EntelisTeam\Lbaf\Hydrator\Exception\RequiredArgumentException;
use EntelisTeam\Lbaf\Hydrator\Internal\HydratorEngine;
use EntelisTeam\Lbaf\Reflection\MethodParameters;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionMethod;

/**
 * Разрешает значения параметров метода по Inject*-атрибутам, опираясь на контейнер.
 *
 * Это граница между DI-контейнером и гидратором: атрибуты дают сырое значение
 * (объект из POST, строка из ENV и т.п.), а гидратор приводит его к типу параметра.
 * Все гидраторные исключения тут оборачиваются в Lbaf-овские, наследники BadRequestException.
 */
class InjectionResolver
{
    /**
     * Формирует массив [paramName => value] для метода на основе его Inject*-атрибутов.
     * @return array<string, mixed>
     */
    public static function resolve(ReflectionMethod $methodReflection, ContainerInterface $container): array
    {
        $args = [];

        //Inject для метода — заполняет аргументы классами из контейнера
        foreach ($methodReflection->getAttributes(Inject::class, ReflectionAttribute::IS_INSTANCEOF) as $attributeReflection) {
            /** @var Inject $attribute */
            $attribute = $attributeReflection->newInstance();

            //Inject('db') для function doAction (Mysql $db) — тип берётся из подписи параметра
            if (is_null($attribute->targetClass)) {
                $attribute->targetClass = self::getParameterTypeName($methodReflection, $attribute->paramName);
            }

            $args[$attribute->paramName] = $container->get($attribute->targetClass);
        }

        //Inject для отдельных параметров
        foreach ($methodReflection->getParameters() as $paramReflection) {
            foreach ($paramReflection->getAttributes(Inject::class, ReflectionAttribute::IS_INSTANCEOF) as $attributeReflection) {
                /** @var Inject $attribute */
                $attribute = $attributeReflection->newInstance();
                //грязный код для сдвига
                $args[$paramReflection->getName()] = $container->get(
                    $attribute->paramName ?? self::getParameterTypeName($methodReflection, $paramReflection->getName())
                );
            }
        }

        //InjectValue*: сначала собираем типизацию массивов из ArrayTypeOf — отдельно для метода и для параметров
        $functionArrayParametersType = [];
        foreach ($methodReflection->getAttributes(ArrayTypeOf::class, ReflectionAttribute::IS_INSTANCEOF) as $attributeReflection) {
            /** @var ArrayTypeOf $attribute */
            $attribute = $attributeReflection->newInstance();
            $functionArrayParametersType[$attribute->param] = $attribute->targetClass;
        }
        foreach ($methodReflection->getParameters() as $paramReflection) {
            foreach ($paramReflection->getAttributes(ArrayTypeOf::class, ReflectionAttribute::IS_INSTANCEOF) as $attributeReflection) {
                /** @var ArrayTypeOf $attribute */
                $attribute = $attributeReflection->newInstance();
                $functionArrayParametersType[$paramReflection->getName()] = $attribute->targetClass;
            }
        }

        //InjectValue* для метода
        foreach ($methodReflection->getAttributes(InjectValueAbstract::class, ReflectionAttribute::IS_INSTANCEOF) as $attributeReflection) {
            /** @var InjectValueAbstract $param */
            $param = $attributeReflection->newInstance();
            $paramName = $param->getParam();

            $paramReflection = self::getParameterReflection($methodReflection, $paramName);
            $valueFromInject = $param->getValue();

            if (is_null($valueFromInject)) {
                if (!$paramReflection->isDefaultValueAvailable() && !$paramReflection->allowsNull()) {
                    throw new InjectRequiredArgumentException($paramReflection);
                }
                //дефолтное значение само подставится конструктором/контейнером
                continue;
            }

            $args[$paramName] = self::hydrateValue(
                $paramReflection,
                $valueFromInject,
                $functionArrayParametersType[$paramName] ?? 'mixed',
            );
        }

        //InjectValue* для параметров
        foreach ($methodReflection->getParameters() as $paramReflection) {
            foreach ($paramReflection->getAttributes(InjectValueAbstract::class, ReflectionAttribute::IS_INSTANCEOF) as $attributeReflection) {
                $attribute = $attributeReflection->newInstance();
                $paramName = $paramReflection->getName();

                //патчим для InjectGet, InjectPost и т.п., чтобы ключ массива совпадал с именем параметра
                if ($attribute instanceof InjectValueArrayAbstract) {
                    $attribute->updateKeyIfNull($paramName);
                }

                $valueFromInject = $attribute->getValue();

                if (is_null($valueFromInject)) {
                    if (!$paramReflection->isDefaultValueAvailable() && !$paramReflection->allowsNull()) {
                        throw new InjectRequiredArgumentException($paramReflection);
                    }
                    continue;
                }

                $args[$paramName] = self::hydrateValue(
                    $paramReflection,
                    $valueFromInject,
                    $functionArrayParametersType[$paramName] ?? 'mixed',
                );
            }
        }

        return $args;
    }

    /**
     * Тонкая обёртка над Hydrator::hydrateValue — переводит гидраторные исключения в Lbaf-овские.
     */
    private static function hydrateValue(\ReflectionParameter $paramReflection, mixed $value, string $arrayTypeOf): mixed
    {
        try {
            return HydratorEngine::hydrateValue($paramReflection, $value, $arrayTypeOf);
        } catch (RequiredArgumentException $e) {
            throw new InjectRequiredArgumentException($e->param, $e->path);
        } catch (ArgumentTypeException $e) {
            throw new InjectArgumentTypeException($e->getMessage(), '');
        }
    }

    private static function getParameterReflection(ReflectionMethod $reflectionMethod, string $parameterName): \ReflectionParameter
    {
        try {
            return MethodParameters::getReflection($reflectionMethod, $parameterName);
        } catch (InvalidArgumentException $e) {
            //исторически Lbaf бросает тут BadRequestException — это попадает в обработчик и возвращает 400.
            //По-хорошему это LogicException разработчика, см. @todo в исходном коде ReflectionHelper.
            throw new BadRequestException(
                'Inject of ' . $parameterName . ' not found in ' . $reflectionMethod->class . '::' . $reflectionMethod->getName()
            );
        }
    }

    private static function getParameterTypeName(ReflectionMethod $reflectionMethod, string $parameterName): string
    {
        return self::getParameterReflection($reflectionMethod, $parameterName)->getType()?->getName() ?? 'mixed';
    }
}
