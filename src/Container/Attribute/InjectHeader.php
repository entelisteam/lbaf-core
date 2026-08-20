<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

use Attribute;

/**
 * Атрибут для внедрения данных из HTTP-заголовков
 *
 * Примеры:
 *
 * 1. Внедряем параметр с совпадающим названием
 *    function foo (#[InjectHeader()] string $authorization)
 *
 * 2. Явное указание названия аргумента
 *    function foo (#[InjectHeader('authorization')] string $auth)
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class InjectHeader extends InjectValueArrayDirectAbstract
{
    /**
     * @param string|null $key Ключ в массиве
     */
    public function __construct(?string $key = null)
    {
        //имена заголовков регистронезависимы, приводим к нижнему регистру с обеих сторон
        $headers = array_change_key_case(getallheaders());
        parent::__construct($headers, is_null($key) ? null : strtolower($key));
    }
}
