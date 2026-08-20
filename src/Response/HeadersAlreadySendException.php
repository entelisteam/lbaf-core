<?php

namespace EntelisTeam\Lbaf\Core\Response;

use EntelisTeam\Lbaf\Exception\CustomException;
use EntelisTeam\Lbaf\Exception\LogLevelEnum;

class HeadersAlreadySendException extends CustomException
{
    function __construct(string $message = 'Headers already sent')
    {
        parent::__construct(
            message: $message,
            logLevel: LogLevelEnum::Error,
        );
    }

}
