<?php

namespace Tests\integration\Lifecycle;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class InheritanceTest extends TestCase
{
    public function testChildOverrideBeforeDoesNotCallParentBefore(): void
    {
        $response = Harness::http('GET', '/lifecycle/inheritance/override-before');

        self::assertSame(
            ['trace' => ['child-before', 'parent-after']],
            json_decode($response->pack(), true)
        );
    }

    public function testChildBeforeCallingParentRunsBoth(): void
    {
        $response = Harness::http('GET', '/lifecycle/inheritance/call-parent-before');

        self::assertSame(
            ['trace' => ['parent-before', 'child-before', 'parent-after']],
            json_decode($response->pack(), true)
        );
    }

    public function testChildWithoutBeforeOverrideInheritsParentBefore(): void
    {
        $response = Harness::http('GET', '/lifecycle/inheritance/inherits-before');

        self::assertSame(
            ['trace' => ['parent-before', 'parent-after']],
            json_decode($response->pack(), true)
        );
    }

    public function testChildOverrideAfterDoesNotCallParentAfter(): void
    {
        $response = Harness::http('GET', '/lifecycle/inheritance/override-after');

        self::assertSame(
            ['trace' => ['child-after']],
            json_decode($response->pack(), true)
        );
    }

    public function testChildAfterCallingParentRunsBoth(): void
    {
        $response = Harness::http('GET', '/lifecycle/inheritance/call-parent-after');

        self::assertSame(
            ['trace' => ['parent-after', 'child-after']],
            json_decode($response->pack(), true)
        );
    }

    public function testChildWithoutAfterOverrideInheritsParentAfter(): void
    {
        $response = Harness::http('GET', '/lifecycle/inheritance/inherits-after');

        self::assertSame(
            ['trace' => ['parent-after']],
            json_decode($response->pack(), true)
        );
    }
}
