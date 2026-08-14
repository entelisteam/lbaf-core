<?php

namespace Tests\integration\App\Controller\Routing;

use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class MultiRouteController extends AbstractApiController
{
    #[Route('GET', '/multi-route-a')]
    #[Route('GET', '/multi-route-b')]
    public function index()
    {
        return ['handler' => 'multi-route'];
    }
}
