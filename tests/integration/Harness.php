<?php

namespace Tests\integration;

use EntelisTeam\Lbaf\Core\Response\AbstractResponse;
use EntelisTeam\Lbaf\Core\Router\FastRouteRouter;
use EntelisTeam\Lbaf\Core\Router\Route\RouteGenerator;
use Psr\Log\NullLogger;
use Tests\integration\App\TestApp;

class Harness
{
    private static ?array $routes = null;

    public static function http(
        string $method,
        string $path,
        array $body = [],
        array $headers = [],
        array $cookies = [],
        array $env = [],
    ): ?AbstractResponse {
        $_GET = [];
        $_POST = $body;
        $_COOKIE = $cookies;
        $_ENV = $env;

        foreach (array_keys($_SERVER) as $key) {
            if (str_starts_with($key, 'HTTP_')) {
                unset($_SERVER[$key]);
            }
        }
        foreach ($headers as $name => $value) {
            $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
        }

        if (false !== $pos = strpos($path, '?')) {
            parse_str(substr($path, $pos + 1), $_GET);
        }

        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $path;

        $app = TestApp::init();
        $app->setLogger(new NullLogger());
        $app->setRouter(new FastRouteRouter(self::routes()));
        $app->run();

        return $app->captured;
    }

    private static function routes(): array
    {
        if (self::$routes === null) {
            $cwd = getcwd();
            chdir(__DIR__ . '/App');
            try {
                self::$routes = RouteGenerator::getRoutes(null, 'Tests\\integration\\App', 'Controller');
            } finally {
                chdir($cwd);
            }
        }
        return self::$routes;
    }
}
