<?php

namespace Tests\Rector\MoveInjectAttributesToParametersRule\Fixture;

use SomeOtherAttribute;

class SkipOtherAttributes
{
    #[SomeOtherAttribute('bar')]
    public function foo(string $bar): void
    {
    }
}
