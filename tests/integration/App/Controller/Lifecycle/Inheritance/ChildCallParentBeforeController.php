<?php

namespace Tests\integration\App\Controller\Lifecycle\Inheritance;

use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class ChildCallParentBeforeController extends AbstractTraceController
{
    public function __before()
    {
        parent::__before();
        $this->trace[] = 'child-before';
    }

    #[Route('GET', '/lifecycle/inheritance/call-parent-before')]
    public function action()
    {
        return null;
    }
}
