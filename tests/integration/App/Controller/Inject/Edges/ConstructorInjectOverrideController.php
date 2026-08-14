<?php

namespace Tests\integration\App\Controller\Inject\Edges;

use EntelisTeam\Lbaf\Core\Container\Attribute\Inject;
use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;
use Tests\integration\App\Dto\Service\BarService;
use Tests\integration\App\Dto\Service\NamedInterface;

class ConstructorInjectOverrideController extends AbstractApiController
{
    public function __construct(
        #[Inject(BarService::class)] public NamedInterface $service,
    ) {}

    #[Route('GET', '/inject-edges/ctor-inject-override')]
    public function action()
    {
        return ['name' => $this->service->getName()];
    }
}
