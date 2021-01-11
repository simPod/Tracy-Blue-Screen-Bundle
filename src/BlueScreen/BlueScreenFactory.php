<?php

declare(strict_types=1);

namespace Cdn77\TracyBlueScreenBundle\BlueScreen;

use Tracy\BlueScreen;
use Tracy\Debugger;

use function array_merge;

final class BlueScreenFactory
{
    /** @param string[] $collapsePaths */
    public static function create(array $collapsePaths) : BlueScreen
    {
        $blueScreen = Debugger::getBlueScreen();
        $blueScreen->collapsePaths = array_merge($blueScreen->collapsePaths, $collapsePaths);

        return $blueScreen;
    }
}
