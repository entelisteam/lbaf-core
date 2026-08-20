<?php

namespace Tests\integration\App\Controller\Inject;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectPostPacked;
use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Packer\Json;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;
use EntelisTeam\Lbaf\Hydrator\Attribute\ArrayTypeOf;
use Tests\integration\App\Dto\ItemDto;
use Tests\integration\App\Packer\Base64Json;

class InjectPostPackedController extends AbstractApiController
{
    #[Route('POST', '/inject-post-packed/v1')]
    public function v1(#[InjectPostPacked] ItemDto $item)
    {
        return ['name' => $item->name, 'value' => $item->value, 'value_type' => gettype($item->value)];
    }

    #[Route('POST', '/inject-post-packed/v2')]
    public function v2(#[InjectPostPacked('input')] ItemDto $myObject)
    {
        return ['name' => $myObject->name];
    }

    #[Route('POST', '/inject-post-packed/explicit-packer')]
    public function explicitPacker(#[InjectPostPacked(packer: new Json())] ItemDto $item)
    {
        return ['name' => $item->name];
    }

    #[Route('POST', '/inject-post-packed/custom-packer')]
    public function customPacker(#[InjectPostPacked(packer: new Base64Json())] ItemDto $item)
    {
        return ['name' => $item->name];
    }

    #[Route('POST', '/inject-post-packed/optional')]
    public function optional(#[InjectPostPacked] ?ItemDto $maybe = null)
    {
        return ['name' => $maybe?->name];
    }

    #[Route('POST', '/inject-post-packed/array-of-dto')]
    public function arrayOfDto(#[InjectPostPacked] #[ArrayTypeOf(ItemDto::class)] array $items)
    {
        return ['names' => array_map(fn($i) => $i->name, $items)];
    }
}
