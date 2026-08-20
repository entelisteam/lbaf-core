<?php

namespace Tests\Rector\ReplacePackerTypeWithPackedInjectRule\Fixture;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectGet;
use EntelisTeam\Lbaf\Core\Packer\PackerType;

class InjectGetKeyAndPackerType
{
    public function foo(#[InjectGet('input', packerType: PackerType::Json)] Request $myObject): void
    {
    }
}

?>
-----
<?php

namespace Tests\Rector\ReplacePackerTypeWithPackedInjectRule\Fixture;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectGetPacked;
class InjectGetKeyAndPackerType
{
    public function foo(#[InjectGetPacked('input')] Request $myObject): void
    {
    }
}

?>
