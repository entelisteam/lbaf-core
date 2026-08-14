<?php

namespace Tests\integration\Lifecycle;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class AfterTest extends TestCase
{
    public function testAfterRunsWhenActionReturnsNull(): void
    {
        $response = Harness::http('GET', '/lifecycle/after-when-action-returns-null');

        self::assertSame('{"source":"__after"}', $response->pack());
    }

    /**
     * Документирует фактическое поведение: ApiResponse имеет stopSequenceAfterResponse=true
     * по умолчанию, поэтому штатный action-response прерывает sequence и __after не запускается.
     */
    public function testAfterIsSkippedWhenActionReturnsValue(): void
    {
        $response = Harness::http('GET', '/lifecycle/after-when-action-returns-value');

        self::assertSame('{"source":"action"}', $response->pack());
    }

    public function testAfterIsSkippedWhenActionThrows(): void
    {
        $response = Harness::http('GET', '/lifecycle/after-when-action-throws');

        self::assertSame(500, $response->getHttpResponseCode());
        $body = json_decode($response->pack(), true);
        self::assertSame(500, $body['error']['code']);
        self::assertSame('boom', $body['error']['error_message']);
    }
}
