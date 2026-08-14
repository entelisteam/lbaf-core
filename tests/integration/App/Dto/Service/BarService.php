<?php

namespace Tests\integration\App\Dto\Service;

class BarService implements NamedInterface
{
    public function getName(): string
    {
        return 'bar';
    }
}
