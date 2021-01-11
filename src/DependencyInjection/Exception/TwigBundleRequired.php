<?php

declare(strict_types=1);

namespace Cdn77\TracyBlueScreenBundle\DependencyInjection\Exception;

use Exception;
use Throwable;

final class TwigBundleRequired extends Exception
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('TwigBundle must be registered for this bundle to work properly', 0, $previous);
    }
}
