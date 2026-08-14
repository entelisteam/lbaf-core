<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

use EntelisTeam\Lbaf\Core\Packer\PackerInterface;
use EntelisTeam\Lbaf\Core\Packer\PackerType;

abstract class InjectValueArrayAbstract extends InjectValueAbstract
{
    protected mixed $value = null;

    public function __construct(private array &$arr, private ?string $key, private ?PackerType $packerType = null)
    {

    }

    public function updateKeyIfNull(string $key): self
    {
        if (is_null($this->key)) {
            $this->key = $key;
        }
        return $this;
    }

    public function getValue(): mixed
    {

        $this->value = $this->arr[$this->key] ?? null;

        if (!is_null($this->packerType)) {
            /**
             * @var PackerInterface $packer
             */
            $packer = new $this->packerType->value;
            if (!is_null($this->value)) {
                $this->value = $packer->unpack($this->value);
            }
        }

        return $this->value;
    }
}
