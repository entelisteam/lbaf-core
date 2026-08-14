<?php

namespace EntelisTeam\Lbaf\Core\Controller;

use EntelisTeam\Lbaf\Core\Container\ContainerTrait;
use EntelisTeam\Lbaf\Core\Response\AbstractResponse;
use EntelisTeam\Lbaf\Exception\CustomException;
use Throwable;

abstract class AbstractController
{
    use ContainerTrait;

    abstract static public function _createResponse(mixed $data): AbstractResponse;

    abstract static public function _createErrorResponse(CustomException|Throwable $e): AbstractResponse;

}