<?php

namespace Tests\integration\App\Controller\Lifecycle\Inheritance;

use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class ChildOverrideBeforeController extends AbstractTraceController
{
    public function __before()
    {
        $this->trace[] = 'child-before';
    }

    #[Route('GET', '/lifecycle/inheritance/override-before')]
    public function action()
    {
        return null;
    }
}
