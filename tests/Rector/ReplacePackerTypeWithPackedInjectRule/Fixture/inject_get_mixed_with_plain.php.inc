<?php

namespace Tests\Rector\ReplacePackerTypeWithPackedInjectRule\Fixture;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectGet;
use EntelisTeam\Lbaf\Core\Packer\PackerType;

class InjectGetMixedWithPlain
{
    public function foo(#[InjectGet] string $bar, #[InjectGet(packerType: PackerType::Json)] Request $request): void
    {
    }
}

?>
-----
<?php

namespace Tests\Rector\ReplacePackerTypeWithPackedInjectRule\Fixture;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectGetPacked;
use EntelisTeam\Lbaf\Core\Container\Attribute\InjectGet;

class InjectGetMixedWithPlain
{
    public function foo(#[InjectGet] string $bar, #[InjectGetPacked] Request $request): void
    {
    }
}

?>
