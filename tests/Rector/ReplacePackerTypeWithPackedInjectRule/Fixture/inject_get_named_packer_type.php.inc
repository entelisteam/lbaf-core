<?php

namespace Tests\Rector\ReplacePackerTypeWithPackedInjectRule\Fixture;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectGet;
use EntelisTeam\Lbaf\Core\Packer\PackerType;

class InjectGetNamedPackerType
{
    public function foo(#[InjectGet(packerType: PackerType::Json)] Request $request): void
    {
    }
}

?>
-----
<?php

namespace Tests\Rector\ReplacePackerTypeWithPackedInjectRule\Fixture;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectGetPacked;
class InjectGetNamedPackerType
{
    public function foo(#[InjectGetPacked] Request $request): void
    {
    }
}

?>
