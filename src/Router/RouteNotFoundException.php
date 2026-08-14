<?php

namespace EntelisTeam\Lbaf\Core\Router;


use EntelisTeam\Lbaf\Exception\CustomException;
use EntelisTeam\Lbaf\Exception\LogLevelEnum;

class RouteNotFoundException extends CustomException
{
    function __construct(string $method, string $url)
    {
        parent::__construct(
            message: 'Route ' . $method . ' ' . $url . ' not found',
            httpCode: 404,
            logLevel: LogLevelEnum::Notice,
            isError: false,
        );
    }

}