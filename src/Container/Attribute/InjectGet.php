<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

use Attribute;
use EntelisTeam\Lbaf\Core\Packer\PackerType;

/**
 * Атрибут для внедрения данных из GET-запроса
 *
 * Примеры:
 *
 * 1. Заполняет одноименным значением
 *    #[InjectGet('bar')]
 *    function foo (string $bar)
 *
 * 2. Внедрение через параметр
 *    function foo (#[InjectGet()] string $bar)
 *
 * 3. Явно указываем ключ в глобальном массиве
 *    #[InjectGet('bar', 'anyGetKey')]
 *    function foo (string $bar)
 *
 * 4. Явный ключ через параметр
 *    function foo (#[InjectGet('anyGetKey')] string $bar)
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class InjectGet extends InjectValueArrayAbstract
{
    /**
     * @param string $param Название параметра для вставки или название ключа в массиве если это TARGET_PARAMETER
     * @param string|null $key Ключ в массиве
     */
    public function __construct(protected ?string $param = null, null|string|PackerType $key = null, ?PackerType $packerType = null)
    {
        if ($key instanceof PackerType) {
            parent::__construct($_GET, $param, $key);
        } else {
            parent::__construct($_GET, $key ?? $param, $packerType);
        }
    }
}
