<?php

namespace Tests\integration\Routing\FastRouteRouter;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class UrlDecodingTest extends TestCase
{
    public function testPercentEncodedSpaceIsDecoded(): void
    {
        $response = Harness::http('GET', '/files/hello%20world');

        self::assertSame(['name' => 'hello world'], json_decode($response->pack(), true));
    }

    public function testCyrillicIsDecoded(): void
    {
        $response = Harness::http('GET', '/files/' . rawurlencode('привет'));

        self::assertSame(['name' => 'привет'], json_decode($response->pack(), true));
    }
}
