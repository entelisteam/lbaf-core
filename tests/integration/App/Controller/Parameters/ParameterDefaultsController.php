<?php

namespace Tests\integration\App\Controller\Parameters;

use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class ParameterDefaultsController extends AbstractApiController
{
    #[Route('GET', '/parameters/optional-default')]
    public function optionalDefault(string $foo = 'default-value')
    {
        return ['foo' => $foo];
    }

    #[Route('GET', '/parameters/nullable')]
    public function nullable(?string $foo)
    {
        return ['foo' => $foo];
    }

    #[Route('GET', '/parameters/required')]
    public function required(string $foo)
    {
        return ['foo' => $foo];
    }
}
