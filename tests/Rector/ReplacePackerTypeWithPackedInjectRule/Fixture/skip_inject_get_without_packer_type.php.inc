<?php

namespace Tests\Rector\ReplacePackerTypeWithPackedInjectRule\Fixture;

use EntelisTeam\Lbaf\Core\Container\Attribute\InjectGet;

class SkipInjectGetWithoutPackerType
{
    public function foo(#[InjectGet] string $bar, #[InjectGet('customKey')] string $baz): void
    {
    }
}

?>
