<?php

namespace Tests\integration\App\Controller\Lifecycle\Inheritance;

use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class ChildCallParentAfterController extends AbstractTraceController
{
    public function __before()
    {
        // suppress parent's __before to isolate __after behavior
    }

    public function __after()
    {
        parent::__after();
        $this->trace[] = 'child-after';
        return ['trace' => $this->trace];
    }

    #[Route('GET', '/lifecycle/inheritance/call-parent-after')]
    public function action()
    {
        return null;
    }
}
