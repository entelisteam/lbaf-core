<?php

namespace Tests\integration\Inject;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class InjectPostPackedTest extends TestCase
{
    public function testVariant1KeyMatchesParamName(): void
    {
        $response = Harness::http('POST', '/inject-post-packed/v1', ['item' => '{"name":"foo","value":1}']);

        self::assertSame(
            ['name' => 'foo', 'value' => 1, 'value_type' => 'integer'],
            json_decode($response->pack(), true)
        );
    }

    public function testVariant2ExplicitKey(): void
    {
        $response = Harness::http('POST', '/inject-post-packed/v2', ['input' => '{"name":"foo","value":1}']);

        self::assertSame('{"name":"foo"}', $response->pack());
    }

    public function testVariant2IgnoresParamNameInBody(): void
    {
        $response = Harness::http('POST', '/inject-post-packed/v2', ['myObject' => '{"name":"foo","value":1}']);

        self::assertSame(400, $response->getHttpResponseCode());
    }

    /**
     * Значения из json приводятся к типам DTO гидратором.
     */
    public function testValueIsCoercedToDtoType(): void
    {
        $response = Harness::http('POST', '/inject-post-packed/v1', ['item' => '{"name":"foo","value":"42"}']);

        self::assertSame(
            ['name' => 'foo', 'value' => 42, 'value_type' => 'integer'],
            json_decode($response->pack(), true)
        );
    }

    public function testExplicitJsonPackerWorksSameAsDefault(): void
    {
        $response = Harness::http('POST', '/inject-post-packed/explicit-packer', ['item' => '{"name":"foo","value":1}']);

        self::assertSame('{"name":"foo"}', $response->pack());
    }

    public function testCustomPackerIsUsedInsteadOfDefault(): void
    {
        $response = Harness::http(
            'POST',
            '/inject-post-packed/custom-packer',
            ['item' => base64_encode('{"name":"foo","value":1}')]
        );

        self::assertSame('{"name":"foo"}', $response->pack());
    }

    public function testCustomPackerRejectsPlainJson(): void
    {
        $response = Harness::http('POST', '/inject-post-packed/custom-packer', ['item' => '{"name":"foo","value":1}']);

        self::assertSame(400, $response->getHttpResponseCode());
    }

    public function testJsonArrayHydratesIntoArrayParam(): void
    {
        $response = Harness::http(
            'POST',
            '/inject-post-packed/array-of-dto',
            ['items' => '[{"name":"foo","value":1},{"name":"bar","value":2}]']
        );

        self::assertSame(['names' => ['foo', 'bar']], json_decode($response->pack(), true));
    }

    public function testOptionalParamMissingReturnsDefault(): void
    {
        $response = Harness::http('POST', '/inject-post-packed/optional');

        self::assertSame('{"name":null}', $response->pack());
    }

    public function testOptionalParamPresentIsUsed(): void
    {
        $response = Harness::http('POST', '/inject-post-packed/optional', ['maybe' => '{"name":"foo","value":1}']);

        self::assertSame('{"name":"foo"}', $response->pack());
    }

    public function testRequiredParamMissingReturns400(): void
    {
        $response = Harness::http('POST', '/inject-post-packed/v1');

        self::assertSame(400, $response->getHttpResponseCode());
    }

    /**
     * Битый json распаковывается в null — для обязательного параметра это 400, как и его отсутствие.
     */
    public function testBrokenJsonReturns400(): void
    {
        $response = Harness::http('POST', '/inject-post-packed/v1', ['item' => '{"name":"foo"']);

        self::assertSame(400, $response->getHttpResponseCode());
    }

    /**
     * Обязательное поле DTO отсутствует в распакованных данных.
     */
    public function testMissingDtoFieldReturns400(): void
    {
        $response = Harness::http('POST', '/inject-post-packed/v1', ['item' => '{"name":"foo"}']);

        self::assertSame(400, $response->getHttpResponseCode());
    }

    /**
     * Массив в $_POST вместо упакованной строки — некорректный ввод, а не 500.
     */
    public function testArrayValueReturns400(): void
    {
        $response = Harness::http('POST', '/inject-post-packed/v1', ['item' => ['a']]);

        self::assertSame(400, $response->getHttpResponseCode());
    }
}
