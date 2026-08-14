<?php

namespace Tests\integration\Routing\FastRouteRouter;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class ComboRoutesAndMethodsTest extends TestCase
{
    public function testGetOnFirstRoute(): void
    {
        $response = Harness::http('GET', '/combo-a');

        self::assertSame('{"handler":"combo"}', $response->pack());
    }

    public function testPostOnSecondRoute(): void
    {
        $response = Harness::http('POST', '/combo-b');

        self::assertSame('{"handler":"combo"}', $response->pack());
    }

    public function testPutOnSecondRoute(): void
    {
        $response = Harness::http('PUT', '/combo-b');

        self::assertSame('{"handler":"combo"}', $response->pack());
    }

    public function testPostOnFirstRouteIs404(): void
    {
        $response = Harness::http('POST', '/combo-a');

        self::assertNull($response);
        self::assertSame(404, http_response_code());
    }

    public function testGetOnSecondRouteIs404(): void
    {
        $response = Harness::http('GET', '/combo-b');

        self::assertNull($response);
        self::assertSame(404, http_response_code());
    }

    public function testDeleteOnSecondRouteIs404(): void
    {
        $response = Harness::http('DELETE', '/combo-b');

        self::assertNull($response);
        self::assertSame(404, http_response_code());
    }
}
