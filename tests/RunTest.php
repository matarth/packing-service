<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;

final class RunTest extends TestCase
{
    public function testAcceptsValidJsonInput(): void
    {
        $result = $this->runCommand('{"products":[{"width":1,"height":2,"length":3,"weight":4}]}');

        self::assertSame(0, $result['exitCode'], $result['output']);
        $response = json_decode($result['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertNull($response['error']);
        self::assertIsArray($response['data']);
        self::assertArrayHasKey('box', $response['data']);
    }

    public function testReturnsStructuredErrorForMalformedJson(): void
    {
        $result = $this->runCommand('{');

        self::assertSame(1, $result['exitCode'], $result['output']);
        self::assertSame([
            'data' => null,
            'error' => ['code' => 'invalid_input', 'message' => 'Input must be valid JSON.'],
        ], json_decode($result['output'], true, 512, JSON_THROW_ON_ERROR));
    }

    public function testReturnsStructuredErrorForInvalidProduct(): void
    {
        $result = $this->runCommand('{"products":[{"width":0,"height":2,"length":3,"weight":4}]}');

        self::assertSame(1, $result['exitCode'], $result['output']);
        self::assertSame([
            'data' => null,
            'error' => ['code' => 'invalid_input', 'message' => 'Width must be greater than 0'],
        ], json_decode($result['output'], true, 512, JSON_THROW_ON_ERROR));
    }

    public function testReturnsStructuredErrorForAProductThatIsNotAnObject(): void
    {
        $result = $this->runCommand('{"products":[true]}');

        self::assertSame(1, $result['exitCode'], $result['output']);
        self::assertSame([
            'data' => null,
            'error' => ['code' => 'invalid_input', 'message' => 'Each product must be an object.'],
        ], json_decode($result['output'], true, 512, JSON_THROW_ON_ERROR));
    }

    /** @return array{exitCode: int, output: string} */
    private function runCommand(string $input): array
    {
        $command = sprintf(
            '%s %s %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__DIR__ . '/../run.php'),
            escapeshellarg($input)
        );
        exec($command, $output, $exitCode);

        return ['exitCode' => $exitCode, 'output' => implode("\n", $output)];
    }
}
