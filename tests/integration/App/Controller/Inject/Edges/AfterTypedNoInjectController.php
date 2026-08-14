<?php

namespace Tests\integration\App\Controller\Inject\Edges;

use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;
use Tests\integration\App\Dto\Service\SimpleService;

class AfterTypedNoInjectController extends AbstractApiController
{
    public function __after(SimpleService $service)
    {
        return ['name' => $service->name];
    }

    #[Route('GET', '/inject-edges/after-typed-no-inject')]
    public function action()
    {
        return null;
    }
}
