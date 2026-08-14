<?php

namespace Tests\Rector\MoveInjectAttributesToParametersRule\Fixture;

use Lbaf\Container\Attribute\InjectGet;

class InjectGetMultipleArgs
{
    #[InjectGet('bar', 'anyGetKey')]
    public function foo(string $bar): void
    {
    }
}

?>
-----
<?php

namespace Tests\Rector\MoveInjectAttributesToParametersRule\Fixture;

use Lbaf\Container\Attribute\InjectGet;

class InjectGetMultipleArgs
{
    public function foo(
        #[InjectGet('anyGetKey')]
        string $bar
    ): void
    {
    }
}

?>
