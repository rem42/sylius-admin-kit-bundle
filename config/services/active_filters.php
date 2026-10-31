<?php

declare(strict_types=1);

use Rem42\SyliusAdminKit\ActiveFilters\CriterionFormatter;
use Rem42\SyliusAdminKit\ActiveFilters\Twig\ActiveFiltersExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->set('rem42_sylius_admin_kit.active_filters.criterion_formatter', CriterionFormatter::class)
        ->args([
            service('translator'),
            service('doctrine')->nullOnInvalid(),
            service('property_accessor')->nullOnInvalid(),
        ])
    ;

    $services
        ->set('rem42_sylius_admin_kit.twig.extension.active_filters', ActiveFiltersExtension::class)
        ->args([
            service('rem42_sylius_admin_kit.active_filters.criterion_formatter'),
            service('translator'),
            service('request_stack'),
            service('router'),
        ])
        ->tag('twig.extension')
    ;
};
