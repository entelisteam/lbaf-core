<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

abstract class InjectValueArrayDirectAbstract extends InjectValueArrayAbstract
{
    protected mixed $value = null;

    public function __construct(array &$arr, ?string $key)
    {
        $this->arr = &$arr;
        $this->key = $key;
    }

    public function getValue(): mixed
    {

        $this->value = $this->arr[$this->key] ?? null;
        return $this->value;
    }
}
