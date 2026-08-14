<?php

namespace Tests\integration\App\Controller\Routing;

use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class RegexController extends AbstractApiController
{
    #[Route('GET', '/regex/letters/{value:[a-z]+}', enableUrlImprovement: false)]
    public function letters(string $value)
    {
        return ['letters' => $value];
    }

    #[Route('GET', '/regex/slug/{value:[a-zA-Z0-9_-]+}', enableUrlImprovement: false)]
    public function slug(string $value)
    {
        return ['slug' => $value];
    }

    #[Route('GET', '/regex/date/{value:\d{4}-\d{2}-\d{2}}', enableUrlImprovement: false)]
    public function date(string $value)
    {
        return ['date' => $value];
    }

    #[Route('GET', '/regex/optional[/{page:\d+}]', enableUrlImprovement: false)]
    public function optional(?string $page = null)
    {
        return ['page' => $page];
    }
}
