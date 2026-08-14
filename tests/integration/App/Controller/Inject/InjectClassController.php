<?php

namespace Tests\integration\App\Controller\Inject;

use EntelisTeam\Lbaf\Core\Container\Attribute\Inject;
use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;
use Tests\integration\App\Dto\Service\ArrayService;
use Tests\integration\App\Dto\Service\BarService;
use Tests\integration\App\Dto\Service\CompositeService;
use Tests\integration\App\Dto\Service\GetAwareService;
use Tests\integration\App\Dto\Service\NamedInterface;
use Tests\integration\App\Dto\Service\SimpleService;

class InjectClassController extends AbstractApiController
{
    #[Route('GET', '/inject-class/v1')]
    #[Inject('service')]
    public function v1(SimpleService $service)
    {
        return ['name' => $service->name];
    }

    #[Route('GET', '/inject-class/v2')]
    #[Inject('service', BarService::class)]
    public function v2(NamedInterface $service)
    {
        return ['name' => $service->getName()];
    }

    #[Route('GET', '/inject-class/v3')]
    public function v3(#[Inject] SimpleService $service)
    {
        return ['name' => $service->name];
    }

    #[Route('GET', '/inject-class/v4')]
    public function v4(#[Inject(BarService::class)] NamedInterface $service)
    {
        return ['name' => $service->getName()];
    }

    #[Route('GET', '/inject-class/nested-get')]
    public function nestedGet(#[Inject] GetAwareService $service)
    {
        return ['foo' => $service->foo];
    }

    #[Route('GET', '/inject-class/nested-array')]
    public function nestedArray(#[Inject] ArrayService $service)
    {
        return [
            'items' => array_map(
                fn($i) => ['name' => $i->name, 'value' => $i->value, 'value_type' => gettype($i->value)],
                $service->items
            ),
        ];
    }

    #[Route('GET', '/inject-class/composite')]
    public function composite(#[Inject] CompositeService $service)
    {
        return ['nested_name' => $service->simple->name];
    }
}
