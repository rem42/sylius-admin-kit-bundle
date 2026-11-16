<?php

declare(strict_types=1);

use Rem42\SyliusAdminKit\Grid\GridPage;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

// Shared by the quick filter and the active filters: loaded when either is enabled.
return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('rem42_sylius_admin_kit.grid.page', GridPage::class)
        ->args([
            service('request_stack'),
            service('router'),
        ])
    ;
};
