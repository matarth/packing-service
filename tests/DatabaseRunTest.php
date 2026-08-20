<?php

declare(strict_types=1);

namespace App\Tests;

final class DatabaseRunTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (
            [
                [2.5, 3.0, 1.0, 20.0],
                [4.0, 4.0, 4.0, 20.0],
                [2.0, 2.0, 10.0, 20.0],
                [5.5, 6.0, 7.5, 30.0],
                [9.0, 9.0, 9.0, 30.0],
            ] as [$width, $height, $length, $maxWeight]
        ) {
            $this->packagingBuilder
                ->withWidth($width)
                ->withHeight($height)
                ->withLength($length)
                ->withMaxWeight($maxWeight)
                ->build();
        }
    }

    public function testAcceptsValidJsonInput(): void
    {
        $result = ApplicationRunner::run('{"products":[{"width":1,"height":2,"length":3,"weight":4}]}');

        self::assertSame(0, $result['exitCode'], $result['output']);
        /** @var array{error: mixed, data: mixed} $response */
        $response = json_decode($result['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertNull($response['error']);
        self::assertIsArray($response['data']);
        self::assertArrayHasKey('box', $response['data']);
    }
}
