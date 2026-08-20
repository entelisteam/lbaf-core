<?php

namespace EntelisTeam\Lbaf\Core\Container\Attribute;

use Attribute;
use EntelisTeam\Lbaf\Core\Packer\Json;
use EntelisTeam\Lbaf\Core\Packer\PackerInterface;

/**
 * Атрибут для внедрения данных из POST-запроса
 *
 * Примеры:
 *
 * 1. В $_POST['request'] лежит строкой json из которого будет создан объект $request
 *    function foo (#[InjectPostPacked()] ComplexJsonObjectRequest $request)
 *
 * 1.2. Возможность передачи явно Packer (функционал на будущее, в Lbaf идет только Json)
 *      function foo (#[InjectPostPacked(packer:new Json())] ComplexJsonObjectRequest $request)
 *
 * 2. В $_POST['input'] лежит строкой json из которого будет создан объект $myObject
 *    function foo (#[InjectPostPacked('input')] ComplexJsonObjectRequest $myObject)
 *
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class InjectPostPacked extends InjectValueArrayPackedAbstract
{
    /**
     * @param string|null $key Ключ в массиве
     */
    public function __construct(?string $key = null, PackerInterface $packer = new Json())
    {
        parent::__construct($_POST, $key, $packer);
    }
}
