<?php

namespace Tests\integration\App\Controller\Inject\Edges;

use EntelisTeam\Lbaf\Core\Container\Attribute\Inject;
use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;
use Tests\integration\App\Dto\Service\SimpleService;

class AfterInjectController extends AbstractApiController
{
    public function __after(#[Inject] SimpleService $service)
    {
        return ['name' => $service->name];
    }

    #[Route('GET', '/inject-edges/after-inject')]
    public function action()
    {
        return null;
    }
}
