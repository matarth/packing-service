<?php

declare(strict_types=1);

namespace App\Facade;

use App\DTO\PackagingDTO;
use App\DTO\PackingRequestDTO;
use App\Exception\InvalidInputException;
use App\Input\PackingInput;
use App\Output\AbstractOutput;
use App\Output\ErrorOutput;
use App\Output\SuccessOutput;
use App\Repository\PackagingRepository;
use App\Service\PackingService\PackingServiceInterface;

final class PackingFacade
{
    public function __construct(
        private PackingServiceInterface $packingService,
        private PackagingRepository $packagingRepository,
    ) {
    }

    public function findSmallestBox(PackingInput $input): AbstractOutput
    {
        try {
            $request = new PackingRequestDTO(
                packingInput: $input,
                packagings: array_map(
                    PackagingDTO::fromEntity(...),
                    $this->packagingRepository->findAll(),
                ),
            );
            $result = $this->packingService->findSmallestBox($request);

            return new SuccessOutput($result);
        } catch (InvalidInputException $exception) {
            return new ErrorOutput('invalid_input', $exception->getMessage());
        }
    }
}
