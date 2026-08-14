<?php

namespace Tests\integration\Lifecycle;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class BeforeTest extends TestCase
{
    public function testBeforeRunsBeforeActionAndCanMutateControllerState(): void
    {
        $response = Harness::http('GET', '/lifecycle/before-state');

        self::assertSame('{"state":"set-by-before"}', $response->pack());
    }

    public function testBeforeReturningNullContinuesToAction(): void
    {
        $response = Harness::http('GET', '/lifecycle/before-short-circuit?authorized=yes');

        self::assertSame('{"source":"action"}', $response->pack());
    }

    public function testBeforeReturningArrayShortCircuitsAction(): void
    {
        $response = Harness::http('GET', '/lifecycle/before-short-circuit');

        self::assertSame('{"source":"__before","blocked":true}', $response->pack());
    }

    public function testBeforeReturningResponseObjectShortCircuitsAction(): void
    {
        $response = Harness::http('GET', '/lifecycle/before-response-object');

        self::assertSame('{"source":"__before-response-object"}', $response->pack());
        self::assertSame(418, $response->getHttpResponseCode());
    }
}
