<?php

declare(strict_types=1);

namespace App\Facade;

use App\DTO\PackagingDTO;
use App\DTO\PackingRequestDTO;
use App\Exception\InvalidInputException;
use App\Exception\NoPackagingFitsException;
use App\Exception\PackingProviderUnavailableException;
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
            $packagings = $this->packagingRepository->findPotentiallyFitting($input);
            if ($packagings === []) {
                throw new NoPackagingFitsException('No available packaging can fit the products.');
            }

            $request = new PackingRequestDTO(
                packingInput: $input,
                packagings: array_map(
                    PackagingDTO::withNormalizedRotation(...),
                    $packagings,
                ),
            );
            $result = $this->packingService->findSmallestBox($request);

            return new SuccessOutput($result);
        } catch (InvalidInputException $exception) {
            return new ErrorOutput('invalid_input', $exception->getMessage());
        } catch (NoPackagingFitsException $exception) {
            return new ErrorOutput('no_packaging_fits', $exception->getMessage());
        } catch (PackingProviderUnavailableException $exception) {
            return new ErrorOutput('packing_provider_unavailable', $exception->getMessage());
        } catch (\Throwable) {
            return new ErrorOutput('operational_error', 'Unable to calculate packaging at this time.');
        }
    }
}
