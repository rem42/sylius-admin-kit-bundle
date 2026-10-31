<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\DependencyInjection;

/**
 * @internal
 */
final class EnvironmentFeatureTest extends BundleTestCase
{
    private const SERVICE = 'rem42_sylius_admin_kit.twig.extension.environment';

    private const STYLESHEETS = 'sylius_admin.base#stylesheets';

    private const NAVBAR = 'sylius_admin.common.component.navbar.items';

    private const LOGIN = 'sylius_admin.security.login.page';

    public function testItRegistersTheServiceAndTheThreeHookables(): void
    {
        $container = $this->buildContainer(['environment' => ['enabled' => true]]);
        $hooks = $this->hooks($container);

        self::assertTrue($container->has(self::SERVICE));
        self::assertSame('@Rem42SyliusAdminKit/bootstrap/environment/strip.html.twig', $this->hookable($hooks, self::STYLESHEETS)['template']);
        self::assertSame('@Rem42SyliusAdminKit/bootstrap/environment/badge.html.twig', $this->hookable($hooks, self::NAVBAR)['template']);
        self::assertSame(['placement' => 'navbar'], $this->hookable($hooks, self::NAVBAR)['configuration']);
        self::assertSame('@Rem42SyliusAdminKit/bootstrap/environment/badge.html.twig', $this->hookable($hooks, self::LOGIN)['template']);
        self::assertSame(['placement' => 'login'], $this->hookable($hooks, self::LOGIN)['configuration']);
    }

    public function testTheHooksUseTheShadcnTemplatesWhenTheShadcnBundleIsLoaded(): void
    {
        $container = $this->buildContainer(
            ['environment' => ['enabled' => true]],
            ['SyliusAdminUiBundle' => true, 'SyliusTwigHooksBundle' => true, 'Rem42ShadcnAdminUiBundle' => true],
        );
        $hooks = $this->hooks($container);

        self::assertSame('@Rem42SyliusAdminKit/shadcn/environment/strip.html.twig', $this->hookable($hooks, self::STYLESHEETS)['template']);
        self::assertSame('@Rem42SyliusAdminKit/shadcn/environment/badge.html.twig', $this->hookable($hooks, self::NAVBAR)['template']);
        self::assertSame('@Rem42SyliusAdminKit/shadcn/environment/badge.html.twig', $this->hookable($hooks, self::LOGIN)['template']);
    }

    public function testTheHooksHaveTheirPriorities(): void
    {
        $hooks = $this->hooks($this->buildContainer(['environment' => ['enabled' => true]]));

        self::assertSame(100, $this->hookable($hooks, self::NAVBAR)['priority']);
        self::assertSame(1000, $this->hookable($hooks, self::LOGIN)['priority']);
    }

    public function testEachPlacementCanBeTurnedOff(): void
    {
        $hooks = $this->hooks($this->buildContainer(['environment' => ['enabled' => true, 'navbar' => false, 'login' => false]]));

        self::assertSame([self::STYLESHEETS], array_keys($hooks));
    }

    public function testTheStripCanBeTurnedOff(): void
    {
        $hooks = $this->hooks($this->buildContainer(['environment' => ['enabled' => true, 'strip' => false]]));

        self::assertSame([self::NAVBAR, self::LOGIN], array_keys($hooks));
    }

    public function testParametersAreResolvedWhenPrepending(): void
    {
        $container = $this->buildContainer(
            ['environment' => ['enabled' => '%app.flag%', 'navbar' => '%app.off%', 'login' => false]],
            ['SyliusAdminUiBundle' => true, 'SyliusTwigHooksBundle' => true],
            ['app.flag' => true, 'app.off' => false],
        );

        self::assertSame([self::STYLESHEETS], array_keys($this->hooks($container)));
    }

    public function testNothingIsRegisteredWhenDisabled(): void
    {
        $container = $this->buildContainer(['environment' => ['enabled' => false, 'strip' => true]]);

        self::assertFalse($container->has(self::SERVICE));
        self::assertSame([], $this->hooks($container));
    }

    public function testNoHookIsRegisteredWithoutTheTwigHooksBundle(): void
    {
        $container = $this->buildContainer(['environment' => ['enabled' => true]], ['SyliusAdminUiBundle' => true]);

        self::assertSame([], $this->hooks($container));
    }

    /**
     * @param array<mixed> $hooks
     *
     * @return array<mixed>
     */
    private function hookable(array $hooks, string $hook): array
    {
        self::assertIsArray($hooks[$hook] ?? null, \sprintf('Hook "%s" is missing.', $hook));
        self::assertIsArray($hooks[$hook]['rem42_admin_kit_environment'] ?? null);

        return $hooks[$hook]['rem42_admin_kit_environment'];
    }
}
