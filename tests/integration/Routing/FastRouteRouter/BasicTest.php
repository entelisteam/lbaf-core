<?php

namespace Tests\integration\Routing\FastRouteRouter;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class BasicTest extends TestCase
{
    public function testRootGetReturnsControllerPayload(): void
    {
        $response = Harness::http('GET', '/');

        self::assertNotNull($response);
        self::assertSame('{"foo":"bar"}', $response->pack());
    }
}
