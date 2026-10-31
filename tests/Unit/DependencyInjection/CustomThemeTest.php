<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\DependencyInjection;

/**
 * @internal
 */
final class CustomThemeTest extends BundleTestCase
{
    public function testEveryHookTemplateComesFromTheCustomTheme(): void
    {
        $container = $this->buildContainer([
            'theme' => 'acme',
            'themes' => ['acme' => '@AcmeAdmin/kit'],
            'quick_filter' => ['enabled' => true],
            'environment' => ['enabled' => true],
            'entity_filter_autocomplete' => ['enabled' => true],
        ], ['SyliusAdminUiBundle' => true, 'SyliusTwigHooksBundle' => true, 'AutocompleteBundle' => true]);

        $templates = [];

        foreach ($this->hooks($container) as $hookables) {
            self::assertIsArray($hookables);

            foreach ($hookables as $hookable) {
                self::assertIsArray($hookable);
                $templates[] = $hookable['template'];
            }
        }

        self::assertCount(4, $templates);

        foreach ($templates as $template) {
            self::assertIsString($template);
            self::assertStringStartsWith('@AcmeAdmin/kit/', $template);
        }

        self::assertSame('acme', $container->getParameter('rem42_sylius_admin_kit.theme'));
        self::assertSame('@AcmeAdmin/kit', $container->getParameter('rem42_sylius_admin_kit.template_prefix'));
    }

    public function testAnUnknownThemeIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown admin kit theme "nope"');

        $this->buildContainer(['theme' => 'nope']);
    }
}
