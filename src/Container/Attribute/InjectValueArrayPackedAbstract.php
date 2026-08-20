<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

use EntelisTeam\Lbaf\Core\Packer\PackerInterface;

abstract class InjectValueArrayPackedAbstract extends InjectValueArrayAbstract
{
    protected mixed $value = null;

    public function __construct(array &$arr, ?string $key, private PackerInterface $packer)
    {
        $this->arr = &$arr;
        $this->key = $key;
    }

    public function getValue(): mixed
    {

        $this->value = $this->arr[$this->key] ?? null;

        //в упакованном виде значение всегда строка, всё остальное (например массив из $_GET) — некорректный ввод
        $this->value = is_string($this->value) ? $this->packer->unpack($this->value) : null;

        return $this->value;
    }
}
