<?php

namespace Tests\integration\App\Controller\Routing;

use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class MultiMethodController extends AbstractApiController
{
    #[Route(['GET', 'POST'], '/multi-method')]
    public function index()
    {
        return ['handler' => 'multi-method'];
    }
}
