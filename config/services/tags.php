<?php

declare(strict_types=1);

use Rem42\SyliusAdminKit\Tags\Form\TagsType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('rem42_sylius_admin_kit.form.type.tags', TagsType::class)
        ->tag('form.type')
    ;
};
