<?php

namespace Tests\integration\App\Controller\Inject\Edges;

use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;
use Tests\integration\App\Dto\Service\SimpleService;

class ActionTypedNoInjectController extends AbstractApiController
{
    #[Route('GET', '/inject-edges/action-typed-no-inject')]
    public function action(SimpleService $service)
    {
        return ['name' => $service->name];
    }
}
