<?php

declare(strict_types=1);

namespace Cdn77\TracyBlueScreenBundle;

use Cdn77\TracyBlueScreenBundle\DependencyInjection\TracyBlueScreenExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class TracyBlueScreenBundleTest extends TestCase
{
    public function testRegistersTwig() : void
    {
        $containerBuilder = new ContainerBuilder();
        $bundle = new TracyBlueScreenBundle();
        $bundle->build($containerBuilder);

        self::assertTrue($containerBuilder->hasExtension(TracyBlueScreenExtension::TWIG_BUNDLE_ALIAS));
    }
}
