<?php

declare(strict_types=1);

use Rem42\SyliusAdminKit\QuickFilter\Twig\QuickFilterExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('rem42_sylius_admin_kit.twig.extension.quick_filter', QuickFilterExtension::class)
        ->args([
            tagged_locator('rem42_sylius_admin_kit.quick_filter_provider'),
            service('translator'),
            service('request_stack'),
            service('router'),
        ])
        ->tag('twig.extension')
    ;
};
