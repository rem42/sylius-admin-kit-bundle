<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\DependencyInjection;

/**
 * @internal
 */
final class BundleTest extends BundleTestCase
{
    public function testNothingIsRegisteredByDefault(): void
    {
        $container = $this->buildContainer([]);

        self::assertSame([], $this->hooks($container));

        foreach ($container->getServiceIds() as $id) {
            self::assertStringStartsNotWith('rem42_sylius_admin_kit.', $id);
        }
    }

    public function testUnknownThemeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->buildContainer(['theme' => 'nope']);
    }
}
