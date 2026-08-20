<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

use Attribute;

/**
 * Атрибут для внедрения переменных окружения
 *
 * Примеры:
 *
 * 1. Внедряем параметр с совпадающим названием
 *    function connect (#[InjectEnv()] string $dbHost)
 *
 * 2. Явное указание названия аргумента
 *    function connect (#[InjectEnv('DB_HOST')] string $host)
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class InjectEnv extends InjectValueArrayDirectAbstract
{
    /**
     * @param string|null $key Ключ в массиве
     */
    public function __construct(?string $key = null)
    {
        parent::__construct($_ENV, $key);
    }
}
