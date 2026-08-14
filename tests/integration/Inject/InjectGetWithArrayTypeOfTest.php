<?php

namespace Tests\integration\Inject;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class InjectGetWithArrayTypeOfTest extends TestCase
{
    public function testArrayOfIntCoercesEachElement(): void
    {
        $response = Harness::http('GET', '/inject-get/array-of-int?numbers[]=1&numbers[]=2&numbers[]=3');

        self::assertSame(
            ['numbers' => [1, 2, 3], 'first_type' => 'integer'],
            json_decode($response->pack(), true)
        );
    }

    public function testArrayOfDtoHydratesEachElement(): void
    {
        $response = Harness::http(
            'GET',
            '/inject-get/array-of-dto?items[0][name]=foo&items[0][value]=1&items[1][name]=bar&items[1][value]=2'
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
}
