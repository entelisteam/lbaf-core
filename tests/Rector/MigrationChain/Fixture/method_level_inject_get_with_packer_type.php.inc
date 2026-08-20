<?php

namespace Tests\Rector\MigrationChain\Fixture;

use Lbaf\Container\Attribute\InjectGet;
use Lbaf\Packer\PackerType;

class MethodLevelInjectGetWithPackerType
{
    #[InjectGet('foo', packerType: PackerType::Json)]
    public function bar(Request $foo): void
    {
    }
}

?>
-----
<?php

namespace Tests\Rector\MigrationChain\Fixture;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectGetPacked;


class MethodLevelInjectGetWithPackerType
{
    public function bar(
        #[InjectGetPacked]
        Request $foo
    ): void
    {
    }
}

?>
