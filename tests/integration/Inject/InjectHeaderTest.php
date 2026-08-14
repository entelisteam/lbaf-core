<?php

namespace Tests\integration\Inject;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class InjectHeaderTest extends TestCase
{
    public function testVariant1MethodLevelKeyMatchesParamName(): void
    {
        $response = Harness::http('GET', '/inject-header/v1', headers: ['authorization' => 'Bearer xyz']);

        self::assertSame('{"value":"Bearer xyz"}', $response->pack());
    }

    public function testVariant2ParameterLevelKeyMatchesParamName(): void
    {
        $response = Harness::http('GET', '/inject-header/v2', headers: ['authorization' => 'Bearer xyz']);

        self::assertSame('{"value":"Bearer xyz"}', $response->pack());
    }

    public function testVariant3MethodLevelExplicitKey(): void
    {
        $response = Harness::http('GET', '/inject-header/v3', headers: ['authorization' => 'Bearer xyz']);

        self::assertSame('{"value":"Bearer xyz"}', $response->pack());
    }

    public function testVariant4ParameterLevelExplicitKey(): void
    {
        $response = Harness::http('GET', '/inject-header/v4', headers: ['authorization' => 'Bearer xyz']);

        self::assertSame('{"value":"Bearer xyz"}', $response->pack());
    }

    public function testIntTypeIsCoerced(): void
    {
        $response = Harness::http('GET', '/inject-header/typed-int', headers: ['xcount' => '42']);

        self::assertSame(['count' => 42, 'type' => 'integer'], json_decode($response->pack(), true));
    }

    public function testOptionalParamMissingReturnsDefault(): void
    {
        $response = Harness::http('GET', '/inject-header/optional');

        self::assertSame('{"maybe":null}', $response->pack());
    }

    public function testOptionalParamPresentIsUsed(): void
    {
        $response = Harness::http('GET', '/inject-header/optional', headers: ['xmaybe' => 'yes']);

        self::assertSame('{"maybe":"yes"}', $response->pack());
    }

    public function testRequiredParamMissingReturns400(): void
    {
        $response = Harness::http('GET', '/inject-header/required');

        self::assertSame(400, $response->getHttpResponseCode());
    }

    /**
     * Имя заголовка с дефисом задаётся в атрибуте как есть — ровно так, как оно приходит в запросе.
     */
    public function testDashedHeaderKeyIsMapped(): void
    {
        $response = Harness::http('GET', '/inject-header/dashed-key', headers: ['x-custom' => 'present']);

        self::assertSame('{"value":"present"}', $response->pack());
    }
}
