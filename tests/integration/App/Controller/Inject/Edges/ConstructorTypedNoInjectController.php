<?php

namespace Tests\integration\App\Controller\Inject\Edges;

use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;
use Tests\integration\App\Dto\Service\SimpleService;

class ConstructorTypedNoInjectController extends AbstractApiController
{
    public function __construct(
        public SimpleService $service,
    ) {}

    #[Route('GET', '/inject-edges/ctor-typed-no-inject')]
    public function action()
    {
        return ['name' => $this->service->name];
    }
}
