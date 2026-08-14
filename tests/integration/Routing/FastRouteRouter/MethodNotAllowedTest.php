<?php

namespace Tests\integration\Routing\FastRouteRouter;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class MethodNotAllowedTest extends TestCase
{
    public function testPostOnGetOnlyRouteIsTreatedAs404(): void
    {
        $response = Harness::http('POST', '/');

        self::assertNull($response);
        self::assertSame(404, http_response_code());
    }
}
