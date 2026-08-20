<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

use Attribute;

/**
 * Атрибут для внедрения данных из Cookie
 *
 * Примеры:
 *
 * 1. Внедряем параметр с совпадающим названием
 *    function validate (#[InjectCookie()] string $sessionId)
 *
 * 2. Явное указание названия аргумента
 *    function validate (#[InjectCookie('PHPSESSID')] string $session)
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class InjectCookie extends InjectValueArrayDirectAbstract
{
    /**
     * @param string|null $key Ключ в массиве
     */
    public function __construct(?string $key = null)
    {
        parent::__construct($_COOKIE, $key);
    }
}
