<?php
declare(strict_types=1);

namespace EntelisTeam\Lbaf\Core\Container\Exception;

use EntelisTeam\Lbaf\Exception\CustomException;
use EntelisTeam\Lbaf\Exception\LogLevelEnum;

class ContainerException extends CustomException
{
    function __construct(string $message)
    {
        parent::__construct(
            message: $message,
            httpCode: 500,
            logLevel: LogLevelEnum::Alert,
        );
    }
}
