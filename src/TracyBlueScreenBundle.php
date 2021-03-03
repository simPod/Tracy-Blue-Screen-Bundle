<?php

declare(strict_types=1);

namespace Cdn77\TracyBlueScreenBundle;

use Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class TracyBlueScreenBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);

        $container->registerExtension(new TwigExtension());
    }
}
