<?php
//класс простого роутера который получает данные из .htaccess
namespace EntelisTeam\Lbaf\Core\Router;

use EntelisTeam\Lbaf\Core\Container\ContainerInterface;
use EntelisTeam\Lbaf\Core\Controller\AbstractCliController;
use EntelisTeam\Lbaf\Core\ControllerProxy;
use Error;

/**
 * Роутер для маршрутизации консольных запросов
 *
 * Синтаксис php src/index.php Cli/ControllerClass method --param=value
 *   где Cli/ControllerClass соответствует $controllerFolder/Cli/ControllerClass.php
 */
class CliRouter implements RouterInterface
{
    public function __construct(
        private mixed $argv,
        private string $baseNamespace = 'App',
        private string $controllerFolder = 'Controller',
    )
    {

    }

    public function dispatch(ContainerInterface $container): mixed
    {

        $args = $this->parseArgs($this->argv);

        if (count($args) < 1) {
            throw new Error('You must specify controller name');
        }

        $controllerName = $this->baseNamespace . '\\' . $this->controllerFolder . '\\' . $args[0];
        $controllerName = str_replace('/', '\\', $controllerName);
        unset($args[0]);

        $actionName = $args[1] ?? 'index';

        if (isset($args[1])) {
            unset($args[1]);
        }

        if (isset($args['http_host'])) {
            $_SERVER['HTTP_HOST'] = $args['http_host'];
            unset($args['http_host']);
        }

        //@todo добавить проверку существования класса
        $ControllerProxy = new ControllerProxy(
            controllerClass: $controllerName,
            container: $container,
            requiredControllerClass: AbstractCliController::class,
        );

        return $ControllerProxy->$actionName(...$args);

    }

    /**
     * Парсинг аргументов командной строки
     * @param $argv
     * @return array
     * @todo вынести в helper\console
     */
    private function parseArgs($argv): array
    {
        array_shift($argv);
        $o = [];
        foreach ($argv as $a) {
            if (substr($a, 0, 2) == '--') {
                $eq = strpos($a, '=');
                if ($eq !== false) {
                    $o[substr($a, 2, $eq - 2)] = substr($a, $eq + 1);
                } else {
                    $k = substr($a, 2);
                    if (!isset($o[$k])) {
                        $o[$k] = true;
                    }
                }
            } else if (substr($a, 0, 1) == '-') {
                if (substr($a, 2, 1) == '=') {
                    $o[substr($a, 1, 1)] = substr($a, 3);
                } else {
                    foreach (str_split(substr($a, 1)) as $k) {
                        if (!isset($o[$k])) {
                            $o[$k] = true;
                        }
                    }
                }
            } else {
                $o[] = $a;
            }
        }
        return $o;
    }
}
