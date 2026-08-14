<?php

namespace Tests\Rector\MoveInjectAttributesToParametersRule\Fixture;

use Lbaf\Container\Attribute\InjectPost;

class InjectPostTest
{
    #[InjectPost('data')]
    public function process(array $data): void
    {
    }
}

?>
-----
<?php

namespace Tests\Rector\MoveInjectAttributesToParametersRule\Fixture;

use Lbaf\Container\Attribute\InjectPost;

class InjectPostTest
{
    public function process(
        #[InjectPost]
        array $data
    ): void
    {
    }
}

?>
