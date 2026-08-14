<?php

namespace EntelisTeam\Lbaf\Core\Container;

use EntelisTeam\Lbaf\Core\Application\AbstractApplication;

/**
 * Inspired by Psr\Container\ContainerInterface
 */
interface ContainerInterface
{
    /**
     * @template T
     * @param T $id
     * @return T
     */
    public function &get(string $id): mixed;
    public function addDefinitions(array $definitions): self;

    public function setApplicationClass(string $applicationClass): self;

    public function getApplicationClass(): string;

    public function getApplication(): AbstractApplication;
}