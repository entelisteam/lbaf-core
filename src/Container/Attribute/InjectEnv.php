<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

use Attribute;

/**
 * Атрибут для внедрения переменных окружения
 *
 * Примеры:
 *
 * 1. Заполняет одноименным значением
 *    #[InjectEnv('dbHost')]
 *    function connect (string $dbHost)
 *
 * 2. Внедрение через параметр
 *    function connect (#[InjectEnv()] string $dbHost)
 *
 * 3. Явно указываем ключ переменной окружения
 *    #[InjectEnv('host', 'DB_HOST')]
 *    function connect (string $host)
 *
 * 4. Явный ключ через параметр
 *    function connect (#[InjectEnv('DB_HOST')] string $host)
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class InjectEnv extends InjectValueArrayAbstract
{
    /**
     * @param string $param Название параметра для вставки или название ключа в массиве если это TARGET_PARAMETER
     * @param string|null $key Ключ в массиве
     */
    public function __construct(protected ?string $param = null, string $key = null)
    {
        parent::__construct($_ENV, $key ?? $param);
    }
}
