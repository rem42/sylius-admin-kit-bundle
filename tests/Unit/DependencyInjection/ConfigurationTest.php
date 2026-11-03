<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\DependencyInjection;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rem42\SyliusAdminKit\Rem42SyliusAdminKitBundle;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface;

/**
 * @internal
 */
final class ConfigurationTest extends TestCase
{
    public function testEverythingIsDisabledByDefault(): void
    {
        $config = $this->process([]);

        self::assertFalse($config['quick_filter']['enabled']);
        self::assertFalse($config['environment']['enabled']);
        self::assertFalse($config['entity_filter_autocomplete']['enabled']);
        self::assertFalse($config['tags']['enabled']);
        self::assertNull($config['theme']);
        self::assertSame([], $config['themes']);
    }

    public function testEnvironmentDefaults(): void
    {
        $environment = $this->process([])['environment'];

        self::assertSame('%kernel.environment%', $environment['name']);
        self::assertTrue($environment['strip']);
        self::assertTrue($environment['navbar']);
        self::assertTrue($environment['login']);
        self::assertSame(['dev', 'test', 'staging', 'preprod'], array_keys($environment['environments']));
        self::assertSame('oklch(55.3% 0.195 38.402)', $environment['environments']['dev']['color']);
        self::assertSame('#fff', $environment['environments']['dev']['foreground']);
    }

    public function testEnvironmentsAreReplacedNotMerged(): void
    {
        $environment = $this->process(['environment' => ['environments' => ['qa' => ['color' => '#f00']]]])['environment'];

        self::assertSame(['qa'], array_keys($environment['environments']));
    }

    public function testColorIsRequired(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['environment' => ['environments' => ['qa' => []]]]);
    }

    #[DataProvider('provideInvalidColors')]
    public function testColorRejectsCssInjection(string $value): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['environment' => ['environments' => ['qa' => ['color' => $value]]]]);
    }

    #[DataProvider('provideInvalidColors')]
    public function testForegroundRejectsCssInjection(string $value): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['environment' => ['environments' => ['qa' => ['color' => '#f00', 'foreground' => $value]]]]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidColors(): iterable
    {
        yield 'declaration break' => ['red;} body{display:none'];
        yield 'style tag' => ['red</style>'];
        yield 'quotes' => ['"red"'];
    }

    public function testThemeCanBeForced(): void
    {
        self::assertSame('bootstrap', $this->process(['theme' => 'bootstrap'])['theme']);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array{
     *     theme: ?string,
     *     themes: array<string, string>,
     *     quick_filter: array{enabled: bool},
     *     environment: array{enabled: bool, name: string, strip: bool, navbar: bool, login: bool, environments: array<string, array{label: ?string, color: string, foreground: string}>},
     *     entity_filter_autocomplete: array{enabled: bool},
     *     tags: array{enabled: bool},
     * }
     */
    private function process(array $config): array
    {
        $extension = (new Rem42SyliusAdminKitBundle())->getContainerExtension();
        self::assertInstanceOf(ConfigurationExtensionInterface::class, $extension);
        $configs = [$config];
        $configuration = $extension->getConfiguration($configs, new ContainerBuilder());
        self::assertInstanceOf(ConfigurationInterface::class, $configuration);

        /** @var array{theme: ?string, themes: array<string, string>, quick_filter: array{enabled: bool}, environment: array{enabled: bool, name: string, strip: bool, navbar: bool, login: bool, environments: array<string, array{label: ?string, color: string, foreground: string}>}, entity_filter_autocomplete: array{enabled: bool}, tags: array{enabled: bool}} */
        return (new Processor())->processConfiguration($configuration, $configs);
    }
}
