<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\DependencyInjection;

/**
 * @internal
 */
final class EntityFilterFeatureTest extends BundleTestCase
{
    private const SERVICE = 'rem42_sylius_admin_kit.form.extension.entity_filter';

    public function testItRegistersTheFormTypeExtension(): void
    {
        $container = $this->buildContainer(
            ['entity_filter_autocomplete' => ['enabled' => true]],
            ['SyliusAdminUiBundle' => true, 'SyliusTwigHooksBundle' => true, 'AutocompleteBundle' => true],
        );

        self::assertTrue($container->has(self::SERVICE));
        self::assertArrayHasKey('form.type_extension', $container->getDefinition(self::SERVICE)->getTags());
    }

    public function testItRequiresTheAutocompleteBundle(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "entity_filter_autocomplete" feature of rem42_sylius_admin_kit requires symfony/ux-autocomplete: install it and enable its AutocompleteBundle.');

        $this->buildContainer(['entity_filter_autocomplete' => ['enabled' => true]]);
    }

    public function testNothingIsRegisteredWhenDisabled(): void
    {
        $container = $this->buildContainer(['entity_filter_autocomplete' => ['enabled' => false]]);

        self::assertFalse($container->has(self::SERVICE));
    }
}
