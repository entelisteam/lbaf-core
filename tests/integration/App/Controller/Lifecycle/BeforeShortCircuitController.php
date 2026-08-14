<?php

namespace Tests\integration\App\Controller\Lifecycle;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectGet;
use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class BeforeShortCircuitController extends AbstractApiController
{
    public function __before(#[InjectGet] ?string $authorized = null)
    {
        if ($authorized !== 'yes') {
            return ['source' => '__before', 'blocked' => true];
        }
        return null;
    }

    #[Route('GET', '/lifecycle/before-short-circuit')]
    public function action()
    {
        return ['source' => 'action'];
    }
}
