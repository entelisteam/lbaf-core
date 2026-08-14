<?php

namespace Tests\Rector\MoveInjectAttributesToParametersRule\Fixture;

use Lbaf\Container\Attribute\InjectEnv;

class InjectEnvTest
{
    #[InjectEnv('apiKey', 'API_KEY')]
    public function connect(string $apiKey): void
    {
    }
}

?>
-----
<?php

namespace Tests\Rector\MoveInjectAttributesToParametersRule\Fixture;

use Lbaf\Container\Attribute\InjectEnv;

class InjectEnvTest
{
    public function connect(
        #[InjectEnv('API_KEY')]
        string $apiKey
    ): void
    {
    }
}

?>
