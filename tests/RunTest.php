<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;

final class RunTest extends TestCase
{
    public function testReturnsStructuredErrorForMalformedJson(): void
    {
        $result = ApplicationRunner::run('{');

        self::assertSame(1, $result['exitCode'], $result['output']);
        self::assertSame([
            'data' => null,
            'error' => ['code' => 'invalid_input', 'message' => 'Input must be valid JSON.'],
        ], json_decode($result['output'], true, 512, JSON_THROW_ON_ERROR));
    }

    public function testReturnsStructuredErrorForInvalidProduct(): void
    {
        $result = ApplicationRunner::run('{"products":[{"width":0,"height":2,"length":3,"weight":4}]}');

        self::assertSame(1, $result['exitCode'], $result['output']);
        self::assertSame([
            'data' => null,
            'error' => ['code' => 'invalid_input', 'message' => 'Width must be greater than 0'],
        ], json_decode($result['output'], true, 512, JSON_THROW_ON_ERROR));
    }

    public function testReturnsStructuredErrorForAProductThatIsNotAnObject(): void
    {
        $result = ApplicationRunner::run('{"products":[true]}');

        self::assertSame(1, $result['exitCode'], $result['output']);
        self::assertSame([
            'data' => null,
            'error' => ['code' => 'invalid_input', 'message' => 'Each product must be an object.'],
        ], json_decode($result['output'], true, 512, JSON_THROW_ON_ERROR));
    }
}
