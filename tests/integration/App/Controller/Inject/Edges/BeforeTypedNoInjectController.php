<?php

namespace Tests\integration\App\Controller\Inject\Edges;

use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;
use Tests\integration\App\Dto\Service\SimpleService;

class BeforeTypedNoInjectController extends AbstractApiController
{
    public function __before(SimpleService $service)
    {
        // unreachable: framework will reject at parameter resolution
    }

    #[Route('GET', '/inject-edges/before-typed-no-inject')]
    public function action()
    {
        return ['source' => 'action'];
    }
}
