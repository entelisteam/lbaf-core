<?php

namespace Tests\integration\App\Controller\Inject;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectEnv;
use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class InjectEnvController extends AbstractApiController
{
    #[Route('GET', '/inject-env/v2')]
    public function v2(#[InjectEnv] string $dbHost)
    {
        return ['value' => $dbHost];
    }

    #[Route('GET', '/inject-env/v4')]
    public function v4(#[InjectEnv('DB_HOST')] string $host)
    {
        return ['value' => $host];
    }

    #[Route('GET', '/inject-env/typed-int')]
    public function typedInt(#[InjectEnv] int $count)
    {
        return ['count' => $count, 'type' => gettype($count)];
    }

    #[Route('GET', '/inject-env/optional')]
    public function optional(#[InjectEnv] ?string $maybe = null)
    {
        return ['maybe' => $maybe];
    }

    #[Route('GET', '/inject-env/required')]
    public function required(#[InjectEnv] string $must)
    {
        return ['must' => $must];
    }
}
