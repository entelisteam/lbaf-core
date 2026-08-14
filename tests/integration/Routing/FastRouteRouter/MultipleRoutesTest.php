<?php

namespace Tests\integration\Routing\FastRouteRouter;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class MultipleRoutesTest extends TestCase
{
    public function testFirstRouteAttributeWorks(): void
    {
        $response = Harness::http('GET', '/multi-route-a');

        self::assertSame('{"handler":"multi-route"}', $response->pack());
    }

    public function testSecondRouteAttributeWorks(): void
    {
        $response = Harness::http('GET', '/multi-route-b');

        self::assertSame('{"handler":"multi-route"}', $response->pack());
    }
}
