<?php

namespace Tests\integration\App\Controller\Inject;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectGetPacked;
use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Packer\Json;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;
use EntelisTeam\Lbaf\Hydrator\Attribute\ArrayTypeOf;
use Tests\integration\App\Dto\ItemDto;
use Tests\integration\App\Packer\Base64Json;

class InjectGetPackedController extends AbstractApiController
{
    #[Route('GET', '/inject-get-packed/v1')]
    public function v1(#[InjectGetPacked] ItemDto $item)
    {
        return ['name' => $item->name, 'value' => $item->value, 'value_type' => gettype($item->value)];
    }

    #[Route('GET', '/inject-get-packed/v2')]
    public function v2(#[InjectGetPacked('input')] ItemDto $myObject)
    {
        return ['name' => $myObject->name];
    }

    #[Route('GET', '/inject-get-packed/explicit-packer')]
    public function explicitPacker(#[InjectGetPacked(packer: new Json())] ItemDto $item)
    {
        return ['name' => $item->name];
    }

    #[Route('GET', '/inject-get-packed/custom-packer')]
    public function customPacker(#[InjectGetPacked(packer: new Base64Json())] ItemDto $item)
    {
        return ['name' => $item->name];
    }

    #[Route('GET', '/inject-get-packed/optional')]
    public function optional(#[InjectGetPacked] ?ItemDto $maybe = null)
    {
        return ['name' => $maybe?->name];
    }

    #[Route('GET', '/inject-get-packed/array-of-dto')]
    public function arrayOfDto(#[InjectGetPacked] #[ArrayTypeOf(ItemDto::class)] array $items)
    {
        return ['names' => array_map(fn($i) => $i->name, $items)];
    }
}
