<?php

namespace Tests\integration\Inject;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class InjectCookieTest extends TestCase
{
    public function testVariant2ParameterLevelNoExplicitKey(): void
    {
        $response = Harness::http('GET', '/inject-cookie/v2', cookies: ['sessionId' => 'abc123']);

        self::assertSame('{"value":"abc123"}', $response->pack());
    }

    public function testVariant4ParameterLevelExplicitKey(): void
    {
        $response = Harness::http('GET', '/inject-cookie/v4', cookies: ['PHPSESSID' => 'abc123']);

        self::assertSame('{"value":"abc123"}', $response->pack());
    }

    public function testIntTypeIsCoerced(): void
    {
        $response = Harness::http('GET', '/inject-cookie/typed-int', cookies: ['count' => '42']);

        self::assertSame(['count' => 42, 'type' => 'integer'], json_decode($response->pack(), true));
    }

    public function testOptionalParamMissingReturnsDefault(): void
    {
        $response = Harness::http('GET', '/inject-cookie/optional');

        self::assertSame('{"maybe":null}', $response->pack());
    }

    public function testOptionalParamPresentIsUsed(): void
    {
        $response = Harness::http('GET', '/inject-cookie/optional', cookies: ['maybe' => 'yes']);

        self::assertSame('{"maybe":"yes"}', $response->pack());
    }

    public function testRequiredParamMissingReturns400(): void
    {
        $response = Harness::http('GET', '/inject-cookie/required');

        self::assertSame(400, $response->getHttpResponseCode());
    }
}
