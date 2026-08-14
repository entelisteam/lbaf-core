<?php

namespace Tests\integration\Inject;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class InjectPostWithArrayTypeOfTest extends TestCase
{
    public function testArrayOfIntCoercesEachElement(): void
    {
        $response = Harness::http('POST', '/inject-post/array-of-int', [
            'numbers' => ['1', '2', '3'],
        ]);

        self::assertSame(
            ['numbers' => [1, 2, 3], 'first_type' => 'integer'],
            json_decode($response->pack(), true)
        );
    }

    public function testArrayOfDtoHydratesEachElement(): void
    {
        $response = Harness::http('POST', '/inject-post/array-of-dto', [
            'items' => [
                ['name' => 'foo', 'value' => '1'],
                ['name' => 'bar', 'value' => '2'],
            ],
        ]);

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
}
