<?php

namespace Tests\integration\App\Controller\Inject\Edges;

use EntelisTeam\Lbaf\Core\Container\Attribute\Inject;
use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;
use Tests\integration\App\Dto\Service\SimpleService;

class ConstructorInjectController extends AbstractApiController
{
    public function __construct(
        #[Inject] public SimpleService $service,
    ) {}

    #[Route('GET', '/inject-edges/ctor-inject')]
    public function action()
    {
        return ['name' => $this->service->name];
    }
}
