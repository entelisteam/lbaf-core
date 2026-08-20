<?php
declare(strict_types=1);

namespace EntelisTeam\Lbaf\Core\Container\Exception;

use EntelisTeam\Lbaf\Exception\BadRequestException;
use ReflectionParameter;
use ReflectionProperty;

//@todo Переименовать
//@todo Показывать не только название аргумента, но и откуда его пытались заполнить, e.g $_GET['is_active']...

class InjectArrayTypeUnspecifiedException extends BadRequestException
{
    function __construct(ReflectionParameter|ReflectionProperty $param)
    {
        parent::__construct('Array "' . $param->getName() . '" type MUST be specified in source code.');
    }
}
