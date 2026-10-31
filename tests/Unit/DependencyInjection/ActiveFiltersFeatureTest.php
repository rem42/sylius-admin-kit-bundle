<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class ActiveFiltersFeatureTest extends BundleTestCase
{
    private const SERVICE = 'rem42_sylius_admin_kit.twig.extension.active_filters';

    private const HOOK = 'sylius_admin.common.index.content.grid';

    public function testItRegistersTheServicesAndTheHookable(): void
    {
        $container = $this->buildContainer(['active_filters' => ['enabled' => true]]);
        $hookable = $this->hookable($container);

        self::assertTrue($container->has(self::SERVICE));
        self::assertTrue($container->has('rem42_sylius_admin_kit.active_filters.criterion_formatter'));
        self::assertIsArray($hookable);
        self::assertSame('@Rem42SyliusAdminKit/bootstrap/active_filters/bar.html.twig', $hookable['template']);
        self::assertSame(50, $hookable['priority']);
    }

    public function testWithShadcnAdminUiItHooksIntoTheFiltersPanel(): void
    {
        $container = $this->buildContainer(
            ['active_filters' => ['enabled' => true]],
            ['SyliusAdminUiBundle' => true, 'SyliusTwigHooksBundle' => true, 'Rem42ShadcnAdminUiBundle' => true],
        );
        $hooks = $this->hooks($container);

        self::assertSame(['filters' => ['configuration' => ['open' => false, 'indicator' => false]]], $hooks[self::HOOK] ?? null);
        self::assertSame(
            ['rem42_admin_kit_active_filters' => ['template' => '@Rem42SyliusAdminKit/shadcn/active_filters/count.html.twig']],
            $hooks[self::HOOK . '.filters#trigger'] ?? null,
        );
        self::assertSame(
            ['rem42_admin_kit_active_filters' => ['template' => '@Rem42SyliusAdminKit/shadcn/active_filters/summary.html.twig']],
            $hooks[self::HOOK . '.filters#summary'] ?? null,
        );
    }

    public function testItIsHookedNextToTheQuickFilter(): void
    {
        $container = $this->buildContainer(['quick_filter' => ['enabled' => true], 'active_filters' => ['enabled' => true]]);
        $hook = $this->hooks($container)[self::HOOK] ?? null;

        self::assertIsArray($hook);
        self::assertSame(['rem42_admin_kit_quick_filters', 'rem42_admin_kit_active_filters'], array_keys($hook));
    }

    public function testNothingIsRegisteredWhenDisabled(): void
    {
        $container = $this->buildContainer(['active_filters' => ['enabled' => false]]);

        self::assertFalse($container->has(self::SERVICE));
        self::assertSame([], $this->hooks($container));
    }

    /**
     * @return array<mixed>|null
     */
    private function hookable(ContainerBuilder $container): ?array
    {
        $hook = $this->hooks($container)[self::HOOK] ?? null;
        $hookable = \is_array($hook) ? $hook['rem42_admin_kit_active_filters'] ?? null : null;

        return \is_array($hookable) ? $hookable : null;
    }
}
