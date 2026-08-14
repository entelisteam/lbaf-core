<?php

namespace Tests\Rector\MoveInjectAttributesToParametersRule\Fixture;

use Lbaf\Container\Attribute\InjectGet;
use Lbaf\Packer\PackerType;

class InjectGetWithPackerType
{
    #[InjectGet('bar', PackerType::Int)]
    public function fooWithSecondArg(int $bar): void
    {
    }

    #[InjectGet('baz', 'customKey', PackerType::String)]
    public function fooWithThirdArg(string $baz): void
    {
    }
}

?>
-----
<?php

namespace Tests\Rector\MoveInjectAttributesToParametersRule\Fixture;

use Lbaf\Container\Attribute\InjectGet;
use Lbaf\Packer\PackerType;

class InjectGetWithPackerType
{
    public function fooWithSecondArg(
        #[InjectGet(packerType: PackerType::Int)]
        int $bar
    ): void
    {
    }

    public function fooWithThirdArg(
        #[InjectGet('customKey', packerType: PackerType::String)]
        string $baz
    ): void
    {
    }
}

?>
