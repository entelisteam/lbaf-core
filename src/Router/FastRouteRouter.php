<?php

namespace EntelisTeam\Lbaf\Core\Router;

use EntelisTeam\Lbaf\Core\Container\ContainerInterface;
use EntelisTeam\Lbaf\Core\ControllerProxy;
use EntelisTeam\Lbaf\Core\Router\Route\RouteItem;
use Error;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

/**
 * Роутер-обертка для nikic/FastRoute
 * @url https://github.com/nikic/FastRoute
 * @url https://coderoad.ru/38686776/%D0%9A%D0%B0%D0%BA-%D1%8F-%D0%BC%D0%BE%D0%B3%D1%83-%D0%B8%D1%81%D0%BF%D0%BE%D0%BB%D1%8C%D0%B7%D0%BE%D0%B2%D0%B0%D1%82%D1%8C-FastRoute
 * @url https://github.com/tochix/shapes-api/blob/master/src/Config/routes.php
 * @todo настройки кеширования роутера
 */
class FastRouteRouter implements RouterInterface
{

    private readonly Dispatcher $dispatcher;

    /**
     * @param RouteItem[] $routes
     */
    function __construct(array $routes = [])
    {
        if (count($routes)) {
            $this->setRoutes($routes);
        }
    }

    /**
     * @param RouteItem[] $routes
     */
    function setRoutes(array $routes): self
    {
        $this->dispatcher = simpleDispatcher(function (RouteCollector $r) use (&$routes) {
            foreach ($routes as $item) {
                $r->addRoute($item->httpMethod, $item->route, $item->handler);
            }
        });
        return $this;
    }

    public function dispatch(ContainerInterface $container): mixed
    {

        //from https://github.com/nikic/FastRoute

        // Fetch method and URI from somewhere
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                //@todo

                throw new RouteNotFoundException($httpMethod, $uri);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                //@todo

                throw new RouteNotFoundException($httpMethod, $uri);
                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                // ... call $handler with $vars

                $controller = new ControllerProxy(
                    controllerClass: $handler[0],
                    container: $container,
                    requiredControllerClass: null,
                );
                $action = $handler[1] ?? 'index';
                return $controller->$action(...$vars);
            default:
                throw new Error('unexpected return from router');
        }
    }
}
