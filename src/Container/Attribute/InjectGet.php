<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

use Attribute;

/**
 * Атрибут для внедрения данных из GET-запроса
 *
 * Примеры:
 *
 * 1. Внедряем параметр с совпадающим названием
 *    function foo (#[InjectGet()] string $bar)
 *
 * 2. Явное указание названия аргумента
 *    function foo (#[InjectGet('bar')] string $strangeInnerNaming)
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class InjectGet extends InjectValueArrayDirectAbstract
{
    /**
     * @param string|null $key Ключ в массиве
     */
    public function __construct(?string $key = null)
    {
        parent::__construct($_GET, $key);
    }
}
