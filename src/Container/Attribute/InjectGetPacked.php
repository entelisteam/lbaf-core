<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

use Attribute;
use EntelisTeam\Lbaf\Core\Packer\Json;
use EntelisTeam\Lbaf\Core\Packer\PackerInterface;

/**
 * Атрибут для внедрения данных из GET-запроса
 *
 * Примеры:
 *
 * 1. В $_GET['request'] лежит строкой json из которого будет создан объект $request
 *    function foo (#[InjectGetPacked()] ComplexJsonObjectRequest $request)
 *
 * 1.2. Возможность передачи явно Packer (функционал на будущее, в Lbaf идет только Json)
 *      function foo (#[InjectGetPacked(packer:new Json())] ComplexJsonObjectRequest $request)
 *
 * 2. В $_GET['input'] лежит строкой json из которого будет создан объект $myObject
 *    function foo (#[InjectGetPacked('input')] ComplexJsonObjectRequest $myObject)
 *
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class InjectGetPacked extends InjectValueArrayPackedAbstract
{
    /**
     * @param string|null $key Ключ в массиве
     */
    public function __construct(?string $key = null, PackerInterface $packer = new Json())
    {
        parent::__construct($_GET, $key, $packer);
    }
}
