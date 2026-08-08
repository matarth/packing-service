<?php

declare(strict_types=1);

namespace App\Tests\Facade;

use App\Exception\InvalidInput;
use App\Exception\NoPackagingAvailable;
use App\Exception\PackingProviderUnavailable;
use App\Facade\PackingFacade;
use App\Input\PackingInput;
use App\Service\PackingService\PackingBox;
use App\Service\PackingService\PackingResult;
use App\Tests\ServiceBuilder;
use PHPUnit\Framework\TestCase;

final class PackingFacadeTest extends TestCase
{
}
