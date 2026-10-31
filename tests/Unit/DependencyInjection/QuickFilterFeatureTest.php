<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\DependencyInjection;

use Rem42\SyliusAdminKit\QuickFilter\QuickFilterProviderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class QuickFilterFeatureTest extends BundleTestCase
{
    private const SERVICE = 'rem42_sylius_admin_kit.twig.extension.quick_filter';

    private const HOOK = 'sylius_admin.common.index.content.grid';

    public function testItRegistersTheServiceAndTheHookable(): void
    {
        $container = $this->buildContainer(['quick_filter' => ['enabled' => true]]);
        $hookable = $this->hookable($container);

        self::assertTrue($container->has(self::SERVICE));
        self::assertIsArray($hookable);
        self::assertSame('@Rem42SyliusAdminKit/bootstrap/quick_filter/tabs.html.twig', $hookable['template']);
        self::assertSame(100, $hookable['priority']);
    }

    public function testTheHookableUsesTheShadcnTemplateWhenTheShadcnBundleIsLoaded(): void
    {
        $container = $this->buildContainer(
            ['quick_filter' => ['enabled' => true]],
            ['SyliusAdminUiBundle' => true, 'SyliusTwigHooksBundle' => true, 'Rem42ShadcnAdminUiBundle' => true],
        );

        self::assertSame('@Rem42SyliusAdminKit/shadcn/quick_filter/tabs.html.twig', $this->hookable($container)['template'] ?? null);
    }

    public function testProvidersAreAutoconfigured(): void
    {
        $container = $this->buildContainer(['quick_filter' => ['enabled' => true]]);
        $autoconfigured = $container->getAutoconfiguredInstanceof();

        self::assertArrayHasKey(QuickFilterProviderInterface::class, $autoconfigured);
        self::assertArrayHasKey('rem42_sylius_admin_kit.quick_filter_provider', $autoconfigured[QuickFilterProviderInterface::class]->getTags());
    }

    public function testNothingIsRegisteredWhenDisabled(): void
    {
        $container = $this->buildContainer(['quick_filter' => ['enabled' => false]]);

        self::assertFalse($container->has(self::SERVICE));
        self::assertSame([], $this->hooks($container));
        self::assertArrayNotHasKey(QuickFilterProviderInterface::class, $container->getAutoconfiguredInstanceof());
    }

    public function testNoHookIsRegisteredWithoutTheTwigHooksBundle(): void
    {
        $container = $this->buildContainer(['quick_filter' => ['enabled' => true]], ['SyliusAdminUiBundle' => true]);

        self::assertSame([], $this->hooks($container));
    }

    /**
     * @return array<mixed>|null
     */
    private function hookable(ContainerBuilder $container): ?array
    {
        $hooks = $this->hooks($container);
        $hook = $hooks[self::HOOK] ?? null;
        $hookable = \is_array($hook) ? $hook['rem42_admin_kit_quick_filters'] ?? null : null;

        return \is_array($hookable) ? $hookable : null;
    }
}
