<?php

declare(strict_types=1);

use App\Exception\InvalidInputException;
use App\Facade\PackingFacade;
use App\Input\PackingInput;
use App\Output\ErrorOutput;

require __DIR__ . '/vendor/autoload.php';

try {
    if ($argc !== 2) {
        throw new InvalidInputException('Expected exactly one JSON argument.');
    }

    try {
        $decodedInput = json_decode($argv[1], true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        throw new InvalidInputException('Input must be valid JSON.');
    }

    if (!is_array($decodedInput) || array_is_list($decodedInput)) {
        throw new InvalidInputException('Input must be a JSON object.');
    }


    $input = PackingInput::fromArray($decodedInput);

    /** @var PackingFacade $facade */
    $facade = (require __DIR__ . '/src/container.php')->get(PackingFacade::class);
    $output = $facade->findSmallestBox($input);
    $exitCode = $output instanceof ErrorOutput ? 1 : 0;
} catch (InvalidInputException $exception) {
    $output = new ErrorOutput('invalid_input', $exception->getMessage());
    $exitCode = 1;
} catch (\Throwable) {
    $output = new ErrorOutput('operational_error', 'Unable to calculate packaging at this time.');
    $exitCode = 1;
}

echo json_encode($output, JSON_THROW_ON_ERROR) . PHP_EOL;

exit($exitCode);
