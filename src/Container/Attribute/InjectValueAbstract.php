<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

abstract class InjectValueAbstract
{
    protected ?string $param;

    /**
     * Название параметра для замены
     * @return string
     */
    function getParam(): ?string
    {
        return $this->param;
    }

    /**
     * Значение параметра
     * @return mixed
     */
    abstract function getValue(): mixed;
}
