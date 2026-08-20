<?php

namespace Tests\integration\App\Controller\Inject;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectHeader;
use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class InjectHeaderController extends AbstractApiController
{
    #[Route('GET', '/inject-header/v2')]
    public function v2(#[InjectHeader] string $authorization)
    {
        return ['value' => $authorization];
    }

    #[Route('GET', '/inject-header/v4')]
    public function v4(#[InjectHeader('authorization')] string $auth)
    {
        return ['value' => $auth];
    }

    #[Route('GET', '/inject-header/typed-int')]
    public function typedInt(#[InjectHeader('xcount')] int $count)
    {
        return ['count' => $count, 'type' => gettype($count)];
    }

    #[Route('GET', '/inject-header/optional')]
    public function optional(#[InjectHeader('xmaybe')] ?string $maybe = null)
    {
        return ['maybe' => $maybe];
    }

    #[Route('GET', '/inject-header/required')]
    public function required(#[InjectHeader('xmust')] string $must)
    {
        return ['must' => $must];
    }

    #[Route('GET', '/inject-header/dashed-key')]
    public function dashedKey(#[InjectHeader('x-custom')] ?string $value = null)
    {
        return ['value' => $value];
    }
}
