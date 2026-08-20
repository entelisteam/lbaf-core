<?php

namespace EntelisTeam\Lbaf\Core\Router\Route;
//вдохновлялся https://github.com/tochix/shapes-api/blob/master/src/Config/routes.php
class RouteItem
{
    /**
     * @param string|string[] $httpMethod
     * @param string $route
     * @param string[] $handler
     */
    public function __construct(
        public string|array $httpMethod,
        public string       $route,
        public array        $handler)
    {
    }

}
