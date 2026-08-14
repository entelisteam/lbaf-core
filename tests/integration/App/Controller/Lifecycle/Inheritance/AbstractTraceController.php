<?php

namespace Tests\integration\App\Controller\Lifecycle\Inheritance;

use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;

abstract class AbstractTraceController extends AbstractApiController
{
    public array $trace = [];

    public function __before()
    {
        $this->trace[] = 'parent-before';
    }

    public function __after()
    {
        $this->trace[] = 'parent-after';
        return ['trace' => $this->trace];
    }
}
