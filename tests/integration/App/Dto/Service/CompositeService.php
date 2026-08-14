<?php

namespace Tests\integration\App\Dto\Service;

class CompositeService
{
    public function __construct(
        public SimpleService $simple,
    ) {}
}
