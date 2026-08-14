<?php

namespace Tests\integration\Routing\FastRouteRouter;

use PHPUnit\Framework\TestCase;
use Tests\integration\Harness;

class RegexTest extends TestCase
{
    public function testLowercaseLettersMatch(): void
    {
        $response = Harness::http('GET', '/regex/letters/abc');

        self::assertSame('{"letters":"abc"}', $response->pack());
    }

    public function testLowercaseLettersRejectDigits(): void
    {
        $response = Harness::http('GET', '/regex/letters/abc123');

        self::assertNull($response);
        self::assertSame(404, http_response_code());
    }

    public function testLowercaseLettersRejectUppercase(): void
    {
        $response = Harness::http('GET', '/regex/letters/ABC');

        self::assertNull($response);
        self::assertSame(404, http_response_code());
    }

    public function testSlugAcceptsMixedCharacters(): void
    {
        $response = Harness::http('GET', '/regex/slug/hello-World_42');

        self::assertSame('{"slug":"hello-World_42"}', $response->pack());
    }

    public function testSlugRejectsSpaces(): void
    {
        $response = Harness::http('GET', '/regex/slug/has%20space');

        self::assertNull($response);
        self::assertSame(404, http_response_code());
    }

    public function testDatePatternMatches(): void
    {
        $response = Harness::http('GET', '/regex/date/2026-05-04');

        self::assertSame('{"date":"2026-05-04"}', $response->pack());
    }

    public function testDatePatternRejectsWrongShape(): void
    {
        $response = Harness::http('GET', '/regex/date/not-a-date');

        self::assertNull($response);
        self::assertSame(404, http_response_code());
    }

    public function testDatePatternRejectsWrongDigitCount(): void
    {
        $response = Harness::http('GET', '/regex/date/26-5-4');

        self::assertNull($response);
        self::assertSame(404, http_response_code());
    }

    public function testOptionalSegmentAbsent(): void
    {
        $response = Harness::http('GET', '/regex/optional');

        self::assertSame('{"page":null}', $response->pack());
    }

    public function testOptionalSegmentPresent(): void
    {
        $response = Harness::http('GET', '/regex/optional/5');

        self::assertSame('{"page":"5"}', $response->pack());
    }

    public function testOptionalSegmentRejectsNonMatchingValue(): void
    {
        $response = Harness::http('GET', '/regex/optional/abc');

        self::assertNull($response);
        self::assertSame(404, http_response_code());
    }
}
