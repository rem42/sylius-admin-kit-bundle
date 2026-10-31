<?php

declare(strict_types=1);

use Rem42\SyliusAdminKit\Environment\Twig\EnvironmentExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('rem42_sylius_admin_kit.twig.extension.environment', EnvironmentExtension::class)
        ->args(['', []])
        ->tag('twig.extension')
    ;
};
