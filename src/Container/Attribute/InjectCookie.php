<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

use Attribute;

/**
 * Атрибут для внедрения данных из Cookie
 *
 * Примеры:
 *
 * 1. Заполняет одноименным значением
 *    #[InjectCookie('sessionId')]
 *    function validate (string $sessionId)
 *
 * 2. Внедрение через параметр
 *    function validate (#[InjectCookie()] string $sessionId)
 *
 * 3. Явно указываем ключ в массиве cookie
 *    #[InjectCookie('session', 'PHPSESSID')]
 *    function validate (string $session)
 *
 * 4. Явный ключ через параметр
 *    function validate (#[InjectCookie('PHPSESSID')] string $session)
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class InjectCookie extends InjectValueArrayAbstract
{
    /**
     * @param string $param Название параметра для вставки или название ключа в массиве если это TARGET_PARAMETER
     * @param string|null $key Ключ в массиве
     */
    public function __construct(protected ?string $param = null, string $key = null)
    {
        parent::__construct($_COOKIE, $key ?? $param);
    }
}
