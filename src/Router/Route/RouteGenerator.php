<?php

namespace EntelisTeam\Lbaf\Core\Router\Route;

use EntelisTeam\Lbaf\Core\Router\Attribute\Route;
use Exception;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

class RouteGenerator
{

    /**
     * Возвращает массив маршрутов для использования в роутере
     * @param ?string $cacheFile Если передан - будет использоваться для кеширования маршрутов
     * @return RouteItem[]
     */
    public static function getRoutes(?string $cacheFile = null, string $baseNamespace = 'App', string $controllerFolder = 'Controller')
    {
        if ($cacheFile === null) {
            return self::generateRoutes($baseNamespace, $controllerFolder);
        } else {
            $routes = null;
            if (file_exists($cacheFile)) {
                try {
                    //грязный хак на случай notice в файле
                    @$routes = unserialize(file_get_contents($cacheFile));
                } catch (Throwable) {

                }
            }

            if (is_array($routes) && count($routes) > 0) {
                return $routes;
            } else {
                $routes = self::generateRoutes($baseNamespace, $controllerFolder);
                //не кешируем пустой результат, что-то пошло не так
                if (count($routes) === 0) {
                    throw new Exception('No routes found');
                }
                file_put_contents($cacheFile, serialize($routes));
                return $routes;
            }
        }
    }

    /**
     * Генерирует набор роутов на основе аттрибута контроллера
     * @param string $controllerFolder Путь до папки с контроллерами
     * @return RouteItem[]
     */
    private static function generateRoutes(string $baseNamespace, string $controllerFolder)
    {
        $result = [];
        $basePath = realpath($controllerFolder);

        //получаем содержимое папки
        $fileList = self::getDirContents($basePath);

        //формируем список классов
        $classList = [];
        foreach ($fileList as $item) {
            $classList[] = $baseNamespace . '\\' . str_replace(
                    '.php',
                    '',
                    $controllerFolder . str_replace($basePath, '', $item));
        }

        foreach ($classList as $controllerClassname) {

            $controllerClassname = str_replace('/', '\\', $controllerClassname);

            if (!class_exists($controllerClassname)) {
                continue;
            }

            $controllerReflection = new ReflectionClass($controllerClassname);
            $methods = $controllerReflection->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $methodReflections) {
                if (
                    $methodReflections->class !== $controllerClassname
                    || $methodReflections->getName() == '__construct'
                    || $methodReflections->getName() == '__before'
                    || $methodReflections->getName() == '__after'
                ) {
                    continue;
                }

                //пытаемся получать атрибуты
                $methodRoutes = $methodReflections->getAttributes(Route::class, ReflectionAttribute::IS_INSTANCEOF);
                foreach ($methodRoutes as $routeAttributeReflection) {
                    /**
                     * @var Route $routeAttribute
                     */
                    $routeAttribute = $routeAttributeReflection->newInstance();
                    $result[] = new RouteItem($routeAttribute->httpMethod, $routeAttribute->route, [$controllerReflection->getName(), $methodReflections->getName()]);
                }
            }
        }
        return $result;
    }

    static private function getDirContents($dir, &$results = [])
    {
        $files = scandir($dir);
        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                $results[] = $path;
            } else if ($value != "." && $value != "..") {
                self::getDirContents($path, $results);
            }
        }
        return $results;
    }

    /**
     * @param string $filename
     * @return RouteItem[]
     */
    public static function loadRoutesFromFile(string $filename = 'config/routesCache.php')
    {
        $data = file_get_contents($filename);
        return unserialize($data);
    }


}
