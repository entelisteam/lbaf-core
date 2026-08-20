<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

use Attribute;

/**
 * Атрибут для внедрения данных из POST-запроса
 *
 * Примеры:
 *
 * 1. Внедряем параметр с совпадающим названием
 *    function foo (#[InjectPost()] string $bar)
 *
 * 2. Явное указание названия аргумента
 *    function foo (#[InjectPost('bar')] string $strangeInnerNaming)
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class InjectPost extends InjectValueArrayDirectAbstract
{
    /**
     * @param string|null $key Ключ в массиве
     */
    public function __construct(?string $key = null)
    {
        parent::__construct($_POST, $key);
    }
}
