<?php

namespace Tests\integration\App\Controller\Inject;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectPost;
use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;
use EntelisTeam\Lbaf\Hydrator\Attribute\ArrayTypeOf;
use Tests\integration\App\Dto\ItemDto;

class InjectPostController extends AbstractApiController
{
    #[Route('POST', '/inject-post/v1')]
    #[InjectPost('foo')]
    public function v1(string $foo)
    {
        return ['foo' => $foo];
    }

    #[Route('POST', '/inject-post/v2')]
    public function v2(#[InjectPost] string $foo)
    {
        return ['foo' => $foo];
    }

    #[Route('POST', '/inject-post/v3')]
    #[InjectPost('foo', 'customKey')]
    public function v3(string $foo)
    {
        return ['foo' => $foo];
    }

    #[Route('POST', '/inject-post/v4')]
    public function v4(#[InjectPost('customKey')] string $foo)
    {
        return ['foo' => $foo];
    }

    #[Route('POST', '/inject-post/typed-int')]
    public function typedInt(#[InjectPost] int $count)
    {
        return ['count' => $count, 'type' => gettype($count)];
    }

    #[Route('POST', '/inject-post/typed-array')]
    public function typedArray(#[InjectPost] array $items)
    {
        return ['items' => $items];
    }

    #[Route('POST', '/inject-post/optional')]
    public function optional(#[InjectPost] ?string $maybe = null)
    {
        return ['maybe' => $maybe];
    }

    #[Route('POST', '/inject-post/required')]
    public function required(#[InjectPost] string $must)
    {
        return ['must' => $must];
    }

    #[Route('POST', '/inject-post/array-of-int')]
    public function arrayOfInt(#[InjectPost] #[ArrayTypeOf('int')] array $numbers)
    {
        return [
            'numbers' => $numbers,
            'first_type' => isset($numbers[0]) ? gettype($numbers[0]) : null,
        ];
    }

    #[Route('POST', '/inject-post/array-of-dto')]
    public function arrayOfDto(#[InjectPost] #[ArrayTypeOf(ItemDto::class)] array $items)
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
