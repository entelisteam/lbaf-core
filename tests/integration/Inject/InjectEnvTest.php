<?php

namespace Tests\integration\Inject;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class InjectEnvTest extends TestCase
{
    public function testVariant1MethodLevelKeyMatchesParamName(): void
    {
        $response = Harness::http('GET', '/inject-env/v1', env: ['dbHost' => 'localhost']);

        self::assertSame('{"value":"localhost"}', $response->pack());
    }

    public function testVariant2ParameterLevelNoExplicitKey(): void
    {
        $response = Harness::http('GET', '/inject-env/v2', env: ['dbHost' => 'localhost']);

        self::assertSame('{"value":"localhost"}', $response->pack());
    }

    public function testVariant3MethodLevelExplicitKey(): void
    {
        $response = Harness::http('GET', '/inject-env/v3', env: ['DB_HOST' => 'localhost']);

        self::assertSame('{"value":"localhost"}', $response->pack());
    }

    public function testVariant4ParameterLevelExplicitKey(): void
    {
        $response = Harness::http('GET', '/inject-env/v4', env: ['DB_HOST' => 'localhost']);

        self::assertSame('{"value":"localhost"}', $response->pack());
    }

    public function testIntTypeIsCoerced(): void
    {
        $response = Harness::http('GET', '/inject-env/typed-int', env: ['count' => '42']);

        self::assertSame(['count' => 42, 'type' => 'integer'], json_decode($response->pack(), true));
    }

    public function testOptionalParamMissingReturnsDefault(): void
    {
        $response = Harness::http('GET', '/inject-env/optional');

        self::assertSame('{"maybe":null}', $response->pack());
    }

    public function testOptionalParamPresentIsUsed(): void
    {
        $response = Harness::http('GET', '/inject-env/optional', env: ['maybe' => 'yes']);

        self::assertSame('{"maybe":"yes"}', $response->pack());
    }

    public function testRequiredParamMissingReturns400(): void
    {
        $response = Harness::http('GET', '/inject-env/required');

        self::assertSame(400, $response->getHttpResponseCode());
    }
}
