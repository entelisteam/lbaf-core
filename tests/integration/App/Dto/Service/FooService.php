<?php

namespace Tests\integration\App\Dto\Service;

class FooService implements NamedInterface
{
    public function getName(): string
    {
        return 'foo';
    }
}
