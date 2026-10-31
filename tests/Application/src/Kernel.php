<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Demo application of the bundle, served once per theme (see docker-compose.yml), admin / admin:
 * - bootstrap (sylius/bootstrap-admin-ui): http://localhost:8080/admin
 * - shadcn (rem42/shadcn-admin-ui): http://localhost:8080/shadcn/admin
 *
 * Each theme gets its own container: bundles flagged with its name in config/bundles.php, configuration of
 * config/packages/{theme}/ and templates of templates/themes/{theme}/, which take precedence over templates/.
 * A variant (config/variants/{variant}.yaml) loads extra configuration on top of it, e.g. for the tests.
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public const THEMES = ['bootstrap', 'shadcn'];

    public const DEFAULT_THEME = 'bootstrap';

    public function __construct(
        string $environment,
        bool $debug,
        private readonly string $theme = self::DEFAULT_THEME,
        private readonly ?string $variant = null,
    ) {
        if (!\in_array($theme, self::THEMES, true)) {
            throw new \InvalidArgumentException(\sprintf('Unknown theme "%s", expected one of "%s".', $theme, implode('", "', self::THEMES)));
        }

        if (null !== $variant && 1 !== preg_match('/^[a-z0-9_]+$/', $variant)) {
            throw new \InvalidArgumentException(\sprintf('Invalid variant "%s".', $variant));
        }

        parent::__construct($environment, $debug);
    }

    /**
     * Theme serving a path: "/shadcn/..." is rendered by the shadcn theme, anything else by the default one.
     */
    public static function getThemeForPath(string $path): string
    {
        $prefix = explode('/', ltrim($path, '/'), 2)[0];

        return \in_array($prefix, self::THEMES, true) ? $prefix : self::DEFAULT_THEME;
    }

    /**
     * Prefix of the URLs served by a theme: none for the default one, "/{theme}" for the others.
     */
    public static function getPathPrefix(string $theme): string
    {
        return self::DEFAULT_THEME === $theme ? '' : '/' . $theme;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function getVariant(): ?string
    {
        return $this->variant;
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    public function getCacheDir(): string
    {
        return \sprintf('%s/var/cache/%s%s/%s', $this->getProjectDir(), $this->theme, null !== $this->variant ? '-' . $this->variant : '', $this->environment);
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir() . '/var/log';
    }

    public function registerBundles(): iterable
    {
        /** @var array<class-string<BundleInterface>, array<string, bool>> $bundles */
        $bundles = require $this->getProjectDir() . '/config/bundles.php';

        foreach ($bundles as $class => $themes) {
            if ($themes[$this->theme] ?? $themes['all'] ?? false) {
                yield new $class();
            }
        }
    }

    protected function getContainerClass(): string
    {
        return \sprintf(
            'App_%s%s%s%sContainer',
            ucfirst($this->theme),
            null !== $this->variant ? str_replace('_', '', ucwords($this->variant, '_')) : '',
            ucfirst($this->environment),
            $this->debug ? 'Debug' : '',
        );
    }

    private function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/packages/*.yaml');
        $container->import('../config/{packages}/' . $this->theme . '/*.yaml');
        $container->import('../config/services.yaml');

        if (null !== $this->variant) {
            $container->import('../config/variants/' . $this->variant . '.yaml');
        }

        $container->parameters()
            ->set('app.theme', $this->theme)
            ->set('app.path_prefix', self::getPathPrefix($this->theme))
        ;

        if (is_dir($this->getProjectDir() . '/templates/themes/' . $this->theme)) {
            $container->extension('twig', ['paths' => ['%kernel.project_dir%/templates/themes/' . $this->theme => null]]);
        }
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $prefix = self::getPathPrefix($this->theme);

        $routes->import('../config/{routes}/*.yaml')->prefix($prefix);
        $routes->import('../config/routes.yaml')->prefix($prefix);
    }
}
