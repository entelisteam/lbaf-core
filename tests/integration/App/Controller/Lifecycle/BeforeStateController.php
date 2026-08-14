<?php

namespace Tests\integration\App\Controller\Lifecycle;

use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class BeforeStateController extends AbstractApiController
{
    public ?string $sharedState = null;

    public function __before()
    {
        $this->sharedState = 'set-by-before';
    }

    #[Route('GET', '/lifecycle/before-state')]
    public function readState()
    {
        return ['state' => $this->sharedState];
    }
}
