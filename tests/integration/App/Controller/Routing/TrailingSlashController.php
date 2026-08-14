<?php

namespace Tests\integration\App\Controller\Routing;

use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class TrailingSlashController extends AbstractApiController
{
    #[Route('GET', '/with-slash/')]
    public function withSlash()
    {
        return ['matched' => 'with-slash'];
    }

    #[Route('GET', '/no-slash')]
    public function noSlash()
    {
        return ['matched' => 'no-slash'];
    }
}
