<?php

namespace Tests\integration\App\Controller\Lifecycle;

use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;
use EntelisTeam\Lbaf\Core\Packer\Json;
use EntelisTeam\Lbaf\Core\Response\ApiResponse;
use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class BeforeResponseObjectController extends AbstractApiController
{
    public function __before()
    {
        return (new ApiResponse(new Json(), ['source' => '__before-response-object']))
            ->setHttpResponseCode(418);
    }

    #[Route('GET', '/lifecycle/before-response-object')]
    public function action()
    {
        return ['source' => 'action'];
    }
}
