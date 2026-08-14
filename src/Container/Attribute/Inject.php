<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class Inject
{

    /**
     * @param string $paramName Название параметра для вставки. Обязателен если TARGET_METHOD
     * @param string|null $targetClass Класс который нужно вставить туда. Если не указан класс параметра будет определен автоматически
     */
    public function __construct(public ?string $paramName = null, public ?string $targetClass = null)
    {
    }

    public function getParam(): string
    {
        return $this->paramName;
    }
}
