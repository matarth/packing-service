<?php

declare(strict_types=1);

namespace App\Tests\Facade;

use App\Facade\PackingFacade;
use App\Input\PackingInput;
use PHPUnit\Framework\TestCase;

final class PackingFacadeTest extends TestCase
{
    public function testMapsTypedInputToSuccessOutput(): void
    {
        $input = PackingInput::fromArray([
            'products' => [[
                'width' => 1,
                'height' => 2,
                'length' => 3,
                'weight' => 4,
            ]],
        ]);

        $output = (new PackingFacade())->run($input);

        $response = $output->jsonSerialize();

        self::assertNull($response['error']);
        self::assertIsArray($response['data']);
        self::assertArrayHasKey('box', $response['data']);
        self::assertIsArray($response['data']['box']);
        self::assertArrayHasKey('width', $response['data']['box']);
        self::assertArrayHasKey('height', $response['data']['box']);
        self::assertArrayHasKey('length', $response['data']['box']);
    }
}
