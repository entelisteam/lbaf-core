<?php

namespace Tests\integration\Inject;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class InjectClassTest extends TestCase
{
    public function testVariant1MethodLevelAutoClassFromParameterType(): void
    {
        $response = Harness::http('GET', '/inject-class/v1');

        self::assertSame('{"name":"simple"}', $response->pack());
    }

    public function testVariant2MethodLevelExplicitClassOverridesInterfaceType(): void
    {
        $response = Harness::http('GET', '/inject-class/v2');

        self::assertSame('{"name":"bar"}', $response->pack());
    }

    public function testVariant3ParameterLevelAutoClassFromParameterType(): void
    {
        $response = Harness::http('GET', '/inject-class/v3');

        self::assertSame('{"name":"simple"}', $response->pack());
    }

    public function testVariant4ParameterLevelExplicitClassOverridesInterfaceType(): void
    {
        $response = Harness::http('GET', '/inject-class/v4');

        self::assertSame('{"name":"bar"}', $response->pack());
    }

    public function testNestedInjectGetInServiceConstructorReadsCurrentRequest(): void
    {
        $response = Harness::http('GET', '/inject-class/nested-get?foo=hello');

        self::assertSame('{"foo":"hello"}', $response->pack());
    }

    public function testNestedInjectGetReturnsNullWhenAbsent(): void
    {
        $response = Harness::http('GET', '/inject-class/nested-get');

        self::assertSame('{"foo":null}', $response->pack());
    }

    public function testNestedArrayTypeOfInServiceConstructorHydratesDtos(): void
    {
        $response = Harness::http(
            'GET',
            '/inject-class/nested-array?items[0][name]=foo&items[0][value]=1&items[1][name]=bar&items[1][value]=2'
        );

        self::assertSame(
            [
                'items' => [
                    ['name' => 'foo', 'value' => 1, 'value_type' => 'integer'],
                    ['name' => 'bar', 'value' => 2, 'value_type' => 'integer'],
                ],
            ],
            json_decode($response->pack(), true)
        );
    }

    public function testCompositeServiceTransitivelyResolved(): void
    {
        $response = Harness::http('GET', '/inject-class/composite');

        self::assertSame('{"nested_name":"simple"}', $response->pack());
    }

    public function testActionWithTypedClassParamButNoInjectReturns400(): void
    {
        $response = Harness::http('GET', '/inject-edges/action-typed-no-inject');

        self::assertSame(400, $response->getHttpResponseCode());
    }

    public function testInjectInConstructorWorks(): void
    {
        $response = Harness::http('GET', '/inject-edges/ctor-inject');

        self::assertSame('{"name":"simple"}', $response->pack());
    }

    /**
     * Документирует асимметрию: в __construct контроллера типизированный класс БЕЗ #[Inject]
     * автоматически резолвится через контейнер (Container::get → $this->get($paramType)),
     * в отличие от action/__before/__after, где такой параметр даёт 400.
     */
    public function testTypedClassInConstructorAutoResolvesThroughContainer(): void
    {
        $response = Harness::http('GET', '/inject-edges/ctor-typed-no-inject');

        self::assertSame('{"name":"simple"}', $response->pack());
    }

    public function testInjectInBeforeWorks(): void
    {
        $response = Harness::http('GET', '/inject-edges/before-inject');

        self::assertSame('{"name":"simple"}', $response->pack());
    }

    public function testTypedClassInBeforeWithoutInjectReturns400(): void
    {
        $response = Harness::http('GET', '/inject-edges/before-typed-no-inject');

        self::assertSame(400, $response->getHttpResponseCode());
    }

    public function testInjectInAfterWorks(): void
    {
        $response = Harness::http('GET', '/inject-edges/after-inject');

        self::assertSame('{"name":"simple"}', $response->pack());
    }

    public function testTypedClassInAfterWithoutInjectReturns400(): void
    {
        $response = Harness::http('GET', '/inject-edges/after-typed-no-inject');

        self::assertSame(400, $response->getHttpResponseCode());
    }

    public function testInjectInConstructorOverridesAutoResolve(): void
    {
        $response = Harness::http('GET', '/inject-edges/ctor-inject-override');

        self::assertSame('{"name":"bar"}', $response->pack());
    }
}
