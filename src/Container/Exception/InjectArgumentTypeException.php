<?php
declare(strict_types=1);

namespace EntelisTeam\Lbaf\Core\Container\Exception;

use EntelisTeam\Lbaf\Exception\BadRequestException;

class InjectArgumentTypeException extends BadRequestException
{
    function __construct(string $message, string $path = '')
    {
        if ($path !== '') {
            $message .= ' at path: ' . $path;
        }
        parent::__construct($message);
    }
}
