<?php

namespace Tests\integration\Routing\FastRouteRouter;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class QueryStringTest extends TestCase
{
    public function testQueryStringDoesNotBreakMatching(): void
    {
        $response = Harness::http('GET', '/?foo=bar&baz=42');

        self::assertSame('{"foo":"bar"}', $response->pack());
    }
}
