<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectValueAbstract;

abstract class InjectValueArrayAbstract extends InjectValueAbstract
{
    protected array $arr;
    protected ?string $key;

    public function updateKeyIfNull(string $key): self
    {
        if (is_null($this->key)) {
            $this->key = $key;
        }
        return $this;
    }

}
