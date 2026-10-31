<?php

declare(strict_types=1);

// Bundles enabled for every theme ("all") or for one theme only (see App\Kernel).
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Symfony\UX\TwigComponent\TwigComponentBundle::class => ['all' => true],
    Symfony\UX\StimulusBundle\StimulusBundle::class => ['all' => true],
    Sylius\TwigHooks\SyliusTwigHooksBundle::class => ['all' => true],
    BabDev\PagerfantaBundle\BabDevPagerfantaBundle::class => ['all' => true],
    Sylius\Bundle\ResourceBundle\SyliusResourceBundle::class => ['all' => true],
    Knp\Bundle\MenuBundle\KnpMenuBundle::class => ['all' => true],
    Sylius\AdminUi\Symfony\SyliusAdminUiBundle::class => ['all' => true],
    Sylius\Bundle\GridBundle\SyliusGridBundle::class => ['all' => true],
    Sylius\TwigExtra\Symfony\SyliusTwigExtraBundle::class => ['all' => true],
    Sylius\UiTranslations\Symfony\SyliusUiTranslationsBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Twig\Extra\TwigExtraBundle\TwigExtraBundle::class => ['all' => true],
    Symfony\UX\Icons\UXIconsBundle::class => ['all' => true],
    Symfony\UX\Autocomplete\AutocompleteBundle::class => ['all' => true],
    Symfony\UX\LiveComponent\LiveComponentBundle::class => ['all' => true],
    Rem42\SyliusAdminKit\Rem42SyliusAdminKitBundle::class => ['all' => true],

    // sylius/bootstrap-admin-ui (Tabler)
    Sylius\BootstrapAdminUi\Symfony\SyliusBootstrapAdminUiBundle::class => ['bootstrap' => true],

    // rem42/shadcn-admin-ui
    Symfonycasts\TailwindBundle\SymfonycastsTailwindBundle::class => ['shadcn' => true],
    TalesFromADev\Twig\Extra\Tailwind\Bridge\Symfony\Bundle\TalesFromADevTwigExtraTailwindBundle::class => ['shadcn' => true],
    Rem42\ShadcnAdminUi\Rem42ShadcnAdminUiBundle::class => ['shadcn' => true],
];
