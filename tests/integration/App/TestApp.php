<?php

namespace Tests\integration\App;

use EntelisTeam\Lbaf\Core\Application\AbstractApplication;
use EntelisTeam\Lbaf\Core\Response\AbstractResponse;

class TestApp extends AbstractApplication
{
    public ?AbstractResponse $captured = null;

    #[\Override]
    public function sendResponse(AbstractResponse $response): void
    {
        $this->captured = $response;
    }
}
