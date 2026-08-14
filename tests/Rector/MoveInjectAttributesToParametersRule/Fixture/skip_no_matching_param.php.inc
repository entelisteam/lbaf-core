<?php

namespace Tests\Rector\MoveInjectAttributesToParametersRule\Fixture;

use Lbaf\Container\Attribute\InjectGet;

class SkipNoMatchingParam
{
    #[InjectGet('nonExistent')]
    public function foo(string $bar): void
    {
    }
}
