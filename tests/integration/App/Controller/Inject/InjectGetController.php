<?php

namespace Tests\integration\App\Controller\Inject;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectGet;
use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;
use EntelisTeam\Lbaf\Hydrator\Attribute\ArrayTypeOf;
use Tests\integration\App\Dto\ItemDto;

class InjectGetController extends AbstractApiController
{
    #[Route('GET', '/inject-get/v2')]
    public function v2(#[InjectGet] string $foo)
    {
        return ['foo' => $foo];
    }

    #[Route('GET', '/inject-get/v4')]
    public function v4(#[InjectGet('customKey')] string $foo)
    {
        return ['foo' => $foo];
    }

    #[Route('GET', '/inject-get/typed-int')]
    public function typedInt(#[InjectGet] int $count)
    {
        return ['count' => $count, 'type' => gettype($count)];
    }

    #[Route('GET', '/inject-get/typed-array')]
    public function typedArray(#[InjectGet] array $items)
    {
        return ['items' => $items];
    }

    #[Route('GET', '/inject-get/optional')]
    public function optional(#[InjectGet] ?string $maybe = null)
    {
        return ['maybe' => $maybe];
    }

    #[Route('GET', '/inject-get/required')]
    public function required(#[InjectGet] string $must)
    {
        return ['must' => $must];
    }

    #[Route('GET', '/inject-get/array-of-int')]
    public function arrayOfInt(#[InjectGet] #[ArrayTypeOf('int')] array $numbers)
    {
        return [
            'numbers' => $numbers,
            'first_type' => isset($numbers[0]) ? gettype($numbers[0]) : null,
        ];
    }

    #[Route('GET', '/inject-get/array-of-dto')]
    public function arrayOfDto(#[InjectGet] #[ArrayTypeOf(ItemDto::class)] array $items)
    {
        return [
            'items' => array_map(
                fn($i) => [
                    'name' => $i->name,
                    'value' => $i->value,
                    'value_type' => gettype($i->value),
                ],
                $items
            ),
        ];
    }
}
