<?php
declare(strict_types=1);

namespace EntelisTeam\Lbaf\Core\Container\Exception;

use EntelisTeam\Lbaf\Exception\BadRequestException;
use ReflectionParameter;
use ReflectionProperty;

//@todo Переименовать
//@todo Показывать не только название аргумента, но и откуда его пытались заполнить, e.g $_GET['is_active']...

class InjectRequiredArgumentException extends BadRequestException
{
    function __construct(ReflectionParameter|ReflectionProperty $param, string $path = '')
    {
        $message = 'Required argument "' . $param->getName() . '" (' . $param->getType()->getName() . ') is missing or invalid';
        if ($path !== '') {
            $message .= ' at path: ' . $path;
        }
        parent::__construct($message);
    }
}