<?php

namespace Tests\integration\Parameters;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class ParameterDefaultsTest extends TestCase
{
    public function testOptionalParamWithDefaultUsesDefault(): void
    {
        $response = Harness::http('GET', '/parameters/optional-default');

        self::assertSame('{"foo":"default-value"}', $response->pack());
    }

    public function testNullableParamGetsNull(): void
    {
        $response = Harness::http('GET', '/parameters/nullable');

        self::assertSame('{"foo":null}', $response->pack());
    }

    public function testRequiredParamMissingReturns400(): void
    {
        $response = Harness::http('GET', '/parameters/required');

        self::assertSame(400, $response->getHttpResponseCode());
        $body = json_decode($response->pack(), true);
        self::assertStringContainsString('foo', $body['error']['error_message']);
    }
}
