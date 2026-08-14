<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

use Attribute;
use LogicException;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class InjectValue extends InjectValueAbstract
{
    /**
     * @param string $param Название параметра для вставки
     * @param mixed $value Значение
     */
    function __construct(protected ?string $param, protected mixed $value)
    {
        if ($this->param === null) {
            throw new LogicException('Parameter name must be set');
        }
    }

    function getValue(): mixed
    {
        return $this->value;
    }
}
