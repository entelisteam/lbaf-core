<?php

namespace Tests\integration\App\Controller\Routing;

use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class ComboController extends AbstractApiController
{
    #[Route('GET', '/combo-a')]
    #[Route(['POST', 'PUT'], '/combo-b')]
    public function combo()
    {
        return ['handler' => 'combo'];
    }
}
