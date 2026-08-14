<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

use Attribute;

/**
 * Атрибут для внедрения данных из HTTP-заголовков
 *
 * Примеры:
 *
 * 1. Заполняет одноименным значением
 *    #[InjectHeader('authorization')]
 *    function foo (string $authorization)
 *
 * 2. Внедрение через параметр
 *    function foo (#[InjectHeader()] string $authorization)
 *
 * 3. Явно указываем ключ заголовка
 *    #[InjectHeader('auth', 'authorization')]
 *    function foo (string $auth)
 *
 * 4. Явный ключ через параметр
 *    function foo (#[InjectHeader('authorization')] string $auth)
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class InjectHeader extends InjectValueArrayAbstract
{
    /**
     * @param string $param Название параметра для вставки или название ключа в массиве если это TARGET_PARAMETER
     * @param string|null $key Ключ в массиве
     */
    public function __construct(protected ?string $param = null, ?string $key = null)
    {
        //имена заголовков регистронезависимы, приводим к нижнему регистру с обеих сторон
        $headers = array_change_key_case(getallheaders());
        $realKey = $key ?? $param;
        parent::__construct($headers, is_null($realKey) ? null : strtolower($realKey));
    }
}
