<?php

namespace Tests\integration\Routing\FastRouteRouter;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class MultipleMethodsTest extends TestCase
{
    public function testGetMatches(): void
    {
        $response = Harness::http('GET', '/multi-method');

        self::assertSame('{"handler":"multi-method"}', $response->pack());
    }

    public function testPostMatches(): void
    {
        $response = Harness::http('POST', '/multi-method');

        self::assertSame('{"handler":"multi-method"}', $response->pack());
    }

    public function testOtherMethodIs404(): void
    {
        $response = Harness::http('PUT', '/multi-method');

        self::assertNull($response);
        self::assertSame(404, http_response_code());
    }
}
