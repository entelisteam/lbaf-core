<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

abstract class InjectValueAbstract
{
    /**
     * Название параметра для замены.
     * @todo проверить нужно ли вообще, мы хотим сделать почти все Inject target parameter
     */
    protected ?string $param = null;


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
