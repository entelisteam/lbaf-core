<?php

namespace Tests\integration\Inject;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class InjectValueTest extends TestCase
{
    public function testLiteralIsPassedToAction(): void
    {
        $response = Harness::http('GET', '/inject-value/basic');

        self::assertSame('{"foo":"hello"}', $response->pack());
    }

    public function testMultipleAttributesOnSameAction(): void
    {
        $response = Harness::http('GET', '/inject-value/multiple');

        self::assertSame(['a' => 1, 'b' => 'two'], json_decode($response->pack(), true));
    }

    public function testIntLiteralKeepsType(): void
    {
        $response = Harness::http('GET', '/inject-value/typed-int');

        self::assertSame(['count' => 42, 'type' => 'integer'], json_decode($response->pack(), true));
    }

    public function testArrayLiteral(): void
    {
        $response = Harness::http('GET', '/inject-value/typed-array');

        self::assertSame(['items' => ['a', 'b', 'c']], json_decode($response->pack(), true));
    }

    public function testNullLiteralOnOptionalParamUsesDefault(): void
    {
        $response = Harness::http('GET', '/inject-value/optional-null');

        self::assertSame('{"maybe":null}', $response->pack());
    }

    public function testNullLiteralOnRequiredParamReturns400(): void
    {
        $response = Harness::http('GET', '/inject-value/required-null');

        self::assertSame(400, $response->getHttpResponseCode());
    }
}
