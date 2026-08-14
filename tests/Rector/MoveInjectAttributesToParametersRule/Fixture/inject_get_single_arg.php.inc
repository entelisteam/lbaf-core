<?php

namespace Tests\Rector\MoveInjectAttributesToParametersRule\Fixture;

use Lbaf\Container\Attribute\InjectGet;

class InjectGetSingleArg
{
    #[InjectGet('bar')]
    public function foo(string $bar): void
    {
    }
}

?>
-----
<?php

namespace Tests\Rector\MoveInjectAttributesToParametersRule\Fixture;

use Lbaf\Container\Attribute\InjectGet;

class InjectGetSingleArg
{
    public function foo(
        #[InjectGet]
        string $bar
    ): void
    {
    }
}

?>
