<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Rem42\SyliusAdminKit\Rem42SyliusAdminKitBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

abstract class BundleTestCase extends TestCase
{
    /**
     * Mimics the kernel for this bundle: registers its extension, prepends the given configuration, then runs
     * prepend() and load(). The container is not compiled.
     *
     * @param array<string, mixed>       $config
     * @param array<string, string|true> $bundles    kernel.bundles, name => class (or any placeholder)
     * @param array<string, scalar|null> $parameters extra container parameters, set before the extension is prepended
     */
    protected function buildContainer(array $config, array $bundles = ['SyliusAdminUiBundle' => true, 'SyliusTwigHooksBundle' => true], array $parameters = []): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', $bundles);
        $container->setParameter('kernel.bundles_metadata', []);
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.project_dir', \dirname(__DIR__, 3));
        $container->setParameter('kernel.cache_dir', sys_get_temp_dir() . '/rem42_sylius_admin_kit/cache');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir() . '/rem42_sylius_admin_kit/build');

        foreach ($parameters as $name => $value) {
            $container->setParameter($name, $value);
        }

        $extension = (new Rem42SyliusAdminKitBundle())->getContainerExtension();
        self::assertInstanceOf(ExtensionInterface::class, $extension);
        self::assertInstanceOf(PrependExtensionInterface::class, $extension);

        $container->registerExtension($extension);
        $container->prependExtensionConfig($extension->getAlias(), $config);

        $extension->prepend($container);

        // Like MergeExtensionConfigurationPass, which resolves parameters before load() (but after prepend()).
        /** @var list<array<string, mixed>> $configs */
        $configs = $container->getParameterBag()->resolveValue($container->getExtensionConfig($extension->getAlias()));
        $extension->load($configs, $container);

        return $container;
    }

    /**
     * Merged "hooks" of every "sylius_twig_hooks" extension config.
     *
     * @return array<mixed>
     */
    protected function hooks(ContainerBuilder $container): array
    {
        $hooks = [];

        foreach ($container->getExtensionConfig('sylius_twig_hooks') as $config) {
            if (\is_array($config['hooks'] ?? null)) {
                $hooks = array_replace_recursive($hooks, $config['hooks']);
            }
        }

        return $hooks;
    }
}
