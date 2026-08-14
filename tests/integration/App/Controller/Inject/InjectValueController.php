<?php

namespace Tests\integration\App\Controller\Inject;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectValue;
use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class InjectValueController extends AbstractApiController
{
    #[Route('GET', '/inject-value/basic')]
    #[InjectValue('foo', 'hello')]
    public function basic(string $foo)
    {
        return ['foo' => $foo];
    }

    #[Route('GET', '/inject-value/multiple')]
    #[InjectValue('a', 1)]
    #[InjectValue('b', 'two')]
    public function multiple(int $a, string $b)
    {
        return ['a' => $a, 'b' => $b];
    }

    #[Route('GET', '/inject-value/typed-int')]
    #[InjectValue('count', 42)]
    public function typedInt(int $count)
    {
        return ['count' => $count, 'type' => gettype($count)];
    }

    #[Route('GET', '/inject-value/typed-array')]
    #[InjectValue('items', ['a', 'b', 'c'])]
    public function typedArray(array $items)
    {
        return ['items' => $items];
    }

    #[Route('GET', '/inject-value/optional-null')]
    #[InjectValue('maybe', null)]
    public function optional(?string $maybe = null)
    {
        return ['maybe' => $maybe];
    }

    #[Route('GET', '/inject-value/required-null')]
    #[InjectValue('must', null)]
    public function required(string $must)
    {
        return ['must' => $must];
    }
}
