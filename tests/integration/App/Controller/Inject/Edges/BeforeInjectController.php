<?php

namespace Tests\integration\App\Controller\Inject\Edges;

use EntelisTeam\Lbaf\Core\Container\Attribute\Inject;
use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;
use Tests\integration\App\Dto\Service\SimpleService;

class BeforeInjectController extends AbstractApiController
{
    public ?string $injectedName = null;

    public function __before(#[Inject] SimpleService $service)
    {
        $this->injectedName = $service->name;
    }

    #[Route('GET', '/inject-edges/before-inject')]
    public function action()
    {
        return ['name' => $this->injectedName];
    }
}
