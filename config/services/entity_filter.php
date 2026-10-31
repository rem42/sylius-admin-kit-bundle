<?php

declare(strict_types=1);

use Rem42\SyliusAdminKit\EntityFilter\Form\EntityFilterTypeExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('rem42_sylius_admin_kit.form.extension.entity_filter', EntityFilterTypeExtension::class)
        ->tag('form.type_extension')
    ;
};
