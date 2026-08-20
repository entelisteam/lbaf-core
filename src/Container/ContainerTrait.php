<?php

namespace EntelisTeam\Lbaf\Core\Container;

trait ContainerTrait
{
    protected ContainerInterface $container;

    public final function &getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public final function setContainer(ContainerInterface &$container)
    {
        $this->container = &$container;
    }
}
