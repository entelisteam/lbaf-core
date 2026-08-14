<?php

namespace Tests\integration\Routing\FastRouteRouter;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class ParameterTest extends TestCase
{
    public function testSingleParameterIsPassedToAction(): void
    {
        $response = Harness::http('GET', '/users/42');

        self::assertSame('{"id":"42"}', $response->pack());
    }

    public function testMultipleParametersWithRegexConstraint(): void
    {
        $response = Harness::http('GET', '/items/book/7');

        self::assertSame('{"type":"book","id":"7"}', $response->pack());
    }

    public function testRegexConstraintRejectsNonMatchingValue(): void
    {
        $response = Harness::http('GET', '/items/book/abc');

        self::assertNull($response);
        self::assertSame(404, http_response_code());
    }
}
