<?php

namespace Tests\integration\Routing\FastRouteRouter;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class TrailingSlashTest extends TestCase
{
    public function testRouteWithTrailingSlashMatchesOnlyExact(): void
    {
        $response = Harness::http('GET', '/with-slash');

        self::assertNull($response);
        self::assertSame(404, http_response_code());
    }

    public function testRouteWithoutTrailingSlashMatchesOnlyExact(): void
    {
        $response = Harness::http('GET', '/no-slash/');

        self::assertNull($response);
        self::assertSame(404, http_response_code());
    }

    public function testExactTrailingSlashMatches(): void
    {
        $response = Harness::http('GET', '/with-slash/');

        self::assertSame('{"matched":"with-slash"}', $response->pack());
    }

    public function testExactNoTrailingSlashMatches(): void
    {
        $response = Harness::http('GET', '/no-slash');

        self::assertSame('{"matched":"no-slash"}', $response->pack());
    }
}
