<?php

declare(strict_types=1);

namespace Rem42\SyliusAdminKit\Theme;

final class ThemeResolver
{
    public const array BUILTIN = [
        'shadcn' => '@Rem42SyliusAdminKit/shadcn',
        'bootstrap' => '@Rem42SyliusAdminKit/bootstrap',
    ];

    /**
     * @param array<string, string> $customThemes  theme name => templates directory
     * @param array<string, mixed>  $kernelBundles the "kernel.bundles" parameter
     */
    public static function resolve(?string $theme, array $customThemes, array $kernelBundles): ResolvedTheme
    {
        $theme ??= \array_key_exists('Rem42ShadcnAdminUiBundle', $kernelBundles) ? 'shadcn' : 'bootstrap';

        foreach (array_keys($customThemes) as $name) {
            if (isset(self::BUILTIN[$name])) {
                throw new \InvalidArgumentException(\sprintf('The admin kit theme name "%s" is reserved for a built-in theme, pick another name for your custom theme.', $name));
            }
        }

        $available = self::BUILTIN + $customThemes;

        if (!isset($available[$theme])) {
            throw new \InvalidArgumentException(\sprintf('Unknown admin kit theme "%s", expected one of "%s".', $theme, implode('", "', array_keys($available))));
        }

        return new ResolvedTheme($theme, $available[$theme]);
    }
}
