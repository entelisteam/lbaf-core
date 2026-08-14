<?php

namespace Tests\integration\App\Controller\Routing;

use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class ParameterController extends AbstractApiController
{
    #[Route('GET', '/users/{id}')]
    public function showUser(string $id)
    {
        return ['id' => $id];
    }

    #[Route('GET', '/items/{type}/{id:\d+}', enableUrlImprovement: false)]
    public function showItem(string $type, string $id)
    {
        return ['type' => $type, 'id' => $id];
    }

    #[Route('GET', '/files/{name}')]
    public function showByName(string $name)
    {
        return ['name' => $name];
    }
}
