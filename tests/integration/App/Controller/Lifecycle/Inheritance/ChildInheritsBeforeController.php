<?php

namespace Tests\integration\App\Controller\Lifecycle\Inheritance;

use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class ChildInheritsBeforeController extends AbstractTraceController
{
    #[Route('GET', '/lifecycle/inheritance/inherits-before')]
    public function action()
    {
        return null;
    }
}
