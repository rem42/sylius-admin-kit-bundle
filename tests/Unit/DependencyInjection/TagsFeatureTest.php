<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class TagsFeatureTest extends BundleTestCase
{
    private const SERVICE = 'rem42_sylius_admin_kit.form.type.tags';

    private const BUNDLES = [
        'FrameworkBundle' => true,
        'TwigBundle' => true,
        'StimulusBundle' => true,
        'SyliusAdminUiBundle' => true,
        'SyliusTwigHooksBundle' => true,
    ];

    public function testItRegistersTheFormTypeTheFormThemeAndTheController(): void
    {
        $container = $this->buildContainer(['tags' => ['enabled' => true]], self::BUNDLES);

        self::assertTrue($container->has(self::SERVICE));
        self::assertArrayHasKey('form.type', $container->getDefinition(self::SERVICE)->getTags());
        self::assertContains('@Rem42SyliusAdminKit/bootstrap/tags/form_theme.html.twig', $this->formThemes($container));
        $assetMapper = $this->merged($container, 'framework')['asset_mapper'] ?? null;
        self::assertIsArray($assetMapper);
        self::assertContains('@rem42/sylius-admin-kit', $this->list($assetMapper['paths'] ?? []));
        self::assertContains('%kernel.project_dir%/assets/controllers', $this->list($this->merged($container, 'stimulus')['controller_paths'] ?? []));
    }

    public function testTheFormThemeFollowsTheShadcnTheme(): void
    {
        $container = $this->buildContainer(['tags' => ['enabled' => true]], self::BUNDLES + ['Rem42ShadcnAdminUiBundle' => true]);

        self::assertContains('@Rem42SyliusAdminKit/shadcn/tags/form_theme.html.twig', $this->formThemes($container));
    }

    public function testItRequiresTheStimulusBundle(): void
    {
        $bundles = self::BUNDLES;
        unset($bundles['StimulusBundle']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('symfony/stimulus-bundle');

        $this->buildContainer(['tags' => ['enabled' => true]], $bundles);
    }

    public function testNothingIsRegisteredWhenDisabled(): void
    {
        $container = $this->buildContainer(['tags' => ['enabled' => false]], self::BUNDLES);

        self::assertFalse($container->has(self::SERVICE));
        self::assertSame([], $this->formThemes($container));
        self::assertSame([], $container->getExtensionConfig('stimulus'));
    }

    /**
     * @return list<mixed>
     */
    private function formThemes(ContainerBuilder $container): array
    {
        return $this->list($this->merged($container, 'twig')['form_themes'] ?? []);
    }

    /**
     * @return list<mixed>
     */
    private function list(mixed $value): array
    {
        return \is_array($value) ? array_values($value) : [];
    }

    /**
     * @return array<mixed>
     */
    private function merged(ContainerBuilder $container, string $extension): array
    {
        return array_merge_recursive([], ...$container->getExtensionConfig($extension));
    }
}
