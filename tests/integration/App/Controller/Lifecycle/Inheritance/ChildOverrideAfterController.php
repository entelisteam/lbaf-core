<?php

namespace Tests\integration\App\Controller\Lifecycle\Inheritance;

use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class ChildOverrideAfterController extends AbstractTraceController
{
    public function __before()
    {
        // suppress parent's __before to isolate __after behavior
    }

    public function __after()
    {
        $this->trace[] = 'child-after';
        return ['trace' => $this->trace];
    }

    #[Route('GET', '/lifecycle/inheritance/override-after')]
    public function action()
    {
        return null;
    }
}
