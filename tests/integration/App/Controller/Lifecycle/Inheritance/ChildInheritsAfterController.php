<?php

namespace Tests\integration\App\Controller\Lifecycle\Inheritance;

use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class ChildInheritsAfterController extends AbstractTraceController
{
    public function __before()
    {
        // suppress parent's __before to isolate __after behavior
    }

    #[Route('GET', '/lifecycle/inheritance/inherits-after')]
    public function action()
    {
        return null;
    }
}
