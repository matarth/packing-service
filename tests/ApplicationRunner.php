<?php

declare(strict_types=1);

namespace App\Tests;

final class ApplicationRunner
{
    /** @return array{exitCode: int, output: string} */
    public static function run(string $input): array
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
