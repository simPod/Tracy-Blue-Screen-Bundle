<?php

declare(strict_types=1);

namespace Cdn77\TracyBlueScreenBundle\DependencyInjection;

use Cdn77\TracyBlueScreenBundle\DependencyInjection\Exception\TwigBundleRequired;
use Cdn77\TracyBlueScreenBundle\TracyBlueScreenBundle;
use ReflectionClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

use function assert;
use function dirname;
use function is_bool;
use function is_string;

//phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
final class TracyBlueScreenExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    public const CONTAINER_PARAMETER_BLUE_SCREEN_COLLAPSE_PATHS = 'cdn77.tracy_blue_screen.blue_screen.collapse_paths';
    public const CONTAINER_PARAMETER_CONSOLE_BROWSER = 'cdn77.tracy_blue_screen.console.browser';
    public const CONTAINER_PARAMETER_CONSOLE_LISTENER_PRIORITY = 'cdn77.tracy_blue_screen.console.listener_priority';
    public const CONTAINER_PARAMETER_CONSOLE_LOG_DIRECTORY = 'cdn77.tracy_blue_screen.console.log_directory';
    public const CONTAINER_PARAMETER_CONTROLLER_LISTENER_PRIORITY = 'cdn77.tracy_blue_screen.controller.listener_priority';
    public const TWIG_BUNDLE_ALIAS = 'twig';
    private const TWIG_TEMPLATES_NAMESPACE = 'Twig';

    public function prepend(ContainerBuilder $container) : void
    {
        if (! $container->hasExtension(self::TWIG_BUNDLE_ALIAS)) {
            throw new TwigBundleRequired();
        }

        $container->loadFromExtension(
            self::TWIG_BUNDLE_ALIAS,
            [
                'paths' => [
                    $this->getTemplatesDirectory() => self::TWIG_TEMPLATES_NAMESPACE,
                ],
            ]
        );
    }

    /** @param mixed[] $mergedConfig */
    public function loadInternal(array $mergedConfig, ContainerBuilder $container) : void
    {
        $container->setParameter(
            self::CONTAINER_PARAMETER_BLUE_SCREEN_COLLAPSE_PATHS,
            $mergedConfig[Configuration::SECTION_BLUE_SCREEN][Configuration::PARAMETER_COLLAPSE_PATHS]
        );
        $container->setParameter(
            self::CONTAINER_PARAMETER_CONSOLE_BROWSER,
            $mergedConfig[Configuration::SECTION_CONSOLE][Configuration::PARAMETER_CONSOLE_BROWSER]
        );
        $container->setParameter(
            self::CONTAINER_PARAMETER_CONSOLE_LISTENER_PRIORITY,
            $mergedConfig[Configuration::SECTION_CONSOLE][Configuration::PARAMETER_CONSOLE_LISTENER_PRIORITY]
        );
        $container->setParameter(
            self::CONTAINER_PARAMETER_CONSOLE_LOG_DIRECTORY,
            $mergedConfig[Configuration::SECTION_CONSOLE][Configuration::PARAMETER_CONSOLE_LOG_DIRECTORY]
        );
        $container->setParameter(
            self::CONTAINER_PARAMETER_CONTROLLER_LISTENER_PRIORITY,
            $mergedConfig[Configuration::SECTION_CONTROLLER][Configuration::PARAMETER_CONTROLLER_LISTENER_PRIORITY]
        );

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
        $loader->load('services.yml');

        $environment = $container->getParameter('kernel.environment');
        assert(is_string($environment));
        $debug = $container->getParameter('kernel.debug');
        assert(is_bool($debug));

        if (
            $this->isEnabled(
                $mergedConfig[Configuration::SECTION_CONSOLE][Configuration::PARAMETER_CONSOLE_ENABLED],
                $environment,
                $debug
            )
        ) {
            $loader->load('console_listener.yml');
        }

        if (
            ! $this->isEnabled(
                $mergedConfig[Configuration::SECTION_CONTROLLER][Configuration::PARAMETER_CONTROLLER_ENABLED],
                $environment,
                $debug
            )
        ) {
            return;
        }

        $loader->load('controller_listener.yml');
    }

    /** @param mixed[] $config */
    public function getConfiguration(array $config, ContainerBuilder $container) : Configuration
    {
        $kernelProjectDir = $container->getParameter('kernel.project_dir');
        $kernelLogsDir = $container->getParameter('kernel.logs_dir');
        $kernelCacheDir = $container->getParameter('kernel.cache_dir');
        assert(is_string($kernelProjectDir));
        assert(is_string($kernelLogsDir));
        assert(is_string($kernelCacheDir));

        return new Configuration(
            $this->getAlias(),
            $kernelProjectDir,
            $kernelLogsDir,
            $kernelCacheDir
        );
    }

    private function getTemplatesDirectory() : string
    {
        $bundleClassReflection = new ReflectionClass(TracyBlueScreenBundle::class);
        $fileName = $bundleClassReflection->getFileName();
        assert($fileName !== false);

        $srcDirectoryPath = dirname($fileName);

        return $srcDirectoryPath . '/Resources/views';
    }

    private function isEnabled(?bool $configOption, string $environment, bool $debug) : bool
    {
        if ($configOption === null) {
            return $environment === 'dev' && $debug === true;
        }

        return $configOption;
    }
}
