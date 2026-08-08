<?php

declare(strict_types=1);

namespace App\Facade;

use App\Exception\InvalidInput;
use App\Exception\NoPackagingAvailable;
use App\Exception\PackingProviderUnavailable;
use App\Input\PackingInput;
use App\Output\AbstractOutput;
use App\Output\ErrorOutput;
use App\Output\SuccessOutput;
use App\Service\PackingService\PackingServiceInterface;

final class PackingFacade
{
    public function __construct(
        private PackingServiceInterface $packingService
    ) {
    }

    public function findSmallestBox(PackingInput $input): AbstractOutput
    {
        try {
            $result = $this->packingService->findSmallestBox($input);

            return new SuccessOutput($result);
        } catch (InvalidInput $exception) {
            return new ErrorOutput('invalid_input', $exception->getMessage());
        } catch (PackingProviderUnavailable $exception) {
            return new ErrorOutput('packing_provider_unavailable', $exception->getMessage());
        } catch (NoPackagingAvailable $exception) {
            return new ErrorOutput('no_packaging_available', $exception->getMessage());
        }
    }
}
