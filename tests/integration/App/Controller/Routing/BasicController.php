<?php

namespace Tests\integration\App\Controller\Routing;

use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class BasicController extends AbstractApiController
{
    #[Route('GET', '/')]
    public function index()
    {
        return ['foo' => 'bar'];
    }
}
