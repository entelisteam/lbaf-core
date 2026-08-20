<?php

namespace Tests\integration\Inject;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class InjectPostTest extends TestCase
{
    public function testVariant2ParameterLevelNoExplicitKey(): void
    {
        $response = Harness::http('POST', '/inject-post/v2', ['foo' => 'hello']);

        self::assertSame('{"foo":"hello"}', $response->pack());
    }

    public function testVariant4ParameterLevelExplicitKey(): void
    {
        $response = Harness::http('POST', '/inject-post/v4', ['customKey' => 'hello']);

        self::assertSame('{"foo":"hello"}', $response->pack());
    }

    public function testVariant4IgnoresParamNameInBody(): void
    {
        $response = Harness::http('POST', '/inject-post/v4', ['foo' => 'hello']);

        self::assertSame(400, $response->getHttpResponseCode());
    }

    public function testIntTypeIsCoerced(): void
    {
        $response = Harness::http('POST', '/inject-post/typed-int', ['count' => '42']);

        self::assertSame(['count' => 42, 'type' => 'integer'], json_decode($response->pack(), true));
    }

    public function testArrayTypeAcceptsArrayBody(): void
    {
        $response = Harness::http('POST', '/inject-post/typed-array', ['items' => ['a', 'b']]);

        self::assertSame(['items' => ['a', 'b']], json_decode($response->pack(), true));
    }

    public function testOptionalParamMissingReturnsDefault(): void
    {
        $response = Harness::http('POST', '/inject-post/optional');

        self::assertSame('{"maybe":null}', $response->pack());
    }

    public function testOptionalParamPresentIsUsed(): void
    {
        $response = Harness::http('POST', '/inject-post/optional', ['maybe' => 'yes']);

        self::assertSame('{"maybe":"yes"}', $response->pack());
    }

    public function testRequiredParamMissingReturns400(): void
    {
        $response = Harness::http('POST', '/inject-post/required');

        self::assertSame(400, $response->getHttpResponseCode());
        $body = json_decode($response->pack(), true);
        self::assertSame(400, $body['error']['code']);
        self::assertStringContainsString('must', $body['error']['error_message']);
    }
}
