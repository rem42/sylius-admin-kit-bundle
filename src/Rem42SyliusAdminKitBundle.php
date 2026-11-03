<?php

declare(strict_types=1);

namespace Rem42\SyliusAdminKit;

use Rem42\SyliusAdminKit\Environment\Twig\EnvironmentExtension;
use Rem42\SyliusAdminKit\QuickFilter\QuickFilterProviderInterface;
use Rem42\SyliusAdminKit\Theme\ThemeResolver;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class Rem42SyliusAdminKitBundle extends AbstractBundle
{
    protected string $extensionAlias = 'rem42_sylius_admin_kit';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('theme')
            ->defaultNull()
            ->info('Theme whose templates are used. null = auto: "shadcn" if Rem42ShadcnAdminUiBundle is loaded, "bootstrap" otherwise.')
            ->end()
            ->arrayNode('themes')
            ->info('Custom themes: name => templates directory holding the same files as templates/{shadcn,bootstrap}/.')
            ->useAttributeAsKey('name')
            ->scalarPrototype()->end()
            ->defaultValue([])
            ->end()
            ->arrayNode('quick_filter')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')->defaultFalse()->end()
            ->end()
            ->end()
            ->arrayNode('active_filters')
            ->info('Bar listing the criteria that filter the grid, each removable on its own.')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')->defaultFalse()->end()
            ->end()
            ->end()
            ->arrayNode('environment')
            ->info('Coloured strip on top of every admin page and badge in the navbar and on the login page.')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')->defaultFalse()->end()
            ->scalarNode('name')
            ->info('Current environment, e.g. "%env(default:kernel.environment:APP_STAGE)%".')
            ->defaultValue('%kernel.environment%')
            ->end()
            ->arrayNode('environments')
            ->info('Environments that show the indicator. Any other one (e.g. "prod") shows nothing.')
            ->useAttributeAsKey('name')
            ->performNoDeepMerging()
            ->arrayPrototype()
            ->children()
            ->scalarNode('label')->defaultNull()->info('Defaults to the upper-cased environment name.')->end()
            ->scalarNode('color')
            ->isRequired()
            ->cannotBeEmpty()
            ->info('Any CSS color.')
            ->validate()
            ->ifTrue(static fn (mixed $v): bool => \is_string($v) && 1 === preg_match(EnvironmentExtension::FORBIDDEN_COLOR_CHARACTERS, $v))
            ->thenInvalid('Invalid CSS color %s.')
            ->end()
            ->end()
            ->scalarNode('foreground')
            ->defaultValue('#fff')
            ->info('Text color on top of "color".')
            ->validate()
            ->ifTrue(static fn (mixed $v): bool => \is_string($v) && 1 === preg_match(EnvironmentExtension::FORBIDDEN_COLOR_CHARACTERS, $v))
            ->thenInvalid('Invalid CSS color %s.')
            ->end()
            ->end()
            ->end()
            ->end()
            ->defaultValue([
                'dev' => ['label' => null, 'color' => 'oklch(55.3% 0.195 38.402)', 'foreground' => '#fff'],
                'test' => ['label' => null, 'color' => 'oklch(54.1% 0.281 293.009)', 'foreground' => '#fff'],
                'staging' => ['label' => null, 'color' => 'oklch(54.6% 0.245 262.881)', 'foreground' => '#fff'],
                'preprod' => ['label' => null, 'color' => 'oklch(54.6% 0.245 262.881)', 'foreground' => '#fff'],
            ])
            ->end()
            ->booleanNode('strip')->defaultTrue()->end()
            ->booleanNode('navbar')->defaultTrue()->end()
            ->booleanNode('login')->defaultTrue()->end()
            ->end()
            ->end()
            ->arrayNode('entity_filter_autocomplete')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')->defaultFalse()->end()
            ->end()
            ->end()
            ->arrayNode('tags')
            ->info('TagsType form type: a list of strings typed as removable tags (Stimulus controller "admin-kit--tags").')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')->defaultFalse()->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $config = $this->processConfiguration($builder);

        /** @var array<string, mixed> $kernelBundles */
        $kernelBundles = $builder->getParameter('kernel.bundles');
        $theme = ThemeResolver::resolve($config['theme'], $config['themes'], $kernelBundles);

        $builder->setParameter('rem42_sylius_admin_kit.theme', $theme->name);
        $builder->setParameter('rem42_sylius_admin_kit.template_prefix', $theme->templatePrefix);

        if ($config['tags']['enabled']) {
            $this->prependTags($container, $builder, $theme->templatePrefix);
        }

        if (!isset($kernelBundles['SyliusTwigHooksBundle'])) {
            return;
        }

        $hooks = [];

        if ($config['quick_filter']['enabled']) {
            $hooks['sylius_admin.common.index.content.grid'] = ['rem42_admin_kit_quick_filters' => [
                'template' => $theme->templatePrefix . '/quick_filter/tabs.html.twig',
                'priority' => 100,
            ]];
        }

        if ($config['active_filters']['enabled']) {
            $hooks = array_replace_recursive($hooks, $this->activeFiltersHooks($theme->templatePrefix, isset($kernelBundles['Rem42ShadcnAdminUiBundle'])));
        }

        if ($config['environment']['enabled']) {
            $hooks = array_replace($hooks, $this->environmentHooks($config['environment'], $theme->templatePrefix));
        }

        if ([] !== $hooks) {
            $container->extension('sylius_twig_hooks', ['hooks' => $hooks], prepend: true);
        }
    }

    /**
     * shadcn-admin-ui exposes hooks inside its filters panel: the count goes in its trigger, the list below it, and the
     * panel stays collapsed on load since the list shows the criteria. Other admin UIs get a bar above their panel.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function activeFiltersHooks(string $templatePrefix, bool $shadcnAdminUi): array
    {
        $template = $templatePrefix . '/active_filters/%s.html.twig';
        $hookable = 'rem42_admin_kit_active_filters';

        if (!$shadcnAdminUi) {
            return ['sylius_admin.common.index.content.grid' => [$hookable => [
                'template' => \sprintf($template, 'bar'),
                'priority' => 50,
            ]]];
        }

        return [
            // Only the configuration: the template of the panel stays the one of shadcn-admin-ui.
            'sylius_admin.common.index.content.grid' => ['filters' => [
                'configuration' => ['open' => false, 'indicator' => false],
            ]],
            'sylius_admin.common.index.content.grid.filters#trigger' => [$hookable => ['template' => \sprintf($template, 'count')]],
            'sylius_admin.common.index.content.grid.filters#summary' => [$hookable => ['template' => \sprintf($template, 'summary')]],
        ];
    }

    /**
     * @param array{strip: bool, navbar: bool, login: bool} $config
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function environmentHooks(array $config, string $templatePrefix): array
    {
        $template = $templatePrefix . '/environment/%s.html.twig';
        $hookable = 'rem42_admin_kit_environment';
        $hooks = [];

        if ($config['strip']) {
            $hooks['sylius_admin.base#stylesheets'] = [$hookable => ['template' => \sprintf($template, 'strip')]];
        }

        if ($config['navbar']) {
            $hooks['sylius_admin.common.component.navbar.menu'] = [$hookable => [
                'template' => \sprintf($template, 'badge'),
                'priority' => 100,
                'configuration' => ['placement' => 'navbar'],
            ]];
        }

        if ($config['login']) {
            $hooks['sylius_admin.security.login.page'] = [$hookable => [
                'template' => \sprintf($template, 'badge'),
                'priority' => 1000,
                'configuration' => ['placement' => 'login'],
            ]];
        }

        return $hooks;
    }

    /**
     * The form theme of the tags widget, and its Stimulus controller next to the application ones (identifier
     * "admin-kit--tags", from its sub-directory), the way rem42/shadcn-admin-ui exposes its controllers.
     */
    private function prependTags(ContainerConfigurator $container, ContainerBuilder $builder, string $templatePrefix): void
    {
        /** @var array<string, mixed> $kernelBundles */
        $kernelBundles = $builder->getParameter('kernel.bundles');

        if (isset($kernelBundles['TwigBundle'])) {
            $container->extension('twig', ['form_themes' => [$templatePrefix . '/tags/form_theme.html.twig']], prepend: true);
        }

        // Without StimulusBundle, loadExtension() fails with an explicit message: prepend nothing that could not load.
        if (!interface_exists(AssetMapperInterface::class) || !isset($kernelBundles['FrameworkBundle'], $kernelBundles['StimulusBundle'])) {
            return;
        }

        $container->extension('framework', [
            'asset_mapper' => ['paths' => [$this->getPath() . '/assets' => '@rem42/sylius-admin-kit']],
        ], prepend: true);
        $container->extension('stimulus', [
            'controller_paths' => ['%kernel.project_dir%/assets/controllers', $this->getPath() . '/assets/controllers'],
        ], prepend: true);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        /** @var array{enabled: bool, name: string, environments: array<string, mixed>} $environment */
        $environment = $config['environment'];

        /** @var array{enabled: bool} $quickFilter */
        $quickFilter = $config['quick_filter'];

        if ($quickFilter['enabled']) {
            $container->import('../config/services/quick_filter.php');
            $builder->registerForAutoconfiguration(QuickFilterProviderInterface::class)
                ->addTag('rem42_sylius_admin_kit.quick_filter_provider')
            ;
        }

        /** @var array{enabled: bool} $activeFilters */
        $activeFilters = $config['active_filters'];

        if ($activeFilters['enabled']) {
            $container->import('../config/services/active_filters.php');
        }

        /** @var array{enabled: bool} $entityFilter */
        $entityFilter = $config['entity_filter_autocomplete'];

        if ($entityFilter['enabled']) {
            /** @var array<string, mixed> $kernelBundles */
            $kernelBundles = $builder->getParameter('kernel.bundles');

            if (!isset($kernelBundles['AutocompleteBundle'])) {
                throw new \LogicException('The "entity_filter_autocomplete" feature of rem42_sylius_admin_kit requires symfony/ux-autocomplete: install it and enable its AutocompleteBundle.');
            }

            $container->import('../config/services/entity_filter.php');
        }

        /** @var array{enabled: bool} $tags */
        $tags = $config['tags'];

        if ($tags['enabled']) {
            /** @var array<string, mixed> $kernelBundles */
            $kernelBundles = $builder->getParameter('kernel.bundles');

            if (!isset($kernelBundles['StimulusBundle'])) {
                throw new \LogicException('The "tags" feature of rem42_sylius_admin_kit requires symfony/stimulus-bundle: install it and enable its StimulusBundle.');
            }

            $container->import('../config/services/tags.php');
        }

        if ($environment['enabled']) {
            $container->import('../config/services/environment.php');
            $container->services()->get('rem42_sylius_admin_kit.twig.extension.environment')
                ->arg('$name', $environment['name'])
                ->arg('$environments', $environment['environments'])
            ;
        }
    }

    /**
     * @return array{theme: ?string, themes: array<string, string>, quick_filter: array{enabled: bool}, active_filters: array{enabled: bool}, environment: array{enabled: bool, name: string, environments: array<string, array{label: ?string, color: string, foreground: string}>, strip: bool, navbar: bool, login: bool}, entity_filter_autocomplete: array{enabled: bool}, tags: array{enabled: bool}}
     */
    private function processConfiguration(ContainerBuilder $builder): array
    {
        $extension = $this->getContainerExtension();

        if (!$extension instanceof ConfigurationExtensionInterface) {
            throw new \LogicException('The bundle extension must provide a configuration.');
        }

        // Prepending happens before parameters are resolved: do it here so that e.g. "enabled: '%app.flag%'" works.
        /** @var list<array<string, mixed>> $configs */
        $configs = $builder->getParameterBag()->resolveValue($builder->getExtensionConfig($extension->getAlias()));
        $configuration = $extension->getConfiguration($configs, $builder);

        if (null === $configuration) {
            throw new \LogicException('The bundle extension must provide a configuration.');
        }

        /** @var array{theme: ?string, themes: array<string, string>, quick_filter: array{enabled: bool}, active_filters: array{enabled: bool}, environment: array{enabled: bool, name: string, environments: array<string, array{label: ?string, color: string, foreground: string}>, strip: bool, navbar: bool, login: bool}, entity_filter_autocomplete: array{enabled: bool}, tags: array{enabled: bool}} */
        return (new Processor())->processConfiguration($configuration, $configs);
    }
}
