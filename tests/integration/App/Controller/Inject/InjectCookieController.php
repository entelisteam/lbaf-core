<?php

namespace Tests\integration\App\Controller\Inject;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectCookie;
use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class InjectCookieController extends AbstractApiController
{
    #[Route('GET', '/inject-cookie/v1')]
    #[InjectCookie('sessionId')]
    public function v1(string $sessionId)
    {
        return ['value' => $sessionId];
    }

    #[Route('GET', '/inject-cookie/v2')]
    public function v2(#[InjectCookie] string $sessionId)
    {
        return ['value' => $sessionId];
    }

    #[Route('GET', '/inject-cookie/v3')]
    #[InjectCookie('session', 'PHPSESSID')]
    public function v3(string $session)
    {
        return ['value' => $session];
    }

    #[Route('GET', '/inject-cookie/v4')]
    public function v4(#[InjectCookie('PHPSESSID')] string $session)
    {
        return ['value' => $session];
    }

    #[Route('GET', '/inject-cookie/typed-int')]
    public function typedInt(#[InjectCookie] int $count)
    {
        return ['count' => $count, 'type' => gettype($count)];
    }

    #[Route('GET', '/inject-cookie/optional')]
    public function optional(#[InjectCookie] ?string $maybe = null)
    {
        return ['maybe' => $maybe];
    }

    #[Route('GET', '/inject-cookie/required')]
    public function required(#[InjectCookie] string $must)
    {
        return ['must' => $must];
    }
}
