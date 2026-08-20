<?php

namespace Tests\Rector\ReplacePackerTypeWithPackedInjectRule\Fixture;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectPost;
use EntelisTeam\Lbaf\Core\Packer\PackerType;

class InjectPostPositionalPackerType
{
    public function foo(#[InjectPost('customKey', PackerType::Json)] Request $request): void
    {
    }
}

?>
-----
<?php

namespace Tests\Rector\ReplacePackerTypeWithPackedInjectRule\Fixture;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectPostPacked;
class InjectPostPositionalPackerType
{
    public function foo(#[InjectPostPacked('customKey')] Request $request): void
    {
    }
}

?>
