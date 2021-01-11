<?php

declare(strict_types=1);

namespace Cdn77\TracyBlueScreenBundle\Tests\DependencyInjection;

use Cdn77\TracyBlueScreenBundle\BlueScreen\ConsoleBlueScreenErrorListener;
use Cdn77\TracyBlueScreenBundle\DependencyInjection\TracyBlueScreenExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

final class TracyBlueScreenExtensionConsoleTest extends AbstractExtensionTestCase
{
    public function setUp() : void
    {
        parent::setUp();

        $this->setParameter('kernel.project_dir', __DIR__);
        $this->setParameter('kernel.logs_dir', __DIR__ . '/tests-logs-dir');
        $this->setParameter('kernel.cache_dir', __DIR__ . '/tests-cache-dir');
        $this->setParameter('kernel.environment', 'dev');
        $this->setParameter('kernel.debug', true);
        $this->setParameter(
            'kernel.bundles_metadata',
            [
                'TwigBundle' => [
                    'namespace' => 'Symfony\\Bundle\\TwigBundle',
                    'path' => __DIR__,
                ],
            ]
        );
    }

    public function testEnabledByDefault() : void
    {
        $this->loadExtensions();

        $this->assertContainerBuilderHasService(
            'cdn77.tracy_blue_screen.blue_screen.console_blue_screen_error_listener',
            ConsoleBlueScreenErrorListener::class
        );
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'cdn77.tracy_blue_screen.blue_screen.console_blue_screen_error_listener',
            'kernel.event_listener',
            [
                'event' => 'console.error',
                'priority' => '%' . TracyBlueScreenExtension::CONTAINER_PARAMETER_CONSOLE_LISTENER_PRIORITY . '%',
            ]
        );
    }

    public function testDisabled() : void
    {
        $this->loadExtensions(
            [
                'tracy_blue_screen' => [
                    'console' => ['enabled' => false],
                ],
            ]
        );

        $this->assertContainerBuilderNotHasService(
            'cdn77.tracy_blue_screen.blue_screen.console_blue_screen_error_listener'
        );
    }

    public function testEnabled() : void
    {
        $this->loadExtensions(
            [
                'tracy_blue_screen' => [
                    'console' => ['enabled' => true],
                ],
            ]
        );

        $this->assertContainerBuilderHasService(
            'cdn77.tracy_blue_screen.blue_screen.console_blue_screen_error_listener',
            ConsoleBlueScreenErrorListener::class
        );
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'cdn77.tracy_blue_screen.blue_screen.console_blue_screen_error_listener',
            'kernel.event_listener',
            [
                'event' => 'console.error',
                'priority' => '%' . TracyBlueScreenExtension::CONTAINER_PARAMETER_CONSOLE_LISTENER_PRIORITY . '%',
            ]
        );
    }

    public function testDefaultLogsDirIsKernelLogsDir() : void
    {
        $this->loadExtensions();

        $this->assertContainerBuilderHasParameter(
            TracyBlueScreenExtension::CONTAINER_PARAMETER_CONSOLE_LOG_DIRECTORY,
            __DIR__ . '/tests-logs-dir'
        );
    }

    public function testCustomLogsDir() : void
    {
        $this->loadExtensions(
            [
                'tracy_blue_screen' => [
                    'console' => [
                        'log_directory' => __DIR__ . '/foobar',
                    ],
                ],
            ]
        );

        $this->assertContainerBuilderHasParameter(
            TracyBlueScreenExtension::CONTAINER_PARAMETER_CONSOLE_LOG_DIRECTORY,
            __DIR__ . '/foobar'
        );
    }

    public function testDefaultBrowserIsNull() : void
    {
        $this->loadExtensions();

        $this->assertContainerBuilderHasParameter(
            TracyBlueScreenExtension::CONTAINER_PARAMETER_CONSOLE_BROWSER,
            null
        );
    }

    public function testCustomBrowser() : void
    {
        $this->loadExtensions(
            [
                'tracy_blue_screen' => [
                    'console' => ['browser' => 'google-chrome'],
                ],
            ]
        );

        $this->assertContainerBuilderHasParameter(
            TracyBlueScreenExtension::CONTAINER_PARAMETER_CONSOLE_BROWSER,
            'google-chrome'
        );
    }

    public function testConfigureListenerPriority() : void
    {
        $this->loadExtensions(
            [
                'tracy_blue_screen' => [
                    'console' => ['listener_priority' => 123],
                ],
            ]
        );

        $this->assertContainerBuilderHasParameter(
            TracyBlueScreenExtension::CONTAINER_PARAMETER_CONSOLE_LISTENER_PRIORITY,
            123
        );
    }

    /** @return ExtensionInterface[] */
    protected function getContainerExtensions() : array
    {
        return [
            new TracyBlueScreenExtension(),
            new TwigExtension(),
        ];
    }

    /** @param mixed[] $configuration format: extensionAlias(string) => configuration(mixed[]) */
    private function loadExtensions(array $configuration = []) : void
    {
        TracyBlueScreenExtensionTest::loadExtensionsToContainer(
            $this->container,
            $configuration,
            $this->getMinimalConfiguration()
        );
    }
}
