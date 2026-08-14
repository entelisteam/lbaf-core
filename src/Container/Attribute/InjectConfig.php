<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

use Attribute;
use EntelisTeam\Lbaf\Core\Config\Config;
use LogicException;

/**
 * @todo Работа с конфигом нуждается в переосмыслении
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class InjectConfig extends InjectValue
{
    /**
     * @param string $param Название параметра для вставки или название ключа в массиве если это TARGET_PARAMETER
     * @param string|null $key Ключ в массиве
     */
    public function __construct(protected ?string $param = null, ?string $key = null)
    {
        if ($this->param === null) {
            throw new LogicException('Parameter name must be set');
        }
        $key = $key ?: $this->param;
        parent::__construct($param, Config::get()->$key);
    }
}
