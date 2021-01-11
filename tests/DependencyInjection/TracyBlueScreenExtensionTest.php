<?php

declare(strict_types=1);

namespace Cdn77\TracyBlueScreenBundle\Tests\DependencyInjection;

use Cdn77\TracyBlueScreenBundle\DependencyInjection\Exception\TwigBundleRequired;
use Cdn77\TracyBlueScreenBundle\DependencyInjection\TracyBlueScreenExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Tracy\BlueScreen;

use function array_key_exists;
use function assert;
use function sprintf;
use function strpos;
use function var_export;

final class TracyBlueScreenExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @param mixed[] $configuration        format: extensionAlias(string) => configuration(mixed[])
     * @param mixed[] $minimalConfiguration format: extensionAlias(string) => configuration(mixed[])
     */
    public static function loadExtensionsToContainer(
        ContainerBuilder $container,
        array $configuration = [],
        array $minimalConfiguration = []
    ) : void {
        $configurations = [];
        foreach ($container->getExtensions() as $extensionAlias => $extension) {
            $configurations[$extensionAlias] = [];
            if (array_key_exists($extensionAlias, $minimalConfiguration)) {
                $container->loadFromExtension($extensionAlias, $minimalConfiguration[$extensionAlias]);
                $configurations[$extensionAlias][] = $minimalConfiguration[$extensionAlias];
            }

            if (! array_key_exists($extensionAlias, $configuration)) {
                continue;
            }

            $container->loadFromExtension($extensionAlias, $configuration[$extensionAlias]);
            $configurations[$extensionAlias][] = $configuration[$extensionAlias];
        }

        foreach ($container->getExtensions() as $extensionAlias => $extension) {
            if (! ($extension instanceof PrependExtensionInterface)) {
                continue;
            }

            $extension->prepend($container);
        }

        foreach ($container->getExtensions() as $extensionAlias => $extension) {
            $extension->load($configurations[$extensionAlias], $container);
        }
    }

    public function setUp() : void
    {
        parent::setUp();

        $this->setParameter('kernel.project_dir', __DIR__);
        $this->setParameter('kernel.logs_dir', __DIR__);
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

    public function testDependsOnTwigBundle() : void
    {
        $containerBuilder = new ContainerBuilder();
        $extension = new TracyBlueScreenExtension();

        $this->expectException(TwigBundleRequired::class);
        $extension->prepend($containerBuilder);
    }

    public function testOnlyAddCollapsePaths() : void
    {
        $this->loadExtensions();

        $this->assertContainerBuilderHasService(
            'cdn77.tracy_blue_screen.tracy.blue_screen.default',
            BlueScreen::class
        );

        $blueScreen = $this->container->get('cdn77.tracy_blue_screen.tracy.blue_screen.default');
        assert($blueScreen instanceof BlueScreen);

        $collapsePaths = $blueScreen->collapsePaths;

        $this->assertArrayContainsStringPart('/bootstrap.php.cache', $collapsePaths);
        $this->assertArrayContainsStringPart('/tests-cache-dir', $collapsePaths);
        $this->assertArrayContainsStringPart('/vendor', $collapsePaths);
    }

    public function testCollapseCacheDirsByDefault() : void
    {
        $this->loadExtensions();

        $this->assertContainerBuilderHasParameter('cdn77.tracy_blue_screen.blue_screen.collapse_paths');
        $collapsePaths = $this->container->getParameter('cdn77.tracy_blue_screen.blue_screen.collapse_paths');

        $this->assertArrayContainsStringPart('/bootstrap.php.cache', $collapsePaths);
        $this->assertArrayContainsStringPart('/tests-cache-dir', $collapsePaths);
    }

    public function testSetCollapseDirs() : void
    {
        $paths = [
            __DIR__ . '/foobar',
        ];

        $this->loadExtensions(
            [
                'tracy_blue_screen' => [
                    'blue_screen' => ['collapse_paths' => $paths],
                ],
            ]
        );

        $this->assertContainerBuilderHasParameter('cdn77.tracy_blue_screen.blue_screen.collapse_paths');
        $collapsePaths = $this->container->getParameter('cdn77.tracy_blue_screen.blue_screen.collapse_paths');

        self::assertEquals($paths, $collapsePaths);
    }

    public function testEmptyCollapseDirs() : void
    {
        $this->loadExtensions(
            [
                'tracy_blue_screen' => [
                    'blue_screen' => [
                        'collapse_paths' => [],
                    ],
                ],
            ]
        );

        $this->assertContainerBuilderHasParameter('cdn77.tracy_blue_screen.blue_screen.collapse_paths');
        $collapsePaths = $this->container->getParameter('cdn77.tracy_blue_screen.blue_screen.collapse_paths');

        self::assertEmpty($collapsePaths);
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
        self::loadExtensionsToContainer($this->container, $configuration, $this->getMinimalConfiguration());
    }

    /** @param string[] $array */
    private function assertArrayContainsStringPart(string $string, array $array) : void
    {
        $found = false;
        foreach ($array as $item) {
            if (strpos($item, $string) !== false) {
                $found = true;

                break;
            }
        }

        self::assertTrue(
            $found,
            sprintf('%s not found in any elements of the given %s', $string, var_export($array, true))
        );
    }
}
