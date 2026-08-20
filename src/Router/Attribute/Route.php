<?php

namespace EntelisTeam\Lbaf\Core\Router\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
    /**
     * @param string|array $httpMethod GET/POST/OPTIONS/... matches $_SERVER['REQUEST_METHOD']
     * @param string $route Url
     * @param bool $enableUrlImprovement false to disable url fixes (MUST be set to true to use URL rexexp)
     */
    function __construct(
        public string|array $httpMethod,
        public string       $route,
        bool                $enableUrlImprovement = true,
    )
    {
        if ($enableUrlImprovement) {
            if ($this->route[0] !== '/') {
                $this->route = '/' . $this->route;
            }
            $this->route = str_replace('\\', '/', $this->route);
        }
    }
}
