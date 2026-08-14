<?php

namespace Tests\Rector\MoveInjectAttributesToParametersRule\Fixture;

use Lbaf\Container\Attribute\InjectGet;
use Lbaf\Container\Attribute\InjectPost;

class MultipleParams
{
    #[InjectGet('page')]
    #[InjectPost('data')]
    public function handle(int $page, array $data): void
    {
    }
}

?>
-----
<?php

namespace Tests\Rector\MoveInjectAttributesToParametersRule\Fixture;

use Lbaf\Container\Attribute\InjectGet;
use Lbaf\Container\Attribute\InjectPost;

class MultipleParams
{
    public function handle(
        #[InjectGet]
        int $page,
        #[InjectPost]
        array $data
    ): void
    {
    }
}

?>
