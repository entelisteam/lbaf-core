<?php

namespace EntelisTeam\Lbaf\Core\Router;

use EntelisTeam\Lbaf\Core\Container\ContainerInterface;

interface RouterInterface
{
    /**
     * Запускает контроллер согласно настройкам роутинга. Настройки задаются в конструкторе каждого роутера самостоятельно
     * @throws RouteNotFoundException
     */
    public function dispatch(ContainerInterface $container): mixed;
}
