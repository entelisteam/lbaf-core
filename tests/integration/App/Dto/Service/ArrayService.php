<?php

namespace Tests\integration\App\Dto\Service;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectGet;
use EntelisTeam\Lbaf\Hydrator\Attribute\ArrayTypeOf;
use Tests\integration\App\Dto\ItemDto;

class ArrayService
{
    public function __construct(
        #[InjectGet] #[ArrayTypeOf(ItemDto::class)] public array $items = [],
    ) {}
}
