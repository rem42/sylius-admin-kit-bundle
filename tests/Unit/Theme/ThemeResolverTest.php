<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\Theme;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rem42\SyliusAdminKit\Theme\ThemeResolver;

/**
 * @internal
 */
final class ThemeResolverTest extends TestCase
{
    public function testAutoPicksShadcnWhenItsBundleIsLoaded(): void
    {
        $theme = ThemeResolver::resolve(null, [], ['Rem42ShadcnAdminUiBundle' => 'Some\Class']);

        self::assertSame('shadcn', $theme->name);
        self::assertSame('@Rem42SyliusAdminKit/shadcn', $theme->templatePrefix);
    }

    public function testAutoFallsBackToBootstrap(): void
    {
        $theme = ThemeResolver::resolve(null, [], ['SyliusAdminUiBundle' => 'Some\Class']);

        self::assertSame('bootstrap', $theme->name);
        self::assertSame('@Rem42SyliusAdminKit/bootstrap', $theme->templatePrefix);
    }

    public function testBootstrapCanBeForcedWhileShadcnIsLoaded(): void
    {
        $theme = ThemeResolver::resolve('bootstrap', [], ['Rem42ShadcnAdminUiBundle' => 'Some\Class']);

        self::assertSame('bootstrap', $theme->name);
    }

    public function testCustomTheme(): void
    {
        $theme = ThemeResolver::resolve('acme', ['acme' => '@AcmeAdmin/kit'], []);

        self::assertSame('acme', $theme->name);
        self::assertSame('@AcmeAdmin/kit', $theme->templatePrefix);
    }

    #[DataProvider('reservedNames')]
    public function testACustomThemeCannotUseABuiltinName(string $name): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The admin kit theme name "%s" is reserved', $name));

        ThemeResolver::resolve(null, [$name => '@Acme/kit'], []);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function reservedNames(): iterable
    {
        yield 'shadcn' => ['shadcn'];
        yield 'bootstrap' => ['bootstrap'];
    }

    public function testUnknownThemeListsTheAvailableOnes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"shadcn", "bootstrap"');

        ThemeResolver::resolve('nope', [], []);
    }
}
