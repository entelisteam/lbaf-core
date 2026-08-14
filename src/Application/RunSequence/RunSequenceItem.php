<?php

namespace EntelisTeam\Lbaf\Core\Application\RunSequence;

use EntelisTeam\Lbaf\Core\Controller\AbstractController;

class RunSequenceItem
{

    /**
     * @param AbstractController|string $controller
     * @param string $action
     * @param array $arguments
     */
    public function __construct(public AbstractController|string &$controller, public string $action, public array $arguments = [], public bool $isStatic = false)
    {
    }

}