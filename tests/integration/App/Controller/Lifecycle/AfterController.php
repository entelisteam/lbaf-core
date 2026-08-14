<?php

namespace Tests\integration\App\Controller\Lifecycle;

use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;
use RuntimeException;

class AfterController extends AbstractApiController
{
    public function __after()
    {
        return ['source' => '__after'];
    }

    #[Route('GET', '/lifecycle/after-when-action-returns-null')]
    public function returnsNull()
    {
        return null;
    }

    #[Route('GET', '/lifecycle/after-when-action-returns-value')]
    public function returnsValue()
    {
        return ['source' => 'action'];
    }

    #[Route('GET', '/lifecycle/after-when-action-throws')]
    public function throws()
    {
        throw new RuntimeException('boom');
    }
}
