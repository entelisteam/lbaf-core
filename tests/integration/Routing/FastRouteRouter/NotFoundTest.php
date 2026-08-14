<?php

namespace Tests\integration\Routing\FastRouteRouter;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class NotFoundTest extends TestCase
{
    public function testUnknownRouteReturns404(): void
    {
        $response = Harness::http('GET', '/does-not-exist');

        self::assertNull($response);
        self::assertSame(404, http_response_code());
    }
}
