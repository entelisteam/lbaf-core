<?php

namespace Tests\integration\App\Dto\Service;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectGet;

class GetAwareService
{
    public function __construct(
        #[InjectGet] public ?string $foo = null,
    ) {}
}
